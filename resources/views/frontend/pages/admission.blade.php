@extends('frontend.layouts.app')

@section('title', 'Admission ' . config('landing.session') . ' | Maestro Crown School & College')
@section('meta_description', 'Admission is open at Maestro Crown School & College, Savar, for School (Play Group to Class 9) and College (HSC). Apply in the MCC Hello Future app. Documents, eligibility, fees and FAQs.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $mc = config('landing');
    $app = $mc['app'];
    $contact = $mc['contact'];
    $docs = [
        ['icon' => 'fa-file-contract', 'title' => 'Academic documents', 'items' => [
            'Original SSC mark sheet / transcript',
            '2 photocopies of the SSC mark sheet',
            'Original testimonial from the headmaster',
            'SSC admit card & registration card (photocopy)',
        ]],
        ['icon' => 'fa-address-card', 'title' => 'Photos & ID', 'items' => [
            '4 passport-size photos (student)',
            '2 stamp-size photos (student)',
            '2 passport-size photos (parent / guardian)',
            'Birth certificate',
        ]],
    ];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['Admission']],
    'eyebrow' => 'Admission ' . $mc['session'] . ' · Now open',
    'title'   => 'Your journey at MCC <em>starts here</em>',
    'lead'    => 'Admission is open for School (Play Group to Class 9) and College (HSC). Apply in a few minutes from the ' . $app['name'] . ' app, then follow every step in real time.',
    'chips'   => ['School: Play Group – Class 9', 'College: HSC', 'Apply in the app'],
    'actions' => [
        ['label' => 'Apply in the app', 'href' => $app['play_store'], 'icon' => 'fa-brands fa-google-play', 'style' => 'primary', 'external' => true],
        ['label' => 'Requirements', 'href' => '#requirements', 'style' => 'outline'],
    ],
    'media'   => ['type' => 'collage', 'images' => ['campus/admission-poster-2026-27', 'campus/students-2'], 'badge' => ['2', 'sections<br>open']],
])

{{-- Choose a section --}}
<section class="ad-paths section-pad" aria-labelledby="ad-paths-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">Choose your section</span>
            <h2 class="sec-title" id="ad-paths-title">School or College, <em>we’ve got you</em></h2>
        </header>
        <div class="ad-path-grid" data-stagger>
            <article class="ad-path" data-reveal>
                <div class="ad-path-img">@include('frontend.landing.partials._img', ['name' => 'campus/classroom-hall-1', 'alt' => '', 'sizes' => '(max-width: 768px) 100vw, 50vw'])</div>
                <div class="ad-path-body">
                    <span class="ad-path-kicker"><i class="fa-solid fa-shapes"></i> School section</span>
                    <h3>Play Group – Class 9</h3>
                    <p>{{ $mc['school']['body'] }}</p>
                    <ul class="ad-path-list">
                        @foreach ($mc['school']['stages'] as $st)<li><strong>{{ $st['title'] }}</strong> {{ $st['classes'] }}</li>@endforeach
                    </ul>
                    <div class="ad-path-actions">
                        <a href="{{ $app['play_store'] }}" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa-brands fa-google-play"></i> Apply in the app</a>
                        <a href="{{ route('frontend.programs') }}" class="link-arrow">Programs <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </article>
            <article class="ad-path ad-path--college" data-reveal>
                <div class="ad-path-img">@include('frontend.landing.partials._img', ['name' => 'campus/computer-lab-3', 'alt' => '', 'sizes' => '(max-width: 768px) 100vw, 50vw'])</div>
                <div class="ad-path-body">
                    <span class="ad-path-kicker"><i class="fa-solid fa-graduation-cap"></i> College section</span>
                    <h3>Higher Secondary (HSC)</h3>
                    <p>{{ $mc['college']['body'] }}</p>
                    <ul class="ad-path-list">
                        @foreach ($mc['college']['groups'] as $g)<li><strong>{{ $g['title'] }}</strong> Class XI – XII</li>@endforeach
                    </ul>
                    <div class="ad-path-actions">
                        <a href="{{ $app['play_store'] }}" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa-brands fa-google-play"></i> Apply in the app</a>
                        <a href="{{ route('frontend.programs') }}#fees" class="link-arrow">Fees &amp; seats <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>

{{-- How to apply --}}
<section class="ad-steps section-pad" aria-labelledby="ad-steps-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">How to apply</span>
                <h2 class="sec-title" id="ad-steps-title">Four steps, <em>right from your phone</em></h2>
            </div>
            @include('frontend.landing.partials._stores')
        </header>
        <ol class="stepper" data-stagger>
            @foreach ($mc['process'] as $i => $p)
                <li class="step" data-reveal>
                    <span class="step-n"><i class="fa-solid {{ $p['icon'] }}"></i><b>{{ $i + 1 }}</b></span>
                    <h3>{{ $p['title'] }}</h3>
                    <p>{{ $p['desc'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- Requirements --}}
<section class="ad-req section-pad" id="requirements" aria-labelledby="ad-req-title">
    <div class="container ad-req-grid">
        <header class="sec-head" data-reveal>
            <span class="eyebrow">Requirements</span>
            <h2 class="sec-title" id="ad-req-title">What you’ll <em>need</em></h2>
            <p class="sec-sub">Keep these ready before you start your application. For school classes, the app shows the exact documents for the class you choose.</p>
            <div class="elig-mini">
                <strong><i class="fa-solid fa-user-check"></i> HSC eligibility (2026–27)</strong>
                <ul>@foreach ($mc['college']['eligibility'] as $e)<li>{{ $e }}</li>@endforeach</ul>
                <small>Admission depends on seat availability and interview performance.</small>
            </div>
        </header>
        <div class="doc-grid" data-stagger>
            @foreach ($docs as $d)
                <article class="doc-card" data-reveal>
                    <header><span class="doc-icon"><i class="fa-solid {{ $d['icon'] }}"></i></span><h3>{{ $d['title'] }}</h3></header>
                    <ul>@foreach ($d['items'] as $it)<li><i class="fa-solid fa-check"></i> {{ $it }}</li>@endforeach</ul>
                </article>
            @endforeach
            <article class="doc-card doc-card--fees" data-reveal>
                <header><span class="doc-icon"><i class="fa-solid fa-receipt"></i></span><h3>Fees &amp; scholarships</h3></header>
                <p>Application and admission fees for every class are listed on our Programs page and shown in the app when you apply.</p>
                <p class="doc-note"><i class="fa-solid fa-award"></i> {{ $mc['college']['scholarship'] }}</p>
                <a href="{{ route('frontend.programs') }}#fees" class="link-arrow">See fees &amp; seats <i class="fa-solid fa-arrow-right"></i></a>
            </article>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="ad-faq section-pad" aria-labelledby="ad-faq-title">
    <div class="container ad-faq-grid">
        <header class="sec-head" data-reveal>
            <span class="eyebrow">FAQ</span>
            <h2 class="sec-title" id="ad-faq-title">Questions families <em>often ask</em></h2>
            <p class="sec-sub">Can’t find your answer? Our admission team is happy to help.</p>
            <div class="ad-help">
                <a href="{{ $contact['phone_href'] }}"><i class="fa-solid fa-phone"></i><span><small>Call us</small>{{ $contact['phone'] }}</span></a>
                <a href="mailto:{{ $contact['email'] }}"><i class="fa-solid fa-envelope"></i><span><small>Email</small>{{ $contact['email'] }}</span></a>
                <a href="{{ route('frontend.contact') }}"><i class="fa-solid fa-location-dot"></i><span><small>Visit</small>Savar campus</span></a>
            </div>
        </header>
        <div class="qa-list" data-stagger>
            @foreach ($mc['faq'] as $i => $f)
                <details class="qa" data-reveal @if($i === 0) open @endif>
                    <summary><span>{{ $f['q'] }}</span><i class="qa-icon" aria-hidden="true"></i></summary>
                    <p>{{ $f['a'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

@include('frontend.partials.cta-band', ['title' => 'Ready to apply?', 'text' => 'Download the ' . $app['name'] . ' app, choose School or College and submit your application in about ten minutes.'])
@include('frontend.partials.next-links', ['set' => 'academics'])
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
