<?php

namespace Tests\Feature\Security;

use App\Models\Gradebook;
use App\Models\MessageThrade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccessScopeTest extends SecurityTestCase
{
    private int $classId;
    private int $sectionId;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $now = ['created_at' => now(), 'updated_at' => now()];
        $this->classId = (int) DB::table('classes')->insertGetId(['name' => 'Class 7', 'school_id' => $this->schoolId] + $now);
        $this->sectionId = (int) DB::table('sections')->insertGetId(['name' => 'A', 'class_id' => $this->classId] + $now);
        $this->student = $this->user(7);
        DB::table('enrollments')->insert([
            'user_id' => $this->student->id, 'class_id' => $this->classId, 'section_id' => $this->sectionId,
            'school_id' => $this->schoolId, 'department_id' => 0, 'session_id' => 1,
        ] + $now);
    }

    private function permit(User $teacher): User
    {
        DB::table('teacher_permissions')->insert([
            'teacher_id' => $teacher->id, 'class_id' => $this->classId, 'section_id' => $this->sectionId,
            'school_id' => $this->schoolId, 'marks' => 1, 'attendance' => 1, 'updated_at' => time(),
        ]);

        return $teacher;
    }

    private function mark(array $override = []): array
    {
        return $override + [
            'exam_category_id' => 1, 'class_id' => $this->classId, 'section_id' => $this->sectionId,
            'student_id' => $this->student->id, 'subject_id' => 5, 'session_id' => 1, 'mark' => 80, 'comment' => 'Good',
        ];
    }

    private function marksCount(): int
    {
        return Gradebook::where('student_id', $this->student->id)->count();
    }

    public function test_only_permitted_staff_can_change_marks(): void
    {
        $otherSchoolAdmin = $this->user(2, ['school_id' => $this->schoolId + 1000]);

        foreach ([$this->student, $this->user(6), $this->user(3), $this->user(4)] as $user) {
            $this->actingAs($user)->post('/mark/update', $this->mark())->assertForbidden();
        }
        $this->actingAs($otherSchoolAdmin)->post('/mark/update', $this->mark())->assertNotFound();
        $this->assertSame(0, $this->marksCount());

        $this->actingAs($this->permit($this->user(3)))->post('/mark/update', $this->mark())->assertNoContent();
        $this->actingAs($this->user(2))->post('/mark/update', $this->mark(['mark' => 90]))->assertNoContent();

        $this->assertSame(1, $this->marksCount());
        $this->assertEquals(90, json_decode(Gradebook::where('student_id', $this->student->id)->value('marks'), true)[5]);
    }

    public function test_marks_cannot_be_changed_with_a_link(): void
    {
        $this->actingAs($this->user(2))->get('/mark/update?' . http_build_query($this->mark()))->assertStatus(405);

        $this->assertSame(0, $this->marksCount());
    }

    public function test_guardians_only_reach_their_own_children(): void
    {
        $parent = $this->user(6);

        $this->actingAs($parent)->get('/parent/marks/list?student_id=' . $this->student->id)->assertNotFound();
        $this->actingAs($parent)->get('/parent/child/subject/list?user_id=' . $this->student->id)->assertNotFound();
        $this->actingAs($parent)->get('/parent/routine/list?student_id=' . $this->student->id)->assertNotFound();
        $this->actingAs($parent)->get('/parent/student/id_card/' . $this->student->id)->assertNotFound();
    }

    public function test_only_the_two_participants_can_use_a_conversation(): void
    {
        $thread = MessageThrade::create(['sender_id' => $this->user(2)->id, 'reciver_id' => $this->user(3)->id, 'school_id' => $this->schoolId]);

        $this->actingAs($this->student)->get("/student/message/all-message/{$thread->id}")->assertNotFound();
        $this->actingAs($this->student)->post('/student/message/single-chat/save', [
            'message_thrade' => $thread->id, 'reciver_id' => 1, 'message' => 'hello',
        ])->assertNotFound();
        $this->assertSame(0, DB::table('chats')->where('message_thrade', $thread->id)->count());
    }

    public function test_people_lookups_are_for_staff(): void
    {
        $this->actingAs($this->student)->get("/students/{$this->classId}")->assertForbidden();
        $this->actingAs($this->student)->get("/user/{$this->student->id}")->assertForbidden();
        $this->actingAs($this->user(6))->get("/class/section/student/{$this->student->id}")->assertForbidden();
    }

    public function test_a_school_admin_cannot_reset_a_superadmin_or_another_school(): void
    {
        $admin = $this->user(2);
        $superadmin = $this->user(1);
        $outsider = $this->user(6, ['school_id' => $this->schoolId + 1000]);
        $guardian = $this->user(6);
        // The admin area redirects to the subscription page unless the school has an active one.
        DB::table('subscriptions')->insert([
            'package_id' => 1, 'school_id' => $this->schoolId, 'paid_amount' => 0, 'payment_method' => 'test',
            'transaction_keys' => '[]', 'expire_date' => time() + 86400 * 30, 'date_added' => time(), 'active' => 1, 'status' => 1,
        ]);

        foreach ([$superadmin, $outsider] as $target) {
            $this->actingAs($admin)->post('/admin/user_password', ['user_id' => $target->id, 'password' => 'changed-123'])->assertNotFound();
            $this->assertTrue(Hash::check('original-secret', $target->fresh()->password));
        }

        $this->actingAs($admin)->post('/admin/user_password', ['user_id' => $guardian->id, 'password' => 'changed-123'])->assertRedirect();
        $this->assertTrue(Hash::check('changed-123', $guardian->fresh()->password));
    }

    public function test_a_teacher_sees_analytics_only_for_assigned_students(): void
    {
        $url = "/api/teacher/student-analytics/{$this->student->id}";

        $this->withToken($this->user(3)->createToken('t')->plainTextToken)->getJson($url)->assertNotFound();
        $this->withToken($this->permit($this->user(3))->createToken('t')->plainTextToken)->getJson($url)->assertOk();
    }
}
