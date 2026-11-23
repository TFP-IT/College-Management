<?php

/*
|--------------------------------------------------------------------------
| Faculty & staff profiles (public website)
|--------------------------------------------------------------------------
| Single source for the Teachers / Staff pages, each person's profile page
| (/teacher-information/{slug}, /staff-information/{slug}) and the
| "Meet our faculty" videos on the home page. Later this can move to the CMS.
|
| group:  teacher | staff
| poster: image under public/frontend/mcc/img (no extension) — also the profile photo
| video:  mp4 under public/ — loads only when someone presses play
| lead:   one-line summary shown under the name on the profile page
| date:   when the introduction was published (orders the home page media feed)
*/

return [
    'members' => [
        [
            'slug' => 'salma-khatun',
            'date' => '2026-11-16',
            'lead' => 'Bringing dedication, subject knowledge and a real commitment to student development to our Business Studies classrooms.',
            'group' => 'teacher',
            'name' => 'Salma Khatun',
            'role' => 'Lecturer in Finance & Banking',
            'department' => 'Business Studies',
            'subjects' => ['Finance, Banking & Insurance'],
            'education' => [],
            'experience' => null,
            'tag' => 'Meet our faculty',
            'bio' => 'We are glad to introduce Salma Khatun, Lecturer in Finance & Banking. With her dedication, subject knowledge and commitment to student development, we believe she will make a valuable contribution to our academic community. Please join us in welcoming her to the MCC family.',
            'video' => 'frontend/mcc/video/teachers/salma-khatun.mp4',
            'poster' => 'campus/video-salma-khatun',
        ],
        [
            'slug' => 'lopa-rani-chowdhuri',
            'date' => '2026-11-14',
            'lead' => 'A Public Administration graduate of Jatiya Kabi Kazi Nazrul Islam University and former Lecturer at Mymensingh Girls’ Cadet College.',
            'group' => 'teacher',
            'name' => 'Ms. Lopa Rani Chowdhuri',
            'role' => 'Lecturer in Civics & Good Governance',
            'department' => 'Humanities',
            'subjects' => ['Civics & Good Governance'],
            'education' => [
                ['degree' => 'Honours & Master’s in Public Administration', 'institution' => 'Jatiya Kabi Kazi Nazrul Islam University'],
            ],
            'experience' => 'Lecturer, Mymensingh Girls’ Cadet College',
            'tag' => 'Meet our faculty',
            'bio' => 'We are delighted to welcome Ms. Lopa Rani Chowdhuri as our new Lecturer in Civics & Good Governance. She completed both her Honours and Master’s degrees in Public Administration at Jatiya Kabi Kazi Nazrul Islam University and brings valuable teaching experience from her previous role as a Lecturer at Mymensingh Girls’ Cadet College. We look forward to her contribution to our students’ intellectual growth and to the academic excellence of our institution.',
            'video' => 'frontend/mcc/video/teachers/lopa-rani-chowdhury.mp4',
            'poster' => 'campus/video-lopa-rani-chowdhury',
        ],
        [
            'slug' => 'nur-mohammad',
            'date' => '2026-11-13',
            'lead' => 'A University of Barisal finance graduate guiding our Business Studies students in finance, banking and management.',
            'group' => 'teacher',
            'name' => 'Md. Nur Mohammad',
            'role' => 'Lecturer in Finance, Banking & Insurance',
            'department' => 'Business Studies',
            'subjects' => ['Finance, Banking & Insurance', 'Business Organisation & Management'],
            'education' => [
                ['degree' => 'BBA & MBA in Finance, Banking & Insurance', 'institution' => 'University of Barisal'],
            ],
            'experience' => null,
            'tag' => 'Meet our faculty',
            'bio' => 'We are delighted to welcome Md. Nur Mohammad as a Lecturer in Finance, Banking & Insurance. He completed both his BBA and MBA in the Department of Finance, Banking & Insurance at the University of Barisal. He will teach our Business Studies students, particularly Finance, Banking & Insurance and Business Organisation & Management, helping them build a strong understanding of business and financial studies.',
            'video' => 'frontend/mcc/video/teachers/nur-mohammad.mp4',
            'poster' => 'campus/video-nur-mohammad',
        ],
        [
            'slug' => 'shahrin-nahin',
            'date' => '2026-11-15',
            'lead' => 'A Jahangirnagar University English graduate who brings international-school teaching experience to our classrooms.',
            'group' => 'teacher',
            'name' => 'Ms. Shahrin Nahin',
            'role' => 'Lecturer in English',
            'department' => 'Languages',
            'subjects' => ['English Language & Literature'],
            'education' => [
                ['degree' => 'BA & MA in English Language & Literature', 'institution' => 'Jahangirnagar University'],
            ],
            'experience' => 'English Teacher, Boston International School',
            'tag' => 'Meet our faculty',
            'bio' => 'We are delighted to welcome Ms. Shahrin Nahin as a Lecturer in English. A proud alumna of Jahangirnagar University, she completed both her undergraduate and postgraduate studies in English Language and Literature, and previously taught English at Boston International School. With her strong academic background and passion for the subject, she will further strengthen the academic excellence of our institution.',
            'video' => 'frontend/mcc/video/teachers/shahrin.mp4',
            'poster' => 'campus/video-shahrin',
        ],
        [
            'slug' => 'ayub-ali',
            'date' => '2026-11-12',
            'lead' => 'A Jahangirnagar University chemistry graduate with a strong academic foundation and a genuine commitment to teaching.',
            'group' => 'teacher',
            'name' => 'Mr. Ayub Ali',
            'role' => 'Lecturer in Chemistry',
            'department' => 'Science',
            'subjects' => ['Chemistry'],
            'education' => [
                ['degree' => 'Graduate, Department of Chemistry', 'institution' => 'Jahangirnagar University'],
            ],
            'experience' => null,
            'tag' => 'Meet our faculty',
            'bio' => 'We are delighted to introduce Mr. Ayub Ali, who joins us as a Lecturer in Chemistry. A graduate of the Department of Chemistry at Jahangirnagar University, he brings a strong academic foundation, subject expertise and a genuine commitment to teaching. We are confident his knowledge and dedication will enrich the learning and development of our students.',
            'video' => 'frontend/mcc/video/teachers/ayub-ali.mp4',
            'poster' => 'campus/video-ayub-ali',
        ],
        [
            'slug' => 'shoriful-islam',
            'date' => '2026-11-11',
            'lead' => 'An MCC alumnus who has come home, now serving the campus where his own journey began.',
            'group' => 'teacher',
            'name' => 'Shoriful Islam',
            'role' => 'Executive (Administration)',
            'department' => 'Administration',
            'subjects' => [],
            'education' => [
                ['degree' => 'BBA & MBA', 'institution' => 'Gopalganj Science and Technology University'],
                ['degree' => 'SSC & HSC', 'institution' => 'Maestro Crown School & College'],
            ],
            'experience' => null,
            'alumnus' => true,
            'tag' => 'Welcome home',
            'bio' => 'Some homecomings are more than a return: they are moments of pride and joy. Shoriful Islam, a former student who completed both his SSC and HSC at Maestro Crown School & College, has come back to the campus he knows so well, this time as our Executive (Administration). After MCC he earned his BBA and MBA at Gopalganj Science and Technology University. Welcome home, Shoriful!',
            'video' => 'frontend/mcc/video/teachers/shoriful-islam.mp4',
            'poster' => 'campus/video-shoriful-islam',
        ],
    ],

    'department_icons' => [
        'Science' => 'fa-flask',
        'Business Studies' => 'fa-briefcase',
        'Humanities' => 'fa-landmark',
        'Languages' => 'fa-language',
        'Administration' => 'fa-building-user',
    ],
];
