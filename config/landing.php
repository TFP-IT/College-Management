<?php

/*
|--------------------------------------------------------------------------
| Public landing page content (Maestro Crown College)
|--------------------------------------------------------------------------
| Static copy for the "/" page. Kept in sync with the mobile app's
| src/constants/content.js. Live data (open programmes, notices, gallery)
| comes from the database in HomeController@index; the values here are
| the fallback and the editorial copy.
*/

return [

    'session' => '2027',

    'contact' => [
        'phone'       => '+880 1309-136876',
        'phone_href'  => 'tel:+8801309136876',
        'email'       => 'admin@maestrocrown.edu.bd',
        'address'     => '107/2, Dogormura, Radio Colony, Dhaka–Aricha Highway, Savar, Dhaka 1343',
        'facebook'    => 'https://www.facebook.com/Maestrocrown',
        'map_embed'   => 'https://www.google.com/maps?q=Maestro+Crown+College+Savar+Dhaka&output=embed',
        'map_link'    => 'https://maps.google.com/?q=Maestro+Crown+College+Savar+Dhaka',
    ],

    'app' => [
        'name'       => 'MCC Hello Future',
        'play_store' => 'https://play.google.com/store/apps/details?id=com.mcc.hellofuture&hl=en',
        // Set the App Store URL when the iOS app is live; until then the badge shows "Coming soon".
        'app_store'  => env('MCC_APP_STORE_URL'),
        'eyebrow'    => 'The MCC App',
        'title'      => 'Your whole campus, in one pocket.',
        'body'       => 'Apply for admission, follow your child’s day and stay on top of classes, results and fees. Students, guardians and teachers each get their own portal in the MCC Hello Future app.',
        'portals' => [
            ['id' => 'admission', 'icon' => 'fa-file-signature',      'title' => 'Admission',        'desc' => 'Apply for School or College, upload documents, pay online and download your admission slip.'],
            ['id' => 'student',   'icon' => 'fa-user-graduate',       'title' => 'Student Portal',   'desc' => 'Class routine, attendance, results, library and fees, all on one dashboard.'],
            ['id' => 'guardian',  'icon' => 'fa-people-roof',         'title' => 'Guardian Portal',  'desc' => 'Add all your children, track attendance and syllabus, pay fees and request meetings.'],
            ['id' => 'teacher',   'icon' => 'fa-chalkboard-user',     'title' => 'Teacher Portal',   'desc' => 'Assigned classes and subjects, quick attendance and student analytics.'],
        ],
        'tracking' => [
            'title' => 'Real-time tracking',
            'desc'  => 'Live updates on admission status, attendance, notices and school transport, the moment they happen.',
        ],
        'rating_note' => 'Free to download · Secure sign-in',
    ],

    'announcements' => [
        ['text' => 'Admission 2027 is open for School and College', 'link' => null],
        ['text' => 'Apply in the MCC Hello Future app', 'link' => 'app'],
        ['text' => 'School: Play Group to Class 10', 'link' => null],
        ['text' => 'College (HSC): Science · Business Studies · Humanities', 'link' => null],
        ['text' => 'Admission helpline: +880 1309-136876', 'link' => 'tel'],
    ],

    'hero' => [
        'eyebrow'  => 'Admission 2027 · Now Open',
        'line_1'   => 'From first classroom',
        'line_2'   => 'to university-ready.',
        'subtitle' => 'A joint UK–Bangladesh institution in Savar, guiding students from Play Group to HSC since 2014, on one campus and with one standard of care.',
        'meta'     => ['College Code 1933', 'EIIN 136876', 'Savar, Dhaka'],
        'cards' => [
            'school' => [
                'kicker' => 'School Admission',
                'title'  => 'Play Group – Class 10',
                'text'   => 'Strong basics, good character and real confidence.',
                'cta'    => 'Apply in the app',
                'icon'   => 'fa-shapes',
            ],
            'college' => [
                'kicker' => 'College Admission',
                'title'  => 'HSC · Class XI',
                'text'   => 'Science, Business Studies and Humanities.',
                'cta'    => 'Apply in the app',
                'icon'   => 'fa-graduation-cap',
            ],
        ],
    ],

    'stats' => [
        ['value' => 2014, 'suffix' => '',  'label' => 'Established',       'icon' => 'fa-landmark', 'plain' => true],
        ['value' => 1300, 'suffix' => '+', 'label' => 'Active learners',   'icon' => 'fa-children'],
        ['value' => 50,   'suffix' => '+', 'label' => 'Expert faculty',    'icon' => 'fa-chalkboard-user'],
        ['value' => 97,   'suffix' => '%', 'label' => 'HSC pass rate',     'icon' => 'fa-award'],
        ['value' => 2,    'suffix' => '',  'label' => 'Campuses',          'icon' => 'fa-building-columns'],
    ],

    'school' => [
        'eyebrow' => 'School Section',
        'title'   => 'Play Group to Class 10',
        'body'    => 'From the very first day of school to the SSC exam, we help every child build strong basics, good character and real confidence, in small, caring classrooms.',
        'stages' => [
            ['icon' => 'fa-palette',       'title' => 'Early Years', 'classes' => 'Play Group · Nursery · KG', 'desc' => 'A warm, joyful start to learning and to school life.'],
            ['icon' => 'fa-book-open',     'title' => 'Primary',     'classes' => 'Class 1 – 5',               'desc' => 'Strong basics in reading, writing, maths and science.'],
            ['icon' => 'fa-user-graduate', 'title' => 'Secondary',   'classes' => 'Class 6 – 10',              'desc' => 'Focused SSC preparation, with a clear path into MCC College.'],
        ],
        'steps' => [
            ['title' => 'Get the MCC Hello Future app', 'desc' => 'Free on Google Play. Open it and choose School Admission.'],
            ['title' => 'Create a guardian account & apply', 'desc' => 'Choose the class, complete the form and upload the documents.'],
        ],
    ],

    'college' => [
        'eyebrow' => 'College Section',
        'title'   => 'Higher Secondary (HSC)',
        'body'    => 'Two focused years that turn good SSC results into strong HSC grades and real university options, with experienced faculty, modern labs and close mentoring.',
        'groups' => [
            ['icon' => 'fa-flask',     'title' => 'Science',          'desc' => 'Physics, chemistry, biology and higher maths for future engineers and doctors.', 'subjects' => ['Physics', 'Chemistry', 'Biology', 'Higher Math', 'ICT', 'English', 'Bangla']],
            ['icon' => 'fa-briefcase', 'title' => 'Business Studies', 'desc' => 'Accounting, finance and management for tomorrow’s entrepreneurs.',               'subjects' => ['Accounting', 'Finance & Banking', 'Business Org.', 'Economics', 'ICT', 'English', 'Bangla']],
            ['icon' => 'fa-landmark',  'title' => 'Humanities',       'desc' => 'Civics, history and social sciences for leaders in public life.',                'subjects' => ['Civics', 'Social Work', 'Islamic History', 'Economics', 'ICT', 'English', 'Bangla']],
        ],
        'eligibility' => [
            'Minimum SSC GPA 4.50 for Science',
            'Minimum SSC GPA 3.50 for Business Studies & Humanities',
            'Entrance interview for all applicants',
        ],
        'steps' => [
            'Download the MCC Hello Future app from Google Play',
            'Choose College Admission and your group',
            'Fill in the form, upload documents and pay in the app',
        ],
        'scholarship' => 'Scholarships and waivers are available for meritorious and financially challenged students.',
    ],

    'journey' => [
        ['step' => '01', 'title' => 'Early Years',      'classes' => 'Play Group · Nursery · KG', 'desc' => 'Play-based learning builds curiosity, language and confidence.', 'icon' => 'fa-shapes'],
        ['step' => '02', 'title' => 'Primary',          'classes' => 'Class 1 – 5',               'desc' => 'Reading, writing, numbers and the habits of a good learner.',     'icon' => 'fa-book-open'],
        ['step' => '03', 'title' => 'Secondary',        'classes' => 'Class 6 – 10',              'desc' => 'Subject depth, digital skills and focused SSC preparation.',      'icon' => 'fa-pen-ruler'],
        ['step' => '04', 'title' => 'Higher Secondary', 'classes' => 'Class XI – XII',            'desc' => 'HSC in Science, Business Studies or Humanities.',                 'icon' => 'fa-graduation-cap'],
        ['step' => '05', 'title' => 'University & Beyond', 'classes' => 'Life after MCC',         'desc' => 'Ready for top universities, careers and responsible citizenship.', 'icon' => 'fa-earth-asia'],
    ],

    'about' => [
        'eyebrow' => 'About MCC',
        'title'   => 'Two campuses. One mission.',
        'body'    => 'Maestro Crown College was founded in 2014 as a joint venture between England and Bangladesh to bring international-standard education to Savar. What began as a single campus has grown into two campuses, more than 1,300 learners and 50+ dedicated educators, all working toward one goal: shaping confident, capable young leaders.',
        'quote'   => 'Shaping minds and guiding young people is one of the most powerful ways to build a nation.',
        'quote_by'   => 'Dr. Kamrul Ahsan',
        'quote_role' => 'Chairman, Maestro Crown College',
    ],

    'why' => [
        ['icon' => 'fa-globe',            'title' => 'UK–Bangladesh standard', 'desc' => 'An international teaching approach that fully follows the national board curriculum.', 'wide' => true],
        ['icon' => 'fa-book',             'title' => '2,500+ book library',    'desc' => 'Books, journals and daily newspapers for every curious mind.'],
        ['icon' => 'fa-flask-vial',       'title' => 'Modern science labs',    'desc' => 'Fully equipped labs for real, hands-on experiments.'],
        ['icon' => 'fa-laptop-code',      'title' => 'Digital-first classes',  'desc' => 'Computer labs and regular multimedia lessons.'],
        ['icon' => 'fa-house-flag',       'title' => 'House system',           'desc' => 'Four competitive houses that build leadership and teamwork.'],
        ['icon' => 'fa-masks-theater',    'title' => 'Life beyond books',      'desc' => 'Debate, music, dance, qirat, Olympiads, science fairs and sports.', 'wide' => true],
    ],

    'facilities' => [
        ['title' => 'Library',            'desc' => '2,500+ books, journals and daily newspapers.',        'image' => 'library',          'size' => 'tall'],
        ['title' => 'Cultural Stage',     'desc' => 'Music, dance and annual cultural programmes.',        'image' => 'cultural-program', 'size' => 'wide'],
        ['title' => 'Indoor Games Zone',  'desc' => 'Billiards, table tennis and more between classes.',   'image' => 'gaming-zone'],
        ['title' => 'Study Tours',        'desc' => 'Learning outside the classroom, every year.',         'image' => 'study-tour-2'],
        ['title' => 'Science & Computer Labs', 'desc' => 'Hands-on experiments and digital-first learning.', 'icon' => 'fa-microscope'],
        ['title' => 'Sports Ground',      'desc' => 'Cricket, football, rugby and inter-house sports.',     'icon' => 'fa-futbol'],
    ],

    'achievements' => [
        'eyebrow' => 'Results that speak',
        'title'   => 'Excellence, year after year.',
        'items' => [
            ['value' => 97, 'suffix' => '%', 'label' => 'HSC pass rate',            'ring' => true],
            ['value' => 1,  'prefix' => '#', 'label' => 'Results in Savar area (2017)'],
            ['value' => 10, 'suffix' => '+', 'label' => 'Years of shaping leaders'],
        ],
        'note' => 'Multiple inter-school and national sports awards.',
    ],

    'process' => [
        ['icon' => 'fa-mobile-screen-button', 'title' => 'Download the app',        'desc' => 'Get MCC Hello Future free from Google Play.'],
        ['icon' => 'fa-hand-pointer',         'title' => 'Choose School or College', 'desc' => 'Pick the class or group and create your account.'],
        ['icon' => 'fa-file-arrow-up',        'title' => 'Submit form & documents',  'desc' => 'Fill in the student’s details and upload the required papers.'],
        ['icon' => 'fa-circle-check',         'title' => 'Pay & track live',         'desc' => 'Pay the fee in the app and follow every update in real time.'],
    ],

    'leaders' => [
        [
            'name'  => 'Dr. Kamrul Ahsan',
            'role'  => 'Chairman',
            'quote' => 'Shaping minds and guiding young people is one of the most powerful ways to build a nation. At MCC, every child is known, challenged and cared for.',
            'image' => 'chairman-smiling-face',
            'route' => 'frontend.message_chairman',
        ],
        [
            'name'  => 'Md. Khalilur Rahman',
            'role'  => 'Principal',
            'quote' => 'I am truly humbled to welcome you to our vibrant learning community, where discipline, curiosity and kindness grow side by side.',
            'image' => null,
            'route' => 'frontend.message_principal',
        ],
    ],

    'gallery_fallback' => [
        ['image' => 'students',          'title' => 'Admission information day'],
        ['image' => 'cultural-program',  'title' => 'Cultural programme'],
        ['image' => 'some-students',     'title' => 'Our students'],
        ['image' => 'award',             'title' => 'Award ceremony'],
        ['image' => 'study-tour',        'title' => 'Annual programme'],
        ['image' => 'teachers-day',      'title' => 'Teachers’ Day'],
        ['image' => 'students-2',        'title' => 'Campus life'],
        ['image' => 'study-tour-2',      'title' => 'Study tour'],
        ['image' => 'gaming-zone',       'title' => 'Indoor games zone'],
        ['image' => 'library',           'title' => 'Library'],
    ],

    'faq' => [
        ['q' => 'Which classes are open for admission?', 'a' => 'School admission is open from Play Group to Class 10. College admission is open for Class XI (HSC) in Science, Business Studies and Humanities.'],
        ['q' => 'How do I apply?', 'a' => 'Admission is done in the MCC Hello Future app. Download it free from Google Play, choose School or College, fill in the form, upload documents and pay the fee, all in a few minutes. Need help? Call or visit our admission office in Savar.'],
        ['q' => 'What is the eligibility for HSC?', 'a' => 'A minimum SSC GPA of 4.50 for Science and 3.50 for Business Studies or Humanities, plus a short entrance interview.'],
        ['q' => 'How much are the fees? Are scholarships available?', 'a' => 'The current application and admission fees for each class are shown in the MCC Hello Future app when you apply. Scholarships and waivers are available for meritorious and financially challenged students. Ask the admission office for details.'],
        ['q' => 'Which documents do I need?', 'a' => 'For HSC: the original SSC mark sheet with 2 photocopies, the headmaster’s testimonial, photocopies of the SSC admit and registration cards, the birth certificate, 4 passport and 2 stamp-size photos of the student, and 2 passport photos of the guardian. For school classes, the application form lists the exact documents for the class you choose.'],
        ['q' => 'How can I track my application?', 'a' => 'Sign in to the MCC Hello Future app to see your application status in real time, from review to approval and payment.'],
    ],
];
