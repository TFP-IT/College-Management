<?php

namespace App\Modules\Admissions\Enums;

enum DocStatus: string
{
    case PENDING_UPLOAD = 'PENDING_UPLOAD';
    case UPLOADED = 'UPLOADED';
    case VERIFIED = 'VERIFIED';
    case REJECTED = 'REJECTED';
}

