<?php

namespace App\Modules\Admissions\Enums;

enum DocType: string
{
    case BIRTH_CERT = 'BIRTH_CERT';
    case PREVIOUS_TRANSCRIPT = 'PREVIOUS_TRANSCRIPT';
    case PHOTO = 'PHOTO';
    case NID = 'NID';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::BIRTH_CERT => 'Birth Certificate',
            self::PREVIOUS_TRANSCRIPT => 'Previous Academic Transcript / Report Card',
            self::PHOTO => 'Applicant Passport Size Photograph',
            self::NID => 'Parent / Guardian NID / Passport',
            self::OTHER => 'Supporting Document',
        };
    }
}

