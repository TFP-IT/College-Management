@extends('frontend.layouts.app')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $ch = config('chairman');
    $fullName = $ch['name'] . ' ' . $ch['postnominal'];
    [$firstNames, $lastName] = [\Illuminate\Support\Str::beforeLast($ch['name'], ' '), \Illuminate\Support\Str::afterLast($ch['name'], ' ')];
@endphp

@section('title', $fullName . ', Chairman | Maestro Crown School & College')
@section('meta_description', $ch['name'] . ' FRSA is the Chairman of Maestro Crown School & College and Global Chairman of Versatilo Group: a former army officer, technology entrepreneur and educationist with businesses in 14 countries.')

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/profile.css') }}">
@endpush

@push('head')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $ch['name'],
        'honorificPrefix' => 'Dr.',
        'honorificSuffix' => $ch['postnominal'],
        'jobTitle' => $ch['roles'],
        'image' => asset('frontend/mcc/img/' . $ch['portrait'] . '.jpg'),
        'url' => route('frontend.chairman_profile'),
        'worksFor' => ['@type' => 'EducationalOrganization', 'name' => 'Maestro Crown School & College', 'url' => url('/')],
        'memberOf' => ['@type' => 'Organization', 'name' => 'Royal Society of Arts'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
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
                <span aria-current="page">Chairman’s Profile</span>
            </nav>
            <span class="eyebrow" data-reveal>Chairman’s Profile</span>
            <div class="pf-name-row">
                <h1 class="pf-name pf-name--compact" id="pf-name" data-split>{{ $firstNames }} <em>{{ $lastName }}</em></h1>
                <abbr class="pf-postnominal" title="Fellow of the Royal Society of Arts" data-reveal>{{ $ch['postnominal'] }}</abbr>
            </div>
            <p class="pf-role" data-reveal>
                @foreach ($ch['roles'] as $role){{ $role }}@if (!$loop->last) <span aria-hidden="true">·</span> @endif @endforeach
            </p>
            <ul class="pf-tags" data-reveal aria-label="Areas of work">
                @foreach ($ch['tags'] as $tag)<li>{{ $tag }}</li>@endforeach
            </ul>
            <p class="pf-lead" data-reveal>{{ $ch['lead'] }}</p>
            <div class="pf-actions" data-reveal>
                <a href="{{ route('frontend.message_chairman') }}" class="btn btn-primary btn-lg" data-magnetic><i class="fa-solid fa-feather"></i> Read his message</a>
                <a href="#journey" class="btn btn-outline btn-lg">His journey <i class="fa-solid fa-arrow-down"></i></a>
            </div>
        </div>

        <figure class="pf-portrait" data-clip>
            @include('frontend.landing.partials._img', ['name' => $ch['portrait'], 'alt' => $fullName . ', Chairman of Maestro Crown School & College', 'sizes' => '(max-width: 1023px) 90vw, 440px', 'eager' => true])
            <figcaption class="pf-portrait-card">
                <img src="{{ asset('frontend/mcc/img/logo-256.webp') }}" alt="" width="44" height="44">
                <span><strong>{{ $fullName }}</strong>{{ $ch['portrait_card'] }}</span>
            </figcaption>
        </figure>
    </div>
</section>

{{-- ── Key figures ──────────────────────────────────────── --}}
<section class="pf-stats-wrap" aria-label="Key figures">
    <div class="container">
        <ul class="pf-stats" data-stagger>
            @foreach ($ch['stats'] as $s)
                <li data-reveal>
                    <strong><span data-count="{{ $s['value'] }}">{{ $s['value'] }}</span>{{ $s['suffix'] }}</strong>
                    <span>{{ $s['label'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- ── Biography ────────────────────────────────────────── --}}
<section class="pf-bio section-pad" aria-labelledby="pf-bio-title">
    <div class="container pf-bio-grid">
        <aside class="pf-glance" data-reveal aria-label="At a glance">
            <h2>At a glance</h2>
            <dl>
                <div><dt>Name</dt><dd>{{ $fullName }}</dd></div>
                @foreach ($ch['glance'] as $label => $value)
                    <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                @endforeach
            </dl>
            <a href="{{ route('frontend.message_chairman') }}" class="link-arrow">Chairman’s message <i class="fa-solid fa-arrow-right"></i></a>
        </aside>

        <article class="pf-article">
            <span class="eyebrow" data-reveal>Biography</span>
            <h2 class="sec-title" id="pf-bio-title" data-reveal>From soldier to <em>global builder</em></h2>
            @foreach ($ch['bio'] as $i => $para)
                <p @class(['pf-dropcap' => $i === 0]) data-reveal>{{ $para }}</p>
                @if ($i === 1)
                    <blockquote class="pf-pull" data-reveal>
                        <i class="fa-solid fa-quote-left" aria-hidden="true"></i>
                        <p>{{ $ch['pull_quote'] }}</p>
                        <cite>{{ $ch['name'] }}</cite>
                    </blockquote>
                @endif
            @endforeach
        </article>
    </div>
</section>

{{-- ── Journey ──────────────────────────────────────────── --}}
<section class="pf-journey section-pad" id="journey" aria-labelledby="pf-journey-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">Journey</span>
            <h2 class="sec-title" id="pf-journey-title">Milestones that <em>shaped him</em></h2>
        </header>
        <ol class="pf-timeline" data-stagger>
            @foreach ($ch['journey'] as $m)
                <li @class(['pf-timeline-item', 'is-now' => $loop->last]) data-reveal>
                    <span class="pf-timeline-year">{{ $m['year'] }}</span>
                    <span class="pf-timeline-dot" aria-hidden="true"></span>
                    <div class="pf-timeline-body">
                        <h3>{{ $m['title'] }}</h3>
                        <p>{{ $m['text'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ── Credentials ──────────────────────────────────────── --}}
<section class="pf-creds section-pad" aria-labelledby="pf-creds-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Credentials</span>
                <h2 class="sec-title" id="pf-creds-title">Education, fellowship <em>&amp; service</em></h2>
            </div>
            <p class="sec-sub">Academic training in Bangladesh and the United Kingdom, a fellowship of one of Britain’s oldest learned societies, and decades of public and diplomatic service.</p>
        </header>

        <div class="pf-creds-grid">
            <article class="pf-cred" data-reveal>
                <header><span class="pf-cred-icon"><i class="fa-solid fa-graduation-cap"></i></span><h3>Education</h3></header>
                <ol class="pf-edu">
                    @foreach ($ch['education'] as $e)
                        <li><strong>{{ $e['degree'] }}</strong><span>{{ $e['where'] }}</span></li>
                    @endforeach
                </ol>
            </article>

            <article class="pf-cred pf-cred--rsa" data-reveal>
                <header><span class="pf-cred-icon"><i class="fa-solid fa-award"></i></span><h3>{{ $ch['rsa']['title'] }}</h3></header>
                <p>{{ $ch['rsa']['text'] }}</p>
                <p class="pf-cred-label">Past Fellows include</p>
                <ul class="pf-chips">@foreach ($ch['rsa']['fellows'] as $f)<li>{{ $f }}</li>@endforeach</ul>
            </article>

            <article class="pf-cred" data-reveal>
                <header><span class="pf-cred-icon"><i class="fa-solid fa-shield-halved"></i></span><h3>Leadership &amp; public service</h3></header>
                <ul class="pf-list">@foreach ($ch['service'] as $item)<li>{{ $item }}</li>@endforeach</ul>
            </article>

            <article class="pf-cred" data-reveal>
                <header><span class="pf-cred-icon"><i class="fa-solid fa-globe"></i></span><h3>Diplomatic &amp; advisory roles</h3></header>
                <ul class="pf-list">@foreach ($ch['advisory'] as $item)<li>{{ $item }}</li>@endforeach</ul>
            </article>
        </div>
    </div>
</section>

{{-- ── Ventures ─────────────────────────────────────────── --}}
<section class="pf-portfolio section-pad" id="ventures" aria-labelledby="pf-ventures-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Versatilo Group</span>
                <h2 class="sec-title" id="pf-ventures-title">Ventures across <em>{{ count($ch['footprint']) }} countries</em></h2>
            </div>
            <p class="sec-sub">More than 50 business entities in technology, fintech, aviation, consumer brands, energy and manufacturing, with headquarters in the UK, Malaysia, Oman and Bangladesh.</p>
        </header>

        <ul class="pf-footprint" data-reveal aria-label="Countries of operation">
            @foreach ($ch['footprint'] as $country)<li>{{ $country }}</li>@endforeach
        </ul>

        <div class="pf-sector-grid" data-stagger>
            @foreach ($ch['ventures'] as $group)
                <article class="pf-sector" data-reveal>
                    <header>
                        <span class="pf-sector-icon"><i class="fa-solid {{ $group['icon'] }}"></i></span>
                        <h3>{{ $group['sector'] }}</h3>
                        <span class="pf-sector-count">{{ count($group['items']) }}</span>
                    </header>
                    <ul class="pf-venture-list">
                        @foreach ($group['items'] as $v)
                            <li><strong>{{ $v['name'] }}</strong><span>{{ $v['desc'] }}</span></li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>

        <a href="{{ route('frontend.about') }}" class="pf-home" data-reveal>
            <img src="{{ asset('frontend/mcc/img/logo-256.webp') }}" alt="" width="56" height="56">
            <span>
                <small>Education · You are here</small>
                <strong>Maestro Crown School &amp; College</strong>
                <span>Play Group to HSC in Savar, Dhaka, where his commitment to education meets the next generation.</span>
            </span>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</section>

{{-- ── Beyond business ──────────────────────────────────── --}}
<section class="pf-beyond section-pad" aria-labelledby="pf-beyond-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">Beyond business</span>
            <h2 class="sec-title" id="pf-beyond-title">Media, writing &amp; <em>service to society</em></h2>
        </header>
        <div class="pf-beyond-grid" data-stagger>
            @foreach ($ch['beyond'] as $b)
                <article @class(['pf-feature', 'pf-feature--plain' => empty($b['image'])]) data-reveal>
                    @if (!empty($b['image']))
                        <div class="pf-feature-media">
                            @include('frontend.landing.partials._img', ['name' => $b['image'], 'alt' => $b['alt'], 'sizes' => '(max-width: 768px) 92vw, 560px'])
                        </div>
                    @endif
                    <div class="pf-feature-body">
                        <span class="pf-feature-eyebrow"><i class="fa-solid {{ $b['icon'] }}"></i> {{ $b['eyebrow'] }}</span>
                        <h3>{{ $b['title'] }}</h3>
                        <ul class="pf-list">@foreach ($b['points'] as $pt)<li>{{ $pt }}</li>@endforeach</ul>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Philosophy ───────────────────────────────────────── --}}
<section class="pf-vision" aria-labelledby="pf-vision-title">
    <div class="pf-vision-bg" aria-hidden="true"></div>
    <div class="container pf-vision-inner">
        <img src="{{ asset('frontend/mcc/img/logo-256.webp') }}" alt="" width="88" height="88" class="pf-vision-crest" data-reveal>
        <span class="eyebrow eyebrow--light" id="pf-vision-title" data-reveal>His philosophy</span>
        <blockquote data-split>“{{ $ch['philosophy']['headline'][0] }} <em>{{ $ch['philosophy']['headline'][1] }}</em>”</blockquote>
        <p data-reveal>{{ $ch['name'] }}</p>
        <ul class="pf-quotes" data-stagger>
            @foreach ($ch['philosophy']['quotes'] as $q)
                <li data-reveal><i class="fa-solid fa-quote-left" aria-hidden="true"></i><span>{{ $q }}</span></li>
            @endforeach
        </ul>
    </div>
</section>

{{-- ── Continue ─────────────────────────────────────────── --}}
<section class="pf-next section-pad" aria-label="Continue reading">
    <div class="container pf-next-grid" data-stagger>
        <a href="{{ route('frontend.message_chairman') }}" class="pf-next-card" data-reveal>
            <span class="pf-next-avatar">@include('frontend.landing.partials._img', ['name' => 'campus/chairman-face', 'alt' => '', 'sizes' => '54px'])</span>
            <span><small>Read next</small><strong>Message from the Chairman</strong></span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
        <a href="{{ route('frontend.message_principal') }}" class="pf-next-card" data-reveal>
            <span class="pf-next-avatar">@include('frontend.landing.partials._img', ['name' => 'campus/principal', 'alt' => '', 'sizes' => '54px'])</span>
            <span><small>Leadership</small><strong>Message from the Principal</strong></span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
        <a href="{{ route('frontend.governing_body') }}" class="pf-next-card" data-reveal>
            <span class="pf-next-avatar pf-next-avatar--icon"><i class="fa-solid fa-sitemap"></i></span>
            <span><small>Governance</small><strong>Governing Body</strong></span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
