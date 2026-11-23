<?php

/*
|--------------------------------------------------------------------------
| Chairman's profile (public website)
|--------------------------------------------------------------------------
| Content for /chairmans-profile. Source: the official Versatilo Group profile
| (versatilo.group/chairman_profile), edited for a school audience.
| Images live under public/frontend/mcc/img (no extension).
| Later this can move to the CMS without touching the view.
*/

return [
    'name' => 'Dr. Kamrul Ahsan',
    'postnominal' => 'FRSA',
    'roles' => [
        'Global Chairman, Versatilo Group',
        'Chairman, Maestro Crown School & College',
    ],
    'tags' => ['Tech entrepreneur', 'Global strategist', 'Educationist', 'Social reformer', 'Media personality'],
    'lead' => 'A former army officer who became a global entrepreneur. Since 2006 he has built more than 50 businesses across 14 countries, while staying committed to education, public service and the welfare of Bangladeshis at home and abroad.',

    'portrait' => 'campus/chairman',
    'portrait_card' => 'Fellow, Royal Society of Arts (UK)',

    'stats' => [
        ['value' => 50, 'suffix' => '+', 'label' => 'Business entities'],
        ['value' => 14, 'suffix' => '', 'label' => 'Countries of operation'],
        ['value' => 15, 'suffix' => '', 'label' => 'Nationalities in his teams'],
        ['value' => 500, 'suffix' => '+', 'label' => 'International media features'],
    ],

    'glance' => [
        'Global role' => 'Global Chairman, Versatilo Group',
        'At MCC' => 'Chairman, Maestro Crown School & College',
        'Fellowship' => 'Royal Society of Arts (FRSA), UK',
        'Education' => 'MBA · DBA / PhD (UK) · Oxford climate studies',
        'Headquarters' => 'United Kingdom · Malaysia · Oman · Bangladesh',
    ],

    'bio' => [
        'Dr. Kamrul Ahsan began his career in uniform. He graduated with distinction from the Bangladesh Military Academy and was commissioned as an officer in the Bangladesh Army in 1997, an experience that shaped the discipline and sense of duty that still guide his work.',
        'In 2003 he moved to the United Kingdom to continue his studies, completing a Post Graduate Diploma in Management, an MBA and a DBA/PhD, followed by climate change studies at the University of Oxford. He is a Fellow of the Royal Society of Arts.',
        'Since 2006 he has built an international group of more than 50 business entities, with licensed operations across Europe, the Middle East, South and Southeast Asia and Africa, and teams drawn from 15 nationalities. His ventures span technology, financial technology, aviation, consumer brands, energy and manufacturing.',
        'Beyond business, he has advised governments and royal households, pioneered investigative journalism in Bangladesh, and spent more than two decades supporting Bangladeshi workers in the Gulf and ASEAN regions. As Chairman of Maestro Crown School & College, he brings that global outlook to our classrooms in Savar.',
    ],
    'pull_quote' => 'If you are tired of technology, you are tired of life.',

    'journey' => [
        ['year' => '1997', 'title' => 'Commissioned in the Bangladesh Army', 'text' => 'Joins as an officer after graduating with distinction from the Bangladesh Military Academy.'],
        ['year' => '2003', 'title' => 'Higher studies in the United Kingdom', 'text' => 'Completes a PGD in Management, an MBA and a DBA/PhD, and later climate change studies at Oxford.'],
        ['year' => '2005', 'title' => 'Bangladesh at the La Liga Youth Tournament', 'text' => 'Initiates Bangladesh’s participation in the tournament in Malaysia through his ZafRani Sporting Club.'],
        ['year' => '2006', 'title' => 'Building a global group', 'text' => 'Starts international operations, which now span 14 countries and teams from 15 nationalities.'],
        ['year' => '2014', 'title' => 'Tap n Pay', 'text' => 'Launches an NFC card-based e-wallet in Bangladesh.'],
        ['year' => '2017', 'title' => 'Pocket Pay', 'text' => 'Introduces wallet-to-wallet remittance in the UAE and Malaysia.'],
        ['year' => 'Today', 'title' => 'Global Chairman', 'text' => 'Leads more than 50 business entities and serves as Chairman of Maestro Crown School & College.'],
    ],

    'education' => [
        ['degree' => 'Graduated with distinction', 'where' => 'Bangladesh Military Academy'],
        ['degree' => 'Post Graduate Diploma in Management', 'where' => 'United Kingdom'],
        ['degree' => 'Master of Business Administration (MBA)', 'where' => 'United Kingdom'],
        ['degree' => 'Doctor of Business Administration (DBA / PhD)', 'where' => 'United Kingdom'],
        ['degree' => 'Climate change studies', 'where' => 'University of Oxford'],
    ],

    'rsa' => [
        'title' => 'Fellow of the Royal Society of Arts',
        'text' => 'Founded in London in 1754 and granted its Royal Charter in 1847, the RSA brings together Fellows from 80 countries who work on practical solutions to social challenges.',
        'fellows' => ['Benjamin Franklin', 'Adam Smith', 'Charles Dickens', 'Nelson Mandela', 'Stephen Hawking', 'Sir David Attenborough', 'Sir Tim Berners-Lee'],
    ],

    'service' => [
        'Commissioned Officer, Bangladesh Army',
        'Past President, UK International Student Association',
        'Past Secretary General, UK Association for Human Rights',
        'President, The Omniverse Chamber of Commerce (UK)',
    ],

    'advisory' => [
        'Honorary Consul of the Republic of Guinea',
        'Adviser on International Relations to the Vice President of the Maldives',
        'Special Envoy for Technology & Innovation to the Maldives Minister of Tourism',
        'Adviser on International Relations to Malaysia’s Minister of Housing and Local Government',
        'Adviser to members of the royal families of Oman, the UAE, Bahrain and Malaysia',
    ],

    'footprint' => ['Bangladesh', 'United Kingdom', 'Turkey', 'Malaysia', 'Vietnam', 'Qatar', 'Kuwait', 'Bahrain', 'UAE', 'Oman', 'Saudi Arabia', 'India', 'Sri Lanka', 'Nigeria'],

    'ventures' => [
        ['icon' => 'fa-microchip', 'sector' => 'Technology & digital platforms', 'items' => [
            ['name' => 'Hello Superstars', 'desc' => 'A real-time digital ecosystem connecting stars with their followers'],
            ['name' => 'Ummah Stars', 'desc' => 'A unified social platform for the global Muslim community'],
            ['name' => 'Helmet Stars', 'desc' => 'A secure social platform for military audiences'],
            ['name' => 'mGES', 'desc' => 'Migration Governance Ecosystem technology'],
            ['name' => 'HR DNA', 'desc' => 'Tracing and empowering human capital'],
            ['name' => 'JAGO-RISE Bangladesh', 'desc' => 'E-shadow governance and civic accountability'],
            ['name' => 'Space Rose', 'desc' => 'A private rocket technology initiative in Bangladesh'],
        ]],
        ['icon' => 'fa-plane-departure', 'sector' => 'Fintech, aviation & robotics', 'items' => [
            ['name' => 'Tap n Pay', 'desc' => 'NFC card-based e-wallet in Bangladesh (2014)'],
            ['name' => 'Pocket Pay', 'desc' => 'Wallet-to-wallet remittance in the UAE and Malaysia (2017)'],
            ['name' => 'Aviation Link', 'desc' => 'International cargo airline, founded with Aviation Link, USA'],
            ['name' => 'Airline sales', 'desc' => 'General Sales Agent for leading Middle Eastern airlines'],
            ['name' => 'Robotics & drones', 'desc' => 'Bangladesh’s first humanoid robotics technology transfer and drone joint venture'],
        ]],
        ['icon' => 'fa-handshake', 'sector' => 'Global brands & joint ventures', 'items' => [
            ['name' => 'Mecca Cola (France)', 'desc' => 'The first halal beverage concept'],
            ['name' => 'Kova Paint (Vietnam)', 'desc' => 'Nano-technology paints'],
            ['name' => 'VietBean', 'desc' => 'A heritage Vietnamese coffee brand'],
            ['name' => 'Dil Roti', 'desc' => 'A Chinese roti heritage brand'],
            ['name' => 'Rubaiyat', 'desc' => 'A Persian and Arabian fine-dining chain'],
            ['name' => 'La Toya Jackson Lifestyle & Fashion', 'desc' => 'A partnership in London'],
        ]],
    ],

    'beyond' => [
        [
            'icon' => 'fa-tower-broadcast', 'eyebrow' => 'Media', 'title' => 'A voice for accountability',
            'image' => 'campus/chairman-media', 'alt' => 'Dr. Kamrul Ahsan presenting on ATN Bangla',
            'points' => [
                'Featured in more than 500 international outlets, including AP, NBC and FOX',
                'A pioneer of investigative journalism in Bangladesh',
                'Produced and hosted 100 episodes of “Bibeker Kache Prosno” on ATN Bangla, on crime and human rights',
                'Founded “The Mandate”, an English daily for diplomats and policymakers',
            ],
        ],
        [
            'icon' => 'fa-feather-pointed', 'eyebrow' => 'Writing', 'title' => 'Author and poet',
            'image' => 'campus/chairman-writing', 'alt' => 'A book of Bengali poetry by Dr. Kamrul Ahsan',
            'points' => [
                'Author of several Bengali novels and books of poetry',
                'Writes on management, innovation and good governance',
            ],
        ],
        [
            'icon' => 'fa-hand-holding-heart', 'eyebrow' => 'Social impact', 'title' => 'Serving communities',
            'image' => null, 'alt' => '',
            'points' => [
                'More than 20 years supporting Bangladeshi expatriates in the GCC and ASEAN regions',
                'Digital consular and welfare services through e-remittance',
                'Founder of the Zafar Bin Mohammed Al Patwary Foundation, a licensed charity in Bangladesh',
                'Support for colleges, mosques, sanitation, safe drinking water and roads',
            ],
        ],
        [
            'icon' => 'fa-futbol', 'eyebrow' => 'Sport & culture', 'title' => 'Opening doors for young athletes',
            'image' => 'campus/chairman-riding', 'alt' => 'Dr. Kamrul Ahsan horse riding',
            'points' => [
                'Founder of ZafRani Sporting Club, recognised by the Bangladesh Football Federation',
                'Initiated Bangladesh’s participation in the La Liga Youth Tournament, Malaysia (2005)',
                'ZafRani Sporting Club finished runners-up in the U16 tournament',
            ],
        ],
    ],

    'philosophy' => [
        'headline' => ['If you hate to be king,', 'be a kingmaker.'],
        'quotes' => [
            'When life gives you lemons, make lemonade.',
            'If you are tired of technology, you are tired of life.',
            'Whatever challenges come your way, Allah is there to resolve your problem, so keep praying.',
        ],
    ],
];
