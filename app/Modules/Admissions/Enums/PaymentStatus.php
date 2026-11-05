<?php

namespace App\Modules\Admissions\Enums;

enum PaymentStatus: string
{
    case INITIATED = 'INITIATED';
    case PENDING = 'PENDING';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';
    case REFUNDED = 'REFUNDED';
}

