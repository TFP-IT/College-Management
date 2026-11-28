<?php

namespace App\Modules\Campus\Engine;

use App\Models\User;
use App\Modules\Campus\Services\Notifier;
use App\Modules\Campus\Services\FinanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** What a record causes elsewhere: notifications, ledger entries and linked records. */
class Effects
{
    public function __construct(private Notifier $notifier)
    {
    }

    public function created(Module $module, object $record, ?User $actor): void
    {
        $data = ['screen' => 'record', 'type' => $module->key, 'record_id' => $record->id];

        if (($text = $module->get('notify_on_create')) && $record->student_id) {
            $this->notifier->toFamilies([$record->student_id], 'campus', $module->get('label'), $text, $data);
        }
        if ($module->get('notify_staff') && $record->staff_id) {
            $this->notifier->send([$record->staff_id], 'campus', 'New ' . strtolower($module->get('label')) . ' request', $record->title, $data);
        }
        if ($title = $module->get('announce')) {
            $students = User::where('school_id', $record->school_id)->where('role_id', 7)->where('status', 1)->pluck('id');
            $this->notifier->send($students, 'campus', $title, $record->title, $data);
        }
        if ($module->key === 'transport_incident' && $record->parent_id) {
            $riders = DB::table('campus_records')->where('type', 'transport_application')->where('parent_id', $record->parent_id)->where('status', 'approved')->pluck('student_id');
            $this->notifier->toFamilies($riders, 'transport', 'Transport alert', $record->title, $data);
        }
        if ($module->get('verifiable')) {
            $this->patch($record->id, ['code' => 'HF-' . Str::upper(Str::random(16))]);
        }
        $this->saved($module, $record);
    }

    /** Values that follow from other values. */
    public function saved(Module $module, object $record): void
    {
        if ($module->key === 'inventory_item') {
            $d = $record->data;
            $closing = (float) ($d['opening'] ?? 0) + (float) ($d['received'] ?? 0) - (float) ($d['issued'] ?? 0) - (float) ($d['damaged'] ?? 0);
            $status = $record->status === 'closed' ? 'closed' : ($closing <= (float) ($d['reorder_at'] ?? 0) ? 'low' : 'stocked');
            DB::table('campus_records')->where('id', $record->id)->update([
                'status' => $status,
                'title' => $record->title,
                'data' => json_encode(array_merge($d, ['closing' => $closing])),
            ]);
        }
    }

    public function statusChanged(Module $module, object $record, ?User $actor): void
    {
        if (in_array($record->status, $module->get('notify_statuses', []), true)) {
            $label = $module->statusLabel($record->status)['label'];
            $body = $module->get('notify_text') ?? "{$record->title}: {$label}.";
            $data = ['screen' => 'record', 'type' => $module->key, 'record_id' => $record->id];

            $record->student_id
                ? $this->notifier->toFamilies([$record->student_id], 'campus', $module->get('label'), $body, $data)
                : $this->notifier->send([$record->staff_id ?: $record->created_by], 'campus', $module->get('label'), $body, $data);
        }

        if ($module->get('charge_on') === $record->status && $record->student_id && $record->amount > 0) {
            $this->charge($record, "{$module->get('label')}: {$record->title}");
        }
    }

    /** Runs inside the final approval; throwing keeps the request waiting. */
    public function approve(Module $module, object $record, User $approver): void
    {
        $finance = app(FinanceService::class);

        switch ($module->key) {
            case 'fee_waiver':
                $invoiceId = (int) preg_replace('/\D/', '', (string) ($record->data['invoice'] ?? ''));
                $finance->adjust($invoiceId, (int) $record->student_id, -1 * (int) $record->amount, 'waiver', $record->title, 'fee_waiver', $record->id, $approver->id);
                break;

            case 'scholarship_application':
                $program = $record->parent_id ? DB::table('campus_records')->where('id', $record->parent_id)->first() : null;
                $percent = (int) ($record->data['percent'] ?? (json_decode($program->data ?? '[]', true)['percent'] ?? 0));
                if ($percent < 1 || $percent > 100) {
                    throw ValidationException::withMessages(['percent' => 'Set the awarded percent (1 to 100) on the application before approving.']);
                }
                $this->patch($record->id, ['percent' => $percent]);
                $finance->applyScholarship((int) $record->student_id, $record->id, $percent, $approver->id);
                break;

            case 'leave_request':
                $from = Carbon::parse($record->starts_at)->startOfDay();
                $to = Carbon::parse($record->ends_at)->startOfDay();
                DB::table('staff_leaves')->insert([
                    'user_id' => $record->staff_id, 'school_id' => $record->school_id, 'leave_type' => $record->data['kind'] ?? 'casual',
                    'start_date' => $from->toDateString(), 'end_date' => $to->toDateString(), 'days' => $from->diffInDays($to) + 1,
                    'reason' => $record->title, 'status' => 'approved', 'reviewed_by' => $approver->name, 'reviewed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                break;

            case 'transport_application':
                $this->assertSeat($record);
                if ($record->amount > 0) {
                    $this->charge($record, "Transport fee: {$record->title}");
                }
                break;

            case 'activity_membership':
                $this->assertSeat($record);
                break;

            case 'graduation_clearance':
                $missing = $this->clearanceGaps($record);
                if ($missing) {
                    throw ValidationException::withMessages(['note' => 'Cannot approve yet: ' . implode(', ', $missing) . '.']);
                }
                break;
        }
    }

    /** @return string[] what still stands between a student and graduation */
    public function clearanceGaps(object $record): array
    {
        $missing = [];
        $due = app(FinanceService::class)->ledger(User::findOrFail($record->student_id))['summary']['due'];

        if ($due > 0) {
            $missing[] = 'fees of ' . number_format($due) . ' are due';
        }
        if ($open = DB::table('library_loans')->where('student_id', $record->student_id)->where('status', 'issued')->count()) {
            $missing[] = "{$open} library books are not returned";
        }
        foreach (['academic' => 'academic requirements', 'property' => 'college property', 'documents' => 'documents'] as $key => $label) {
            if (($record->data[$key] ?? '') !== 'complete') {
                $missing[] = "{$label} not marked complete";
            }
        }

        return $missing;
    }

    private function assertSeat(object $record): void
    {
        if (!$record->parent_id) {
            return;
        }
        $parent = DB::table('campus_records')->where('id', $record->parent_id)->lockForUpdate()->first();
        $seats = (int) (json_decode($parent->data ?? '[]', true)['seats'] ?? 0);
        $taken = DB::table('campus_records')->where('parent_id', $record->parent_id)->where('status', 'approved')->count();

        if ($seats > 0 && $taken >= $seats) {
            throw ValidationException::withMessages(['note' => "All {$seats} places are taken."]);
        }
    }

    /** One invoice per record, on the student's single ledger. */
    private function charge(object $record, string $title): void
    {
        if (!empty($record->data['invoice_id'])) {
            return;
        }
        $invoice = app(FinanceService::class)->invoice((int) $record->student_id, $title, (int) $record->amount);
        if ($invoice) {
            $this->patch($record->id, ['invoice_id' => $invoice->id]);
        }
    }

    private function patch(int $recordId, array $values): void
    {
        $row = DB::table('campus_records')->where('id', $recordId)->lockForUpdate()->first();
        DB::table('campus_records')->where('id', $recordId)->update(['data' => json_encode(array_merge(json_decode($row->data ?? '[]', true) ?: [], $values))]);
    }
}
