<?php

namespace Database\Seeders;

use App\Modules\Cms\Models\CmsNotice;
use App\Modules\Cms\Models\CmsStaffMember;
use Illuminate\Database\Seeder;

/**
 * Loads the content that used to be hard-coded in the public pages, so the site looks the same after the CMS change.
 * Safe to run again: existing rows with the same title or name are left alone.
 */
class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $notices = [
            ['title' => 'Routine of Test Examination - 2026: Class XII students', 'body' => 'The routine for the upcoming Test Examination for Class XII students has been published. All students are advised to download the routine and prepare accordingly.'],
            ['title' => 'HSC Admission 2026 is now open', 'body' => 'Admission for the HSC batch of 2026 is currently ongoing. Interested candidates are requested to apply through the online portal or visit the college campus.'],
        ];
        foreach ($notices as $n) {
            CmsNotice::firstOrCreate(['title' => $n['title']], $n + ['is_published' => true, 'publish_on' => now()->toDateString()]);
        }

        $governing = [
            ['name' => 'Dr. Kamrul Ahsan', 'designation' => 'Chairman & Founder', 'sort_order' => 1],
            ['name' => 'Dr. Md Saiful Kabir', 'designation' => 'Member', 'sort_order' => 2],
            ['name' => 'Mrs. Najnin Sultana Ruma', 'designation' => 'Member', 'sort_order' => 3],
            ['name' => 'Mrs. Dil Afroz', 'designation' => 'Donor Member', 'sort_order' => 4],
            ['name' => 'Md. Shoeb Khan', 'designation' => 'Member Secretary / Principal', 'sort_order' => 5],
        ];
        $teachers = [
            ['name' => 'Burhan Uddin', 'designation' => 'Assistant Professor in History and Culture', 'sort_order' => 1],
            ['name' => 'Abdur Razzak Khan', 'designation' => 'Assistant Professor in English Language and Learning', 'sort_order' => 2],
            ['name' => 'Souman Chakrabarti', 'designation' => 'Assistant Professor in Physics', 'sort_order' => 3],
            ['name' => 'Md Shahin Alan', 'designation' => 'Senior Lecturer in Accounting', 'sort_order' => 4],
            ['name' => 'Aslam Bishwas', 'designation' => 'Demonstrator, Biology', 'sort_order' => 5],
            ['name' => 'Md Kamrul Hyder', 'designation' => 'Head of Academy (College)', 'sort_order' => 6],
            ['name' => 'Uttam Kumar Paul', 'designation' => 'Head of School', 'sort_order' => 7],
            ['name' => 'Abdul Matin', 'designation' => 'Senior Lecturer in Bangla Language and Literature', 'sort_order' => 8],
            ['name' => 'Anjuman Ara Rupa', 'designation' => 'Lecturer in Bangla Language and Literature', 'sort_order' => 9],
            ['name' => 'Romesh Chakma', 'designation' => 'Lecturer in Economics', 'sort_order' => 10],
            ['name' => 'Abdullah Al Mamun', 'designation' => 'Lecturer in Agriculture', 'sort_order' => 11],
            ['name' => 'Nasir Uddin Howladar', 'designation' => 'Lecturer in Civics and Good Governance', 'sort_order' => 12],
            ['name' => 'Roushon Jamil', 'designation' => 'Lecturer in Mathematics', 'sort_order' => 13],
            ['name' => 'Aziz Muhammad', 'designation' => 'Lecturer in Business Organisation & Management', 'sort_order' => 14],
            ['name' => 'Gulam Rabbi', 'designation' => 'Lecturer in Physics', 'sort_order' => 15],
            ['name' => 'Riaj Al Hyder', 'designation' => 'Lecturer in Chemistry', 'sort_order' => 16],
            ['name' => 'Sonia Gumej', 'designation' => 'Lecturer in Biology', 'sort_order' => 17],
            ['name' => 'Md Abir Hassan', 'designation' => 'Lecturer in Mathematics', 'sort_order' => 18],
            ['name' => 'Md Selim Al Hasan', 'designation' => 'Lecturer in ICT', 'sort_order' => 19],
            ['name' => 'Md Masud Rana', 'designation' => 'Lecturer in ICT', 'sort_order' => 20],
            ['name' => 'Nishat Anjum', 'designation' => 'Lecturer in Civics and Good Governance', 'sort_order' => 21],
            ['name' => 'Saklain Mursalin', 'designation' => 'Lecturer in English', 'sort_order' => 22],
            ['name' => 'Rubel Zaman', 'designation' => 'Lecturer in Social Work', 'sort_order' => 23],
            ['name' => 'Fazle Hasan Rabbi', 'designation' => 'Lecturer in History and Culture', 'sort_order' => 24],
            ['name' => 'Ariful Hassan', 'designation' => 'Lecturer in Economics', 'sort_order' => 25],
            ['name' => 'Kulsum Akter', 'designation' => 'Assistant Teacher', 'sort_order' => 26],
            ['name' => 'Ajmeri Haque', 'designation' => 'Assistant Teacher', 'sort_order' => 27],
            ['name' => 'Nayab Ali', 'designation' => 'Assistant Teacher', 'sort_order' => 28],
            ['name' => 'Shafiur Rahman', 'designation' => 'Assistant Teacher', 'sort_order' => 29],
            ['name' => 'Sourmi Paul', 'designation' => 'Assistant Teacher', 'sort_order' => 30],
            ['name' => 'Najnin Ahmed', 'designation' => 'Assistant Teacher', 'sort_order' => 31],
            ['name' => 'Afroza Khanom', 'designation' => 'Assistant Teacher', 'sort_order' => 32],
            ['name' => 'Rahim Uddin', 'designation' => 'Assistant Teacher', 'sort_order' => 33],
            ['name' => 'Faruk Ahmed', 'designation' => 'Assistant Teacher', 'sort_order' => 34],
            ['name' => 'Suhrab Hussain', 'designation' => 'Assistant Teacher', 'sort_order' => 35],
            ['name' => 'Rafi Al Mujammel', 'designation' => 'Assistant Teacher', 'sort_order' => 36],
            ['name' => 'Simon Deb', 'designation' => 'Assistant Teacher', 'sort_order' => 37],
            ['name' => 'Shefali Begum', 'designation' => 'Assistant Teacher', 'sort_order' => 38],
            ['name' => 'Sarah Ahmed', 'designation' => 'Assistant Teacher', 'sort_order' => 39],
            ['name' => 'Ratan Chandra Shil', 'designation' => 'Assistant Teacher', 'sort_order' => 40],
            ['name' => 'Rajon Uddin Howladar', 'designation' => 'Assistant Teacher', 'sort_order' => 41],
        ];

        foreach ([['GOVERNING_BODY', $governing], ['TEACHER', $teachers]] as [$group, $members]) {
            foreach ($members as $m) {
                CmsStaffMember::firstOrCreate(
                    ['name' => $m['name'], 'group' => $group],
                    $m + ['group' => $group, 'is_published' => true]
                );
            }
        }
    }
}
