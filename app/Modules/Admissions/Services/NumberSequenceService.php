<?php

namespace App\Modules\Admissions\Services;

use App\Modules\Admissions\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

class NumberSequenceService
{
    /**
     * Get next integer atomically for given key and year.
     */
    public function next(string $keyName, ?int $year = null): int
    {
        $year = $year ?? (int) date('Y');

        return DB::transaction(function () use ($keyName, $year) {
            $seq = NumberSequence::where('key_name', $keyName)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                $seq = NumberSequence::create([
                    'key_name' => $keyName,
                    'year' => $year,
                    'last_value' => 0,
                ]);

                $seq = NumberSequence::where('id', $seq->id)->lockForUpdate()->first();
            }

            $seq->last_value += 1;
            $seq->save();

            return (int) $seq->last_value;
        });
    }

    /**
     * Generate application number (e.g. APP-2026-000001).
     */
    public function generateApplicationNumber(): string
    {
        $year = (int) date('Y');
        $val = $this->next('APPLICATION', $year);
        $padded = str_pad((string) $val, 6, '0', STR_PAD_LEFT);

        return "APP-{$year}-{$padded}";
    }

    /**
     * Generate permanent Student ID (e.g. MCC26S000001 or MCC26C000001).
     */
    public function generateStudentId(string $sectionType, ?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $yy = substr((string) $year, -2);
        $typeCode = strtoupper($sectionType) === 'COLLEGE' ? 'C' : 'S';

        $val = $this->next("STUDENT_{$typeCode}", $year);
        $padded = str_pad((string) $val, 6, '0', STR_PAD_LEFT);

        return "MCC{$yy}{$typeCode}{$padded}";
    }
}
