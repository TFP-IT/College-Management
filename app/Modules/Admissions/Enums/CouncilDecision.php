<?php

namespace App\Modules\Admissions\Enums;

enum CouncilDecision: string
{
    case APPROVED = 'APPROVED';
    case WAITLISTED = 'WAITLISTED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return ucfirst(strtolower($this->value));
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::APPROVED => 'bg-emerald-100 text-emerald-800',
            self::WAITLISTED => 'bg-orange-100 text-orange-800',
            self::REJECTED => 'bg-rose-100 text-rose-800',
        };
    }
}
