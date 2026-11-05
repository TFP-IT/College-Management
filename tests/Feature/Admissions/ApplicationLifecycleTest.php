<?php

namespace Tests\Feature\Admissions;

use App\Modules\Admissions\Actions\CreateDraftApplicationAction;
use App\Modules\Admissions\Actions\EnrollStudentAction;
use App\Modules\Admissions\Actions\RecordAssessmentScoreAction;
use App\Modules\Admissions\Actions\RecordCouncilDecisionAction;
use App\Modules\Admissions\Actions\ScheduleAssessmentAction;
use App\Modules\Admissions\Actions\SubmitApplicationAction;
use App\Modules\Admissions\Actions\UpdateDraftApplicationAction;
use App\Modules\Admissions\Actions\VerifyDocumentAction;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Enums\AssessmentType;
use App\Modules\Admissions\Enums\CouncilDecision;
use App\Modules\Admissions\Enums\DocStatus;
use App\Modules\Admissions\Enums\PaymentType;
use App\Modules\Admissions\Events\StudentEnrolled;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

class ApplicationLifecycleTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** Playgroup: no assessment step, so the council decides straight after review. */
    public function test_full_journey_without_assessment_to_enrolment(): void
    {
        Event::fake([StudentEnrolled::class]);

        $program = $this->program('PG-2026');
        $applicant = $this->applicantFor('01755500001');
        $council = $this->staff('council@mcc.edu.bd');
        $officer = $this->staff('officer@mcc.edu.bd');

        $application = app(CreateDraftApplicationAction::class)->execute($program, [
            'candidate_name' => 'Tariq Rahman', 'phone' => '01755500001', 'candidate_dob' => $this->dobForProgram($program, 4),
            'gender' => 'MALE', 'current_address' => 'Radio Colony, Savar',
        ], $applicant);

        $this->assertEquals(ApplicationStatus::DRAFT, $application->application_status);
        $this->assertNull($application->qr_token, 'No pass is issued before approval');

        app(UpdateDraftApplicationAction::class)->execute($application, [
            'guardians' => [['relation' => 'FATHER', 'full_name' => 'Karim Rahman', 'phone' => '01711223344', 'is_primary' => true]],
        ], $applicant);

        $documents = app(DocumentService::class);
        $photo = $documents->store($application, UploadedFile::fake()->image('photo.png'), \App\Modules\Admissions\Enums\DocType::PHOTO, $applicant);
        $birth = $documents->store($application, UploadedFile::fake()->image('birth.png'), \App\Modules\Admissions\Enums\DocType::BIRTH_CERT, $applicant);

        app(SubmitApplicationAction::class)->execute($application, $applicant);
        $this->assertEquals(ApplicationStatus::PENDING_APP_FEE, $application->fresh()->application_status);

        $this->payFee($application, PaymentType::APPLICATION_FEE);
        $this->assertEquals(ApplicationStatus::SUBMITTED, $application->fresh()->application_status);

        app(VerifyDocumentAction::class)->execute($photo, DocStatus::VERIFIED, 'Clear photo', $officer);
        $this->assertEquals(ApplicationStatus::SUBMITTED, $application->fresh()->application_status, 'Waits for every required document');
        app(VerifyDocumentAction::class)->execute($birth, DocStatus::VERIFIED, 'Seal visible', $officer);
        $this->assertEquals(ApplicationStatus::UNDER_REVIEW, $application->fresh()->application_status);

        app(RecordCouncilDecisionAction::class)->execute($application, CouncilDecision::APPROVED, 'Selected', $council);
        $application->refresh();
        $this->assertEquals(ApplicationStatus::APPROVED, $application->application_status);
        $this->assertTrue($application->qrIsValid(), 'Pass issued on approval');

        $this->payFee($application, PaymentType::ADMISSION_FEE);
        $this->assertEquals(ApplicationStatus::ADMISSION_FEE_PAID, $application->fresh()->application_status);

        $profile = app(EnrollStudentAction::class)->execute($application, $this->sectionFor($program), $officer, '1');
        $application->refresh();

        $this->assertEquals(ApplicationStatus::ENROLLED, $application->application_status);
        $this->assertStringStartsWith('MCC26S', $profile->student_number);
        $this->assertEquals($profile->id, $application->enrolled_student_id);
        $this->assertNotNull($application->qr_used_at);
        Event::assertDispatched(StudentEnrolled::class);
    }

    /** Class 6: assessment is required, so the path goes through scheduling and scoring. */
    public function test_assessed_programme_path_requires_schedule_and_score_before_decision(): void
    {
        $application = $this->makeApplication(['status' => 'UNDER_REVIEW'], 'C6-2026', 11);
        $officer = $this->staff('officer@mcc.edu.bd');
        $council = $this->staff('council@mcc.edu.bd');

        // Cannot decide before assessment.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        try {
            app(RecordCouncilDecisionAction::class)->execute($application, CouncilDecision::APPROVED, null, $council);
        } finally {
            $this->assertEquals(ApplicationStatus::UNDER_REVIEW, $application->fresh()->application_status);
        }
    }

    public function test_assessment_is_scheduled_scored_and_then_decided(): void
    {
        $application = $this->makeApplication(['status' => 'UNDER_REVIEW'], 'C6-2026', 11);
        $officer = $this->staff('officer@mcc.edu.bd');
        $council = $this->staff('council@mcc.edu.bd');

        $assessment = app(ScheduleAssessmentAction::class)->execute(
            $application, AssessmentType::WRITTEN, now()->addDays(3), 'Hall B', $officer
        );
        $this->assertEquals(ApplicationStatus::EXAM_SCHEDULED, $application->fresh()->application_status);

        app(RecordAssessmentScoreAction::class)->execute($assessment, 82.5, 'Good', $council);
        $this->assertEquals(ApplicationStatus::ASSESSED, $application->fresh()->application_status);

        app(RecordCouncilDecisionAction::class)->execute($application, CouncilDecision::APPROVED, 'Strong', $council);
        $this->assertEquals(ApplicationStatus::APPROVED, $application->fresh()->application_status);
    }
}
