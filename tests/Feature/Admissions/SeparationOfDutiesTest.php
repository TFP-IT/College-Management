<?php

namespace Tests\Feature\Admissions;

use App\Modules\Admissions\Actions\EnrollStudentAction;
use App\Modules\Admissions\Actions\RecordCouncilDecisionAction;
use App\Modules\Admissions\Enums\CouncilDecision;
use App\Modules\Admissions\Enums\PaymentType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

class SeparationOfDutiesTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    private function readyForEnrolment(): \App\Modules\Admissions\Models\Application
    {
        $application = $this->makeApplication(['status' => 'UNDER_REVIEW']);
        app(RecordCouncilDecisionAction::class)->execute($application, CouncilDecision::APPROVED, 'Good', $this->staff('council@mcc.edu.bd'));
        $this->payFee($application->fresh(), PaymentType::ADMISSION_FEE);

        return $application->fresh();
    }

    public function test_council_member_who_decided_cannot_enrol_the_same_candidate(): void
    {
        $application = $this->readyForEnrolment();

        try {
            app(EnrollStudentAction::class)->execute($application, $this->sectionFor($application->program), $this->staff('council@mcc.edu.bd'));
            $this->fail('Expected separation of duties to block enrolment');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('enroller', $e->errors());
            $this->assertStringContainsString('Separation of duties', $e->errors()['enroller'][0]);
        }

        $this->assertEquals('ADMISSION_FEE_PAID', $application->fresh()->status);
    }

    public function test_a_different_staff_member_can_enrol(): void
    {
        $application = $this->readyForEnrolment();

        $profile = app(EnrollStudentAction::class)->execute($application, $this->sectionFor($application->program), $this->staff('officer@mcc.edu.bd'), '101');

        $this->assertNotEmpty($profile->student_number);
        $this->assertEquals('ENROLLED', $application->fresh()->status);
    }
}
