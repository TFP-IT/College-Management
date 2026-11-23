<?php

/*
|--------------------------------------------------------------------------
| Academics content (Programs, Academic Calendar)
|--------------------------------------------------------------------------
| Editorial copy for the public Academics pages. Live programme fees come
| from the admissions database; everything here is static and can be
| edited without touching the views.
*/

return [

    'calendar' => [
        'year' => 2026,
        'note' => 'Dates may change according to government and Dhaka Education Board directives. Please check the Notice Board regularly for updates.',
        // month: 1–12 (first month of the period) · until: last month of a multi-month period
        // type: academic | exam | holiday | event | national
        'periods' => [
            ['month' => 1,  'label' => 'January', 'events' => [
                ['title' => 'New academic session begins', 'date' => '1 Jan',        'type' => 'academic'],
                ['title' => 'Admission period',             'date' => '1 – 15 Jan',   'type' => 'academic'],
            ]],
            ['month' => 2,  'label' => 'February', 'events' => [
                ['title' => 'International Mother Language Day', 'date' => '21 Feb', 'type' => 'national'],
                ['title' => 'Cultural programme',                'date' => '21 Feb', 'type' => 'event'],
            ]],
            ['month' => 3,  'label' => 'March', 'events' => [
                ['title' => 'Independence Day',  'date' => '26 Mar',        'type' => 'national'],
                ['title' => '1st Term Examination', 'date' => 'March',      'type' => 'exam'],
            ]],
            ['month' => 4,  'label' => 'April', 'events' => [
                ['title' => 'Pohela Boishakh (Bangla New Year)', 'date' => '14 Apr', 'type' => 'event'],
            ]],
            ['month' => 5,  'until' => 6, 'label' => 'May – June', 'events' => [
                ['title' => 'Half-Yearly Examination', 'date' => 'As per Board schedule', 'type' => 'exam'],
                ['title' => 'Ramadan & Eid vacation',  'date' => 'As per government calendar', 'type' => 'holiday'],
            ]],
            ['month' => 7,  'until' => 8, 'label' => 'July – August', 'events' => [
                ['title' => 'Sports Week',          'date' => 'Mid July',    'type' => 'event'],
                ['title' => 'Science Fair',         'date' => 'July – Aug',  'type' => 'event'],
                ['title' => '2nd Term Examination', 'date' => 'End of Aug',  'type' => 'exam'],
            ]],
            ['month' => 9,  'label' => 'September', 'events' => [
                ['title' => 'Test Examination (Class XII)', 'date' => '1 – 20 Sep', 'type' => 'exam'],
            ]],
            ['month' => 10, 'label' => 'October', 'events' => [
                ['title' => 'Durga Puja vacation', 'date' => 'As per calendar', 'type' => 'holiday'],
                ['title' => 'Pre-board review',    'date' => 'October',         'type' => 'academic'],
            ]],
            ['month' => 11, 'label' => 'November', 'events' => [
                ['title' => 'Annual Examination & final assessment', 'date' => '1 – 25 Nov', 'type' => 'exam'],
            ]],
            ['month' => 12, 'label' => 'December', 'events' => [
                ['title' => 'Victory Day',         'date' => '16 Dec',        'type' => 'national'],
                ['title' => 'Result publication',  'date' => 'December',      'type' => 'academic'],
                ['title' => 'Winter break',        'date' => '16 – 31 Dec',   'type' => 'holiday'],
            ]],
        ],
    ],

    'event_types' => [
        'exam'     => ['label' => 'Examinations',  'icon' => 'fa-file-pen'],
        'holiday'  => ['label' => 'Holidays',      'icon' => 'fa-umbrella-beach'],
        'event'    => ['label' => 'Events',        'icon' => 'fa-star'],
        'national' => ['label' => 'National days', 'icon' => 'fa-flag'],
        'academic' => ['label' => 'Academic',      'icon' => 'fa-book-open'],
    ],

    'rules' => [
        ['icon' => 'fa-clipboard-user',       'title' => 'Mandatory attendance',     'desc' => 'Every student must maintain at least 75% attendance in all subjects, as required by Education Board rules. Students below this level cannot sit the final Board Examinations.'],
        ['icon' => 'fa-id-card',              'title' => 'Uniform & ID card',        'desc' => 'Students must wear the prescribed, neat and clean uniform and carry a valid college ID card around the neck at all times on campus.'],
        ['icon' => 'fa-mobile-screen-button', 'title' => 'Mobile phones',            'desc' => 'Mobile phones, smartwatches and other electronic gadgets are not allowed in classrooms, labs or examination halls. Confiscated devices are returned only to guardians.'],
        ['icon' => 'fa-file-pen',             'title' => 'Examination policy',       'desc' => 'All class tests, term exams and test examinations are compulsory. Any use of unfair means leads to immediate expulsion and strict disciplinary action.'],
        ['icon' => 'fa-shield-halved',        'title' => 'Discipline & anti-ragging', 'desc' => 'Zero tolerance for ragging, bullying, political activity or harassment of any kind. Smoking or carrying tobacco within 100 yards of campus is banned.'],
    ],
];
