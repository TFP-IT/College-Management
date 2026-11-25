@extends('frontend.layouts.app')

@section('title', 'Research & Development | Maestro Crown School & College')
@section('meta_description', 'Research & Development at Maestro Crown School & College, Savar: AI in learning, a Robotics Club, drone workshops, skills workshops and science Olympiads that prepare students for the 21st century.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $rd = config('academics.research');
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['Academics', route('frontend.programs')], ['Research & Development']],
    'eyebrow' => 'Research & Development',
    'title'   => 'Where curiosity becomes <em>innovation</em>',
    'lead'    => 'As artificial intelligence and new technology reshape the world, MCC is preparing students for a future that is exciting and unpredictable, through research, robotics and hands-on learning.',
    'chips'   => ['AI in learning', 'Robotics Club', 'Hands-on STEM'],
    'actions' => [
        ['label' => 'See our work', 'href' => '#work', 'icon' => 'fa-arrow-down', 'style' => 'primary'],
        ['label' => 'Programs', 'href' => route('frontend.programs'), 'style' => 'outline'],
    ],
    'media'   => ['type' => 'collage', 'images' => ['campus/rd-drone-workshop', 'campus/rd-quadcopter-demo'], 'badge' => ['R&D','built into<br>every class']],
])

{{-- Why R&D --}}
<section class="ab-story section-pad" aria-labelledby="rd-why-title">
    <div class="container ab-story-grid">
        <div>
            <span class="eyebrow" data-reveal>Why it matters</span>
            <h2 class="sec-title" id="rd-why-title" data-reveal>Education that keeps <em>pace with the world</em></h2>
            <div class="prose">
                <p class="lead-p" data-reveal>In the rapidly changing landscape of the 21st century, research and development have become indispensable to modern education. Institutions that want to prepare students for tomorrow have to adapt and innovate today.</p>
                <p data-reveal>With the generous support and forward-thinking vision of our Chairman, MCC has established a dedicated Research &amp; Development initiative. It brings current research into our academic framework and turns the campus into a place where new ideas about teaching and learning are tested, refined and put to work.</p>
                <p data-reveal>Our R&amp;D work focuses on the power of technology, especially artificial intelligence, to improve learning outcomes and make every lesson more engaging, without losing sight of our core values of integrity and academic rigour.</p>
            </div>
        </div>
        <aside class="ab-name rd-pillars" data-reveal aria-label="Our approach">
            <span class="ab-name-label">Our approach</span>
            <div class="ab-name-row">
                <strong>Research</strong>
                <p>Study what works in classrooms here and around the world.</p>
            </div>
            <div class="ab-name-row">
                <strong>Build</strong>
                <p>Turn ideas into tools, projects and better lessons.</p>
            </div>
            <div class="ab-name-row">
                <strong>Share</strong>
                <p>Bring every student and teacher along for the journey.</p>
            </div>
            <img src="{{ asset('frontend/mcc/img/logo-256.webp') }}" alt="" width="96" height="96" class="ab-name-crest">
        </aside>
    </div>
</section>

{{-- Objectives --}}
<section class="ab-values section-pad" aria-labelledby="rd-goals-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">What the initiative is designed to do</span>
            <h2 class="sec-title" id="rd-goals-title">Four goals, <em>one direction</em></h2>
        </header>
        <div class="card-grid card-grid--4" data-stagger>
            @foreach ($rd['objectives'] as $i => $o)
                <article class="num-card" data-reveal>
                    <span class="num-card-n">0{{ $i + 1 }}</span>
                    <span class="num-card-icon"><i class="fa-solid {{ $o['icon'] }}"></i></span>
                    <h3>{{ $o['title'] }}</h3>
                    <p>{{ $o['desc'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Focus areas --}}
<section class="rd-focus section-pad" aria-labelledby="rd-focus-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Focus areas</span>
                <h2 class="sec-title" id="rd-focus-title">Where we are <em>exploring</em></h2>
            </div>
            <p class="sec-sub">From artificial intelligence to a working drone: practical ways for students to understand how technology works, and how to make it work for them.</p>
        </header>
        <ul class="rd-focus-grid" data-stagger>
            @foreach ($rd['focus'] as $f)
                <li class="rd-focus-item" data-reveal>
                    <span class="rd-focus-icon"><i class="fa-solid {{ $f['icon'] }}"></i></span>
                    <div>
                        <h3>{{ $f['title'] }}</h3>
                        <p>{{ $f['desc'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- Our work --}}
<section class="rd-work section-pad" id="work" aria-labelledby="rd-work-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">In practice</span>
                <h2 class="sec-title" id="rd-work-title">Learning by <em>building</em></h2>
            </div>
            <a href="{{ route('frontend.gallery') }}" class="link-arrow">More in the gallery <i class="fa-solid fa-arrow-right"></i></a>
        </header>
        <div class="rd-work-grid" data-stagger>
            @foreach ($rd['work'] as $w)
                <article @class(['rd-card', 'rd-card--wide' => ($w['size'] ?? '') === 'wide']) data-reveal>
                    <div @class(['rd-card-img', 'rd-card-img--contain' => !empty($w['contain'])])>
                        @include('frontend.landing.partials._img', ['name' => $w['image'], 'alt' => $w['title'], 'sizes' => '(max-width: 768px) 100vw, 50vw'])
                        <span class="rd-card-tag">{{ $w['tag'] }}</span>
                    </div>
                    <div class="rd-card-body">
                        <h3>{{ $w['title'] }}</h3>
                        <p>{{ $w['desc'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Statement --}}
<section class="rd-statement" aria-label="Our commitment">
    <div class="container">
        <div class="rd-statement-card" data-reveal>
            <i class="fa-solid fa-quote-left" aria-hidden="true"></i>
            <p>This initiative is a milestone in our journey towards educational excellence: a commitment to stay at the <em>forefront of innovation</em> while keeping our core values of integrity and academic rigour.</p>
            <span>Maestro Crown School &amp; College</span>
        </div>
    </div>
</section>

@include('frontend.partials.cta-band')
@include('frontend.partials.next-links', ['set' => 'academics'])
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
