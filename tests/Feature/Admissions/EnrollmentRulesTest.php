<?php

namespace Tests\Feature\Admissions;

use App\Modules\Admissions\Actions\EnrollStudentAction;
use App\Modules\Admissions\Actions\RecordCouncilDecisionAction;
use App\Modules\Admissions\Enums\CouncilDecision;
use App\Modules\Admissions\Enums\PaymentType;
use App\Modules\Admissions\Models\AcademicSection;
use App\Modules\Admissions\Models\StudentGuardian;
use App\Modules\Admissions\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

class EnrollmentRulesTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    /** Approved, admission fee paid, pass issued: ready to enrol. */
    private function ready(string $programCode = 'PG-2026', int $age = 4): \App\Modules\Admissions\Models\Application
    {
        $application = $this->makeApplication(['status' => 'UNDER_REVIEW', 'applicant_name' => 'Ready Child'], $programCode, $age);
        $this->addGuardian($application, '01722222222');
        app(RecordCouncilDecisionAction::class)->execute($application, CouncilDecision::APPROVED, 'Good', $this->staff('council@mcc.edu.bd'));
        $this->payFee($application->fresh(), PaymentType::ADMISSION_FEE);

        return $application->fresh();
    }

    private function enrol(\App\Modules\Admissions\Models\Application $application, AcademicSection $section, ?User $by = null): StudentProfile
    {
        return app(EnrollStudentAction::class)->execute($application, $section, $by ?? $this->staff('officer@mcc.edu.bd'), '7');
    }

    public function test_enrolment_is_blocked_without_a_paid_admission_fee(): void
    {
        $application = $this->makeApplication(['status' => 'UNDER_REVIEW']);
        app(RecordCouncilDecisionAction::class)->execute($application, CouncilDecision::APPROVED, 'Good', $this->staff('council@mcc.edu.bd'));

        $this->expectException(ValidationException::class);
        $this->enrol($application->fresh(), $this->sectionFor($application->program));
    }

    public function test_enrolment_is_blocked_when_the_section_is_full(): void
    {
        $application = $this->ready();
        $section = $this->sectionFor($application->program);
        $section->update(['capacity' => 0]);

        $this->expectException(ValidationException::class);
        $this->enrol($application, $section);
    }

    public function test_enrolment_is_blocked_for_a_section_of_another_class(): void
    {
        $application = $this->ready();
        $other = $this->sectionFor($this->program('XI-SCI-2026'));

        try {
            $this->enrol($application, $other);
            $this->fail('Section from another class must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('section_id', $e->errors());
        }
    }

    public function test_enrolment_is_blocked_when_the_pass_has_expired(): void
    {
        $application = $this->ready();
        $application->forceFill(['qr_expires_at' => now()->subMinute()])->save();

        $this->expectException(ValidationException::class);
        $this->enrol($application->fresh(), $this->sectionFor($application->program));
    }

    public function test_double_enrolment_returns_the_same_student_and_creates_nothing_new(): void
    {
        $application = $this->ready();
        $section = $this->sectionFor($application->program);
        $officer = $this->staff('officer@mcc.edu.bd');

        $first = $this->enrol($application, $section, $officer);
        $profiles = StudentProfile::count();
        $users = User::count();

        $second = $this->enrol($application->fresh(), $section, $officer);

        $this->assertTrue($first->is($second));
        $this->assertEquals($profiles, StudentProfile::count());
        $this->assertEquals($users, User::count());
    }

    public function test_student_account_gets_no_known_default_password(): void
    {
        $application = $this->ready();
        $profile = $this->enrol($application, $this->sectionFor($application->program));

        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('Student@123', $profile->user->password));
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('12345678', $profile->user->password));
    }

    public function test_siblings_with_the_same_guardian_share_one_guardian_account(): void
    {
        $section = $this->sectionFor($this->program('PG-2026'));
        $first = $this->ready('PG-2026', 4);
        $this->enrol($first, $section);

        $second = $this->makeApplication(['applicant_name' => 'Sibling', 'status' => 'UNDER_REVIEW'], 'PG-2026', 4);
        $this->addGuardian($second, '01722222222');
        app(RecordCouncilDecisionAction::class)->execute($second, CouncilDecision::APPROVED, 'Good', $this->staff('council@mcc.edu.bd'));
        $this->payFee($second->fresh(), PaymentType::ADMISSION_FEE);
        $this->enrol($second->fresh(), $section);

        $this->assertEquals(1, User::where('phone_number', '01722222222')->where('role_id', 6)->count());
    }

    public function test_a_child_whose_phone_matches_the_father_does_not_become_their_own_guardian(): void
    {
        $application = $this->makeApplication(['applicant_name' => 'Shared', 'mobile' => '01733333333', 'status' => 'UNDER_REVIEW'], 'PG-2026', 4);
        $this->addGuardian($application, '01733333333');
        app(RecordCouncilDecisionAction::class)->execute($application, CouncilDecision::APPROVED, 'Good', $this->staff('council@mcc.edu.bd'));
        $this->payFee($application->fresh(), PaymentType::ADMISSION_FEE);

        $profile = $this->enrol($application->fresh(), $this->sectionFor($application->program));

        $studentUser = $profile->user;
        $guardianLink = StudentGuardian::where('student_id', $profile->id)->first();
        $this->assertNotNull($guardianLink);
        $this->assertNotEquals($studentUser->id, $guardianLink->guardian->user_id, 'Student must not be linked to their own account as guardian');
        $this->assertEquals(6, $guardianLink->guardian->user->role_id);
    }

    public function test_enrolment_records_the_student_on_the_application_and_writes_an_audit_row(): void
    {
        $application = $this->ready();
        $profile = $this->enrol($application, $this->sectionFor($application->program));

        $this->assertEquals($profile->id, $application->fresh()->enrolled_student_id);
        $this->assertEquals($profile->student_number, $application->fresh()->admission_student_id);
        $this->assertTrue(\App\Modules\Admissions\Models\AuditLog::where('record_id', $application->id)->where('action', 'student_enrolled')->exists());
    }
}
