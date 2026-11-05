<?php

namespace Tests\Feature\Admissions;

use App\Modules\Admissions\Actions\RecordCouncilDecisionAction;
use App\Modules\Admissions\Enums\CouncilDecision;
use App\Modules\Admissions\Enums\PaymentType;
use App\Modules\Admissions\Models\ApplicationDocument;
use App\Modules\Admissions\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

class BusinessRulesTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_approval_is_forced_to_waitlist_when_the_programme_has_no_seats_left(): void
    {
        $program = $this->program('PG-2026');
        $program->update(['seats' => 1]);

        $first = $this->makeApplication(['status' => 'ASSESSED'], 'PG-2026', 4);
        app(RecordCouncilDecisionAction::class)->execute($first, CouncilDecision::APPROVED, 'Good', $this->staff('council@mcc.edu.bd'));
        $this->assertEquals('APPROVED', $first->fresh()->status);

        $second = $this->makeApplication(['status' => 'ASSESSED'], 'PG-2026', 4);
        app(RecordCouncilDecisionAction::class)->execute($second, CouncilDecision::APPROVED, 'Also good', $this->staff('council@mcc.edu.bd'));

        $second->refresh();
        $this->assertEquals('WAITLISTED', $second->status, 'Seat capacity forces a waitlist');
        $this->assertStringContainsString('Seats are full', $second->statusHistory()->first()->reason);
    }

    public function test_a_waitlisted_candidate_can_be_approved_later_when_a_seat_frees_up(): void
    {
        $program = $this->program('PG-2026');
        $program->update(['seats' => 1]);
        $council = $this->staff('council@mcc.edu.bd');

        $holder = $this->makeApplication(['status' => 'ASSESSED'], 'PG-2026', 4);
        app(RecordCouncilDecisionAction::class)->execute($holder, CouncilDecision::APPROVED, 'Good', $council);

        $waiting = $this->makeApplication(['status' => 'ASSESSED'], 'PG-2026', 4);
        app(RecordCouncilDecisionAction::class)->execute($waiting, CouncilDecision::WAITLISTED, 'Waiting for a seat', $council);

        // The holder withdraws, freeing the seat.
        $holder->update(['status' => 'WITHDRAWN']);

        app(RecordCouncilDecisionAction::class)->execute($waiting->fresh(), CouncilDecision::APPROVED, 'Seat now free', $council);

        $this->assertEquals('APPROVED', $waiting->fresh()->status);
    }

    public function test_rejected_candidate_cannot_pay_the_admission_fee(): void
    {
        $application = $this->makeApplication(['status' => 'ASSESSED']);
        app(RecordCouncilDecisionAction::class)->execute($application, CouncilDecision::REJECTED, 'Not selected', $this->staff('council@mcc.edu.bd'));

        $this->expectException(ValidationException::class);
        app(\App\Modules\Admissions\Actions\InitiatePaymentAction::class)->execute($application->fresh(), PaymentType::ADMISSION_FEE, 'mock');
    }

    public function test_rejected_candidate_cannot_be_enrolled(): void
    {
        $application = $this->makeApplication(['status' => 'ASSESSED']);
        app(RecordCouncilDecisionAction::class)->execute($application, CouncilDecision::REJECTED, 'Not selected', $this->staff('council@mcc.edu.bd'));

        $this->expectException(ValidationException::class);
        app(\App\Modules\Admissions\Actions\EnrollStudentAction::class)->execute(
            $application->fresh(), $this->sectionFor($application->program), $this->staff('officer@mcc.edu.bd')
        );
    }

    public function test_waitlisting_and_rejecting_need_a_reason(): void
    {
        $application = $this->makeApplication(['status' => 'ASSESSED']);

        $this->expectException(ValidationException::class);
        app(RecordCouncilDecisionAction::class)->execute($application, CouncilDecision::WAITLISTED, '', $this->staff('council@mcc.edu.bd'));
    }

    public function test_a_document_must_be_a_real_pdf_jpeg_png_or_webp(): void
    {
        $application = $this->makeApplication(['status' => 'PENDING_APP_FEE']);

        // A text file renamed to .pdf is refused: the content is checked, not the name.
        $fake = UploadedFile::fake()->createWithContent('birth.pdf', 'this is not a pdf');

        $this->expectException(\InvalidArgumentException::class);
        app(DocumentService::class)->store($application, $fake, \App\Modules\Admissions\Enums\DocType::BIRTH_CERT);
    }

    public function test_a_photo_over_one_megabyte_is_refused(): void
    {
        $application = $this->makeApplication(['status' => 'PENDING_APP_FEE']);
        $big = UploadedFile::fake()->image('big.png')->size(1500);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('1 MB');
        app(DocumentService::class)->store($application, $big, \App\Modules\Admissions\Enums\DocType::PHOTO);
    }

    public function test_replacing_a_document_removes_the_old_file(): void
    {
        $application = $this->makeApplication(['status' => 'PENDING_APP_FEE']);
        $service = app(DocumentService::class);

        $first = $service->store($application, UploadedFile::fake()->image('a.png'), \App\Modules\Admissions\Enums\DocType::PHOTO);
        $oldPath = $first->object_key;
        Storage::disk('local')->assertExists($oldPath);

        $service->store($application, UploadedFile::fake()->image('b.png'), \App\Modules\Admissions\Enums\DocType::PHOTO);

        Storage::disk('local')->assertMissing($oldPath);
        $this->assertEquals(1, ApplicationDocument::where('application_id', $application->id)->count());
    }

    public function test_document_links_only_work_while_signed(): void
    {
        $application = $this->makeApplication(['status' => 'PENDING_APP_FEE']);
        $doc = app(DocumentService::class)->store($application, UploadedFile::fake()->image('p.png'), \App\Modules\Admissions\Enums\DocType::PHOTO);

        $signed = app(DocumentService::class)->getSecureUrl($doc, 10);
        $this->get($signed)->assertOk();

        $unsigned = route('admissions.documents.download', ['document' => $doc->id]);
        $this->get($unsigned)->assertStatus(403);
    }

    public function test_rejecting_a_document_needs_a_reason_and_is_audited(): void
    {
        $application = $this->makeApplication(['status' => 'SUBMITTED']);
        $doc = app(DocumentService::class)->store($application, UploadedFile::fake()->image('p.png'), \App\Modules\Admissions\Enums\DocType::PHOTO);
        $officer = $this->staff('officer@mcc.edu.bd');

        try {
            app(\App\Modules\Admissions\Actions\VerifyDocumentAction::class)->execute($doc, \App\Modules\Admissions\Enums\DocStatus::REJECTED, '', $officer);
            $this->fail('A rejection without a reason must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('review_notes', $e->errors());
        }

        app(\App\Modules\Admissions\Actions\VerifyDocumentAction::class)->execute($doc, \App\Modules\Admissions\Enums\DocStatus::REJECTED, 'Blurry scan', $officer);
        $this->assertTrue(\App\Modules\Admissions\Models\AuditLog::where('record_id', $doc->id)->where('action', 'document_rejected')->exists());
    }
}
