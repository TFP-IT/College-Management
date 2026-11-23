@extends('frontend.layouts.app')

@section('title', 'Dr. Kamrul Ahsan, Chairman | Maestro Crown School & College')
@section('meta_description', 'Dr. Kamrul Ahsan is the Founder and Chairman of Kamrul Group and Chairman of Maestro Crown School & College, Savar. Learn about his leadership, vision and the enterprises he has built since 2011.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');

    $stats = [
        ['value' => 2011, 'suffix' => '',  'label' => 'Kamrul Group founded', 'plain' => true],
        ['value' => 16,   'suffix' => '',  'label' => 'Enterprises led'],
        ['value' => 7,    'suffix' => '',  'label' => 'Industry sectors'],
        ['value' => 4,    'suffix' => '',  'label' => 'Countries of operation'],
    ];

    $pillars = [
        ['icon' => 'fa-chess',          'title' => 'Long-term planning',         'desc' => 'Strategic diversification guided by a clear, patient view of the future.'],
        ['icon' => 'fa-list-check',     'title' => 'Disciplined execution',      'desc' => 'Quality assurance and operational efficiency in every enterprise.'],
        ['icon' => 'fa-lightbulb',      'title' => 'Customer-centric innovation', 'desc' => 'Products and services shaped around the people they serve.'],
        ['icon' => 'fa-people-group',   'title' => 'Developing people',          'desc' => 'Investing in human capital and responsible, ethical governance.'],
    ];

    $portfolio = [
        ['icon' => 'fa-graduation-cap', 'sector' => 'Education', 'companies' => [
            'Maestro Crown School & College',
            'Malaysia University of Science and Technology, Bangladesh Campus',
        ]],
        ['icon' => 'fa-credit-card', 'sector' => 'Financial technology & payments', 'companies' => [
            'Swiftlink Payment Services L.L.C', 'Maestro Pay SDN BHD', 'Tap and Pay WLL, Bahrain', 'Tap N Pay UK Ltd',
        ]],
        ['icon' => 'fa-code', 'sector' => 'Software & digital services', 'companies' => [
            'Maestro Soft Ltd', 'TFP Solutions Bangladesh Ltd', 'Mulamuli.com.bd',
        ]],
        ['icon' => 'fa-wheat-awn', 'sector' => 'Agro & food', 'companies' => [
            'Kamrul Agro Food Industries Ltd', 'Mecca Cola Bangladesh',
        ]],
        ['icon' => 'fa-tower-broadcast', 'sector' => 'Media & entertainment', 'companies' => [
            'Khobor71', 'Wow Play',
        ]],
        ['icon' => 'fa-handshake', 'sector' => 'Services & partnerships', 'companies' => [
            'Worker Welfare Watch SDN BHD', 'GG Consortium Bangladesh Ltd', 'Dignite International, Bangladesh Chapter',
        ]],
    ];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/profile.css') }}">
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
            <h1 class="pf-name" id="pf-name" data-split>Dr. Kamrul <em>Ahsan</em></h1>
            <p class="pf-role" data-reveal>Founder &amp; Chairman, Kamrul Group <span aria-hidden="true">·</span> Chairman, Maestro Crown School & College</p>
            <p class="pf-lead" data-reveal>
                An entrepreneur who believes institutions should outlive their founders. Since 2011, Dr. Ahsan has built a diversified group of enterprises,
                with education at its heart, to serve people and strengthen Bangladesh’s path to self-reliance.
            </p>
            <div class="pf-actions" data-reveal>
                <a href="{{ route('frontend.message_chairman') }}" class="btn btn-primary btn-lg" data-magnetic><i class="fa-solid fa-feather"></i> Read his message</a>
                <a href="#portfolio" class="btn btn-outline btn-lg">View portfolio <i class="fa-solid fa-arrow-down"></i></a>
            </div>
        </div>

        <figure class="pf-portrait" data-clip>
            @include('frontend.landing.partials._img', ['name' => 'campus/chairman', 'alt' => 'Dr. Kamrul Ahsan speaking at a Maestro Crown School & College event', 'sizes' => '(max-width: 1023px) 90vw, 440px', 'eager' => true])
            <figcaption class="pf-portrait-card">
                <img src="{{ asset('frontend/mcc/img/logo-256.webp') }}" alt="" width="44" height="44">
                <span><strong>Dr. Kamrul Ahsan</strong>Founder, Kamrul Group · Since 2011</span>
            </figcaption>
        </figure>
    </div>
</section>

{{-- ── Key figures ──────────────────────────────────────── --}}
<section class="pf-stats-wrap" aria-label="Key figures">
    <div class="container">
        <ul class="pf-stats" data-stagger>
            @foreach ($stats as $s)
                <li data-reveal>
                    <strong><span data-count="{{ $s['value'] }}" @if(!empty($s['plain'])) data-plain @endif>{{ $s['value'] }}</span>{{ $s['suffix'] }}</strong>
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
                <div><dt>Name</dt><dd>Dr. Kamrul Ahsan</dd></div>
                <div><dt>Role</dt><dd>Founder &amp; Chairman, Kamrul Group</dd></div>
                <div><dt>At MCC</dt><dd>Chairman, Maestro Crown School & College</dd></div>
                <div><dt>Group founded</dt><dd>2011</dd></div>
                <div><dt>Operations</dt><dd>Bangladesh, Malaysia, Bahrain &amp; the United Kingdom</dd></div>
            </dl>
            <a href="{{ route('frontend.message_chairman') }}" class="link-arrow">Chairman’s message <i class="fa-solid fa-arrow-right"></i></a>
        </aside>

        <article class="pf-article">
            <span class="eyebrow" data-reveal>Biography</span>
            <h2 class="sec-title" id="pf-bio-title" data-reveal>Building institutions <em>that last</em></h2>
            <p class="pf-dropcap" data-reveal>Kamrul Group began in 2011 as a modest initiative founded by Dr. Kamrul Ahsan. It has since grown into a diversified, forward-looking business conglomerate, driven by a clear mission: to serve people, contribute to national development, and support Bangladesh’s economic self-reliance.</p>
            <p data-reveal>Under his leadership, Kamrul Group has expanded strategically across multiple sectors, building a strong presence in key industries, from agro and food production and education to financial technology, digital services, media, software development and international payment solutions.</p>

            <blockquote class="pf-pull" data-reveal>
                <i class="fa-solid fa-quote-left" aria-hidden="true"></i>
                <p>Shaping minds and guiding young people is one of the most powerful ways to build a nation.</p>
                <cite>Dr. Kamrul Ahsan</cite>
            </blockquote>

            <p data-reveal>Dr. Ahsan has consistently focused on strategic diversification, quality assurance, operational efficiency and responsible corporate governance. His leadership philosophy emphasises long-term planning, disciplined execution, customer-centric innovation and effective human capital development.</p>
            <p data-reveal>Through sustained expansion and adaptation to global economic dynamics, Kamrul Group continues to position itself as a competitive and responsible corporate entity, committed to ethical business practices, social responsibility and meaningful contributions to the national economy.</p>
        </article>
    </div>
</section>

{{-- ── Leadership philosophy ────────────────────────────── --}}
<section class="pf-pillars section-pad" aria-labelledby="pf-pillars-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">Leadership philosophy</span>
            <h2 class="sec-title" id="pf-pillars-title">Four principles behind <em>every venture</em></h2>
        </header>
        <div class="pf-pillar-grid" data-stagger>
            @foreach ($pillars as $i => $p)
                <article class="pf-pillar" data-reveal>
                    <span class="pf-pillar-n">0{{ $i + 1 }}</span>
                    <span class="pf-pillar-icon"><i class="fa-solid {{ $p['icon'] }}"></i></span>
                    <h3>{{ $p['title'] }}</h3>
                    <p>{{ $p['desc'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Portfolio ────────────────────────────────────────── --}}
<section class="pf-portfolio section-pad" id="portfolio" aria-labelledby="pf-portfolio-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Kamrul Group</span>
                <h2 class="sec-title" id="pf-portfolio-title">A portfolio of <em>16 enterprises</em></h2>
            </div>
            <p class="sec-sub">Across seven sectors and four countries, with education, including Maestro Crown School & College, at the heart of the group.</p>
        </header>
        <div class="pf-sector-grid" data-stagger>
            @foreach ($portfolio as $group)
                <article class="pf-sector" data-reveal>
                    <header>
                        <span class="pf-sector-icon"><i class="fa-solid {{ $group['icon'] }}"></i></span>
                        <h3>{{ $group['sector'] }}</h3>
                        <span class="pf-sector-count">{{ count($group['companies']) }}</span>
                    </header>
                    <ul>
                        @foreach ($group['companies'] as $company)
                            <li @if($company === 'Maestro Crown School & College') class="is-home" @endif>
                                {{ $company }}
                                @if($company === 'Maestro Crown School & College')<span class="pf-here">You are here</span>@endif
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Vision ───────────────────────────────────────────── --}}
<section class="pf-vision" aria-labelledby="pf-vision-title">
    <div class="pf-vision-bg" aria-hidden="true"></div>
    <div class="container pf-vision-inner">
        <img src="{{ asset('frontend/mcc/img/logo-256.webp') }}" alt="" width="88" height="88" class="pf-vision-crest" data-reveal>
        <span class="eyebrow eyebrow--light" id="pf-vision-title" data-reveal>Vision</span>
        <blockquote data-split>“To build resilient institutions that create value for stakeholders, strengthen local industry, and contribute to <em>sustainable national progress.</em>”</blockquote>
        <p data-reveal>Dr. Kamrul Ahsan, Founder &amp; Chairman</p>
    </div>
</section>

{{-- ── Continue ─────────────────────────────────────────── --}}
<section class="pf-next section-pad" aria-label="Continue reading">
    <div class="container pf-next-grid" data-stagger>
        <a href="{{ route('frontend.message_chairman') }}" class="pf-next-card" data-reveal>
            <span class="pf-next-avatar">@include('frontend.landing.partials._img', ['name' => 'campus/chairman-face', 'alt' => ''])</span>
            <span><small>Read next</small><strong>Message from the Chairman</strong></span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
        <a href="{{ route('frontend.message_principal') }}" class="pf-next-card" data-reveal>
            <span class="pf-next-avatar pf-next-avatar--initials">KR</span>
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
