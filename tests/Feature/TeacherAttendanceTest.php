<?php

namespace Tests\Feature;

use App\Models\DailyAttendances;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherAttendanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_teacher_saves_manual_attendance_with_a_reason_for_late_and_absent(): void
    {
        $teacher = User::create([
            'name' => 'Attendance Teacher',
            'email' => 'attendance.teacher@example.test',
            'role_id' => 3,
            'school_id' => 999,
            'status' => 1,
            'password' => null,
        ]);
        $student = User::create([
            'name' => 'Late Student',
            'email' => 'late.student@example.test',
            'role_id' => 7,
            'school_id' => 999,
            'status' => 1,
            'password' => null,
            'student_id' => 'MCC9001',
        ]);
        $now = now();
        $classId = DB::table('classes')->insertGetId(['name' => 'Class 7', 'school_id' => 999, 'created_at' => $now, 'updated_at' => $now]);
        $sectionId = DB::table('sections')->insertGetId(['name' => 'A', 'class_id' => $classId, 'created_at' => $now, 'updated_at' => $now]);
        $subjectId = DB::table('subjects')->insertGetId(['name' => 'Mathematics', 'class_id' => $classId, 'school_id' => 999, 'session_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('routines')->insert([
            'class_id' => $classId,
            'section_id' => $sectionId,
            'subject_id' => $subjectId,
            'starting_hour' => 10,
            'starting_minute' => 0,
            'ending_hour' => 10,
            'ending_minute' => 40,
            'day' => 'monday',
            'teacher_id' => $teacher->id,
            'room_id' => 1,
            'session_id' => 1,
            'school_id' => 999,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('enrollments')->insert([
            'user_id' => $student->id,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'school_id' => 999,
            'department_id' => 0,
            'session_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $token = $teacher->createToken('t')->plainTextToken;
        $date = '2026-11-17';

        $this->withToken($token)->postJson('/api/teacher/attendance/submit', [
            'class_id' => $classId,
            'section_id' => $sectionId,
            'date' => $date,
            'records' => [['student_id' => $student->id, 'status' => 'late', 'note' => '']],
        ])->assertStatus(422);

        $this->withToken($token)->postJson('/api/teacher/attendance/submit', [
            'class_id' => $classId,
            'section_id' => $sectionId,
            'date' => $date,
            'records' => [['student_id' => $student->id, 'status' => 'late', 'note' => 'Bus delay']],
        ])->assertOk()->assertJsonPath('data.students.0.status', 'late')->assertJsonPath('data.students.0.note', 'Bus delay');

        $row = DailyAttendances::where('student_id', $student->id)->first();
        $this->assertSame(2, (int) $row->status);
        $this->assertSame('Bus delay', $row->note);
        $this->assertSame('manual', $row->source);
        $this->assertSame(
            Carbon::parse($date, 'Asia/Dhaka')->startOfDay()->timestamp,
            (int) $row->timestamp
        );

        $this->withToken($token)
            ->getJson("/api/teacher/attendance?class_id={$classId}&section_id={$sectionId}&date={$date}")
            ->assertOk()
            ->assertJsonPath('data.students.0.status', 'late')
            ->assertJsonPath('data.students.0.note', 'Bus delay');
    }
}
