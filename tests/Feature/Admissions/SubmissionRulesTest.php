<?php

namespace Tests\Feature\Admissions;

use App\Modules\Admissions\Actions\CreateDraftApplicationAction;
use App\Modules\Admissions\Actions\SubmitApplicationAction;
use App\Modules\Admissions\Actions\WithdrawApplicationAction;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Models\Application;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

class SubmissionRulesTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    public function test_cannot_submit_without_required_documents(): void
    {
        $application = $this->makeApplication();
        $this->addGuardian($application);

        try {
            app(SubmitApplicationAction::class)->execute($application);
            $this->fail('Submission without documents must fail');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('documents', $e->errors());
        }
        $this->assertEquals('DRAFT', $application->fresh()->status);
    }

    public function test_cannot_submit_without_a_guardian(): void
    {
        $application = $this->makeApplication();
        $this->addDocuments($application, ['PHOTO', 'BIRTH_CERT'], \App\Modules\Admissions\Enums\DocStatus::UPLOADED);

        try {
            app(SubmitApplicationAction::class)->execute($application);
            $this->fail('Submission without a guardian must fail');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('guardians', $e->errors());
        }
    }

    public function test_cannot_submit_after_the_deadline(): void
    {
        $application = $this->makeApplication();
        $this->addGuardian($application);
        $this->addDocuments($application, ['PHOTO', 'BIRTH_CERT'], \App\Modules\Admissions\Enums\DocStatus::UPLOADED);
        $application->program->update(['application_deadline' => now()->subDay()]);

        $this->expectException(ValidationException::class);
        app(SubmitApplicationAction::class)->execute($application->fresh());
    }

    public function test_eligibility_is_checked_on_submit(): void
    {
        // Playgroup accepts ages 3 to 4. A 9-year-old must be refused.
        $application = $this->makeApplication(['dob' => now()->subYears(9)->toDateString()], 'PG-2026', 9);
        $this->addGuardian($application);
        $this->addDocuments($application, ['PHOTO', 'BIRTH_CERT'], \App\Modules\Admissions\Enums\DocStatus::UPLOADED);

        try {
            app(SubmitApplicationAction::class)->execute($application);
            $this->fail('Ineligible candidate must not submit');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('dob', $e->errors());
        }
    }

    public function test_a_college_candidate_below_the_gpa_minimum_is_refused(): void
    {
        $application = $this->makeApplication(['previous_gpa' => 3.2], 'XI-SCI-2026', 16);
        $this->addGuardian($application);
        $this->addDocuments($application, ['PHOTO', 'PREVIOUS_TRANSCRIPT'], \App\Modules\Admissions\Enums\DocStatus::UPLOADED);

        try {
            app(SubmitApplicationAction::class)->execute($application);
            $this->fail('GPA below minimum must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('previous_gpa', $e->errors());
        }
    }

    public function test_duplicate_application_for_same_programme_mobile_and_dob_is_refused(): void
    {
        $program = $this->program('PG-2026');
        $draft = app(CreateDraftApplicationAction::class);
        $data = ['candidate_name' => 'Twin', 'phone' => '01744444444', 'candidate_dob' => $this->dobForProgram($program, 4), 'gender' => 'MALE'];

        $draft->execute($program, $data);

        $this->expectException(ValidationException::class);
        $draft->execute($program, $data);
    }

    public function test_applicant_can_withdraw_a_submitted_application(): void
    {
        $application = $this->makeApplication(['status' => 'SUBMITTED']);

        app(WithdrawApplicationAction::class)->execute($application, null, 'Changed school');

        $this->assertEquals('WITHDRAWN', $application->fresh()->status);
        $this->assertNotNull($application->fresh()->withdrawn_at);
    }

    public function test_enrolled_application_cannot_be_withdrawn(): void
    {
        $application = $this->makeApplication(['status' => 'ENROLLED']);

        $this->expectException(ValidationException::class);
        app(WithdrawApplicationAction::class)->execute($application);
    }

    public function test_expiry_command_moves_applications_past_their_deadline_to_expired(): void
    {
        $application = $this->makeApplication(['status' => 'PENDING_APP_FEE']);
        $application->program->update(['application_deadline' => now()->subDay()]);

        Artisan::call('admissions:expire-applications');

        $this->assertEquals('EXPIRED', $application->fresh()->status);
    }

    public function test_expiry_does_not_touch_applications_still_open(): void
    {
        $application = $this->makeApplication(['status' => 'DRAFT']);

        Artisan::call('admissions:expire-applications');

        $this->assertEquals('DRAFT', $application->fresh()->status);
    }
}
