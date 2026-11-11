<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\WebAccountSync;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebAppAccountSyncTest extends TestCase
{
    use DatabaseTransactions;

    private int $schoolId;
    private int $sessionId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schoolId = (int) DB::table('schools')->insertGetId(['title' => 'Sync Test School', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->sessionId = (int) DB::table('sessions')->insertGetId(['session_title' => '2026-2027', 'status' => 1, 'school_id' => $this->schoolId, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('schools')->where('id', $this->schoolId)->update(['running_session' => $this->sessionId]);
    }

    private function user(int $role, array $extra = []): User
    {
        return User::create(array_merge([
            'name' => 'Sync ' . uniqid(),
            'email' => uniqid('sync') . '@example.test',
            'role_id' => $role,
            'school_id' => $this->schoolId,
            'status' => 1,
            'password' => bcrypt('secret123'),
        ], $extra));
    }

    private function admit(User $student, string $className): void
    {
        Admission::create([
            'user_id' => $student->id,
            'application_no' => uniqid('APP'),
            'name' => $student->name,
            'class_name' => $className,
            'department' => 'General',
            'status' => 'pending',
            'mobile' => '01700000000',
            'email' => $student->email,
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'guardian_name' => 'Guardian',
            'relation_with_guardian' => 'Father',
            'guardian_mobile' => '01700000001',
            'present_address' => 'Savar, Dhaka',
        ]);
    }

    private function payAdmissionFee(User $student): void
    {
        $admission = Admission::where('user_id', $student->id)->latest('id')->first();
        PaymentTransaction::create([
            'payable_type' => Admission::class,
            'payable_id' => $admission->id,
            'user_id' => $student->id,
            'type' => 'admission_fee',
            'amount' => 3000,
            'admission_fee' => 3000,
            'status' => 'paid',
            'transaction_id' => uniqid('MCC_ENR_'),
            'paid_at' => now(),
        ]);
        app(WebAccountSync::class)->enrollAfterAdmissionFee($student->fresh());
    }

    public function test_app_student_is_enrolled_in_the_class_from_the_admission(): void
    {
        $student = $this->user(7);
        $this->admit($student, 'Sync Class 7');
        $this->assertDatabaseMissing('enrollments', ['user_id' => $student->id]);

        $this->payAdmissionFee($student);

        $classId = DB::table('classes')->where('school_id', $this->schoolId)->where('name', 'Sync Class 7')->value('id');
        $this->assertNotNull($classId, 'class is created from the admission');
        $this->assertDatabaseHas('sections', ['class_id' => $classId, 'name' => 'A']);
        $this->assertDatabaseHas('enrollments', ['user_id' => $student->id, 'class_id' => $classId, 'session_id' => $this->sessionId]);

        $other = $this->user(7);
        $this->admit($other, ' sync class 7 ');
        $this->assertDatabaseMissing('enrollments', ['user_id' => $other->id]);
        $this->payAdmissionFee($other);
        $this->assertDatabaseHas('enrollments', ['user_id' => $other->id, 'class_id' => $classId]);
        $this->assertSame(1, DB::table('classes')->where('school_id', $this->schoolId)->whereRaw('LOWER(name) = ?', ['sync class 7'])->count());
    }

    public function test_web_enrollment_is_never_overwritten_by_a_later_admission_change(): void
    {
        $student = $this->user(7);
        $this->admit($student, 'Sync Class 8');
        $this->payAdmissionFee($student);
        $webClass = DB::table('classes')->insertGetId(['name' => 'Sync Class 9', 'school_id' => $this->schoolId, 'created_at' => now(), 'updated_at' => now()]);
        $webSection = DB::table('sections')->insertGetId(['name' => 'B', 'class_id' => $webClass, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('enrollments')->where('user_id', $student->id)->update(['class_id' => $webClass, 'section_id' => $webSection]);

        Admission::where('user_id', $student->id)->first()->update(['class_name' => 'Sync Class 10']);

        $this->assertDatabaseHas('enrollments', ['user_id' => $student->id, 'class_id' => $webClass, 'section_id' => $webSection]);
        $this->assertSame(1, DB::table('enrollments')->where('user_id', $student->id)->count());
    }

    public function test_app_accounts_get_the_profile_fields_the_web_pages_read(): void
    {
        $teacher = $this->user(3, ['user_information' => null, 'phone_number' => '01700000000']);
        $guardian = $this->user(6, ['user_information' => json_encode(['nid' => '123', 'address' => 'Savar'])]);

        $teacherInfo = json_decode($teacher->fresh()->user_information, true);
        $this->assertSame('', $teacherInfo['photo']);
        $this->assertSame('01700000000', $teacherInfo['phone']);

        $guardianInfo = json_decode($guardian->fresh()->user_information, true);
        $this->assertSame('123', $guardianInfo['nid']);
        $this->assertSame('Savar', $guardianInfo['address']);
        $this->assertArrayHasKey('photo', $guardianInfo);
    }

    public function test_teacher_app_shows_only_what_the_web_admin_assigned(): void
    {
        $teacher = $this->user(3);
        $token = $teacher->createToken('t')->plainTextToken;

        $this->withToken($token)->getJson('/api/teacher/classes')->assertOk()->assertJsonCount(0, 'data');
        $this->withToken($token)->getJson('/api/teacher/students')->assertOk()->assertJsonCount(0, 'data');

        $mine = $this->user(7);
        $this->admit($mine, 'Sync Class 11');
        $this->payAdmissionFee($mine);
        $notMine = $this->user(7);
        $this->admit($notMine, 'Sync Class 12');
        $this->payAdmissionFee($notMine);

        $class = DB::table('classes')->where('school_id', $this->schoolId)->where('name', 'Sync Class 11')->first();
        $section = DB::table('sections')->where('class_id', $class->id)->first();
        $subject = DB::table('subjects')->insertGetId(['name' => 'Physics', 'class_id' => $class->id, 'school_id' => $this->schoolId, 'session_id' => $this->sessionId, 'created_at' => now(), 'updated_at' => now()]);

        DB::table('teacher_permissions')->insert(['class_id' => $class->id, 'section_id' => $section->id, 'school_id' => $this->schoolId, 'teacher_id' => $teacher->id, 'marks' => 0, 'attendance' => 1, 'updated_at' => time()]);
        DB::table('routines')->insert([
            'class_id' => $class->id, 'section_id' => $section->id, 'subject_id' => $subject, 'teacher_id' => $teacher->id,
            'starting_hour' => 9, 'starting_minute' => 0, 'ending_hour' => 10, 'ending_minute' => 0, 'day' => 'sunday',
            'room_id' => 0, 'session_id' => $this->sessionId, 'school_id' => $this->schoolId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherClass = DB::table('classes')->where('school_id', $this->schoolId)->where('name', 'Sync Class 12')->first();
        $otherSection = DB::table('sections')->where('class_id', $otherClass->id)->first();
        $extraSection = DB::table('sections')->insertGetId(['name' => 'B', 'class_id' => $class->id, 'created_at' => now(), 'updated_at' => now()]);
        $otherSubject = DB::table('subjects')->insertGetId(['name' => 'Bangla', 'class_id' => $class->id, 'school_id' => $this->schoolId, 'session_id' => $this->sessionId, 'created_at' => now(), 'updated_at' => now()]);
        $otherTeacher = $this->user(3);
        DB::table('routines')->insert([
            ['class_id' => $class->id, 'section_id' => $section->id, 'subject_id' => $otherSubject, 'teacher_id' => $otherTeacher->id,
                'starting_hour' => 11, 'starting_minute' => 0, 'ending_hour' => 12, 'ending_minute' => 0, 'day' => 'sunday',
                'room_id' => 0, 'session_id' => $this->sessionId, 'school_id' => $this->schoolId, 'created_at' => now(), 'updated_at' => now()],
            ['class_id' => $otherClass->id, 'section_id' => $otherSection->id, 'subject_id' => $otherSubject, 'teacher_id' => $otherTeacher->id,
                'starting_hour' => 8, 'starting_minute' => 0, 'ending_hour' => 9, 'ending_minute' => 0, 'day' => 'monday',
                'room_id' => 0, 'session_id' => $this->sessionId, 'school_id' => $this->schoolId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withToken($token)->getJson('/api/teacher/classes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Physics')
            ->assertJsonPath('data.0.section', "Sync Class 11 - {$section->name}")
            ->assertJsonPath('data.0.students', 1)
            ->assertJsonPath('data.0.can_take_attendance', true)
            ->assertJsonPath('data.0.can_enter_marks', false);

        $students = $this->withToken($token)->getJson('/api/teacher/students')->assertOk()->json('data');
        $this->assertSame([$mine->id], array_column($students, 'id'));

        $foreignSection = DB::table('sections')->insertGetId(['name' => $section->name, 'class_id' => $otherClass->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('enrollments')->where('user_id', $mine->id)->update(['section_id' => $foreignSection]);

        $routine = $this->withToken($token)->getJson('/api/teacher/routine')->assertOk();
        $routine->assertJsonPath('data.class_id', $class->id);
        $routine->assertJsonPath('data.section_id', $section->id);
        $this->assertSame([$class->id], array_column($routine->json('data.classes'), 'id'));
        $this->assertSame([$section->id], array_column($routine->json('data.sections'), 'id'));
        $this->assertSame([$mine->id], array_column($routine->json('data.students'), 'id'));
        $this->assertNotContains($extraSection, array_column($routine->json('data.sections'), 'id'));
        $sunday = collect($routine->json('data.days'))->firstWhere('day', 'sunday');
        $this->assertCount(1, $sunday['periods']);
        $this->assertSame('Physics', $sunday['periods'][0]['subject']);
        $this->assertSame('9:0 - 10:0', $sunday['periods'][0]['time']);
        $this->assertSame($teacher->name, $sunday['periods'][0]['teacher']);
        $this->assertTrue($sunday['periods'][0]['mine']);
        $this->assertSame(
            ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            array_column($routine->json('data.days'), 'day')
        );

        $portal = $this->withToken($mine->createToken('s')->plainTextToken)->getJson('/api/student/portal')->assertOk();
        $portal->assertJsonPath('data.class_name', 'Sync Class 11');
        $portal->assertJsonPath('data.class_teacher.name', $teacher->name);
        $studentSunday = collect($portal->json('data.days'))->firstWhere('day', 'sunday');
        $this->assertSame('Physics', $studentSunday['periods'][0]['subject']);
        $this->assertSame($teacher->name, $studentSunday['periods'][0]['teacher']);
    }

    public function test_unpaid_phone_student_stays_off_the_admin_student_list(): void
    {
        $admin = $this->user(2);
        DB::table('subscriptions')->insert([
            'package_id' => 1, 'school_id' => $this->schoolId, 'paid_amount' => 0,
            'payment_method' => 'offline', 'transaction_keys' => '[]', 'expire_date' => 0,
            'date_added' => time(), 'active' => 1, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $classId = (int) DB::table('classes')->insertGetId(['name' => 'Web Only Class', 'school_id' => $this->schoolId, 'created_at' => now(), 'updated_at' => now()]);
        $sectionId = (int) DB::table('sections')->insertGetId(['name' => 'A', 'class_id' => $classId, 'created_at' => now(), 'updated_at' => now()]);

        $unpaid = $this->user(7, ['name' => 'Unpaid Fee ' . uniqid()]);
        $this->admit($unpaid, 'Sync Class 7');
        DB::table('enrollments')->insert([
            'user_id' => $unpaid->id, 'class_id' => $classId, 'section_id' => $sectionId,
            'school_id' => $this->schoolId, 'department_id' => 0, 'session_id' => $this->sessionId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $paid = $this->user(7, ['name' => 'Paid Fee ' . uniqid()]);
        $this->admit($paid, 'Sync Class 7');
        $this->payAdmissionFee($paid);

        $web = $this->user(7, ['name' => 'Web Student ' . uniqid()]);
        DB::table('enrollments')->insert([
            'user_id' => $web->id, 'class_id' => $classId, 'section_id' => $sectionId,
            'school_id' => $this->schoolId, 'department_id' => 0, 'session_id' => $this->sessionId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/student')
            ->assertOk()
            ->assertSee($paid->name)
            ->assertSee($web->name)
            ->assertDontSee($unpaid->name);
    }
}
