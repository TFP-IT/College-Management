<?php

namespace App\Modules\Admissions\Enums;

enum PaymentType: string
{
    case APPLICATION_FEE = 'APPLICATION_FEE';
    case ADMISSION_FEE = 'ADMISSION_FEE';

    public function label(): string
    {
        return match ($this) {
            self::APPLICATION_FEE => 'Application Fee',
            self::ADMISSION_FEE => 'Admission Fee',
        };
    }
}
