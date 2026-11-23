@extends('frontend.layouts.app')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $p = $person;
    $isStaff = $group === 'staff';
    $listRoute = $isStaff ? 'frontend.staff_info' : 'frontend.teacher_info';
    $listLabel = $isStaff ? 'Staff' : 'Teachers';
    $hasVideo = !empty($p['video']) && file_exists(public_path($p['video']));
    $dept = $p['department'] ?? null;
    $hasPoster = !empty($p['poster']);
    $icon = $dept ? config('faculty.department_icons.' . $dept, 'fa-user') : ($isStaff ? 'fa-id-badge' : 'fa-chalkboard-user');
    $words = preg_split('/\s+/', trim($p['name']));
    $last = array_pop($words);
    $firstSentence = $p['lead'] ?? (!empty($p['bio']) ? \Illuminate\Support\Str::limit($p['bio'], 160) : $p['role'] . ' at Maestro Crown School & College, Savar.');
    $facts = array_values(array_filter([
        $dept ? ['icon' => $icon, 'label' => 'Department', 'value' => $dept] : ['icon' => $icon, 'label' => 'Role', 'value' => $p['role']],
        !empty($p['subjects']) ? ['icon' => 'fa-book-open', 'label' => count($p['subjects']) > 1 ? 'Subjects' : 'Subject', 'value' => implode(' · ', $p['subjects'])] : null,
        !empty($p['education'][0]) ? ['icon' => 'fa-building-columns', 'label' => 'Studied at', 'value' => $p['education'][0]['institution']] : null,
        !empty($p['experience']) ? ['icon' => 'fa-briefcase', 'label' => 'Previously', 'value' => $p['experience']] : null,
        !empty($p['alumnus']) ? ['icon' => 'fa-graduation-cap', 'label' => 'MCC connection', 'value' => 'Alumnus (SSC & HSC)'] : null,
    ]));
@endphp

@section('title', $p['name'] . ', ' . $p['role'] . ' | Maestro Crown School & College')
@section('meta_description', \Illuminate\Support\Str::limit($p['bio'] ?? ($p['name'] . ', ' . $p['role'] . ' at Maestro Crown School & College, Savar, Dhaka.'), 155))

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/profile.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
{{-- ── Hero ─────────────────────────────────────────────── --}}
<section class="pf-hero" aria-labelledby="pf-name">
    <div class="pf-hero-bg" aria-hidden="true"><span></span><span></span></div>
    <div class="container pf-hero-grid">
        <div class="pf-hero-copy">
            <nav class="pf-crumbs" aria-label="Breadcrumb" data-reveal>
                <a href="{{ route('landing') }}">Home</a>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                <a href="{{ route('frontend.about') }}">About</a>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                <a href="{{ route($listRoute) }}">{{ $listLabel }}</a>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                <span aria-current="page">{{ $p['name'] }}</span>
            </nav>
            <span class="eyebrow" data-reveal>{{ $p['tag'] ?? ($isStaff ? 'Our staff' : 'Meet our faculty') }}</span>
            <h1 class="pf-name" id="pf-name" data-split>{{ implode(' ', $words) }} <em>{{ $last }}</em></h1>
            <p class="pf-role" data-reveal>{{ $p['role'] }}@if ($dept && !\Illuminate\Support\Str::contains($p['role'], $dept)) <span aria-hidden="true">·</span> {{ $dept }}@endif</p>
            <p class="pf-lead" data-reveal>{{ $firstSentence }}</p>
            <div class="pf-actions" data-reveal>
                @if ($hasVideo)
                    <a href="#intro" class="btn btn-primary btn-lg" data-magnetic><i class="fa-solid fa-play"></i> Watch introduction</a>
                @endif
                <a href="{{ route($listRoute) }}" class="btn btn-outline btn-lg"><i class="fa-solid fa-arrow-left"></i> All {{ strtolower($listLabel) }}</a>
            </div>
        </div>

        <figure class="pf-portrait tp-portrait" data-clip>
            @if ($hasPoster)
                @include('frontend.landing.partials._img', ['name' => $p['poster'], 'alt' => $p['name'] . ', ' . $p['role'], 'sizes' => '(max-width: 1023px) 90vw, 440px', 'eager' => true])
            @else
                @include('frontend.partials.portrait', ['name' => $p['name'], 'class' => 'portrait--hero'])
            @endif
            <figcaption class="pf-portrait-card">
                <span class="tp-card-icon"><i class="fa-solid {{ $icon }}"></i></span>
                <span><strong>{{ $dept ?? ($isStaff ? 'Our staff' : 'Our faculty') }}</strong>Maestro Crown School &amp; College</span>
            </figcaption>
        </figure>
    </div>
</section>

{{-- ── Quick facts ──────────────────────────────────────── --}}
<section class="pf-stats-wrap" aria-label="Quick facts">
    <div class="container">
        <ul class="tp-facts tp-facts--{{ count($facts) }}" data-stagger>
            @foreach ($facts as $f)
                <li data-reveal>
                    <span class="tp-fact-icon"><i class="fa-solid {{ $f['icon'] }}"></i></span>
                    <span><small>{{ $f['label'] }}</small><strong>{{ $f['value'] }}</strong></span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- ── Introduction: video (or photo) + about ───────────── --}}
<section class="tp-intro section-pad" id="intro" aria-labelledby="tp-about-title">
    <div class="container tp-intro-grid">
        <div class="tp-media" data-reveal>
            @if ($hasVideo)
                <div class="tp-player">
                    <video controls playsinline preload="none"
                           poster="{{ asset('frontend/mcc/img/' . $p['poster'] . '.webp') }}"
                           aria-label="Introduction video: {{ $p['name'] }}">
                        <source src="{{ asset($p['video']) }}" type="video/mp4">
                    </video>
                </div>
                <p class="tp-media-note"><i class="fa-solid fa-video"></i> Introduction video · {{ $p['name'] }}</p>
            @elseif ($hasPoster)
                <div class="tp-player tp-player--image">
                    @include('frontend.landing.partials._img', ['name' => $p['poster'], 'alt' => $p['name'], 'sizes' => '(max-width: 1023px) 90vw, 420px'])
                </div>
            @else
                <div class="tp-player tp-player--image">
                    @include('frontend.partials.portrait', ['name' => $p['name'], 'class' => 'portrait--hero tp-portrait-ph'])
                </div>
            @endif
        </div>

        <article class="tp-about">
            <span class="eyebrow" data-reveal>About</span>
            <h2 class="sec-title" id="tp-about-title" data-reveal>Meet <em>{{ $p['name'] }}</em></h2>
            @if (!empty($p['bio']))
                <p class="tp-bio" data-reveal>{{ $p['bio'] }}</p>
            @else
                <p class="tp-bio" data-reveal>{{ $p['name'] }} is {{ \Illuminate\Support\Str::startsWith(strtolower($p['role']), ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a' }} {{ $p['role'] }} at Maestro Crown School &amp; College{{ $dept && !$isStaff ? ', in the ' . $dept . ' department' : '' }}.</p>
                <p class="tp-soon" data-reveal><i class="fa-solid fa-clock"></i> A detailed profile with photo and introduction is coming soon.</p>
            @endif

            @if (!empty($p['education']))
                <div class="tp-block" data-reveal>
                    <h3><i class="fa-solid fa-graduation-cap"></i> Education</h3>
                    <ol class="tp-timeline">
                        @foreach ($p['education'] as $e)
                            <li><strong>{{ $e['degree'] }}</strong><span>{{ $e['institution'] }}</span></li>
                        @endforeach
                    </ol>
                </div>
            @endif

            @if (!empty($p['experience']))
                <div class="tp-block" data-reveal>
                    <h3><i class="fa-solid fa-briefcase"></i> Experience</h3>
                    <ol class="tp-timeline">
                        <li><strong>{{ $p['role'] }}</strong><span>Maestro Crown School &amp; College · Present</span></li>
                        <li><strong>{{ \Illuminate\Support\Str::before($p['experience'], ',') }}</strong><span>{{ trim(\Illuminate\Support\Str::after($p['experience'], ',')) }}</span></li>
                    </ol>
                </div>
            @endif

            @if (!empty($p['subjects']))
                <div class="tp-block" data-reveal>
                    <h3><i class="fa-solid fa-book-open"></i> Teaches</h3>
                    <ul class="tp-chips">@foreach ($p['subjects'] as $s)<li>{{ $s }}</li>@endforeach</ul>
                </div>
            @endif
        </article>
    </div>
</section>

{{-- ── More people ──────────────────────────────────────── --}}
@if ($others->isNotEmpty())
<section class="tp-more section-pad" aria-labelledby="tp-more-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">{{ $isStaff ? 'Our team' : 'Our faculty' }}</span>
                <h2 class="sec-title" id="tp-more-title">Meet more of <em>{{ $isStaff ? 'our team' : 'our teachers' }}</em></h2>
            </div>
            <a href="{{ route($listRoute) }}" class="link-arrow">All {{ strtolower($listLabel) }} <i class="fa-solid fa-arrow-right"></i></a>
        </header>
        <div class="pc-grid pc-grid--compact" data-stagger>
            @foreach ($others->take(4) as $o)
                <div class="pc-cell">@include('frontend.partials.person-card', ['p' => $o])</div>
            @endforeach
        </div>
    </div>
</section>
@endif

@include('frontend.partials.cta-band')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
