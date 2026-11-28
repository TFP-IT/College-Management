<?php

namespace App\Modules\Campus\Services;

use App\Modules\Campus\Support\StudentContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Reads the daily_attendances rows that teachers and the office record. 1 present, 2 late, 0 absent. */
class AttendanceService
{
    public const TIMEZONE = 'Asia/Dhaka';

    private const STATUS = [1 => 'present', 2 => 'late', 0 => 'absent'];

    /** @return array{today:?string, session:array, month:array, days:array, month_label:string, month_key:string} */
    public function forStudent(StudentContext $ctx, ?string $month = null): array
    {
        $start = $month
            ? CarbonImmutable::createFromFormat('!Y-m', $month, self::TIMEZONE)
            : CarbonImmutable::now(self::TIMEZONE)->startOfMonth();

        $rows = DB::table('daily_attendances')
            ->where('student_id', $ctx->student->id)
            ->when($ctx->sessionId, fn ($q) => $q->whereIn('session_id', [$ctx->sessionId, 0]))
            ->orderBy('id')
            ->get(['status', 'timestamp', 'note'])
            // One result per calendar day; a later correction replaces the earlier row.
            ->keyBy(fn ($r) => CarbonImmutable::createFromTimestamp($r->timestamp, self::TIMEZONE)->toDateString());

        $inMonth = $rows->filter(fn ($r, $date) => str_starts_with($date, $start->format('Y-m')))->sortKeys();
        $today = $rows->get(CarbonImmutable::now(self::TIMEZONE)->toDateString());

        return [
            'today' => $today ? $this->status($today->status) : null,
            'session' => $this->totals($rows),
            'month' => $this->totals($inMonth),
            'month_key' => $start->format('Y-m'),
            'month_label' => $start->format('F Y'),
            'days' => $inMonth->map(fn ($r, $date) => [
                'date' => $date,
                'status' => $this->status($r->status),
                'note' => $r->note,
            ])->values()->all(),
        ];
    }

    private function status($value): string
    {
        return self::STATUS[(int) $value] ?? 'absent';
    }

    /** Late counts as attended. The rate is null until something has been recorded. */
    private function totals($rows): array
    {
        $counts = ['present' => 0, 'late' => 0, 'absent' => 0];
        foreach ($rows as $row) {
            $counts[$this->status($row->status)]++;
        }
        $total = array_sum($counts);

        return $counts + [
            'total' => $total,
            'rate' => $total ? (int) round(($counts['present'] + $counts['late']) / $total * 100) : null,
        ];
    }
}
