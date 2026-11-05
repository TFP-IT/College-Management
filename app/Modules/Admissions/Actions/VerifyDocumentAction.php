<?php

namespace App\Modules\Admissions\Actions;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Enums\DocStatus;
use App\Modules\Admissions\Models\ApplicationDocument;
use App\Modules\Admissions\Services\ApplicationStateMachine;
use App\Modules\Admissions\Services\AuditService;
use App\Modules\Admissions\Services\RequiredDocuments;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifyDocumentAction
{
    public function __construct(
        protected ApplicationStateMachine $stateMachine,
        protected AuditService $audit
    ) {}

    public function execute(
        ApplicationDocument $document,
        DocStatus $status,
        ?string $reviewNotes = null,
        ?User $reviewer = null
    ): ApplicationDocument {
        if (!in_array($status, [DocStatus::VERIFIED, DocStatus::REJECTED], true)) {
            throw ValidationException::withMessages(['status' => 'A document can only be verified or rejected.']);
        }
        if ($status === DocStatus::REJECTED && trim((string) $reviewNotes) === '') {
            throw ValidationException::withMessages(['review_notes' => 'A reason is required to reject a document.']);
        }

        return DB::transaction(function () use ($document, $status, $reviewNotes, $reviewer) {
            $old = $document->status;

            $document->forceFill([
                'status' => $status->value,
                'rejection_reason' => $status === DocStatus::REJECTED ? $reviewNotes : null,
                'verified_by' => $reviewer?->id,
                'verified_at' => now(),
            ])->save();

            $this->audit->record('document_' . strtolower($status->value), $document->id, ['status' => $old], ['status' => $status->value], $reviewer);

            $application = $document->application()->lockForUpdate()->first();

            if ($application->application_status === ApplicationStatus::SUBMITTED && RequiredDocuments::outstanding($application) === []) {
                $this->stateMachine->transition($application, ApplicationStatus::UNDER_REVIEW, $reviewer, 'All required documents verified.');
            }

            return $document->fresh();
        });
    }
}
