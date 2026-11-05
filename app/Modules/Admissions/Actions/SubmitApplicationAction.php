<?php

namespace App\Modules\Admissions\Actions;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Events\ApplicationSubmitted;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Services\ApplicationStateMachine;
use App\Modules\Admissions\Services\EligibilityService;
use App\Modules\Admissions\Services\RequiredDocuments;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitApplicationAction
{
    public function __construct(
        protected ApplicationStateMachine $stateMachine,
        protected EligibilityService $eligibility
    ) {}

    public function execute(Application $application, ?User $actor = null): Application
    {
        $application->refresh();

        if ($application->application_status !== ApplicationStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'This application has already been submitted.',
            ]);
        }

        $program = $application->program;

        $errors = [];
        if (!$program->isOpen()) {
            $errors['program_id'] = 'The application deadline for this programme has passed.';
        }
        foreach (['applicant_name' => 'Candidate name', 'mobile' => 'Contact phone', 'dob' => 'Date of birth', 'gender' => 'Gender', 'current_address' => 'Current address'] as $column => $label) {
            if (empty($application->{$column})) {
                $errors[$column] = "{$label} is required.";
            }
        }
        if ($application->guardians()->count() === 0) {
            $errors['guardians'] = 'At least one guardian is required.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $eligibilityProblems = $this->eligibility->problems($application);
        if ($eligibilityProblems) {
            throw ValidationException::withMessages($eligibilityProblems);
        }

        $missing = RequiredDocuments::missingAtSubmission($application);
        if ($missing) {
            throw ValidationException::withMessages([
                'documents' => 'Upload these documents before submitting: ' . implode(', ', $missing) . '.',
            ]);
        }

        $target = (float) $program->application_fee > 0
            ? ApplicationStatus::PENDING_APP_FEE
            : ApplicationStatus::SUBMITTED;

        DB::transaction(function () use ($application, $target, $actor) {
            $application->forceFill(['submitted_at' => now()])->save();
            $this->stateMachine->transition($application, $target, $actor, 'Application submitted by applicant.');
        });

        event(new ApplicationSubmitted($application->fresh()));

        return $application->fresh();
    }
}
