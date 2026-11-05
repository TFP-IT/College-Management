<?php

namespace App\Modules\Admissions\Actions;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Models\AdmissionAssessment;
use App\Modules\Admissions\Services\ApplicationStateMachine;
use App\Modules\Admissions\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordAssessmentScoreAction
{
    public function __construct(
        protected ApplicationStateMachine $stateMachine,
        protected AuditService $audit
    ) {}

    public function execute(
        AdmissionAssessment $assessment,
        float $obtainedMarks,
        ?string $comments = null,
        ?User $evaluator = null
    ): AdmissionAssessment {
        $max = (float) $assessment->max_score;

        if ($obtainedMarks < 0 || $obtainedMarks > $max) {
            throw ValidationException::withMessages(['obtained_marks' => "Score must be between 0 and {$max}."]);
        }

        return DB::transaction(function () use ($assessment, $obtainedMarks, $comments, $evaluator, $max) {
            $application = $assessment->application()->lockForUpdate()->first();

            if ($application->application_status !== ApplicationStatus::EXAM_SCHEDULED) {
                throw ValidationException::withMessages(['status' => 'Scores can only be recorded for a scheduled assessment.']);
            }

            $assessment->forceFill([
                'score' => $obtainedMarks,
                'remarks' => $comments,
                'evaluator_id' => $evaluator?->id,
            ])->save();

            $this->audit->record('assessment_scored', $assessment->id, [], ['score' => $obtainedMarks, 'max' => $max], $evaluator);

            $this->stateMachine->transition(
                $application,
                ApplicationStatus::ASSESSED,
                $evaluator,
                "Assessment score recorded: {$obtainedMarks}/{$max}"
            );

            return $assessment->fresh();
        });
    }
}
