<?php

namespace App\Modules\Admissions\Enums;

enum ApplicationStatus: string
{
    case DRAFT = 'DRAFT';
    case PENDING_APP_FEE = 'PENDING_APP_FEE';
    case SUBMITTED = 'SUBMITTED';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case EXAM_SCHEDULED = 'EXAM_SCHEDULED';
    case ASSESSED = 'ASSESSED';
    case APPROVED = 'APPROVED';
    case WAITLISTED = 'WAITLISTED';
    case REJECTED = 'REJECTED';
    case ADMISSION_FEE_PAID = 'ADMISSION_FEE_PAID';
    case ENROLLED = 'ENROLLED';
    case WITHDRAWN = 'WITHDRAWN';
    case EXPIRED = 'EXPIRED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APP_FEE => 'Pending Application Fee',
            self::SUBMITTED => 'Submitted',
            self::UNDER_REVIEW => 'Under Review',
            self::EXAM_SCHEDULED => 'Exam Scheduled',
            self::ASSESSED => 'Assessed',
            self::APPROVED => 'Approved',
            self::WAITLISTED => 'Waitlisted',
            self::REJECTED => 'Rejected',
            self::ADMISSION_FEE_PAID => 'Admission Fee Paid',
            self::ENROLLED => 'Enrolled',
            self::WITHDRAWN => 'Withdrawn',
            self::EXPIRED => 'Expired',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-gray-100 text-gray-800',
            self::PENDING_APP_FEE => 'bg-amber-100 text-amber-800',
            self::SUBMITTED => 'bg-blue-100 text-blue-800',
            self::UNDER_REVIEW => 'bg-indigo-100 text-indigo-800',
            self::EXAM_SCHEDULED => 'bg-purple-100 text-purple-800',
            self::ASSESSED => 'bg-cyan-100 text-cyan-800',
            self::APPROVED => 'bg-emerald-100 text-emerald-800',
            self::WAITLISTED => 'bg-orange-100 text-orange-800',
            self::REJECTED => 'bg-rose-100 text-rose-800',
            self::ADMISSION_FEE_PAID => 'bg-teal-100 text-teal-800',
            self::ENROLLED => 'bg-green-100 text-green-800',
            self::WITHDRAWN => 'bg-stone-100 text-stone-800',
            self::EXPIRED => 'bg-red-100 text-red-800',
        };
    }
}

