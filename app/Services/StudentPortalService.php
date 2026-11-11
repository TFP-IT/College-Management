<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The student app reads the same class routine, teachers, exams, and notices the website stores.
 */
class StudentPortalService
{
    public function build(User $student): array
    {
        $schoolId = $student->school_id;
        $sessionId = $this->runningSession($schoolId);
        $enrollment = DB::table('enrollments')->where('user_id', $student->id)->orderByDesc('id')->first();

        $empty = [
            'class_name' => null,
            'section_name' => null,
            'class_teacher' => null,
            'today' => [],
            'days' => $this->emptyWeek(),
            'subjects' => [],
            'notices' => $this->notices($schoolId),
            'exams' => [],
        ];

        if (!$enrollment) {
            return $empty;
        }

        $classId = (int) $enrollment->class_id;
        $section = DB::table('sections')->where('id', $enrollment->section_id)->first();
        $sectionId = (int) $enrollment->section_id;
        if ($section && (int) $section->class_id !== $classId) {
            $match = DB::table('sections')
                ->where('class_id', $classId)
                ->where('name', $section->name)
                ->orderBy('id')
                ->value('id');
            if ($match) {
                $sectionId = (int) $match;
            }
        }

        $className = DB::table('classes')->where('id', $classId)->value('name');
        $sectionName = DB::table('sections')->where('id', $sectionId)->value('name');

        $rows = DB::table('routines')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->orderBy('starting_hour')->orderBy('starting_minute')
            ->get();

        $subjects = DB::table('subjects')->whereIn('id', $rows->pluck('subject_id'))->pluck('name', 'id');
        $teachers = DB::table('users')->whereIn('id', $rows->pluck('teacher_id'))->pluck('name', 'id');
        $rooms = Schema::hasTable('class_rooms')
            ? DB::table('class_rooms')->whereIn('id', $rows->pluck('room_id'))->pluck('name', 'id')
            : collect();

        $periods = $rows->map(fn ($r) => [
            'id' => (int) $r->id,
            'day' => strtolower((string) $r->day),
            'subject' => $subjects[$r->subject_id] ?? 'Class',
            'subject_id' => (int) $r->subject_id,
            'teacher' => $teachers[$r->teacher_id] ?? '',
            'teacher_id' => (int) $r->teacher_id,
            'room' => $rooms[$r->room_id] ?? '',
            'time' => $r->starting_hour.':'.$r->starting_minute.' - '.$r->ending_hour.':'.$r->ending_minute,
        ]);

        $byDay = $periods->groupBy('day');
        $todayName = strtolower(now()->format('l'));
        $classTeacherId = $periods->countBy('teacher_id')->sortDesc()->keys()->first();

        return [
            'class_name' => $className,
            'section_name' => $sectionName,
            'class_teacher' => $classTeacherId ? [
                'id' => (int) $classTeacherId,
                'name' => $teachers[$classTeacherId] ?? '',
            ] : null,
            'today' => ($byDay->get($todayName) ?? collect())->values(),
            'days' => collect(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
                ->map(fn ($day) => [
                    'day' => $day,
                    'label' => ucfirst($day),
                    'periods' => ($byDay->get($day) ?? collect())->values(),
                ])->values(),
            'subjects' => $periods->unique('subject_id')->values()->map(fn ($p) => [
                'id' => $p['subject_id'],
                'name' => $p['subject'],
                'teacher' => $p['teacher'],
                'room' => $p['room'],
                'time' => $p['time'],
            ])->values(),
            'notices' => $this->notices($schoolId),
            'exams' => $this->exams($classId, $schoolId, $sessionId),
        ];
    }

    private function emptyWeek(): array
    {
        return collect(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
            ->map(fn ($day) => ['day' => $day, 'label' => ucfirst($day), 'periods' => []])
            ->values()
            ->all();
    }

    private function notices($schoolId): array
    {
        if (!Schema::hasTable('noticeboard')) {
            return [];
        }

        return DB::table('noticeboard')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn ($n) => [
                'id' => (int) $n->id,
                'title' => $n->notice_title,
                'body' => trim(strip_tags((string) $n->notice)),
                'start_date' => $n->start_date,
            ])->all();
    }

    private function exams(int $classId, $schoolId, ?int $sessionId): array
    {
        if (!Schema::hasTable('exams')) {
            return [];
        }

        $rows = DB::table('exams')
            ->where('class_id', $classId)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->orderBy('id')
            ->limit(8)
            ->get();

        $subjects = DB::table('subjects')->whereIn('id', $rows->pluck('subject_id'))->pluck('name', 'id');

        return $rows->map(fn ($e) => [
            'id' => (int) $e->id,
            'name' => $e->name,
            'subject' => $subjects[$e->subject_id] ?? '',
            'type' => $e->exam_type,
            'time' => trim($e->starting_time.' - '.$e->ending_time),
            'room' => $e->room_number ?? '',
        ])->all();
    }

    private function runningSession($schoolId): ?int
    {
        if (!$schoolId || !Schema::hasColumn('schools', 'running_session')) {
            return null;
        }

        $id = DB::table('schools')->where('id', $schoolId)->value('running_session');

        return $id ? (int) $id : null;
    }
}
