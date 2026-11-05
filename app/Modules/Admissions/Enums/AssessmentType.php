<?php

namespace App\Modules\Admissions\Enums;

enum AssessmentType: string
{
    case WRITTEN = 'WRITTEN';
    case VIVA = 'VIVA';
    case INTERVIEW = 'INTERVIEW';
}

