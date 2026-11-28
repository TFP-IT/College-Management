<?php

namespace App\Modules\Campus\Services;

use App\Models\StudentFeeManager;
use App\Modules\Campus\Models\FeePayment;
use App\Modules\Campus\Models\ResultPublication;
use App\Modules\Campus\Models\ServiceTicket;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Numbers for the "today at the college" page. Every figure is counted from stored records. */
class OfficeReport
{
    public function __construct(private FinanceService $finance)
    {
    }

    public function today(int $schoolId, ?int $sessionId): array
    {
        $now = CarbonImmutable::now(AttendanceService::TIMEZONE);
        $start = $now->startOfDay()->timestamp;

        // One row per section, with how many students it has and what was recorded today.
        $sections = DB::table('enrollments')
            ->join('users', 'users.id', '=', 'enrollments.user_id')
            ->join('classes', 'classes.id', '=', 'enrollments.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'enrollments.section_id')
            ->where('enrollments.school_id', $schoolId)
            ->where('users.role_id', 7)->where('users.status', 1)
            ->when($sessionId, fn ($q) => $q->where('enrollments.session_id', $sessionId))
            ->groupBy('enrollments.class_id', 'enrollments.section_id', 'classes.name', 'sections.name')
            ->orderBy('classes.name')->orderBy('sections.name')
            ->get(['enrollments.class_id', 'enrollments.section_id', 'classes.name as class', 'sections.name as section', DB::raw('count(distinct users.id) as students')]);

        $marks = DB::table('daily_attendances')->where('school_id', $schoolId)
            ->whereBetween('timestamp', [$start, $start + 86399])->orderBy('id')
            ->get(['class_id', 'section_id', 'student_id', 'status'])->keyBy('student_id');

        $classes = $sections->map(function ($s) use ($marks) {
            $mine = $marks->where('class_id', $s->class_id)->where('section_id', $s->section_id);

            return [
                'label' => trim("{$s->class} {$s->section}"),
                'students' => (int) $s->students,
                // Only students still enrolled in the section count, so "recorded" never exceeds "students".
                'recorded' => min($mine->count(), (int) $s->students),
                'present' => $mine->where('status', 1)->count(),
                'late' => $mine->where('status', 2)->count(),
                'absent' => $mine->where('status', 0)->count(),
            ];
        });

        return [
            'date' => $now,
            'classes' => $classes,
            'students' => $classes->sum('students'),
            'recorded' => $classes->sum('recorded'),
            'attended' => $classes->sum('present') + $classes->sum('late'),
            'late' => $classes->sum('late'),
            'absent' => $classes->sum('absent'),
            'unmarked_sections' => $classes->filter(fn ($c) => $c['recorded'] < $c['students'])->count(),
            'pending_results' => ResultPublication::where('school_id', $schoolId)->where('status', 'submitted')->count(),
            'open_tickets' => ServiceTicket::where('school_id', $schoolId)->whereIn('status', ['open', 'in_progress'])->count(),
            'at_risk' => $this->lowAttendance($schoolId, $sessionId),
        ];
    }

    public function money(int $schoolId): array
    {
        $now = CarbonImmutable::now(AttendanceService::TIMEZONE);
        $confirmed = fn () => FeePayment::where('school_id', $schoolId)->where('status', 'confirmed');
        $invoices = $this->finance->invoices(StudentFeeManager::where('school_id', $schoolId)->get());

        return [
            'today' => (int) $confirmed()->where('paid_at', '>=', $now->startOfDay()->setTimezone(config('app.timezone')))->sum('amount'),
            'month' => (int) $confirmed()->where('paid_at', '>=', $now->startOfMonth()->setTimezone(config('app.timezone')))->sum('amount'),
            'billed' => $invoices->sum('total'),
            'paid' => $invoices->sum('paid'),
            'due' => $invoices->sum('due'),
            'debtors' => $invoices->where('due', '>', 0)->unique('student_id')->count(),
        ];
    }

    /** Students below 80% across recorded days, lowest first. */
    private function lowAttendance(int $schoolId, ?int $sessionId, int $limit = 6)
    {
        return DB::table('daily_attendances')
            ->join('users', 'users.id', '=', 'daily_attendances.student_id')
            ->where('daily_attendances.school_id', $schoolId)
            ->where('users.status', 1)
            ->when($sessionId, fn ($q) => $q->where('daily_attendances.session_id', $sessionId))
            ->groupBy('users.id', 'users.name', 'users.student_id')
            ->havingRaw('count(*) >= 5 and sum(daily_attendances.status in (1, 2)) / count(*) < 0.8')
            ->orderByRaw('sum(daily_attendances.status in (1, 2)) / count(*)')
            ->limit($limit)
            ->get(['users.id', 'users.name', 'users.student_id', DB::raw('round(sum(daily_attendances.status in (1, 2)) / count(*) * 100) as rate'), DB::raw('count(*) as days')]);
    }
}
