<?php

namespace App\Modules\Admissions\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Bangladeshi mobile number, 11 digits starting 01. Stored and matched in this exact form. */
class BdMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !preg_match('/^01[3-9]\d{8}$/', $value)) {
            $fail('Enter an 11-digit mobile number starting with 01.');
        }
    }
}
