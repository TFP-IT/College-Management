<?php

/*
|--------------------------------------------------------------------------
| Public landing page content (Maestro Crown School & College)
|--------------------------------------------------------------------------
| Static copy for the "/" page. Kept in sync with the mobile app's
| src/constants/content.js. Live data (open programmes, notices, gallery)
| comes from the database in HomeController@index; the values here are
| the fallback and the editorial copy.
*/

return [

    'session' => '2027',

    // Local photo fallbacks for people (used until a photo is uploaded in the CMS).
    // Drop e.g. public/frontend/mcc/img/campus/principal.jpg and it appears everywhere automatically.
    'people_photos' => [
        'Dr. Kamrul Ahsan'   => 'campus/chairman-face',
        'Md. Shoeb Khan'     => 'campus/principal',
        'Md Shoeb Khan'      => 'campus/principal',
        'MD SHOEB KHAN'      => 'campus/principal',
    ],

    'contact' => [
        // Same as the official Facebook page and admission posters.
        'phone'       => '+880 1335-252581',
        'phone_href'  => 'tel:+8801335252581',
        'phone_alt'      => '+880 1335-252582',
        'phone_alt_href' => 'tel:+8801335252582',
        'tagline'     => 'Quality education through discipline, knowledge and technology.',
        'email'       => 'admin@maestrocrown.edu.bd',
        'address'     => '107/2, Dogormura, Radio Colony, Dhaka–Aricha Highway, Savar, Dhaka 1343',
        'facebook'    => 'https://www.facebook.com/Maestrocrown',
        'map_embed'   => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3648.51410769363!2d90.257007!3d23.871363!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755e9c0b1160a2b%3A0xc3c942cd894e7561!2sMaestro%20Crown%20College!5e0!3m2!1sen!2sbd!4v1700000000000!5m2!1sen!2sbd',
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
        ['text' => 'School: Play Group to Class 9', 'link' => null],
        ['text' => 'College (HSC): Science · Business Studies · Humanities', 'link' => null],
        ['text' => 'Admission helpline: +880 1335-252581', 'link' => 'tel'],
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
                'title'  => 'Play Group – Class 9',
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
        'title'   => 'Play Group to Class 9',
        'body'    => 'From the very first day of school to Class 9, we help every child build strong basics, good character and real confidence, in small, caring classrooms.',
        'stages' => [
            ['icon' => 'fa-palette',       'title' => 'Early Years', 'classes' => 'Play Group · Nursery · KG', 'desc' => 'A warm, joyful start to learning and to school life.'],
            ['icon' => 'fa-book-open',     'title' => 'Primary',     'classes' => 'Class 1 – 5',               'desc' => 'Strong basics in reading, writing, maths and science.'],
            ['icon' => 'fa-user-graduate', 'title' => 'Secondary',   'classes' => 'Class 6 – 9',              'desc' => 'Subject depth and strong study habits, with a clear path into MCC College.'],
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
            'Minimum SSC GPA 2.00 for Science',
            'Minimum SSC GPA 1.50 for Business Studies & Humanities',
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
        ['step' => '03', 'title' => 'Secondary',        'classes' => 'Class 6 – 9',              'desc' => 'Subject depth, digital skills and a strong base for the years ahead.',      'icon' => 'fa-pen-ruler'],
        ['step' => '04', 'title' => 'Higher Secondary', 'classes' => 'Class XI – XII',            'desc' => 'HSC in Science, Business Studies or Humanities.',                 'icon' => 'fa-graduation-cap'],
        ['step' => '05', 'title' => 'University & Beyond', 'classes' => 'Life after MCC',         'desc' => 'Ready for top universities, careers and responsible citizenship.', 'icon' => 'fa-earth-asia'],
    ],

    'about' => [
        'image_main'  => 'campus/campus-front',
        'image_alt'   => 'The Maestro Crown School & College building in Savar',
        'image_small' => 'campus/classroom-hall-2',
        'eyebrow' => 'About MCC',
        'title'   => 'Two campuses. One mission.',
        'body'    => 'Maestro Crown School & College was founded in 2014 as a joint venture between England and Bangladesh to bring international-standard education to Savar. What began as a single campus has grown into two campuses, more than 1,300 learners and 50+ dedicated educators, all working toward one goal: shaping confident, capable young leaders.',
        'quote'   => 'Shaping minds and guiding young people is one of the most powerful ways to build a nation.',
        'quote_by'   => 'Dr. Kamrul Ahsan',
        'quote_role' => 'Chairman, Maestro Crown School & College',
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
        ['title' => 'Spacious Classrooms', 'desc' => 'Bright, well-ventilated halls built for focused learning.', 'image' => 'campus/classroom-hall-1', 'size' => 'tall'],
        ['title' => 'Computer Labs',       'desc' => 'Hands-on digital skills in modern, fully equipped labs.',     'image' => 'campus/computer-lab-1',   'size' => 'wide'],
        ['title' => 'Library',             'desc' => '2,500+ books, journals and daily newspapers.',               'image' => 'library'],
        ['title' => 'Indoor Games Zone',   'desc' => 'Pool, chess, carrom and ludo between classes.',              'image' => 'campus/indoor-pool-2'],
        ['title' => 'Sports Ground',       'desc' => 'Cricket, football and inter-house tournaments all year.',    'image' => 'campus/football-2'],
        ['title' => 'Culture & Festivals', 'desc' => 'Pohela Boishakh, Pitha Utshob and more, celebrated together.', 'image' => 'campus/boishakh-1'],
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
        'image' => 'campus/cricket-team',
        'image_alt' => 'MCC girls’ cricket team on the sports ground',
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
            'image' => 'campus/chairman-face',
            'route' => 'frontend.message_chairman',
        ],
        [
            'name'  => 'Md. Shoeb Khan',
            'role'  => 'Principal',
            'quote' => 'I believe education is the foundation of a brighter future. Our goal is to nurture students into confident, capable and responsible individuals.',
            'image' => 'campus/principal',
            'route' => 'frontend.message_principal',
        ],
    ],

    /*
    | Videos shown in the "MCC Media" section (played on our own site, no third-party player).
    | src:    an mp4 under public/ (e.g. frontend/mcc/video/xyz.mp4). Missing file = "Video coming soon".
    | poster: a thumbnail under public/frontend/mcc/img (no extension). Only the poster loads with the page;
    |         the video file downloads when someone presses play.
    | Later this list can come from the CMS/database instead of this file.
    */
    'videos' => [
        // Meet our people (portrait introductions). caption + highlights show in the video viewer.
        ['src' => 'frontend/mcc/video/teachers/salma-khatun.mp4', 'poster' => 'campus/video-salma-khatun',
         'tag' => 'Meet our faculty', 'title' => 'Salma Khatun', 'subtitle' => 'Lecturer in Finance & Banking',
         'highlights' => ['Business Studies', 'Finance & Banking'],
         'caption' => 'We are glad to introduce Salma Khatun, Lecturer in Finance & Banking. With her dedication, subject knowledge and commitment to student development, we believe she will make a valuable contribution to our academic community. Please join us in welcoming her to the MCC family.'],
        ['src' => 'frontend/mcc/video/teachers/lopa-rani-chowdhury.mp4', 'poster' => 'campus/video-lopa-rani-chowdhury',
         'tag' => 'Meet our faculty', 'title' => 'Ms. Lopa Rani Chowdhuri', 'subtitle' => 'Lecturer in Civics & Good Governance',
         'highlights' => ['Honours & Master’s, Public Administration', 'Jatiya Kabi Kazi Nazrul Islam University', 'Former Lecturer, Mymensingh Girls’ Cadet College'],
         'caption' => 'We are delighted to welcome Ms. Lopa Rani Chowdhuri as our new Lecturer in Civics & Good Governance. She completed both her Honours and Master’s degrees in Public Administration at Jatiya Kabi Kazi Nazrul Islam University and brings valuable teaching experience from her previous role as a Lecturer at Mymensingh Girls’ Cadet College. We look forward to her contribution to our students’ intellectual growth and to the academic excellence of our institution.'],
        ['src' => 'frontend/mcc/video/teachers/nur-mohammad.mp4', 'poster' => 'campus/video-nur-mohammad',
         'tag' => 'Meet our faculty', 'title' => 'Md. Nur Mohammad', 'subtitle' => 'Lecturer in Finance, Banking & Insurance',
         'highlights' => ['BBA & MBA, Finance, Banking & Insurance', 'University of Barisal', 'Business Studies Group'],
         'caption' => 'We are delighted to welcome Md. Nur Mohammad as a Lecturer in Finance, Banking & Insurance. He completed both his BBA and MBA in the Department of Finance, Banking & Insurance at the University of Barisal. He will teach our Business Studies students, particularly Finance, Banking & Insurance and Business Organisation & Management, helping them build a strong understanding of business and financial studies.'],
        ['src' => 'frontend/mcc/video/teachers/shahrin.mp4', 'poster' => 'campus/video-shahrin',
         'tag' => 'Meet our faculty', 'title' => 'Ms. Shahrin Nahin', 'subtitle' => 'Lecturer in English',
         'highlights' => ['BA & MA, English Language & Literature', 'Jahangirnagar University', 'Former English Teacher, Boston International School'],
         'caption' => 'We are delighted to welcome Ms. Shahrin Nahin as a Lecturer in English. A proud alumna of Jahangirnagar University, she completed both her undergraduate and postgraduate studies in English Language and Literature, and previously taught English at Boston International School. With her strong academic background and passion for the subject, she will further strengthen the academic excellence of our institution.'],
        ['src' => 'frontend/mcc/video/teachers/shoriful-islam.mp4', 'poster' => 'campus/video-shoriful-islam',
         'tag' => 'Welcome home', 'title' => 'Shoriful Islam', 'subtitle' => 'Executive (Administration)',
         'highlights' => ['MCC alumnus: SSC & HSC', 'BBA & MBA', 'Gopalganj Science and Technology University'],
         'caption' => 'Some homecomings are more than a return: they are moments of pride and joy. Shoriful Islam, a former student who completed both his SSC and HSC at Maestro Crown School & College, has come back to the campus he knows so well, this time as our Executive (Administration). After MCC he earned his BBA and MBA at Gopalganj Science and Technology University. Welcome home, Shoriful!'],
        ['src' => 'frontend/mcc/video/teachers/ayub-ali.mp4', 'poster' => 'campus/video-ayub-ali',
         'tag' => 'Meet our faculty', 'title' => 'Mr. Ayub Ali', 'subtitle' => 'Lecturer in Chemistry',
         'highlights' => ['Department of Chemistry', 'Jahangirnagar University'],
         'caption' => 'We are delighted to introduce Mr. Ayub Ali, who joins us as a Lecturer in Chemistry. A graduate of the Department of Chemistry at Jahangirnagar University, he brings a strong academic foundation, subject expertise and a genuine commitment to teaching. We are confident his knowledge and dedication will enrich the learning and development of our students.'],
        // Campus
        ['src' => 'frontend/mcc/video/hero.mp4', 'poster' => 'hero-poster',
         'tag' => 'Campus tour', 'title' => 'A walk through our campus', 'subtitle' => 'Maestro Crown School & College, Savar'],
    ],

    'posters' => [
        ['image' => 'campus/admission-poster-2026-27', 'title' => 'Admissions open: Session 2026–27',       'tag' => 'Admission'],
        ['image' => 'campus/admission-poster-bn-1',    'title' => 'Class XI admission: Science, Business & Humanities', 'tag' => 'Admission'],
        ['image' => 'campus/admission-poster-bn-2',    'title' => 'HSC admission 2026–27 is ongoing',      'tag' => 'Admission'],
        ['image' => 'campus/hsc-result-2026',          'title' => 'HSC Examination 2026 results',          'tag' => 'Result'],
        ['image' => 'campus/school-2-teaser',          'title' => 'Maestro Crown School 2.0 is coming',    'tag' => 'Coming soon'],
    ],

    'gallery' => [
        'initial' => 12,
        'categories' => [
            'campus'     => 'Campus',
            'classrooms' => 'Classrooms & Labs',
            'sports'     => 'Sports',
            'festivals'  => 'Festivals & Culture',
            'games'      => 'Indoor Games',
            'trips'      => 'Trips & Events',
        ],
        // size: wide (2 columns) · tall (2 rows) · std
        'items' => [
            ['image' => 'campus/boishakh-1',          'cat' => 'festivals',  'size' => 'wide', 'title' => 'Pohela Boishakh celebration'],
            ['image' => 'campus/campus-night',        'cat' => 'campus',     'size' => 'tall', 'title' => 'Campus lit up for a celebration'],
            ['image' => 'campus/cricket-1',           'cat' => 'sports',                       'title' => 'Girls’ cricket: a winning moment'],
            ['image' => 'campus/computer-lab-3',      'cat' => 'classrooms',                   'title' => 'Computer lab session'],
            ['image' => 'campus/pitha-utshob-3',      'cat' => 'festivals',                    'title' => 'Pitha Utshob winter cake festival'],
            ['image' => 'campus/football-team',       'cat' => 'sports',     'size' => 'wide', 'title' => 'MCC football team'],
            ['image' => 'campus/indoor-chess',        'cat' => 'games',                        'title' => 'Chess in the indoor games zone'],
            ['image' => 'campus/study-tour-2',        'cat' => 'trips',                        'title' => 'Study tour'],
            ['image' => 'campus/classroom-hall-1',    'cat' => 'classrooms', 'size' => 'tall', 'title' => 'A full classroom in session'],
            ['image' => 'campus/iftar-mahfil',        'cat' => 'festivals',  'size' => 'wide', 'title' => 'Iftar Mahfil'],
            ['image' => 'campus/cricket-4',           'cat' => 'sports',                       'title' => 'Inter-house cricket'],
            ['image' => 'campus/students-2',          'cat' => 'campus',                       'title' => 'Students at the admission information centre'],
            ['image' => 'campus/boishakh-rally-2',    'cat' => 'festivals',                    'title' => 'Boishakhi rally through Savar'],
            ['image' => 'campus/indoor-pool-1',       'cat' => 'games',                        'title' => 'Pool table, indoor games zone'],
            ['image' => 'campus/football-2',          'cat' => 'sports',                       'title' => 'Football on the sports ground'],
            ['image' => 'campus/computer-lab-1',      'cat' => 'classrooms',                   'title' => 'Hands-on computer practice'],
            ['image' => 'campus/picnic-guests',       'cat' => 'trips',      'size' => 'tall', 'title' => 'Annual picnic with guests'],
            ['image' => 'campus/pitha-utshob-1',      'cat' => 'festivals',                    'title' => 'Students at Pitha Utshob'],
            ['image' => 'campus/cricket-team',        'cat' => 'sports',     'size' => 'wide', 'title' => 'MCC girls’ cricket team'],
            ['image' => 'campus/indoor-carrom',       'cat' => 'games',                        'title' => 'Carrom board'],
            ['image' => 'campus/classroom-exam',      'cat' => 'classrooms',                   'title' => 'Classroom assessment'],
            ['image' => 'campus/campus-front',        'cat' => 'campus',     'size' => 'tall', 'title' => 'The MCC building'],
            ['image' => 'campus/boishakh-2',          'cat' => 'festivals',                    'title' => 'Welcoming the Bangla New Year'],
            ['image' => 'campus/football-5',          'cat' => 'sports',                       'title' => 'Inter-house football'],
            ['image' => 'campus/study-tour-1',        'cat' => 'trips',      'size' => 'wide', 'title' => 'Students on a study tour'],
            ['image' => 'campus/indoor-ludo-1',       'cat' => 'games',                        'title' => 'Ludo with friends'],
            ['image' => 'campus/cricket-3',           'cat' => 'sports',                       'title' => 'Celebrating together'],
            ['image' => 'campus/pitha-utshob-4',      'cat' => 'festivals',                    'title' => 'Traditional pitha on display'],
            ['image' => 'campus/classroom-hall-2',    'cat' => 'classrooms', 'size' => 'wide', 'title' => 'Classroom hall'],
            ['image' => 'campus/rochona-competition', 'cat' => 'festivals',                    'title' => 'Independence Day essay & art competition'],
            ['image' => 'campus/football-6',          'cat' => 'sports',                       'title' => 'Match day'],
            ['image' => 'campus/indoor-pool-3',       'cat' => 'games',                        'title' => 'Friendly game of pool'],
            ['image' => 'campus/students-3',          'cat' => 'campus',                       'title' => 'New students with their admission forms'],
            ['image' => 'campus/cricket-5',           'cat' => 'sports',     'size' => 'tall', 'title' => 'At the crease'],
            ['image' => 'campus/pitha-utshob-6',      'cat' => 'festivals',                    'title' => 'Pitha Utshob stalls'],
            ['image' => 'campus/computer-lab-2',      'cat' => 'classrooms',                   'title' => 'Digital-first learning'],
            ['image' => 'campus/boishakh-rally-1',    'cat' => 'festivals',  'size' => 'wide', 'title' => 'Mangal Shobhajatra'],
            ['image' => 'campus/sports-day-guests',   'cat' => 'sports',                       'title' => 'Sports day with guests'],
            ['image' => 'campus/indoor-ludo-2',       'cat' => 'games',                        'title' => 'Indoor games break'],
            ['image' => 'campus/football-3',          'cat' => 'sports',                       'title' => 'Chasing the ball'],
            ['image' => 'campus/pitha-utshob-2',      'cat' => 'festivals',                    'title' => 'Homemade pitha'],
            ['image' => 'campus/students-1',          'cat' => 'campus',                       'title' => 'Students at MCC'],
            ['image' => 'campus/cricket-2',           'cat' => 'sports',                       'title' => 'Team spirit'],
            ['image' => 'campus/pitha-utshob-5',      'cat' => 'festivals',                    'title' => 'Tasting at Pitha Utshob'],
            ['image' => 'campus/football-1',          'cat' => 'sports',                       'title' => 'Football after the rain'],
            ['image' => 'campus/cricket-6',           'cat' => 'sports',                       'title' => 'Batting practice'],
            ['image' => 'campus/pitha-utshob-7',      'cat' => 'festivals',                    'title' => 'A table full of pitha'],
            ['image' => 'campus/football-4',          'cat' => 'sports',                       'title' => 'Inter-house final'],
            ['image' => 'library',                    'cat' => 'classrooms',                   'title' => 'Library'],
            ['image' => 'cultural-program',           'cat' => 'festivals',                    'title' => 'Cultural programme'],
            ['image' => 'gaming-zone',                'cat' => 'games',                        'title' => 'Indoor games zone'],
            ['image' => 'some-students',              'cat' => 'campus',     'size' => 'wide', 'title' => 'The MCC family'],
        ],
    ],

    'faq' => [
        ['q' => 'Which classes are open for admission?', 'a' => 'School admission is open from Play Group to Class 9. College admission is open for Class XI (HSC) in Science, Business Studies and Humanities.'],
        ['q' => 'How do I apply?', 'a' => 'Admission is done in the MCC Hello Future app. Download it free from Google Play, choose School or College, fill in the form, upload documents and pay the fee, all in a few minutes. Need help? Call or visit our admission office in Savar.'],
        ['q' => 'What is the eligibility for HSC?', 'a' => 'For the 2026–27 session the revised minimum SSC GPA is 2.00 for Science and 1.50 for Business Studies or Humanities, plus a short entrance interview.'],
        ['q' => 'How much are the fees? Are scholarships available?', 'a' => 'The current application and admission fees for each class are shown in the MCC Hello Future app when you apply. Scholarships and waivers are available for meritorious and financially challenged students. Ask the admission office for details.'],
        ['q' => 'Which documents do I need?', 'a' => 'For HSC: the original SSC mark sheet with 2 photocopies, the headmaster’s testimonial, photocopies of the SSC admit and registration cards, the birth certificate, 4 passport and 2 stamp-size photos of the student, and 2 passport photos of the guardian. For school classes, the application form lists the exact documents for the class you choose.'],
        ['q' => 'How can I track my application?', 'a' => 'Sign in to the MCC Hello Future app to see your application status in real time, from review to approval and payment.'],
    ],
];
