<?php

namespace App\Modules\Campus\Services;

use App\Models\StudentFeeManager;
use App\Models\User;
use App\Modules\Campus\Models\FeePayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One student, one ledger. Invoices are the rows the office already creates (student_fee_managers);
 * money received is recorded in fee_payments.
 */
class FinanceService
{
    public function __construct(private Notifier $notifier)
    {
    }

    /**
     * Amount received on an invoice. Older office screens only set paid_amount and status, so those are
     * honoured too. For an invoice that is not marked paid, a paid_amount equal to the total is ignored:
     * older code wrote that on declined payments.
     */
    public function paidAmount(object $invoice, int $recorded): int
    {
        $total = (int) $invoice->total_amount;

        if ($invoice->status === 'paid') {
            return max($total, $recorded);
        }

        $legacy = (int) $invoice->paid_amount;

        return max($recorded, $legacy < $total ? $legacy : 0);
    }

    /** @return Collection<int, array> */
    public function invoices(Collection $rows): Collection
    {
        $recorded = FeePayment::whereIn('invoice_id', $rows->pluck('id'))
            ->where('status', 'confirmed')
            ->groupBy('invoice_id')
            ->selectRaw('invoice_id, sum(amount) as total')
            ->pluck('total', 'invoice_id');

        return $rows->map(function ($row) use ($recorded) {
            $total = (int) $row->total_amount;
            $paid = $this->paidAmount($row, (int) ($recorded[$row->id] ?? 0));
            $due = max(0, $total - $paid);
            $status = $due === 0 ? 'paid' : ($row->status === 'pending' ? 'verifying' : ($paid > 0 ? 'partial' : 'due'));

            return [
                'id' => (int) $row->id,
                'number' => 'INV-' . str_pad((string) $row->id, 6, '0', STR_PAD_LEFT),
                'title' => $row->title,
                'student_id' => (int) $row->student_id,
                'class_id' => (int) $row->class_id,
                'total' => $total,
                'discount' => max(0, (int) $row->amount - $total),
                'paid' => $paid,
                'due' => $due,
                'status' => $status,
                'issued_at' => $row->created_at ? (string) $row->created_at : null,
                'due_on' => $row->due_on ? substr((string) $row->due_on, 0, 10) : null,
            ];
        })->values();
    }

    public function ledger(User $student): array
    {
        $rows = StudentFeeManager::where('student_id', $student->id)->orderByDesc('id')->get();
        $invoices = $this->invoices($rows);
        $payments = FeePayment::where('student_id', $student->id)->where('status', 'confirmed')->orderByDesc('paid_at')->get();
        $titles = $rows->pluck('title', 'id');

        return [
            'summary' => [
                'billed' => $invoices->sum('total'),
                'paid' => $invoices->sum('paid'),
                'due' => $invoices->sum('due'),
                'open_count' => $invoices->where('due', '>', 0)->count(),
            ],
            'invoices' => $invoices->all(),
            'receipts' => $payments->map(fn (FeePayment $p) => [
                'id' => $p->id,
                'receipt_no' => $p->receipt_no,
                'invoice_id' => (int) $p->invoice_id,
                'title' => $titles[$p->invoice_id] ?? 'Fee payment',
                'amount' => $p->amount,
                'method' => $p->method,
                'reference' => $p->reference,
                'paid_at' => $p->paid_at->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * Record money against an invoice, issue the receipt and tell the family. Safe to call twice with
     * the same online reference: the second call returns the first receipt.
     */
    public function record(int $invoiceId, int $amount, string $method, ?string $reference = null, ?int $receivedBy = null, bool $capToDue = true): FeePayment
    {
        $payment = DB::transaction(function () use ($invoiceId, $amount, $method, $reference, $receivedBy, $capToDue) {
            $invoice = StudentFeeManager::whereKey($invoiceId)->lockForUpdate()->firstOrFail();

            if ($method === 'online' && $reference) {
                $existing = FeePayment::where('method', 'online')->where('reference', $reference)->first();
                if ($existing) {
                    return $existing;
                }
            }

            $recorded = (int) FeePayment::where('invoice_id', $invoice->id)->where('status', 'confirmed')->sum('amount');
            $due = max(0, (int) $invoice->total_amount - $this->paidAmount($invoice, $recorded));

            if ($amount < 1 || ($capToDue && $amount > $due)) {
                throw ValidationException::withMessages([
                    'amount' => $due === 0 ? 'This invoice is already paid.' : "Enter an amount between 1 and {$due}.",
                ]);
            }

            $payment = FeePayment::create([
                'invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
                'school_id' => $invoice->school_id,
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'received_by' => $receivedBy,
                'paid_at' => now(),
            ]);
            $payment->update(['receipt_no' => 'MCC-R-' . now()->format('y') . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT)]);

            // Keep the older office and accountant screens in step with the ledger.
            $paid = $this->paidAmount($invoice, $recorded) + $amount;
            $invoice->forceFill([
                'paid_amount' => min($paid, (int) $invoice->total_amount),
                'status' => $paid >= (int) $invoice->total_amount ? 'paid' : 'unpaid',
                'payment_method' => $method,
                'timestamp' => time(),
            ])->save();

            return $payment;
        });

        if ($payment->wasRecentlyCreated) {
            $this->notifier->toFamilies(
                [$payment->student_id],
                'fees',
                'Payment received',
                'BDT ' . number_format($payment->amount) . " received. Receipt {$payment->receipt_no}.",
                ['screen' => 'fees'],
            );
        }

        return $payment;
    }

    /** A single invoice on the student's ledger (a charge raised by a campus service). */
    public function invoice(int $studentId, string $title, int $amount): ?StudentFeeManager
    {
        $student = User::find($studentId);
        $enrollment = DB::table('enrollments')->where('user_id', $studentId)->orderByDesc('id')->first();
        if (!$student || $amount < 1) {
            return null;
        }

        $invoice = StudentFeeManager::create([
            'title' => mb_substr($title, 0, 190), 'amount' => $amount, 'discounted_price' => 0, 'total_amount' => $amount, 'paid_amount' => 0,
            'status' => 'unpaid', 'class_id' => $enrollment->class_id ?? 0, 'student_id' => $studentId, 'parent_id' => $student->parent_id,
            'school_id' => $student->school_id, 'session_id' => (int) (DB::table('schools')->where('id', $student->school_id)->value('running_session') ?? 0), 'timestamp' => time(),
        ]);
        $this->notifier->toFamilies([$studentId], 'fees', 'New invoice', "{$title}: BDT " . number_format($amount), ['screen' => 'fees']);

        return $invoice;
    }

    /**
     * Changes what an invoice asks for, keeping the reason and who approved it. Applying the same
     * source to the same invoice twice does nothing.
     */
    public function adjust(int $invoiceId, int $studentId, int $change, string $kind, string $reason, string $sourceType, int $sourceId, ?int $approver): void
    {
        DB::transaction(function () use ($invoiceId, $studentId, $change, $kind, $reason, $sourceType, $sourceId, $approver) {
            $invoice = StudentFeeManager::whereKey($invoiceId)->where('student_id', $studentId)->lockForUpdate()->first();
            if (!$invoice) {
                throw ValidationException::withMessages(['note' => 'That invoice number does not belong to this student.']);
            }
            if (DB::table('fee_adjustments')->where(['invoice_id' => $invoiceId, 'source_type' => $sourceType, 'source_id' => $sourceId])->exists()) {
                return;
            }

            $recorded = (int) FeePayment::where('invoice_id', $invoice->id)->where('status', 'confirmed')->sum('amount');
            $paid = $this->paidAmount($invoice, $recorded);
            $due = max(0, (int) $invoice->total_amount - $paid);
            if ($change === 0 || ($change < 0 && -$change > $due)) {
                throw ValidationException::withMessages(['note' => "Only {$due} is still due on that invoice."]);
            }

            $total = (int) $invoice->total_amount + $change;
            $invoice->forceFill(['total_amount' => $total, 'status' => $paid >= $total ? 'paid' : 'unpaid', 'paid_amount' => min($paid, $total)])->save();
            DB::table('fee_adjustments')->insert([
                'school_id' => $invoice->school_id, 'invoice_id' => $invoice->id, 'student_id' => $studentId, 'kind' => $kind, 'amount' => $change,
                'reason' => mb_substr($reason, 0, 300), 'source_type' => $sourceType, 'source_id' => $sourceId, 'approved_by' => $approver,
            ]);
        });
    }

    /** Takes an award off every tuition invoice that still has money due. */
    public function applyScholarship(int $studentId, int $awardId, int $percent, ?int $approver): void
    {
        $rows = StudentFeeManager::where('student_id', $studentId)->where('title', 'like', 'Tuition%')->get();

        foreach ($this->invoices($rows)->where('due', '>', 0) as $invoice) {
            $off = min($invoice['due'], (int) round($invoice['total'] * $percent / 100));
            if ($off > 0) {
                $this->adjust($invoice['id'], $studentId, -$off, 'scholarship', "Scholarship {$percent}%", 'scholarship_application', $awardId, $approver);
            }
        }
    }

    /** @return array<int, array{id:int, percent:int}> current awards by student */
    private function awards($studentIds)
    {
        return DB::table('campus_records')->where('type', 'scholarship_application')->where('status', 'approved')
            ->whereIn('student_id', $studentIds)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()->startOfDay()))
            ->orderBy('id')->get(['id', 'student_id', 'data'])
            ->mapWithKeys(fn ($r) => [$r->student_id => ['id' => $r->id, 'percent' => (int) (json_decode($r->data, true)['percent'] ?? 0)]]);
    }

    /** @return int number of invoices created; students who already have this title are skipped */
    public function billClass(int $schoolId, int $sessionId, int $classId, ?int $sectionId, string $title, int $amount, int $discount = 0, ?string $dueOn = null): int
    {
        $students = $this->notifier->studentsIn($classId, $sectionId);
        $already = StudentFeeManager::where('school_id', $schoolId)->where('session_id', $sessionId)
            ->where('title', $title)->whereIn('student_id', $students)->pluck('student_id');
        $targets = $students->diff($already)->values();
        $parents = User::whereIn('id', $targets)->pluck('parent_id', 'id');

        $awards = str_starts_with($title, 'Tuition') ? $this->awards($targets) : collect();

        DB::transaction(function () use ($targets, $parents, $schoolId, $sessionId, $classId, $title, $amount, $discount, $dueOn, $awards) {
            foreach ($targets as $studentId) {
                $created = StudentFeeManager::create([
                    'title' => $title,
                    'amount' => $amount,
                    'discounted_price' => $discount,
                    'total_amount' => max(0, $amount - $discount),
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                    'class_id' => $classId,
                    'student_id' => $studentId,
                    'parent_id' => $parents[$studentId] ?? null,
                    'school_id' => $schoolId,
                    'session_id' => $sessionId,
                    'timestamp' => time(),
                ]);
                if ($dueOn) {
                    DB::table('student_fee_managers')->where('id', $created->id)->update(['due_on' => $dueOn]);
                }
                $award = $awards[$studentId] ?? null;
                if ($award && $award['percent'] > 0 && $created->total_amount > 0) {
                    $this->adjust($created->id, $studentId, -min((int) $created->total_amount, (int) round($created->total_amount * $award['percent'] / 100)), 'scholarship', "Scholarship {$award['percent']}%", 'scholarship_application', $award['id'], null);
                }
            }
        });

        if ($targets->isNotEmpty()) {
            $this->notifier->toFamilies($targets, 'fees', 'New invoice', "{$title}: BDT " . number_format(max(0, $amount - $discount)), ['screen' => 'fees']);
        }

        return $targets->count();
    }
}
