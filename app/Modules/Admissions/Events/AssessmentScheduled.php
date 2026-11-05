<?php

namespace App\Modules\Admissions\Events;

use App\Modules\Admissions\Models\AdmissionAssessment;
use App\Modules\Admissions\Models\Application;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssessmentScheduled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Application $application,
        public AdmissionAssessment $assessment
    ) {}
}

