<?php

namespace App\Modules\Admissions\Actions;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Enums\AssessmentType;
use App\Modules\Admissions\Events\AssessmentScheduled;
use App\Modules\Admissions\Models\AdmissionAssessment;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Services\ApplicationStateMachine;
use App\Modules\Admissions\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleAssessmentAction
{
    public function __construct(
        protected ApplicationStateMachine $stateMachine,
        protected AuditService $audit
    ) {}

    public function execute(
        Application $application,
        AssessmentType $type,
        Carbon|string $scheduledAt,
        ?string $venue = null,
        ?User $scheduledBy = null
    ): AdmissionAssessment {
        if (!$application->program->requires_assessment) {
            throw ValidationException::withMessages([
                'status' => 'This programme does not use an assessment. Record the council decision directly.',
            ]);
        }

        return DB::transaction(function () use ($application, $type, $scheduledAt, $venue, $scheduledBy) {
            $assessment = AdmissionAssessment::create([
                'application_id' => $application->id,
                'assessment_type' => $type->value,
                'scheduled_at' => $scheduledAt,
                'venue' => $venue,
                'max_score' => 100.00,
            ]);

            $this->stateMachine->transition(
                $application,
                ApplicationStatus::EXAM_SCHEDULED,
                $scheduledBy,
                "Assessment scheduled: {$type->value} on " . Carbon::parse($scheduledAt)->toDateTimeString()
            );

            $this->audit->record('assessment_scheduled', $assessment->id, [], ['type' => $type->value], $scheduledBy);

            event(new AssessmentScheduled($application, $assessment));

            return $assessment;
        });
    }
}
