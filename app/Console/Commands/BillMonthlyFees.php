<?php

namespace App\Console\Commands;

use App\Models\StudentFeeManager;
use App\Modules\Campus\Services\FinanceService;
use App\Modules\Campus\Support\StudentContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BillMonthlyFees extends Command
{
    protected $signature = 'campus:bill-fees';

    protected $description = 'Create this month\'s invoices from the active fee plans and add late fees. Safe to run every day.';

    public function handle(FinanceService $finance): int
    {
        $today = now('Asia/Dhaka');
        $created = 0;
        $late = 0;

        foreach (DB::table('campus_records')->where('type', 'fee_plan')->where('status', 'active')->get() as $plan) {
            $data = json_decode($plan->data, true) ?: [];
            if (!preg_match('/^(\d+)-(\d+)$/', (string) ($data['section'] ?? ''), $m) || (int) $plan->amount < 1) {
                continue;
            }
            $session = StudentContext::runningSession((int) $plan->school_id);
            if (!$session) {
                continue;
            }

            $title = "{$plan->title}, " . $today->format('F Y');
            $dueOn = $today->copy()->startOfMonth()->addDays(max(1, min(28, (int) ($data['due_day'] ?? 10))) - 1)->toDateString();
            $created += $finance->billClass((int) $plan->school_id, $session, (int) $m[1], (int) $m[2], $title, (int) $plan->amount, 0, $dueOn);

            $fee = (int) ($data['late_fee'] ?? 0);
            if ($fee < 1) {
                continue;
            }
            $limit = Carbon::parse($today->toDateString())->subDays((int) ($data['grace_days'] ?? 0))->toDateString();
            $overdue = StudentFeeManager::where('school_id', $plan->school_id)->where('class_id', (int) $m[1])
                ->where('title', 'like', addcslashes($plan->title, '%_\\') . ', %')->whereNotNull('due_on')->where('due_on', '<', $limit)->where('status', '!=', 'paid')->get();

            foreach ($finance->invoices($overdue)->where('due', '>', 0) as $invoice) {
                $already = DB::table('fee_adjustments')->where(['invoice_id' => $invoice['id'], 'source_type' => 'late_fee', 'source_id' => $plan->id])->exists();
                if (!$already && $invoice['status'] !== 'verifying') {
                    $finance->adjust($invoice['id'], $invoice['student_id'], $fee, 'late_fee', 'Late payment fee', 'late_fee', (int) $plan->id, null);
                    $late++;
                }
            }
        }

        $this->info("{$created} invoices created, {$late} late fees added.");

        return self::SUCCESS;
    }
}
