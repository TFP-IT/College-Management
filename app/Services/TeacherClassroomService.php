<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only view of what the web admin assigned to a teacher: subjects come from the
 * class routine, attendance/marks rights from Teacher Permission, students from enrollments.
 */
class TeacherClassroomService
{
    public function classes(User $teacher): Collection
    {
        $schoolId = $teacher->school_id;
        $sessionId = $this->runningSession($schoolId);

        $routines = DB::table('routines')
            ->where('teacher_id', $teacher->id)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->get(['class_id', 'section_id', 'subject_id']);

        $permissions = DB::table('teacher_permissions')
            ->where('teacher_id', $teacher->id)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get(['class_id', 'section_id', 'marks', 'attendance'])
            ->keyBy(fn ($p) => "{$p->class_id}-{$p->section_id}");

        $rows = $routines
            ->unique(fn ($r) => "{$r->class_id}-{$r->section_id}-{$r->subject_id}")
            ->map(fn ($r) => ['class_id' => (int) $r->class_id, 'section_id' => (int) $r->section_id, 'subject_id' => (int) $r->subject_id ?: null]);

        foreach ($permissions as $key => $p) {
            if (!$rows->contains(fn ($r) => "{$r['class_id']}-{$r['section_id']}" === $key)) {
                $rows->push(['class_id' => (int) $p->class_id, 'section_id' => (int) $p->section_id, 'subject_id' => null]);
            }
        }

        if ($rows->isEmpty()) {
            return collect();
        }

        $classNames = DB::table('classes')->whereIn('id', $rows->pluck('class_id'))->pluck('name', 'id');
        $sectionNames = DB::table('sections')->whereIn('id', $rows->pluck('section_id'))->pluck('name', 'id');
        $subjectNames = DB::table('subjects')->whereIn('id', $rows->pluck('subject_id')->filter())->pluck('name', 'id');
        $studentCounts = DB::table('enrollments')
            ->whereIn('class_id', $rows->pluck('class_id')->unique())
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->select('class_id', 'section_id', DB::raw('count(*) as c'))
            ->groupBy('class_id', 'section_id')
            ->get()
            ->keyBy(fn ($e) => "{$e->class_id}-{$e->section_id}");
        $topics = Schema::hasTable('teacher_syllabus_topics')
            ? DB::table('teacher_syllabus_topics')->where('teacher_id', $teacher->id)->get(['class_id', 'section_id', 'subject_id', 'completed_at'])
            : collect();

        return $rows->values()->map(function ($r, $index) use ($permissions, $classNames, $sectionNames, $subjectNames, $studentCounts, $topics) {
            $key = "{$r['class_id']}-{$r['section_id']}";
            $permission = $permissions->get($key);
            $mine = $topics->filter(fn ($t) => $t->class_id == $r['class_id']
                && ($t->section_id === null || $t->section_id == $r['section_id'])
                && $t->subject_id == $r['subject_id']);
            $progress = $mine->count() ? (int) round($mine->whereNotNull('completed_at')->count() / $mine->count() * 100) : 0;
            $className = $classNames[$r['class_id']] ?? "Class {$r['class_id']}";
            $sectionName = $sectionNames[$r['section_id']] ?? null;

            return [
                'id' => $index + 1,
                'name' => $r['subject_id'] ? ($subjectNames[$r['subject_id']] ?? "Subject {$r['subject_id']}") : 'Assigned Class',
                'section' => $sectionName ? "{$className} - {$sectionName}" : $className,
                'students' => (int) optional($studentCounts->get($key))->c,
                'progress' => "{$progress}%",
                'class_id' => $r['class_id'],
                'section_id' => $r['section_id'],
                'subject_id' => $r['subject_id'],
                'can_take_attendance' => (bool) optional($permission)->attendance,
                'can_enter_marks' => (bool) optional($permission)->marks,
            ];
        });
    }

    /**
     * Weekly routine for the classes and sections this teacher is assigned to on the web.
     * Other classes, other sections, and other teachers' periods are left out.
     */
    public function routine(User $teacher, ?int $classId = null, ?int $sectionId = null): array
    {
        $schoolId = $teacher->school_id;
        $sessionId = $this->runningSession($schoolId);

        $assigned = DB::table('routines')
            ->where('teacher_id', $teacher->id)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->orderBy('starting_hour')->orderBy('starting_minute')
            ->get();

        $classes = DB::table('classes')
            ->whereIn('id', $assigned->pluck('class_id')->unique())
            ->orderBy('name')
            ->get(['id', 'name']);

        $classId = $classId && $classes->contains('id', $classId)
            ? $classId
            : (int) ($assigned->first()->class_id ?? 0);
        $classId = $classId ?: null;

        $sections = $classId
            ? DB::table('sections')
                ->whereIn('id', $assigned->where('class_id', $classId)->pluck('section_id')->unique())
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $sectionId = $sectionId && $sections->contains('id', $sectionId)
            ? $sectionId
            : (int) (optional($assigned->first(fn ($row) => (int) $row->class_id === (int) $classId))->section_id ?? 0);
        $sectionId = $sectionId ?: null;

        $rows = $assigned
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->values();

        $subjects = DB::table('subjects')->whereIn('id', $rows->pluck('subject_id'))->pluck('name', 'id');
        $teachers = DB::table('users')->whereIn('id', $rows->pluck('teacher_id'))->pluck('name', 'id');
        $rooms = Schema::hasTable('class_rooms')
            ? DB::table('class_rooms')->whereIn('id', $rows->pluck('room_id'))->pluck('name', 'id')
            : collect();

        $byDay = $rows->groupBy(fn ($r) => strtolower((string) $r->day));
        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $students = ($classId && $sectionId) ? $this->enrolledStudents($classId, $sectionId, $sessionId) : collect();

        return [
            'class_id' => $classId,
            'section_id' => $sectionId,
            'classes' => $classes->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])->values(),
            'sections' => $sections->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])->values(),
            'students' => $students,
            'days' => collect($days)->map(function ($day) use ($byDay, $subjects, $teachers, $rooms, $teacher) {
                $periods = ($byDay->get($day) ?? collect())->map(fn ($r) => [
                    'id' => (int) $r->id,
                    'subject' => $subjects[$r->subject_id] ?? 'Class',
                    'time' => $r->starting_hour.':'.$r->starting_minute.' - '.$r->ending_hour.':'.$r->ending_minute,
                    'teacher' => $teachers[$r->teacher_id] ?? '',
                    'room' => $rooms[$r->room_id] ?? '',
                    'mine' => (int) $r->teacher_id === (int) $teacher->id,
                ])->values();

                return [
                    'day' => $day,
                    'label' => ucfirst($day),
                    'periods' => $periods,
                ];
            })->values(),
        ];
    }

    /**
     * Students enrolled on the web in one class and section.
     */
    private function enrolledStudents(int $classId, int $sectionId, ?int $sessionId): Collection
    {
        $sectionName = DB::table('sections')->where('id', $sectionId)->value('name');

        return DB::table('enrollments')
            ->join('users', 'users.id', '=', 'enrollments.user_id')
            ->leftJoin('sections', 'sections.id', '=', 'enrollments.section_id')
            ->where('enrollments.class_id', $classId)
            ->where(function ($query) use ($sectionId, $sectionName) {
                $query->where('enrollments.section_id', $sectionId);
                if ($sectionName !== null && $sectionName !== '') {
                    $query->orWhere('sections.name', $sectionName);
                }
            })
            ->where('users.role_id', WebAccountSync::STUDENT_ROLE)
            ->where(function ($query) {
                $query->whereNull('users.account_status')->orWhere('users.account_status', '!=', 'disable');
            })
            ->when($sessionId, fn ($q) => $q->where(function ($query) use ($sessionId) {
                $query->where('enrollments.session_id', $sessionId)->orWhereNull('enrollments.session_id')->orWhere('enrollments.session_id', 0);
            }))
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.student_id', 'users.user_information'])
            ->unique('id')
            ->values()
            ->map(function ($s) {
                $info = json_decode($s->user_information ?? '', true) ?: [];

                return [
                    'id' => (int) $s->id,
                    'name' => $s->name,
                    'student_id' => $s->student_id ?: ('MCC'.$s->id),
                    'phone' => $info['phone'] ?? '',
                    'photo' => !empty($info['photo']) ? asset('assets/uploads/user-images/' . $info['photo']) : null,
                ];
            });
    }

    /**
     * Students enrolled in the class sections assigned to this teacher on the web.
     */
    public function students(User $teacher, ?string $classFilter = null): Collection
    {
        $schoolId = $teacher->school_id;
        $sessionId = $this->runningSession($schoolId);

        $pairs = $this->classes($teacher)
            ->map(fn ($c) => ['class_id' => $c['class_id'], 'section_id' => $c['section_id']])
            ->unique(fn ($p) => "{$p['class_id']}-{$p['section_id']}")
            ->values();
        if ($pairs->isEmpty()) {
            return collect();
        }

        $query = DB::table('enrollments')
            ->join('users', 'users.id', '=', 'enrollments.user_id')
            ->join('classes', 'classes.id', '=', 'enrollments.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'enrollments.section_id')
            ->where('users.role_id', WebAccountSync::STUDENT_ROLE)
            ->where('users.status', 1)
            ->when($sessionId, fn ($q) => $q->where('enrollments.session_id', $sessionId))
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as $p) {
                    $q->orWhere(fn ($w) => $w->where('enrollments.class_id', $p['class_id'])->where('enrollments.section_id', $p['section_id']));
                }
            })
            ->when($classFilter, fn ($q, $f) => $q->where('classes.name', 'like', "%{$f}%"))
            ->orderBy('classes.name')->orderBy('sections.name')->orderBy('users.name');

        return $query->get(['users.id', 'users.name', 'users.student_id', 'classes.name as class_name', 'sections.name as section_name'])
            ->values()
            ->map(fn ($s, $index) => [
                'id' => $s->id,
                'name' => $s->name,
                'student_id' => $s->student_id ?: ('MCC' . $s->id),
                'roll' => str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'class' => $s->section_name ? "{$s->class_name} - {$s->section_name}" : $s->class_name,
                'status' => 'present',
            ]);
    }

    private function teaches(User $teacher, int $studentId): bool
    {
        $assigned = $this->classes($teacher)
            ->map(fn ($c) => "{$c['class_id']}-{$c['section_id']}");

        return DB::table('enrollments')
            ->where('user_id', $studentId)
            ->get(['class_id', 'section_id'])
            ->contains(fn ($e) => $assigned->contains("{$e->class_id}-{$e->section_id}"));
    }

    public function studentAnalytics(User $teacher, int $studentId): array
    {
        $schoolId = $teacher->school_id;
        $student = DB::table('users')
            ->where('id', $studentId)
            ->where('school_id', $schoolId)
            ->where('role_id', 7)
            ->first();
        if (!$student || !$this->teaches($teacher, $studentId)) {
            abort(404, 'Student not found');
        }

        $model = User::findOrFail($studentId);
        $context = \App\Modules\Campus\Support\StudentContext::for($model);
        $record = app(\App\Modules\Campus\Services\AttendanceService::class)->forStudent($context);
        $result = app(\App\Modules\Campus\Services\ResultService::class)->publishedFor($context)[0] ?? null;
        $info = json_decode($student->user_information ?? '', true) ?: [];
        $guardian = $student->parent_id ? User::find($student->parent_id) : null;
        $totals = $record['session'];
        $share = fn (int $count) => $totals['total'] ? (int) round($count / $totals['total'] * 100) : 0;

        // Everything below comes from stored records. Sections with no records stay empty.
        return [
            'student' => [
                'id' => (int) $student->id,
                'name' => $student->name,
                'student_id' => $student->student_id ?: ('MCC' . $student->id),
                'email' => $student->email,
                'phone' => $info['phone'] ?? '',
                'gender' => $info['gender'] ?? '',
                'blood_group' => $info['blood_group'] ?? '',
                'photo' => !empty($info['photo']) ? asset('assets/uploads/user-images/' . $info['photo']) : null,
                'class' => $context->className,
                'section' => $context->sectionName,
                'guardian_name' => $guardian?->name,
                'guardian_phone' => $guardian?->phone_number,
            ],
            'attendance' => [
                'rate' => $totals['rate'] ?? 0,
                'total_days' => $totals['total'],
                'present' => $totals['present'],
                'late' => $totals['late'],
                'absent' => $totals['absent'],
                'chart' => [
                    ['label' => 'Present', 'count' => $totals['present'], 'percentage' => $share($totals['present']), 'color' => '#17734A'],
                    ['label' => 'Late', 'count' => $totals['late'], 'percentage' => $share($totals['late']), 'color' => '#8F5B00'],
                    ['label' => 'Absent', 'count' => $totals['absent'], 'percentage' => $share($totals['absent']), 'color' => '#B42318'],
                ],
                'last_10' => collect($record['days'])->reverse()->take(10)->values()->map(fn ($d) => [
                    'date' => date('d M Y', strtotime($d['date'])),
                    'day' => date('l', strtotime($d['date'])),
                    'status' => $d['status'],
                    'check_in' => null,
                    'check_out' => null,
                    'source' => 'manual',
                ]),
            ],
            'leave' => ['total_taken' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0, 'chart' => [], 'history' => []],
            'academic' => [
                'gpa' => $result['gpa'] ?? null,
                'grade' => $result['grade'] ?? null,
                'average_score' => $result['average'] ?? null,
                'subjects' => collect($result['subjects'] ?? [])->map(fn ($s) => [
                    'id' => $s['subject_id'], 'subject' => $s['subject'], 'score' => $s['mark'], 'total' => 100, 'grade' => $s['grade'],
                ])->all(),
            ],
        ];
    }

    private function runningSession($schoolId): ?int
    {
        if (!$schoolId || !Schema::hasColumn('schools', 'running_session')) {
            return null;
        }

        $id = DB::table('schools')->where('id', $schoolId)->value('running_session');

        return $id ? (int) $id : null;
    }

    /**
     * One class section for a day, using the same daily_attendances rows as the website.
     * Status 1 is present, 2 is late, 0 is absent.
     */
    public function attendanceSheet(User $teacher, int $classId, int $sectionId, string $date): array
    {
        $this->assertTeaching($teacher, $classId, $sectionId);
        $sessionId = $this->sessionFor($teacher, $classId, $sectionId);
        $timestamp = \Carbon\Carbon::parse($date, 'Asia/Dhaka')->startOfDay()->timestamp;

        $students = DB::table('enrollments')
            ->join('users', 'users.id', '=', 'enrollments.user_id')
            ->where('enrollments.class_id', $classId)
            ->where('enrollments.section_id', $sectionId)
            ->where('users.role_id', WebAccountSync::STUDENT_ROLE)
            ->where('users.status', 1)
            ->when($sessionId, fn ($q) => $q->where('enrollments.session_id', $sessionId))
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.student_id']);

        $marks = DB::table('daily_attendances')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('timestamp', $timestamp)
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->when($teacher->school_id, fn ($q) => $q->where('school_id', $teacher->school_id))
            ->get()
            ->keyBy('student_id');

        $className = DB::table('classes')->where('id', $classId)->value('name');
        $sectionName = DB::table('sections')->where('id', $sectionId)->value('name');

        return [
            'date' => $date,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'class_name' => $className,
            'section_name' => $sectionName,
            'students' => $students->map(function ($student) use ($marks) {
                $mark = $marks->get($student->id);

                return [
                    'id' => (int) $student->id,
                    'name' => $student->name,
                    'student_id' => $student->student_id ?: ('MCC'.$student->id),
                    'status' => $mark ? $this->attendanceLabel((int) $mark->status) : null,
                    'note' => $mark->note ?? null,
                    'source' => $mark->source ?? null,
                ];
            })->values()->all(),
        ];
    }

    public function saveAttendance(User $teacher, int $classId, int $sectionId, string $date, array $records): array
    {
        $this->assertTeaching($teacher, $classId, $sectionId);
        $sessionId = $this->sessionFor($teacher, $classId, $sectionId);
        $timestamp = \Carbon\Carbon::parse($date, 'Asia/Dhaka')->startOfDay()->timestamp;
        $allowed = collect($this->attendanceSheet($teacher, $classId, $sectionId, $date)['students'])->pluck('id');

        $changed = [];

        foreach ($records as $record) {
            $studentId = (int) $record['student_id'];
            if (!$allowed->contains($studentId)) {
                abort(422, 'A student on this list is not enrolled in the section.');
            }
            $status = ['present' => 1, 'late' => 2, 'absent' => 0][$record['status']];
            $keys = [
                'class_id' => $classId,
                'section_id' => $sectionId,
                'student_id' => $studentId,
                'session_id' => $sessionId,
                'school_id' => $teacher->school_id,
                'timestamp' => $timestamp,
            ];
            $previous = \App\Models\DailyAttendances::where($keys)->value('status');
            \App\Models\DailyAttendances::updateOrCreate($keys, \App\Models\DailyAttendances::manualAttributes($status, $record['note'] ?? null));

            if ($status !== 1 && ($previous === null || (int) $previous !== $status)) {
                $changed[$status][] = $studentId;
            }
        }

        $day = \Carbon\Carbon::parse($date)->format('d M');
        $notifier = app(\App\Modules\Campus\Services\Notifier::class);
        foreach ([0 => 'Marked absent', 2 => 'Arrived late'] as $status => $title) {
            if (!empty($changed[$status])) {
                $notifier->toFamilies($changed[$status], 'attendance', $title, "{$title} on {$day}.", ['screen' => 'attendance']);
            }
        }

        return $this->attendanceSheet($teacher, $classId, $sectionId, $date);
    }

    private function sessionFor(User $teacher, int $classId, int $sectionId): int
    {
        $session = $this->runningSession($teacher->school_id);
        if ($session) {
            return $session;
        }

        $fromRoutine = DB::table('routines')
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->value('session_id');

        return (int) ($fromRoutine ?: 1);
    }

    private function assertTeaching(User $teacher, int $classId, int $sectionId): void
    {
        $teaches = $this->classes($teacher)->contains(
            fn ($class) => (int) $class['class_id'] === $classId && (int) $class['section_id'] === $sectionId
        );
        if (!$teaches) {
            abort(403, 'This class is not on your routine.');
        }
    }

    private function attendanceLabel(int $status): string
    {
        return match ($status) {
            1 => 'present',
            2 => 'late',
            default => 'absent',
        };
    }
}
