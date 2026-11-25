@extends('frontend.layouts.app')

@section('title', 'About Us | Maestro Crown School & College, Savar')
@section('meta_description', 'Founded in 2014, Maestro Crown School & College in Savar, Dhaka is a joint UK–Bangladesh institution for School (Play Group–Class 9) and College (HSC). Discover our story, mission, values and campus life.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $mc = config('landing');

    $stats = [
        ['value' => 2014, 'suffix' => '',  'label' => 'Year founded', 'plain' => true],
        ['value' => 1300, 'suffix' => '+', 'label' => 'Active learners'],
        ['value' => 50,   'suffix' => '+', 'label' => 'Expert faculty'],
        ['value' => 2,    'suffix' => '',  'label' => 'Campuses'],
    ];

    $values = [
        ['icon' => 'fa-award',        'title' => 'Academic excellence',    'desc' => 'Subject experts who earn their students’ respect through true mastery of what they teach.'],
        ['icon' => 'fa-heart',        'title' => 'Character & ethics',     'desc' => 'Education that shapes integrity as carefully as it shapes intellect.'],
        ['icon' => 'fa-scale-balanced', 'title' => 'Discipline & fairness', 'desc' => 'A transparent, merit-based campus with zero tolerance for practices that undermine fairness.'],
        ['icon' => 'fa-flag',         'title' => 'Responsible citizenship', 'desc' => 'Confident, principled young people ready to contribute to society and the nation.'],
    ];

    $apart = [
        ['icon' => 'fa-chalkboard-user', 'title' => 'Expert faculty',        'desc' => 'A dynamic team of educators, many of them graduates of Bangladesh’s leading public universities.', 'image' => 'campus/classroom-hall-2'],
        ['icon' => 'fa-flask-vial',      'title' => 'Modern facilities',     'desc' => 'Science and computer labs, a 2,500+ book library and spacious multimedia classrooms.',            'image' => 'campus/computer-lab-1'],
        ['icon' => 'fa-masks-theater',   'title' => 'Growth beyond books',   'desc' => 'Sports, debate, festivals and a four-house system that builds leadership and teamwork.',          'image' => 'campus/boishakh-1'],
        ['icon' => 'fa-mobile-screen-button', 'title' => 'A connected campus', 'desc' => 'Admission, attendance, results and fees in the MCC Hello Future app for students, guardians and teachers.', 'image' => 'campus/students-2'],
    ];

    // The four houses students are divided into (colours from the house flags).
    $houses = [
        ['name' => 'Kazi Nazrul Islam',    'colour' => 'Yellow', 'hex' => '#E2A900'],
        ['name' => 'Begum Rokeya',         'colour' => 'Green',  'hex' => '#2E8B57'],
        ['name' => 'Jafar Ullah Patwary',  'colour' => 'Blue',   'hex' => '#2F6FB7'],
        ['name' => 'Dr. Kamrul Ahsan',     'colour' => 'Red',    'hex' => '#C62828'],
    ];

    $life = [
        ['icon' => 'fa-microphone-lines', 'title' => 'Clubs & co-curriculars', 'desc' => 'Activities that bring out hidden talent and build confidence and leadership.', 'tags' => ['Debate', 'Recitation', 'Music', 'Dance', 'Science club', 'Qirat']],
        ['icon' => 'fa-trophy',           'title' => 'Competitions',           'desc' => 'Held regularly through the year, inside the houses and across the campus.', 'tags' => ['Art', 'Essay writing', 'Poetry recitation', 'Spelling', 'Impromptu speech', 'Debate']],
        ['icon' => 'fa-atom',             'title' => 'Olympiads & Science Fair', 'desc' => 'Students compete in subject Olympiads and present projects at our annual Science Fair.', 'tags' => ['Maths', 'Physics', 'Chemistry', 'Biology', 'Science Fair']],
        ['icon' => 'fa-book-open-reader', 'title' => 'Library',                'desc' => 'About 2,500 books plus journals, magazines and daily newspapers, with borrowing for every student.', 'tags' => ['2,500+ books', 'Journals', 'Newspapers']],
        ['icon' => 'fa-newspaper',        'title' => 'Annual magazine',        'desc' => 'Published every year since our founding, showcasing students’ writing and artwork.', 'tags' => ['Articles', 'Artwork', 'Poetry']],
        ['icon' => 'fa-futbol',           'title' => 'Games & sports',         'desc' => 'Indoor and outdoor games all year round to build stamina and keep minds fresh.', 'tags' => ['Cricket', 'Football', 'Rugby', 'Table tennis', 'Billiards']],
    ];

    $mosaic = [
        ['campus/cricket-team', 'Girls’ cricket team'], ['campus/pitha-utshob-3', 'Pitha Utshob'], ['campus/classroom-hall-1', 'Classroom hall'],
        ['campus/indoor-chess', 'Indoor games'], ['campus/study-tour-2', 'Study tour'], ['campus/iftar-mahfil', 'Iftar Mahfil'],
    ];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['About MCC']],
    'eyebrow' => 'About MCC',
    'title'   => 'Built on <em>mastery</em> and <em>honour.</em>',
    'lead'    => 'Since 2014, Maestro Crown School & College has guided students in Savar from their first day of school to their HSC, with an international outlook, a national curriculum and a deep respect for character.',
    'chips'   => ['Est. 2014', 'EIIN 136876', 'College Code 1933', 'Savar, Dhaka'],
    'actions' => [
        ['label' => 'Our story', 'href' => '#story', 'icon' => 'fa-arrow-down', 'style' => 'primary'],
        ['label' => 'Meet our leaders', 'href' => '#leadership', 'style' => 'outline'],
    ],
    'media'   => ['type' => 'collage', 'images' => ['campus/campus-front', 'campus/classroom-hall-1'], 'badge' => [now()->year - 2014 . '+', 'years of<br>excellence']],
])

{{-- Key figures --}}
<section class="stat-band-wrap" aria-label="MCC at a glance">
    <div class="container">
        <ul class="stat-band" data-stagger>
            @foreach ($stats as $s)
                <li data-reveal>
                    <strong><span data-count="{{ $s['value'] }}" @if(!empty($s['plain'])) data-plain @endif>{{ $s['value'] }}</span>{{ $s['suffix'] }}</strong>
                    <span>{{ $s['label'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- Story --}}
<section class="ab-story section-pad" id="story" aria-labelledby="story-title">
    <div class="container ab-story-grid">
        <div>
            <span class="eyebrow" data-reveal>Our story</span>
            <h2 class="sec-title" id="story-title" data-reveal>A teacher’s dream, <em>a nation’s future</em></h2>
            <div class="prose">
                <p class="lead-p" data-reveal>Maestro Crown School & College began with a simple conviction from our founder, Dr. Kamrul Ahsan: shaping minds and guiding young people is one of the most powerful ways to build a nation.</p>
                <p data-reveal>Our voyage began on 15 June 2014, as a joint venture between England and Bangladesh. Since then MCC has grown from a single campus into two, welcoming more than 1,300 learners from Play Group to Class XII. Our teachers combine an international approach with full compliance with the national board curriculum.</p>
                <p data-reveal>Today we remain committed to the same promise we made on day one: a disciplined, transparent and caring place to learn, where academic excellence and moral integrity grow side by side.</p>
            </div>
        </div>
        <aside class="ab-name" data-reveal aria-label="The meaning of our name">
            <span class="ab-name-label">What’s in a name</span>
            <div class="ab-name-row">
                <strong>Maestro</strong>
                <p>Mastery and excellence: teachers who truly know their subjects.</p>
            </div>
            <div class="ab-name-row">
                <strong>Crown</strong>
                <p>Honour and distinction: the respect that great teaching earns.</p>
            </div>
            <img src="{{ asset('frontend/mcc/img/logo-256.webp') }}" alt="" width="96" height="96" class="ab-name-crest">
        </aside>
    </div>
</section>

{{-- Mission & vision --}}
<section class="ab-mv" aria-label="Mission and vision">
    <div class="container ab-mv-grid" data-stagger>
        <article class="ab-mv-card" data-reveal>
            <span class="ab-mv-icon"><i class="fa-solid fa-compass"></i></span>
            <span class="eyebrow eyebrow--plain">Our mission</span>
            <p>To provide an inclusive, innovative and rigorous learning environment that empowers every student to reach their full potential and contribute meaningfully to society.</p>
        </article>
        <article class="ab-mv-card ab-mv-card--dark" data-reveal>
            <span class="ab-mv-icon"><i class="fa-solid fa-eye"></i></span>
            <span class="eyebrow eyebrow--plain eyebrow--light">Our vision</span>
            <p>To be recognised as a centre of academic brilliance, shaping the leaders and innovators of tomorrow through quality education and character development.</p>
        </article>
    </div>
</section>

{{-- Values --}}
<section class="ab-values section-pad" aria-labelledby="values-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">What we stand for</span>
            <h2 class="sec-title" id="values-title">Four values, <em>every single day</em></h2>
        </header>
        <div class="card-grid card-grid--4" data-stagger>
            @foreach ($values as $i => $v)
                <article class="num-card" data-reveal>
                    <span class="num-card-n">0{{ $i + 1 }}</span>
                    <span class="num-card-icon"><i class="fa-solid {{ $v['icon'] }}"></i></span>
                    <h3>{{ $v['title'] }}</h3>
                    <p>{{ $v['desc'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- What sets us apart --}}
<section class="ab-apart section-pad" aria-labelledby="apart-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Why families choose MCC</span>
                <h2 class="sec-title" id="apart-title">What sets us <em>apart</em></h2>
            </div>
            <p class="sec-sub">Small things done well, every day, by people who care about your child’s future.</p>
        </header>
        <div class="apart-grid" data-stagger>
            @foreach ($apart as $a)
                <article class="apart-card" data-reveal>
                    <div class="apart-img">@include('frontend.landing.partials._img', ['name' => $a['image'], 'alt' => '', 'sizes' => '(max-width: 768px) 100vw, 25vw'])</div>
                    <div class="apart-body">
                        <span class="apart-icon"><i class="fa-solid {{ $a['icon'] }}"></i></span>
                        <h3>{{ $a['title'] }}</h3>
                        <p>{{ $a['desc'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Beyond the classroom --}}
<section class="ab-beyond section-pad" id="beyond" aria-labelledby="beyond-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Beyond the classroom</span>
                <h2 class="sec-title" id="beyond-title">Talent finds <em>its stage here</em></h2>
            </div>
            <p class="sec-sub">A wide range of activities helps every student discover what they are good at, and grow the confidence to lead.</p>
        </header>
        <div class="life-grid" data-stagger>
            @foreach ($life as $l)
                <article class="life-card" data-reveal>
                    <span class="life-icon"><i class="fa-solid {{ $l['icon'] }}"></i></span>
                    <h3>{{ $l['title'] }}</h3>
                    <p>{{ $l['desc'] }}</p>
                    <ul class="life-tags">@foreach ($l['tags'] as $t)<li>{{ $t }}</li>@endforeach</ul>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- House system --}}
<section class="ab-houses section-pad" id="houses" aria-labelledby="houses-title">
    <div class="container ab-houses-grid">
        <header class="sec-head" data-reveal>
            <span class="eyebrow eyebrow--light">House system</span>
            <h2 class="sec-title sec-title--light" id="houses-title">Four houses, <em>one spirit</em></h2>
            <p class="sec-sub sec-sub--light">To create a healthy, spirited competitive atmosphere, students are divided equally into four houses. They compete in sports, debate, culture and academics all year, and learn teamwork and leadership along the way.</p>
        </header>
        <ul class="house-list" data-stagger>
            @foreach ($houses as $h)
                <li class="house" style="--house: {{ $h['hex'] }}" data-reveal>
                    <span class="house-flag" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span>
                    <span class="house-body">
                        <strong>{{ $h['name'] }}</strong>
                        <span>{{ $h['colour'] }} House</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- Campus life mosaic --}}
<section class="ab-life section-pad" aria-labelledby="life-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Campus life</span>
                <h2 class="sec-title" id="life-title">Where learning <em>feels alive</em></h2>
            </div>
            <a href="{{ route('frontend.gallery') }}" class="link-arrow">Visit the gallery <i class="fa-solid fa-arrow-right"></i></a>
        </header>
        <div class="mosaic">
            @foreach ($mosaic as $i => [$img, $label])
                <figure class="mosaic-item mosaic-item--{{ $i }}" data-clip>
                    @include('frontend.landing.partials._img', ['name' => $img, 'alt' => $label, 'sizes' => '(max-width: 768px) 50vw, 33vw'])
                    <figcaption>{{ $label }}</figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

{{-- Leadership teaser --}}
<section class="ab-leaders section-pad" id="leadership" aria-labelledby="leaders-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">Leadership</span>
            <h2 class="sec-title" id="leaders-title">The people <em>behind MCC</em></h2>
        </header>
        <div class="leader-duo" data-stagger>
            <a href="{{ route('frontend.chairman_profile') }}" class="leader-tile" data-reveal>
                @include('frontend.partials.portrait', ['name' => 'Dr. Kamrul Ahsan', 'image' => 'campus/chairman-face', 'class' => 'leader-tile-photo', 'sizes' => '120px'])
                <span class="leader-tile-body">
                    <small>Founder &amp; Chairman</small>
                    <strong>Dr. Kamrul Ahsan</strong>
                    <span>“Shaping minds and guiding young people is one of the most powerful ways to build a nation.”</span>
                </span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <a href="{{ route('frontend.message_principal') }}" class="leader-tile" data-reveal>
                @include('frontend.partials.portrait', ['name' => 'Md. Shoeb Khan', 'image' => 'campus/principal', 'class' => 'leader-tile-photo', 'sizes' => '120px'])
                <span class="leader-tile-body">
                    <small>Principal</small>
                    <strong>Md. Shoeb Khan</strong>
                    <span>“Education is the foundation of a brighter future.”</span>
                </span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <p class="ab-leaders-more" data-reveal>
            <a href="{{ route('frontend.governing_body') }}" class="link-arrow">Governing Body <i class="fa-solid fa-arrow-right"></i></a>
            <a href="{{ route('frontend.teacher_info') }}" class="link-arrow">Our teachers <i class="fa-solid fa-arrow-right"></i></a>
        </p>
    </div>
</section>

@include('frontend.partials.cta-band')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
