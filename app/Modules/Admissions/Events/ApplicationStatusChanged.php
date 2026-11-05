<?php

namespace App\Modules\Admissions\Events;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Models\Application;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Application $application,
        public ApplicationStatus $fromStatus,
        public ApplicationStatus $toStatus,
        public ?User $changedBy = null,
        public ?string $remarks = null
    ) {}
}

