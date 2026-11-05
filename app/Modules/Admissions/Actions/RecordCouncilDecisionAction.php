<?php

namespace App\Modules\Admissions\Actions;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Enums\CouncilDecision;
use App\Modules\Admissions\Events\AdmissionDecisionRecorded;
use App\Modules\Admissions\Models\AdmissionDecision;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Services\ApplicationStateMachine;
use App\Modules\Admissions\Services\AuditService;
use App\Modules\Admissions\Services\QrTokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordCouncilDecisionAction
{
    public function __construct(
        protected ApplicationStateMachine $stateMachine,
        protected AuditService $audit,
        protected QrTokenService $qr
    ) {}

    public function execute(
        Application $application,
        CouncilDecision $decision,
        ?string $remarks = null,
        ?User $decidedBy = null
    ): AdmissionDecision {
        if (!$decidedBy) {
            throw ValidationException::withMessages(['auth' => 'An authenticated council member is required.']);
        }

        if (in_array($decision, [CouncilDecision::REJECTED, CouncilDecision::WAITLISTED], true) && trim((string) $remarks) === '') {
            throw ValidationException::withMessages(['remarks' => 'A reason is required to reject or waitlist a candidate.']);
        }

        return DB::transaction(function () use ($application, $decision, $remarks, $decidedBy) {
            $locked = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $program = $locked->program;

            // Programmes without an assessment go straight from review to the council.
            if ($locked->application_status === ApplicationStatus::UNDER_REVIEW && !$program->requires_assessment) {
                $this->stateMachine->transition($locked, ApplicationStatus::ASSESSED, $decidedBy, 'No assessment required for this programme.');
            }

            $current = $locked->application_status;
            $allowed = in_array($current, [ApplicationStatus::ASSESSED, ApplicationStatus::WAITLISTED], true);
            if (!$allowed) {
                throw ValidationException::withMessages([
                    'decision' => "A decision can only be recorded after assessment. Current status: {$current->label()}.",
                ]);
            }

            $target = match ($decision) {
                CouncilDecision::APPROVED => ApplicationStatus::APPROVED,
                CouncilDecision::WAITLISTED => ApplicationStatus::WAITLISTED,
                CouncilDecision::REJECTED => ApplicationStatus::REJECTED,
            };

            // Seat capacity: a full programme forces a waitlist, unless an override with a reason is recorded.
            $note = $remarks;
            if ($decision === CouncilDecision::APPROVED && $this->seatsFull($program, $locked)) {
                if ($this->canOverrideCapacity($decidedBy) && trim((string) $remarks) !== '') {
                    $note = 'Capacity override: ' . $remarks;
                } else {
                    $target = ApplicationStatus::WAITLISTED;
                    $decision = CouncilDecision::WAITLISTED;
                    $note = 'Seats are full; automatically waitlisted.' . ($remarks ? ' ' . $remarks : '');
                }
            }

            $record = AdmissionDecision::create([
                'application_id' => $locked->id,
                'decision' => $decision->value,
                'decided_by' => $decidedBy->id,
                'decided_at' => now(),
                'notes' => $note,
                'waitlist_rank' => $decision === CouncilDecision::WAITLISTED
                    ? AdmissionDecision::where('application_id', '!=', $locked->id)
                        ->where('decision', 'WAITLISTED')->whereHas('application', fn ($q) => $q->where('program_id', $locked->program_id))
                        ->count() + 1
                    : null,
            ]);

            if ($target !== $current) {
                $this->stateMachine->transition($locked, $target, $decidedBy, "Council decision: {$decision->value}. " . ($note ?? ''));
            }

            if ($target === ApplicationStatus::APPROVED) {
                $this->qr->generateToken($locked->fresh());
            }

            $this->audit->record('council_decision', $locked->id, [], ['decision' => $decision->value], $decidedBy);

            event(new AdmissionDecisionRecorded($locked, $record));

            return $record;
        });
    }

    private function seatsFull($program, Application $application): bool
    {
        if (!$program->seats) {
            return false;
        }

        $taken = Application::where('program_id', $program->id)
            ->whereIn('status', ['APPROVED', 'ADMISSION_FEE_PAID', 'ENROLLED'])
            ->where('id', '!=', $application->id)
            ->count();

        return $taken >= $program->seats;
    }

    private function canOverrideCapacity(User $user): bool
    {
        return $user->can('admission.override_capacity');
    }
}
