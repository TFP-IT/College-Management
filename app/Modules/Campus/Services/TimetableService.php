<?php

namespace App\Modules\Campus\Services;

use Illuminate\Support\Facades\DB;

/** The weekly routine the office builds on the web, in a shape both apps can draw as a day timeline. */
class TimetableService
{
    public const DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    /** @param array{class_id?:int, section_id?:int, teacher_id?:int, school_id?:int, session_id?:?int} $where */
    public function week(array $where): array
    {
        $rows = DB::table('routines')
            ->leftJoin('subjects', 'subjects.id', '=', 'routines.subject_id')
            ->leftJoin('users', 'users.id', '=', 'routines.teacher_id')
            ->leftJoin('class_rooms', 'class_rooms.id', '=', 'routines.room_id')
            ->leftJoin('classes', 'classes.id', '=', 'routines.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'routines.section_id')
            ->when($where['class_id'] ?? null, fn ($q, $v) => $q->where('routines.class_id', $v))
            ->when($where['section_id'] ?? null, fn ($q, $v) => $q->where('routines.section_id', $v))
            ->when($where['teacher_id'] ?? null, fn ($q, $v) => $q->where('routines.teacher_id', $v))
            ->when($where['school_id'] ?? null, fn ($q, $v) => $q->where('routines.school_id', $v))
            ->when($where['session_id'] ?? null, fn ($q, $v) => $q->where('routines.session_id', $v))
            ->orderBy('routines.starting_hour')
            ->orderBy('routines.starting_minute')
            ->get([
                'routines.id', 'routines.day', 'routines.class_id', 'routines.section_id', 'routines.subject_id',
                'routines.starting_hour', 'routines.starting_minute', 'routines.ending_hour', 'routines.ending_minute',
                'subjects.name as subject', 'users.name as teacher', 'class_rooms.name as room',
                'classes.name as class_name', 'sections.name as section_name',
            ]);

        $byDay = $rows->groupBy(fn ($r) => strtolower((string) $r->day));

        return collect(self::DAYS)->map(fn ($day) => [
            'day' => $day,
            'periods' => ($byDay->get($day) ?? collect())->map(fn ($r) => [
                'id' => (int) $r->id,
                'start' => sprintf('%02d:%02d', $r->starting_hour, $r->starting_minute),
                'end' => sprintf('%02d:%02d', $r->ending_hour, $r->ending_minute),
                'subject' => $r->subject ?: 'Class',
                'subject_id' => (int) $r->subject_id,
                'teacher' => $r->teacher,
                'room' => $r->room,
                'class_id' => (int) $r->class_id,
                'section_id' => (int) $r->section_id,
                'class_label' => trim($r->class_name . ($r->section_name ? " {$r->section_name}" : '')),
            ])->values()->all(),
        ])->all();
    }

    public static function todayKey(): string
    {
        return strtolower(now(AttendanceService::TIMEZONE)->format('l'));
    }
}
