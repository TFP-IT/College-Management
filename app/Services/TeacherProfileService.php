<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds the read-only Teacher 360° profile: Academic Workspace, Employee Workspace and a scorecard.
 * The scorecard is a set of separate indicators with context; it deliberately has no combined score.
 */
class TeacherProfileService
{
    private const PASS_MARK = 33;
    private const MIN_FEEDBACK_RESPONSES = 5;

    private User $teacher;
    private ?int $sessionId;
    private Collection $assignments;

    public function build(User $teacher): array
    {
        $this->teacher = $teacher;
        $this->sessionId = $this->runningSession();
        $this->assignments = $this->teachingAssignments();

        $academic = $this->academicWorkspace();
        $employee = $this->employeeWorkspace();
        $employee['workload']['pending_marking'] = $academic['assignments']['marking']['pending'];
        $employee['workload']['upcoming_appointments'] = count($academic['appointments']['upcoming']);

        return [
            'teacher' => $this->teacherSummary(),
            'today' => $this->todayClasses($teacher),
            'academic' => $academic,
            'employee' => $employee,
            'scorecard' => $this->scorecard($academic, $employee),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public const CLASS_SLOTS = [
        'ongoing' => 'Ongoing',
        'upcoming' => 'Upcoming',
        'earlier' => 'Earlier',
    ];

    public const CLASS_MODES = [
        'in_class' => 'In class',
        'online' => 'Online',
        'postpone' => 'Postpone',
        'done' => 'Done',
    ];

    /**
     * Today's periods from the class routine on the website.
     * The slot follows the clock. The mode is set by the teacher.
     */
    public function todayClasses(User $teacher): array
    {
        $this->teacher = $teacher;
        $this->sessionId = $this->runningSession();

        $day = strtolower(now()->format('l'));
        $rows = DB::table('routines')
            ->where('teacher_id', $teacher->id)
            ->where('day', $day)
            ->when($this->sessionId, fn ($q) => $q->where('session_id', $this->sessionId))
            ->orderBy('starting_hour')->orderBy('starting_minute')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $subjects = DB::table('subjects')->whereIn('id', $rows->pluck('subject_id'))->pluck('name', 'id');
        $classes = DB::table('classes')->whereIn('id', $rows->pluck('class_id'))->pluck('name', 'id');
        $sections = DB::table('sections')->whereIn('id', $rows->pluck('section_id'))->pluck('name', 'id');
        $rooms = Schema::hasTable('class_rooms')
            ? DB::table('class_rooms')->whereIn('id', $rows->pluck('room_id'))->pluck('name', 'id')
            : collect();
        $marks = Schema::hasTable('routine_class_statuses')
            ? DB::table('routine_class_statuses')
                ->where('teacher_id', $teacher->id)
                ->whereDate('class_date', now()->toDateString())
                ->whereIn('routine_id', $rows->pluck('id'))
                ->pluck('status', 'routine_id')
            : collect();

        $nowMinutes = now()->timezone('Asia/Dhaka')->hour * 60 + now()->timezone('Asia/Dhaka')->minute;
        $clock = function (int $minutes) {
            $minutes = $minutes % (24 * 60);
            return Carbon::createFromTime(intdiv($minutes, 60), $minutes % 60, 0, 'Asia/Dhaka')->format('g:i A');
        };

        return $rows->map(function ($r) use ($subjects, $classes, $sections, $rooms, $marks, $clock, $nowMinutes) {
            $window = $this->periodWindow(
                (int) $r->starting_hour,
                (int) $r->starting_minute,
                (int) $r->ending_hour,
                (int) $r->ending_minute,
                $nowMinutes
            );
            $slot = $window['slot'];

            $mode = $marks[$r->id] ?? null;
            if (!isset(self::CLASS_MODES[$mode])) {
                $mode = null;
            }

            return [
                'id' => $r->id,
                'start' => $clock($window['start']),
                'end' => $clock($window['end']),
                'sort' => $window['start'],
                'subject' => $subjects[$r->subject_id] ?? null,
                'class' => trim(($classes[$r->class_id] ?? '') . (isset($sections[$r->section_id]) ? ' - ' . $sections[$r->section_id] : '')),
                'room' => $rooms[$r->room_id] ?? null,
                'slot' => $slot,
                'slot_label' => self::CLASS_SLOTS[$slot],
                'mode' => $mode,
                'mode_label' => $mode ? self::CLASS_MODES[$mode] : null,
            ];
        })->sortBy('sort')->map(function ($row) {
            unset($row['sort']);
            return $row;
        })->values()->all();
    }

    /**
     * Routine hours of 1–11 are the morning option in the admin form.
     * During the afternoon, 3:00–3:50 is the 3 PM period, so 3:37 PM is ongoing.
     */
    private function periodWindow(int $startHour, int $startMinute, int $endHour, int $endMinute, int $nowMinutes): array
    {
        $start = $startHour * 60 + $startMinute;
        $end = $endHour * 60 + $endMinute;
        $afternoon = $nowMinutes >= 12 * 60;
        if ($afternoon && $startHour > 0 && $startHour < 12 && $endHour < 12) {
            $start += 12 * 60;
            $end += 12 * 60;
        }

        if ($nowMinutes >= $end) {
            $slot = 'earlier';
        } elseif ($nowMinutes >= $start) {
            $slot = 'ongoing';
        } else {
            $slot = 'upcoming';
        }

        return ['start' => $start, 'end' => $end, 'slot' => $slot];
    }

    public function setClassStatus(User $teacher, int $routineId, string $status): void
    {
        if (!isset(self::CLASS_MODES[$status])) {
            abort(422, 'Choose In class, Online, Postpone, or Done.');
        }

        $ownsRoutine = DB::table('routines')
            ->where('id', $routineId)
            ->where('teacher_id', $teacher->id)
            ->exists();
        if (!$ownsRoutine) {
            abort(403, 'This class is not on your routine.');
        }

        $where = [
            'routine_id' => $routineId,
            'class_date' => now()->toDateString(),
        ];

        if ($status === 'in_class') {
            DB::table('routine_class_statuses')->where($where)->where('teacher_id', $teacher->id)->delete();
            return;
        }

        DB::table('routine_class_statuses')->updateOrInsert(
            $where,
            ['teacher_id' => $teacher->id, 'status' => $status, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function teacherSummary(): array
    {
        $t = $this->teacher;
        $info = json_decode((string) $t->user_information, true);
        $info = is_array($info) ? $info : [];
        $department = $t->department_id ? DB::table('departments')->where('id', $t->department_id)->value('name') : null;

        $photo = trim((string) ($info['photo'] ?? ''));
        $birthday = is_numeric($info['birthday'] ?? null) && (int) $info['birthday'] > 0
            ? date('Y-m-d', (int) $info['birthday'])
            : null;
        // The web edit form used to prepend ">" to the address on every save.
        $address = trim(ltrim(trim((string) ($info['address'] ?? '')), '>'));

        return [
            'id' => $t->id,
            'name' => $t->name,
            'email' => $t->email,
            'username' => $t->username,
            'employee_id' => $t->teacher_id ?: 'MCC-T-' . str_pad((string) $t->id, 4, '0', STR_PAD_LEFT),
            'designation' => $t->designation,
            'department' => $department,
            'phone' => ($info['phone'] ?? '') !== '' ? $info['phone'] : $t->phone_number,
            'gender' => ($info['gender'] ?? '') !== '' ? $info['gender'] : null,
            'blood_group' => ($info['blood_group'] ?? '') !== '' ? strtoupper($info['blood_group']) : null,
            'birthday' => $birthday,
            'address' => $address !== '' ? $address : null,
            'photo_url' => $photo !== '' ? url('assets/uploads/user-images/' . $photo) : null,
            'joined_on' => optional($t->created_at)->toDateString(),
        ];
    }

    // ---------------------------------------------------------------- Academic Workspace

    private function academicWorkspace(): array
    {
        $pairs = $this->assignments->map(fn ($a) => [$a['class_id'], $a['section_id']])->unique(fn ($p) => implode('-', $p))->values();
        $studentIds = $this->studentIdsFor($pairs);

        return [
            'summary' => [
                'classes' => $pairs->count(),
                'subjects' => $this->assignments->pluck('subject_id')->filter()->unique()->count(),
                'students' => $studentIds->count(),
                'weekly_periods' => $this->assignments->sum('weekly_periods'),
            ],
            'classes' => $this->assignments->values()->all(),
            'attendance' => $this->studentAttendance($pairs),
            'lesson_plans' => $this->lessonPlans(),
            'syllabus' => $this->syllabusProgress(),
            'assignments' => $this->assignmentSummary(),
            'exam_questions' => $this->examQuestions(),
            'performance' => $this->studentPerformance(),
            'appointments' => $this->appointments(),
        ];
    }

    /** Class/section/subject combinations the teacher is timetabled for or has been given permissions on. */
    private function teachingAssignments(): Collection
    {
        $routines = DB::table('routines')
            ->where('teacher_id', $this->teacher->id)
            ->when($this->sessionId, fn ($q) => $q->where('session_id', $this->sessionId))
            ->get();

        $rows = $routines->groupBy(fn ($r) => "{$r->class_id}-{$r->section_id}-{$r->subject_id}")
            ->map(function ($group) {
                $first = $group->first();
                $minutes = $group->sum(fn ($r) => max(0, ((int) $r->ending_hour * 60 + (int) $r->ending_minute) - ((int) $r->starting_hour * 60 + (int) $r->starting_minute)));
                return [
                    'class_id' => (int) $first->class_id,
                    'section_id' => (int) $first->section_id,
                    'subject_id' => (int) $first->subject_id,
                    'weekly_periods' => $group->count(),
                    'weekly_minutes' => $minutes,
                ];
            });

        $permissions = DB::table('teacher_permissions')->where('teacher_id', $this->teacher->id)->get();
        foreach ($permissions as $p) {
            $alreadyListed = $rows->contains(fn ($r) => $r['class_id'] == $p->class_id && $r['section_id'] == $p->section_id);
            if (!$alreadyListed) {
                $rows->put("{$p->class_id}-{$p->section_id}-0", [
                    'class_id' => (int) $p->class_id,
                    'section_id' => (int) $p->section_id,
                    'subject_id' => null,
                    'weekly_periods' => 0,
                    'weekly_minutes' => 0,
                ]);
            }
        }

        $classNames = DB::table('classes')->whereIn('id', $rows->pluck('class_id'))->pluck('name', 'id');
        $sectionNames = DB::table('sections')->whereIn('id', $rows->pluck('section_id'))->pluck('name', 'id');
        $subjectNames = DB::table('subjects')->whereIn('id', $rows->pluck('subject_id')->filter())->pluck('name', 'id');

        return $rows->map(function ($r) use ($classNames, $sectionNames, $subjectNames) {
            $r['class_name'] = $classNames[$r['class_id']] ?? "Class {$r['class_id']}";
            $r['section_name'] = $sectionNames[$r['section_id']] ?? null;
            $r['subject_name'] = $r['subject_id'] ? ($subjectNames[$r['subject_id']] ?? "Subject {$r['subject_id']}") : null;
            $r['students'] = $this->studentIdsFor(collect([[$r['class_id'], $r['section_id']]]))->count();
            return $r;
        })->values();
    }

    private function studentIdsFor(Collection $pairs): Collection
    {
        if ($pairs->isEmpty() || !Schema::hasTable('enrollments')) {
            return collect();
        }

        return DB::table('enrollments')
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as [$classId, $sectionId]) {
                    $q->orWhere(fn ($w) => $w->where('class_id', $classId)->where('section_id', $sectionId));
                }
            })
            ->when($this->sessionId, fn ($q) => $q->where('session_id', $this->sessionId))
            ->distinct()
            ->pluck('user_id');
    }

    private function studentAttendance(Collection $pairs): array
    {
        if ($pairs->isEmpty()) {
            return ['period_days' => 30, 'records' => 0, 'present' => 0, 'absent' => 0, 'rate' => null];
        }

        $records = DB::table('daily_attendances')
            ->where('timestamp', '>=', now()->subDays(30)->startOfDay()->timestamp)
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as [$classId, $sectionId]) {
                    $q->orWhere(fn ($w) => $w->where('class_id', $classId)->where('section_id', $sectionId));
                }
            })
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present')
            ->first();

        $total = (int) $records->total;
        $present = (int) $records->present;

        return [
            'period_days' => 30,
            'records' => $total,
            'present' => $present,
            'absent' => $total - $present,
            'rate' => $total ? round($present / $total * 100, 1) : null,
        ];
    }

    private function lessonPlans(): array
    {
        $plans = DB::table('teacher_lesson_plans')->where('teacher_id', $this->teacher->id);

        return [
            'total' => (clone $plans)->count(),
            'delivered' => (clone $plans)->where('status', 'delivered')->count(),
            'upcoming' => (clone $plans)->where('status', 'planned')
                ->where(fn ($q) => $q->whereNull('planned_date')->orWhere('planned_date', '>=', today()))
                ->orderBy('planned_date')->limit(5)->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'planned_date' => $p->planned_date,
                    'class' => $this->classLabel($p->class_id, $p->section_id),
                    'subject' => $this->subjectName($p->subject_id),
                ])->all(),
        ];
    }

    private function syllabusProgress(): array
    {
        $topics = DB::table('teacher_syllabus_topics')->where('teacher_id', $this->teacher->id)->get();

        $bySubject = $topics->groupBy(fn ($t) => "{$t->class_id}-{$t->section_id}-{$t->subject_id}")
            ->map(function ($group) {
                $first = $group->first();
                $done = $group->whereNotNull('completed_at')->count();
                return [
                    'subject' => $this->subjectName($first->subject_id) ?? 'General',
                    'class' => $this->classLabel($first->class_id, $first->section_id),
                    'completed' => $done,
                    'total' => $group->count(),
                    'percent' => round($done / $group->count() * 100),
                    'behind_schedule' => $group->whereNull('completed_at')->filter(fn ($t) => $t->target_date && $t->target_date < today()->toDateString())->count(),
                ];
            })->values();

        $completed = $topics->whereNotNull('completed_at')->count();

        return [
            'total_topics' => $topics->count(),
            'completed' => $completed,
            'percent' => $topics->count() ? round($completed / $topics->count() * 100) : null,
            'by_subject' => $bySubject->all(),
        ];
    }

    private function assignmentSummary(): array
    {
        $items = DB::table('teacher_assignments')->where('teacher_id', $this->teacher->id)
            ->orderByDesc('due_date')->limit(20)->get();

        $submitted = $items->sum('submitted_count');
        $marked = $items->sum('marked_count');

        return [
            'active' => $items->filter(fn ($a) => !$a->due_date || $a->due_date >= today()->toDateString())->count(),
            'marking' => [
                'submitted' => (int) $submitted,
                'marked' => (int) $marked,
                'pending' => max(0, (int) $submitted - (int) $marked),
            ],
            'items' => $items->take(5)->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'due_date' => $a->due_date,
                'class' => $this->classLabel($a->class_id, $a->section_id),
                'subject' => $this->subjectName($a->subject_id),
                'submitted' => (int) $a->submitted_count,
                'marked' => (int) $a->marked_count,
                'total_students' => (int) $a->total_students,
            ])->values()->all(),
        ];
    }

    private function examQuestions(): array
    {
        $items = DB::table('teacher_exam_questions')->where('teacher_id', $this->teacher->id)
            ->orderByRaw('deadline IS NULL, deadline')->get();

        return [
            'counts' => [
                'draft' => $items->where('status', 'draft')->count(),
                'submitted' => $items->where('status', 'submitted')->count(),
                'approved' => $items->where('status', 'approved')->count(),
                'returned' => $items->where('status', 'returned')->count(),
            ],
            'items' => $items->take(5)->map(fn ($q) => [
                'id' => $q->id,
                'title' => $q->title,
                'status' => $q->status,
                'deadline' => $q->deadline,
                'question_count' => (int) $q->question_count,
                'subject' => $this->subjectName($q->subject_id),
            ])->values()->all(),
        ];
    }

    /** Average marks per exam for the teacher's own class/section/subject combinations. */
    private function studentPerformance(): array
    {
        $combos = $this->assignments->filter(fn ($a) => $a['subject_id']);
        if ($combos->isEmpty()) {
            return ['by_exam' => [], 'latest_average' => null, 'latest_pass_rate' => null, 'improvement' => null];
        }

        $rows = DB::table('gradebooks')
            ->where(function ($q) use ($combos) {
                foreach ($combos->unique(fn ($c) => "{$c['class_id']}-{$c['section_id']}") as $c) {
                    $q->orWhere(fn ($w) => $w->where('class_id', $c['class_id'])->where('section_id', $c['section_id']));
                }
            })
            ->when($this->sessionId, fn ($q) => $q->where('session_id', $this->sessionId))
            ->get();

        $marksByExam = [];
        foreach ($rows as $row) {
            $marks = json_decode($row->marks ?? '', true);
            if (!is_array($marks)) {
                continue;
            }
            foreach ($combos as $c) {
                if ($c['class_id'] == $row->class_id && $c['section_id'] == $row->section_id && isset($marks[$c['subject_id']]) && is_numeric($marks[$c['subject_id']])) {
                    $marksByExam[$row->exam_category_id][$row->student_id . '-' . $c['subject_id']] = (float) $marks[$c['subject_id']];
                }
            }
        }

        ksort($marksByExam);
        $examNames = DB::table('exam_categories')->whereIn('id', array_keys($marksByExam))->pluck('name', 'id');

        $byExam = collect($marksByExam)->map(fn ($marks, $examId) => [
            'exam_category_id' => (int) $examId,
            'exam' => $examNames[$examId] ?? "Exam {$examId}",
            'entries' => count($marks),
            'average' => round(array_sum($marks) / count($marks), 1),
            'pass_rate' => round(count(array_filter($marks, fn ($m) => $m >= self::PASS_MARK)) / count($marks) * 100, 1),
        ])->values();

        $improvement = null;
        if (count($marksByExam) >= 2) {
            [$previous, $latest] = array_slice(array_values($marksByExam), -2);
            $common = array_intersect_key($latest, $previous);
            if ($common) {
                $deltas = array_map(fn ($key) => $latest[$key] - $previous[$key], array_keys($common));
                $improvement = [
                    'compared_entries' => count($deltas),
                    'average_change' => round(array_sum($deltas) / count($deltas), 1),
                    'improved' => count(array_filter($deltas, fn ($d) => $d > 0)),
                    'declined' => count(array_filter($deltas, fn ($d) => $d < 0)),
                ];
            }
        }

        $latestExam = $byExam->last();

        return [
            'by_exam' => $byExam->all(),
            'latest_average' => $latestExam['average'] ?? null,
            'latest_pass_rate' => $latestExam['pass_rate'] ?? null,
            'improvement' => $improvement,
        ];
    }

    private function appointments(): array
    {
        $items = DB::table('guardian_appointments')->where('teacher_id', $this->teacher->id)
            ->whereIn('status', ['requested', 'confirmed'])
            ->orderByRaw('scheduled_at IS NULL, scheduled_at')->limit(10)->get();

        $people = User::whereIn('id', $items->pluck('guardian_id')->merge($items->pluck('student_id'))->filter())->pluck('name', 'id');

        return [
            'pending_requests' => $items->where('status', 'requested')->count(),
            'upcoming' => $items->map(fn ($a) => [
                'id' => $a->id,
                'purpose' => $a->purpose,
                'scheduled_at' => $a->scheduled_at,
                'status' => $a->status,
                'guardian' => $people[$a->guardian_id] ?? null,
                'student' => $people[$a->student_id] ?? null,
            ])->values()->all(),
        ];
    }

    // ---------------------------------------------------------------- Employee Workspace

    private function employeeWorkspace(): array
    {
        return [
            'attendance' => $this->staffAttendance(),
            'leave' => $this->leave(),
            'payslips' => $this->payslips(),
            'appraisals' => $this->appraisals(),
            'training' => $this->training(),
            'conduct' => $this->conduct(),
            'notices' => $this->notices(),
            'workload' => $this->workload(),
        ];
    }

    private function staffAttendance(): array
    {
        $since = now()->subDays(90)->toDateString();
        $records = DB::table('staff_attendances')->where('user_id', $this->teacher->id)
            ->where('date', '>=', $since)->orderByDesc('date')->get();

        $month = $records->filter(fn ($r) => Carbon::parse($r->date)->isSameMonth(now()));

        return [
            'month_label' => now()->format('F Y'),
            'this_month' => [
                'present' => $month->where('status', 'present')->count(),
                'late' => $month->where('status', 'late')->count(),
                'absent' => $month->where('status', 'absent')->count(),
                'on_leave' => $month->where('status', 'on_leave')->count(),
            ],
            'last_90_days' => [
                'present' => $records->where('status', 'present')->count(),
                'late' => $records->where('status', 'late')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'on_leave' => $records->where('status', 'on_leave')->count(),
            ],
            'recent' => $records->take(7)->map(fn ($r) => [
                'date' => $r->date,
                'status' => $r->status,
                'check_in' => $r->check_in,
                'check_out' => $r->check_out,
            ])->values()->all(),
        ];
    }

    private function leave(): array
    {
        $year = now()->year;
        $entitlements = DB::table('staff_leave_entitlements')->where('user_id', $this->teacher->id)->where('year', $year)->get();
        $leaves = DB::table('staff_leaves')->where('user_id', $this->teacher->id)->orderByDesc('start_date')->get();
        $approvedThisYear = $leaves->where('status', 'approved')->filter(fn ($l) => Carbon::parse($l->start_date)->year === $year);

        return [
            'year' => $year,
            'balances' => $entitlements->map(function ($e) use ($approvedThisYear) {
                $used = (float) $approvedThisYear->where('leave_type', $e->leave_type)->sum('days');
                return [
                    'type' => $e->leave_type,
                    'entitled' => (float) $e->days,
                    'used' => $used,
                    'remaining' => max(0, (float) $e->days - $used),
                ];
            })->values()->all(),
            'pending' => $leaves->where('status', 'pending')->count(),
            'recent' => $leaves->take(5)->map(fn ($l) => [
                'id' => $l->id,
                'type' => $l->leave_type,
                'start_date' => $l->start_date,
                'end_date' => $l->end_date,
                'days' => (float) $l->days,
                'status' => $l->status,
                'reason' => $l->reason,
            ])->values()->all(),
        ];
    }

    private function payslips(): array
    {
        $items = DB::table('staff_payslips')->where('user_id', $this->teacher->id)
            ->whereIn('status', ['issued', 'paid'])->orderByDesc('period')->limit(6)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'period' => Carbon::parse($p->period)->format('F Y'),
                'basic' => (float) $p->basic,
                'allowances' => (float) $p->allowances,
                'deductions' => (float) $p->deductions,
                'net' => (float) $p->net,
                'status' => $p->status,
                'paid_at' => $p->paid_at,
                'breakdown' => $p->breakdown ? json_decode($p->breakdown, true) : null,
            ]);

        return ['latest' => $items->first(), 'items' => $items->values()->all()];
    }

    private function appraisals(): array
    {
        $items = DB::table('staff_appraisals')->where('user_id', $this->teacher->id)
            ->orderByDesc('review_date')->limit(5)->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'period' => $a->period_label,
                'reviewer' => $a->reviewer_name,
                'review_date' => $a->review_date,
                'status' => $a->status,
                'summary' => $a->summary,
                'strengths' => $a->strengths,
                'development_areas' => $a->development_areas,
                'acknowledged' => (bool) $a->acknowledged_at,
            ]);

        return ['latest' => $items->first(), 'items' => $items->values()->all()];
    }

    private function training(): array
    {
        $items = DB::table('staff_trainings')->where('user_id', $this->teacher->id)
            ->where('status', '!=', 'cancelled')->orderByDesc('start_date')->get();
        $completed = $items->where('status', 'completed');
        $thisYear = $completed->filter(fn ($t) => $t->completed_on && Carbon::parse($t->completed_on)->year === now()->year);

        return [
            'completed' => $completed->count(),
            'in_progress' => $items->where('status', 'enrolled')->count(),
            'mandatory_total' => $items->where('is_mandatory', true)->count(),
            'mandatory_completed' => $completed->where('is_mandatory', true)->count(),
            'hours_this_year' => (float) $thisYear->sum('hours'),
            'professional_development_hours_this_year' => (float) $thisYear->where('category', 'professional_development')->sum('hours'),
            'items' => $items->take(8)->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'provider' => $t->provider,
                'category' => $t->category,
                'is_mandatory' => (bool) $t->is_mandatory,
                'hours' => (float) $t->hours,
                'status' => $t->status,
                'start_date' => $t->start_date,
                'completed_on' => $t->completed_on,
            ])->values()->all(),
        ];
    }

    private function conduct(): array
    {
        $items = DB::table('staff_conduct_records')->where('user_id', $this->teacher->id)->orderByDesc('issued_on')->get();

        return [
            'open_warnings' => $items->where('type', 'warning')->where('status', 'open')->count(),
            'open_discipline' => $items->where('type', 'discipline')->where('status', 'open')->count(),
            'commendations' => $items->where('type', 'commendation')->count(),
            'items' => $items->take(10)->map(fn ($c) => [
                'id' => $c->id,
                'type' => $c->type,
                'title' => $c->title,
                'details' => $c->details,
                'issued_by' => $c->issued_by,
                'issued_on' => $c->issued_on,
                'status' => $c->status,
            ])->values()->all(),
        ];
    }

    private function notices(): array
    {
        return DB::table('noticeboard')
            ->when($this->teacher->school_id, fn ($q) => $q->where('school_id', $this->teacher->school_id))
            ->orderByDesc('id')->limit(5)->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->notice_title,
                'body' => strip_tags((string) $n->notice),
                'start_date' => $n->start_date,
                'end_date' => $n->end_date,
            ])->values()->all();
    }

    private function workload(): array
    {
        $routines = DB::table('routines')->where('teacher_id', $this->teacher->id)
            ->when($this->sessionId, fn ($q) => $q->where('session_id', $this->sessionId))->get();

        $byDay = $routines->groupBy(fn ($r) => strtolower((string) $r->day))
            ->map(fn ($g, $day) => ['day' => ucfirst($day), 'periods' => $g->count()])->values();

        return [
            'weekly_periods' => $routines->count(),
            'weekly_hours' => round($this->assignments->sum('weekly_minutes') / 60, 1),
            'classes' => $this->assignments->unique(fn ($a) => "{$a['class_id']}-{$a['section_id']}")->count(),
            'subjects' => $this->assignments->pluck('subject_id')->filter()->unique()->count(),
            'by_day' => $byDay->all(),
        ];
    }

    // ---------------------------------------------------------------- Scorecard

    private function scorecard(array $academic, array $employee): array
    {
        $performance = $academic['performance'];
        $syllabus = $academic['syllabus'];
        $attendance = $employee['attendance']['last_90_days'];
        $training = $employee['training'];
        $conduct = $employee['conduct'];

        $feedback = DB::table('teacher_feedback')->where('teacher_id', $this->teacher->id)
            ->where('created_at', '>=', now()->subYear())
            ->selectRaw('COUNT(*) as responses, AVG(rating) as average')->first();

        $arrivals = $attendance['present'] + $attendance['late'];

        $indicators = [
            $this->indicator(
                'student_improvement',
                'Student improvement',
                $performance['improvement'] ? sprintf('%+.1f marks', $performance['improvement']['average_change']) : null,
                $performance['improvement']
                    ? "{$performance['improvement']['improved']} improved, {$performance['improvement']['declined']} declined between the last two exams"
                    : 'Needs marks from at least two exams',
                $performance['improvement'] ? ($performance['improvement']['average_change'] >= 0 ? 'good' : 'watch') : null,
            ),
            $this->indicator(
                'syllabus_completion',
                'Syllabus completion',
                $syllabus['percent'] !== null ? "{$syllabus['percent']}%" : null,
                $syllabus['total_topics'] ? "{$syllabus['completed']} of {$syllabus['total_topics']} topics covered" : 'No syllabus topics recorded',
                $syllabus['percent'] === null ? null : ($syllabus['percent'] >= 70 ? 'good' : 'watch'),
            ),
            $this->indicator(
                'punctuality',
                'Class punctuality',
                $arrivals ? round($attendance['present'] / $arrivals * 100) . '% on time' : null,
                $arrivals ? "{$attendance['late']} late arrival(s) in the last 90 days" : 'No staff attendance recorded',
                $arrivals ? ($attendance['late'] / $arrivals <= 0.1 ? 'good' : 'watch') : null,
            ),
            $this->indicator(
                'student_feedback',
                'Student feedback',
                $feedback->responses >= self::MIN_FEEDBACK_RESPONSES ? number_format((float) $feedback->average, 1) . ' / 5' : null,
                $feedback->responses >= self::MIN_FEEDBACK_RESPONSES
                    ? "{$feedback->responses} responses in the last 12 months"
                    : 'Shown after at least ' . self::MIN_FEEDBACK_RESPONSES . ' responses',
                $feedback->responses >= self::MIN_FEEDBACK_RESPONSES ? ((float) $feedback->average >= 3.5 ? 'good' : 'watch') : null,
            ),
            $this->indicator(
                'academic_results',
                'Academic results',
                $performance['latest_pass_rate'] !== null ? "{$performance['latest_pass_rate']}% pass" : null,
                $performance['latest_average'] !== null ? "Average mark {$performance['latest_average']} in the latest exam" : 'No exam marks recorded',
                $performance['latest_pass_rate'] === null ? null : ($performance['latest_pass_rate'] >= 80 ? 'good' : 'watch'),
            ),
            $this->indicator(
                'professional_conduct',
                'Professional conduct',
                ($conduct['open_warnings'] + $conduct['open_discipline']) === 0 ? 'No open issues' : ($conduct['open_warnings'] + $conduct['open_discipline']) . ' open',
                "{$conduct['commendations']} commendation(s) on record",
                ($conduct['open_warnings'] + $conduct['open_discipline']) === 0 ? 'good' : 'watch',
            ),
            $this->indicator(
                'training_completion',
                'Training completion',
                $training['mandatory_total'] ? "{$training['mandatory_completed']} / {$training['mandatory_total']} mandatory" : ($training['completed'] ? "{$training['completed']} completed" : null),
                "{$training['hours_this_year']} training hours this year",
                $training['mandatory_total'] ? ($training['mandatory_completed'] >= $training['mandatory_total'] ? 'good' : 'watch') : ($training['completed'] ? 'good' : null),
            ),
        ];

        return [
            'note' => 'These indicators give context for a professional conversation. They are not combined into a single score and should be read alongside classroom observation and the appraisal.',
            'indicators' => $indicators,
        ];
    }

    private function indicator(string $key, string $label, ?string $value, string $detail, ?string $status): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'detail' => $detail,
            'status' => $value === null ? 'no_data' : ($status ?? 'neutral'),
        ];
    }

    // ---------------------------------------------------------------- Helpers

    private function runningSession(): ?int
    {
        if (!$this->teacher->school_id || !Schema::hasColumn('schools', 'running_session')) {
            return null;
        }
        $session = DB::table('schools')->where('id', $this->teacher->school_id)->value('running_session');
        return $session ? (int) $session : null;
    }

    private array $classLabels = [];
    private array $subjectNames = [];

    private function classLabel($classId, $sectionId): ?string
    {
        if (!$classId) {
            return null;
        }
        $key = "{$classId}-{$sectionId}";
        if (!array_key_exists($key, $this->classLabels)) {
            $class = DB::table('classes')->where('id', $classId)->value('name') ?? "Class {$classId}";
            $section = $sectionId ? DB::table('sections')->where('id', $sectionId)->value('name') : null;
            $this->classLabels[$key] = $section ? "{$class} - {$section}" : $class;
        }
        return $this->classLabels[$key];
    }

    private function subjectName($subjectId): ?string
    {
        if (!$subjectId) {
            return null;
        }
        return $this->subjectNames[$subjectId] ??= DB::table('subjects')->where('id', $subjectId)->value('name') ?? "Subject {$subjectId}";
    }
}
