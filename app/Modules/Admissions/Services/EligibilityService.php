<?php

namespace App\Modules\Admissions\Services;

use App\Modules\Admissions\Models\Application;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Checks an application against its program's min_eligibility rules.
 * Supported keys: min_age, max_age (age on the programme's opening date),
 * min_ssc_gpa (from previous_gpa), required_subjects (from academic_history.subjects),
 * previous_pass (must be declared as passed in academic_history.previous_pass).
 */
class EligibilityService
{
    /** @return array<string,string> field => message; empty when eligible */
    public function problems(Application $application): array
    {
        $rules = $application->program?->min_eligibility ?? [];
        $problems = [];

        if (isset($rules['min_age']) || isset($rules['max_age'])) {
            // Whole years on the day of submission.
            $age = (int) Carbon::parse($application->dob)->diffInYears(now());
            if (isset($rules['min_age']) && $age < $rules['min_age']) {
                $problems['dob'] = "Candidate must be at least {$rules['min_age']} years old for this programme.";
            }
            if (isset($rules['max_age']) && $age > $rules['max_age']) {
                $problems['dob'] = "Candidate must be at most {$rules['max_age']} years old for this programme.";
            }
        }

        if (isset($rules['min_ssc_gpa'])) {
            $gpa = (float) ($application->previous_gpa ?? 0);
            if ($gpa < (float) $rules['min_ssc_gpa']) {
                $problems['previous_gpa'] = "A minimum GPA of {$rules['min_ssc_gpa']} is required for this programme.";
            }
        }

        if (!empty($rules['required_subjects'])) {
            $declared = $application->academic_history['subjects'] ?? [];
            $missing = array_values(array_diff($rules['required_subjects'], (array) $declared));
            if ($missing) {
                $problems['academic_history'] = 'Required subjects not declared: ' . implode(', ', $missing) . '.';
            }
        }

        if (!empty($rules['previous_pass'])) {
            if (empty($application->academic_history['previous_pass'])) {
                $problems['academic_history'] = "Required previous pass: {$rules['previous_pass']}.";
            }
        }

        return $problems;
    }

    public function assertEligible(Application $application): void
    {
        $problems = $this->problems($application);
        if ($problems) {
            throw ValidationException::withMessages($problems);
        }
    }
}
