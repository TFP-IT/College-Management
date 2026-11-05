<?php

namespace Database\Seeders;

use App\Modules\Admissions\Enums\SectionType;
use App\Modules\Admissions\Models\AcademicClass;
use App\Modules\Admissions\Models\AcademicGroup;
use App\Modules\Admissions\Models\AcademicSection;
use App\Modules\Admissions\Models\AcademicYear;
use App\Modules\Admissions\Models\AdmissionProgram;
use Illuminate\Database\Seeder;

/**
 * Academic structure and admission programmes for 2026-27 (brief section 11).
 * Staff accounts and roles are created by AdmissionsRbacSeeder.
 */
class AdmissionSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::firstOrCreate(
            ['name' => '2026-27'],
            ['starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_current' => true]
        );

        // Classes: Playgroup, Nursery, KG, Class 1-10 (school); XI, XII (college)
        $classes = [
            ['code' => 'PG', 'name' => 'Playgroup', 'type' => SectionType::SCHOOL, 'order' => 1],
            ['code' => 'NURSERY', 'name' => 'Nursery', 'type' => SectionType::SCHOOL, 'order' => 2],
            ['code' => 'KG', 'name' => 'Kindergarten', 'type' => SectionType::SCHOOL, 'order' => 3],
        ];
        for ($n = 1; $n <= 10; $n++) {
            $classes[] = ['code' => (string) $n, 'name' => "Class {$n}", 'type' => SectionType::SCHOOL, 'order' => 3 + $n];
        }
        $classes[] = ['code' => 'XI', 'name' => 'Class XI (HSC)', 'type' => SectionType::COLLEGE, 'order' => 14];
        $classes[] = ['code' => 'XII', 'name' => 'Class XII (HSC)', 'type' => SectionType::COLLEGE, 'order' => 15];

        $byCode = [];
        foreach ($classes as $c) {
            $byCode[$c['code']] = AcademicClass::firstOrCreate(
                ['code' => $c['code']],
                ['name' => $c['name'], 'section_type' => $c['type']->value, 'sort_order' => $c['order']]
            );
        }

        // College groups for XI and XII
        $groups = [];
        foreach (['XI', 'XII'] as $classCode) {
            foreach (['SCIENCE' => 'Science', 'BUSINESS' => 'Business Studies', 'HUMANITIES' => 'Humanities'] as $code => $label) {
                $groups[$classCode][$code] = AcademicGroup::firstOrCreate(
                    ['class_id' => $byCode[$classCode]->id, 'code' => $code],
                    ['name' => "{$label} Group"]
                );
            }
        }

        // Sections: A and B for each school class; A and B for each college group
        foreach ($byCode as $code => $class) {
            if (in_array($code, ['XI', 'XII'], true)) {
                foreach ($groups[$code] as $gCode => $group) {
                    foreach (['A', 'B'] as $name) {
                        AcademicSection::firstOrCreate(
                            ['academic_year_id' => $year->id, 'class_id' => $class->id, 'group_id' => $group->id, 'name' => $name],
                            ['capacity' => 40]
                        );
                    }
                }
            } else {
                foreach (['A', 'B'] as $name) {
                    AcademicSection::firstOrCreate(
                        ['academic_year_id' => $year->id, 'class_id' => $class->id, 'group_id' => null, 'name' => $name],
                        ['capacity' => 40]
                    );
                }
            }
        }

        // Programmes. Assessment is skipped for the youngest classes (interaction is recorded instead).
        $programs = [
            ['PG-2026', 'Playgroup Admission 2026–27', 'PG', null, 500, 12000, 80, ['min_age' => 3, 'max_age' => 4], false],
            ['NUR-2026', 'Nursery Admission 2026–27', 'NURSERY', null, 500, 12000, 80, ['min_age' => 4, 'max_age' => 5], false],
            ['KG-2026', 'Kindergarten Admission 2026–27', 'KG', null, 500, 12000, 80, ['min_age' => 5, 'max_age' => 6], false],
        ];
        for ($n = 1; $n <= 10; $n++) {
            $programs[] = ["C{$n}-2026", "Class {$n} Admission 2026–27", (string) $n, null, 600, 14000 + ($n * 500), 80, ['min_age' => 5 + $n, 'max_age' => 6 + $n], true];
        }
        $programs[] = ['XI-SCI-2026', 'Class XI Science Admission 2026–27', 'XI', 'SCIENCE', 1000, 25000, 120, ['min_ssc_gpa' => 4.00], true];
        $programs[] = ['XI-BUS-2026', 'Class XI Business Studies Admission 2026–27', 'XI', 'BUSINESS', 1000, 22000, 120, ['min_ssc_gpa' => 3.50], true];
        $programs[] = ['XI-HUM-2026', 'Class XI Humanities Admission 2026–27', 'XI', 'HUMANITIES', 1000, 20000, 120, ['min_ssc_gpa' => 3.00], true];

        foreach ($programs as [$code, $title, $classCode, $groupCode, $appFee, $admFee, $seats, $eligibility, $assessment]) {
            AdmissionProgram::firstOrCreate(
                ['code' => $code],
                [
                    'title' => $title,
                    'section_type' => $byCode[$classCode]->sectionTypeValue() ?? SectionType::SCHOOL->value,
                    'class_id' => $byCode[$classCode]->id,
                    'group_id' => $groupCode ? $groups[$classCode][$groupCode]->id : null,
                    'academic_year_id' => $year->id,
                    'min_eligibility' => $eligibility,
                    'fee_application' => $appFee,
                    'fee_admission' => $admFee,
                    'seats' => $seats,
                    'application_opens_at' => now()->subDays(5),
                    'application_deadline' => now()->addDays(60),
                    'requires_assessment' => $assessment,
                    'is_active' => true,
                ]
            );
        }
    }
}
