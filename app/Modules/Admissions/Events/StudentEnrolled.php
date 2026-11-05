<?php

namespace App\Modules\Admissions\Events;

use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Models\StudentEnrollment;
use App\Modules\Admissions\Models\StudentProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentEnrolled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public StudentProfile $studentProfile,
        public StudentEnrollment $studentEnrollment,
        public Application $application
    ) {}
}

