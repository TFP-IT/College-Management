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

    /*
    | HSC subject structure (Programs page). Every group shares the compulsory
    | subjects; each group adds its own subjects; Agriculture is offered as an
    | optional subject in all three groups.
    */
    'hsc' => [
        'compulsory' => ['Bangla', 'English', 'Information & Communication Technology (ICT)'],
        'optional'   => ['Agriculture'],
        'groups' => [
            'Science'          => ['Physics', 'Chemistry', 'Biology', 'Higher Mathematics'],
            'Business Studies' => ['Accounting', 'Finance, Banking & Insurance', 'Business Organisation & Management', 'Economics'],
            'Humanities'       => ['Civics & Good Governance', 'Social Work', 'Islamic History & Culture', 'Economics'],
        ],
    ],

    // National HSC grading system (Ministry of Education, Bangladesh).
    'grading' => [
        ['marks' => '80 – 100', 'grade' => 'A+', 'point' => '5.00', 'remark' => 'Outstanding'],
        ['marks' => '70 – 79',  'grade' => 'A',  'point' => '4.00', 'remark' => 'Excellent'],
        ['marks' => '60 – 69',  'grade' => 'A-', 'point' => '3.50', 'remark' => 'Very good'],
        ['marks' => '50 – 59',  'grade' => 'B',  'point' => '3.00', 'remark' => 'Good'],
        ['marks' => '40 – 49',  'grade' => 'C',  'point' => '2.00', 'remark' => 'Satisfactory'],
        ['marks' => '33 – 39',  'grade' => 'D',  'point' => '1.00', 'remark' => 'Pass'],
        ['marks' => '0 – 32',   'grade' => 'F',  'point' => '0.00', 'remark' => 'Fail'],
    ],

    /*
    | Research & Development page.
    */
    'research' => [
        'objectives' => [
            ['icon' => 'fa-chalkboard-user',  'title' => 'Better teaching',      'desc' => 'Foster a culture of continuous improvement in how our teachers plan, teach and assess.'],
            ['icon' => 'fa-satellite-dish',   'title' => 'Emerging trends',      'desc' => 'Explore new developments in education and technology, and bring the best of them into class.'],
            ['icon' => 'fa-lightbulb',        'title' => 'Learning solutions',   'desc' => 'Develop innovative learning tools built around the needs of the 21st-century learner.'],
            ['icon' => 'fa-screwdriver-wrench', 'title' => 'Theory into practice', 'desc' => 'Bridge the gap between what students read in books and how it works in the real world.'],
        ],
        'focus' => [
            ['icon' => 'fa-brain',        'title' => 'AI in learning',        'desc' => 'Using artificial intelligence and digital tools to make lessons more engaging and learning outcomes stronger.'],
            ['icon' => 'fa-robot',        'title' => 'Robotics & drones',     'desc' => 'Hands-on building and flying sessions that develop creativity, logical thinking and teamwork.'],
            ['icon' => 'fa-comments',     'title' => 'Skills workshops',      'desc' => 'Practical workshops, from spoken English to presentation skills, that build real-world confidence.'],
            ['icon' => 'fa-flask-vial',   'title' => 'Science & Olympiads',   'desc' => 'Science fair projects and Maths, Physics, Chemistry and Biology Olympiads that reward curiosity.'],
        ],
        'work' => [
            ['image' => 'campus/rd-drone-workshop',   'tag' => 'Workshop',      'title' => 'Building a drone, part by part',      'desc' => 'Students explore motors, propellers and flight controllers in a hands-on drone workshop in our computer lab.', 'size' => 'wide'],
            ['image' => 'campus/rd-quadcopter-demo',  'tag' => 'Demonstration', 'title' => 'How a quadcopter really works',       'desc' => 'Our demonstrations cover the technical side of robotics and the teamwork, innovation and critical thinking behind it.'],
            ['image' => 'campus/rd-robotics-club',    'tag' => 'Robotics Club', 'title' => '“Robotics for Everyone”',              'desc' => 'The launch of MCC’s Robotics Club brought young learners from Savar out to the field to see robots and drones in action.'],
            ['image' => 'campus/rd-english-workshop', 'tag' => 'Skills',        'title' => 'Basic English Grammar & Spoken',      'desc' => 'A language workshop hosted by MCC, part of our focus on the communication skills students need to succeed.', 'contain' => true],
        ],
    ],
];
