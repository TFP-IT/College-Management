<?php

namespace App\Modules\Admissions\Actions;

use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Services\ApplicationStateMachine;

/** Moves applications past their programme deadline to EXPIRED (run daily by the scheduler). */
class ExpireApplicationsAction
{
    public function __construct(protected ApplicationStateMachine $stateMachine) {}

    public function execute(): int
    {
        $count = 0;

        Application::query()
            ->whereIn('status', ['DRAFT', 'PENDING_APP_FEE', 'APPROVED'])
            ->whereHas('program', fn ($q) => $q->where('application_deadline', '<', now()))
            ->chunkById(100, function ($applications) use (&$count) {
                foreach ($applications as $application) {
                    $this->stateMachine->transition($application, ApplicationStatus::EXPIRED, null, 'Programme deadline passed.');
                    $count++;
                }
            });

        return $count;
    }
}
