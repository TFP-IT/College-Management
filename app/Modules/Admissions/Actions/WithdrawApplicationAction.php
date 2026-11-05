<?php

namespace App\Modules\Admissions\Actions;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Services\ApplicationStateMachine;
use Illuminate\Validation\ValidationException;

class WithdrawApplicationAction
{
    public function __construct(protected ApplicationStateMachine $stateMachine) {}

    public function execute(Application $application, ?User $actor = null, ?string $reason = null): Application
    {
        $current = $application->application_status;

        if (!in_array($current->value, ['DRAFT', 'PENDING_APP_FEE', 'SUBMITTED', 'APPROVED'], true)) {
            throw ValidationException::withMessages([
                'status' => "An application that is {$current->label()} cannot be withdrawn.",
            ]);
        }

        $application->forceFill(['withdrawn_at' => now()])->save();

        return $this->stateMachine->transition($application, ApplicationStatus::WITHDRAWN, $actor, $reason ?? 'Withdrawn.');
    }
}
