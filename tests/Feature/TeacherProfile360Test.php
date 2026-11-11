<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherProfile360Test extends TestCase
{
    use DatabaseTransactions;

    private function teacher(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Profile Teacher',
            'email' => 'profile.teacher@example.test',
            'role_id' => 3,
            'school_id' => 999,
            'status' => 1,
            'password' => null,
        ], $attributes));
    }

    public function test_teacher_endpoints_require_a_logged_in_teacher(): void
    {
        $this->getJson('/api/teacher/profile-360')->assertStatus(401);
        $this->getJson('/api/teacher/students')->assertStatus(401);

        $student = User::create(['name' => 'S', 'email' => 's360@example.test', 'role_id' => 7, 'status' => 1, 'password' => null]);
        $this->withToken($student->createToken('t')->plainTextToken)->getJson('/api/teacher/profile-360')->assertStatus(403);
    }

    public function test_empty_profile_returns_no_data_indicators_instead_of_a_score(): void
    {
        $teacher = $this->teacher();

        $response = $this->withToken($teacher->createToken('t')->plainTextToken)->getJson('/api/teacher/profile-360')->assertOk();

        $response->assertJsonPath('data.teacher.name', 'Profile Teacher')
            ->assertJsonPath('data.teacher.gender', null)
            ->assertJsonPath('data.teacher.photo_url', null)
            ->assertJsonPath('data.academic.summary.classes', 0)
            ->assertJsonCount(7, 'data.scorecard.indicators')
            ->assertJsonPath('data.scorecard.indicators.0.status', 'no_data')
            ->assertJsonMissingPath('data.scorecard.overall');
    }

    public function test_web_profile_edits_are_returned_to_the_app(): void
    {
        $departmentId = DB::table('departments')->insertGetId(['name' => 'Science', 'school_id' => 999, 'created_at' => now(), 'updated_at' => now()]);
        $teacher = $this->teacher([
            'name' => 'Iftikhar',
            'designation' => 'teacher',
            'department_id' => $departmentId,
            'user_information' => json_encode([
                'gender' => 'Male',
                'blood_group' => 'o+',
                'birthday' => strtotime('2011-11-11'),
                'phone' => '1788981814',
                'address' => '>>Hello world, Dhaka, BD',
                'photo' => 'iftikhar.png',
            ]),
        ]);

        $this->withToken($teacher->createToken('t')->plainTextToken)
            ->getJson('/api/teacher/profile-360')
            ->assertOk()
            ->assertJsonPath('data.teacher.name', 'Iftikhar')
            ->assertJsonPath('data.teacher.designation', 'teacher')
            ->assertJsonPath('data.teacher.department', 'Science')
            ->assertJsonPath('data.teacher.gender', 'Male')
            ->assertJsonPath('data.teacher.blood_group', 'O+')
            ->assertJsonPath('data.teacher.birthday', '2011-11-11')
            ->assertJsonPath('data.teacher.phone', '1788981814')
            ->assertJsonPath('data.teacher.address', 'Hello world, Dhaka, BD')
            ->assertJsonPath('data.teacher.photo_url', url('assets/uploads/user-images/iftikhar.png'));
    }

    public function test_profile_combines_academic_and_employee_records(): void
    {
        $teacher = $this->teacher();
        $student = User::create(['name' => 'Student A', 'email' => 'a360@example.test', 'role_id' => 7, 'status' => 1, 'password' => null]);
        $now = now();

        $classId = DB::table('classes')->insertGetId(['name' => 'Class 9', 'school_id' => 999, 'created_at' => $now, 'updated_at' => $now]);
        $sectionId = DB::table('sections')->insertGetId(['name' => 'A', 'class_id' => $classId, 'created_at' => $now, 'updated_at' => $now]);
        $subjectId = DB::table('subjects')->insertGetId(['name' => 'Physics', 'class_id' => $classId, 'school_id' => 999, 'session_id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        foreach (['monday', 'wednesday'] as $day) {
            DB::table('routines')->insert(['class_id' => $classId, 'section_id' => $sectionId, 'subject_id' => $subjectId, 'starting_hour' => 9, 'starting_minute' => 0, 'ending_hour' => 9, 'ending_minute' => 45, 'day' => $day, 'teacher_id' => $teacher->id, 'room_id' => 1, 'session_id' => 1, 'school_id' => 999, 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('enrollments')->insert(['user_id' => $student->id, 'class_id' => $classId, 'section_id' => $sectionId, 'school_id' => 999, 'department_id' => 0, 'session_id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        foreach ([1 => 50, 2 => 62] as $examCategory => $mark) {
            DB::table('gradebooks')->insert(['class_id' => $classId, 'section_id' => $sectionId, 'student_id' => $student->id, 'exam_category_id' => $examCategory, 'marks' => json_encode([$subjectId => $mark]), 'comment' => '', 'school_id' => 999, 'session_id' => 1, 'timestamp' => $now->timestamp, 'created_at' => $now, 'updated_at' => $now]);
        }

        DB::table('teacher_syllabus_topics')->insert([
            ['teacher_id' => $teacher->id, 'subject_id' => $subjectId, 'class_id' => $classId, 'section_id' => $sectionId, 'topic' => 'Motion', 'completed_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['teacher_id' => $teacher->id, 'subject_id' => $subjectId, 'class_id' => $classId, 'section_id' => $sectionId, 'topic' => 'Force', 'completed_at' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('teacher_assignments')->insert(['teacher_id' => $teacher->id, 'title' => 'Lab report', 'due_date' => $now->copy()->addDays(3), 'submitted_count' => 10, 'marked_count' => 4, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('staff_attendances')->insert([
            ['user_id' => $teacher->id, 'date' => $now->toDateString(), 'status' => 'present', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => $teacher->id, 'date' => $now->copy()->subDay()->toDateString(), 'status' => 'late', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('staff_leave_entitlements')->insert(['user_id' => $teacher->id, 'year' => $now->year, 'leave_type' => 'casual', 'days' => 10, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('staff_leaves')->insert(['user_id' => $teacher->id, 'leave_type' => 'casual', 'start_date' => $now->copy()->startOfYear()->addDays(10), 'end_date' => $now->copy()->startOfYear()->addDays(11), 'days' => 2, 'status' => 'approved', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('staff_payslips')->insert(['user_id' => $teacher->id, 'period' => $now->copy()->startOfMonth()->toDateString(), 'basic' => 30000, 'allowances' => 5000, 'deductions' => 2000, 'net' => 33000, 'status' => 'paid', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('staff_conduct_records')->insert(['user_id' => $teacher->id, 'type' => 'warning', 'title' => 'Late submission', 'issued_on' => $now->toDateString(), 'status' => 'open', 'created_at' => $now, 'updated_at' => $now]);

        $data = $this->withToken($teacher->createToken('t')->plainTextToken)->getJson('/api/teacher/profile-360')->assertOk()->json('data');

        $this->assertSame(1, $data['academic']['summary']['classes']);
        $this->assertSame(1, $data['academic']['summary']['students']);
        $this->assertSame(2, $data['academic']['summary']['weekly_periods']);
        $this->assertSame('Physics', $data['academic']['classes'][0]['subject_name']);
        $this->assertEquals(50, $data['academic']['syllabus']['percent']);
        $this->assertSame(6, $data['academic']['assignments']['marking']['pending']);
        $this->assertEquals(12, $data['academic']['performance']['improvement']['average_change']);
        $this->assertEquals(1.5, $data['employee']['workload']['weekly_hours']);
        $this->assertEquals(8, $data['employee']['leave']['balances'][0]['remaining']);
        $this->assertEquals(33000, $data['employee']['payslips']['latest']['net']);
        $this->assertSame(1, $data['employee']['conduct']['open_warnings']);

        $indicators = collect($data['scorecard']['indicators'])->keyBy('key');
        $this->assertSame('+12.0 marks', $indicators['student_improvement']['value']);
        $this->assertSame('50% on time', $indicators['punctuality']['value']);
        $this->assertSame('watch', $indicators['professional_conduct']['status']);
        $this->assertSame('no_data', $indicators['student_feedback']['status']);
    }
}
