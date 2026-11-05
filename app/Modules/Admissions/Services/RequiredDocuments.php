<?php

namespace App\Modules\Admissions\Services;

use App\Modules\Admissions\Enums\DocType;
use App\Modules\Admissions\Enums\SectionType;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Models\Application;

/** Documents an application must have before it can be submitted or verified. */
class RequiredDocuments
{
    /** @return DocType[] */
    public static function for(AdmissionProgram $program): array
    {
        $school = $program->section_type === SectionType::SCHOOL;

        return $school
            ? [DocType::PHOTO, DocType::BIRTH_CERT]
            : [DocType::PHOTO, DocType::PREVIOUS_TRANSCRIPT];
    }

    /**
     * Required types with no usable upload. Used at submission: a document must be present (not rejected).
     * @return string[]
     */
    public static function missingAtSubmission(Application $application): array
    {
        // toBase() reads the raw column; the model's doc_type accessor returns an enum object.
        $present = $application->documents()
            ->where('status', '!=', 'REJECTED')
            ->toBase()
            ->pluck('doc_type')
            ->all();

        return array_values(array_filter(
            array_map(fn (DocType $t) => $t->value, self::for($application->program)),
            fn (string $type) => !in_array($type, $present, true)
        ));
    }

    /** @return string[] doc types still missing or not verified. Used to move to review after verification. */
    public static function outstanding(Application $application): array
    {
        $verified = $application->documents()
            ->where('status', 'VERIFIED')
            ->toBase()
            ->pluck('doc_type')
            ->all();

        return array_values(array_filter(
            array_map(fn (DocType $t) => $t->value, self::for($application->program)),
            fn (string $type) => !in_array($type, $verified, true)
        ));
    }
}
