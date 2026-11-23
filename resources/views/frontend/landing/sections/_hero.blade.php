@php $hero = $mc['hero']; @endphp
<section class="hero" id="home" data-hero>
    <div class="hero-media" data-hero-media>
        <video class="hero-video" muted loop playsinline preload="none"
               poster="{{ asset('frontend/mcc/img/hero-poster.webp') }}"
               data-src="{{ asset('frontend/mcc/video/hero.mp4') }}" data-hero-video aria-hidden="true"></video>
    </div>
    <div class="hero-shade" data-hero-shade></div>
    <div class="hero-blobs" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="hero-grid-lines" aria-hidden="true"></div>

    <div class="container hero-inner">
        <div class="hero-copy">
            <span class="hero-eyebrow" data-hero-item><span class="hero-eyebrow-dot"></span>{{ $hero['eyebrow'] }}</span>
            <h1 class="hero-title" data-split>
                <span class="hero-line">{{ $hero['line_1'] }}</span>
                <span class="hero-line"><em>{{ $hero['line_2'] }}</em></span>
            </h1>
            <p class="hero-sub" data-hero-item>{{ $hero['subtitle'] }}</p>
            <ul class="hero-meta" data-hero-item>
                @foreach ($hero['meta'] as $m)<li>{{ $m }}</li>@endforeach
            </ul>
            <ul class="hero-levels" data-hero-item aria-label="Admission open for">
                <li><i class="fa-solid fa-shapes"></i><span><b>School</b>Play Group – Class 10</span></li>
                <li><i class="fa-solid fa-graduation-cap"></i><span><b>College</b>HSC · Science, Business, Humanities</span></li>
            </ul>
        </div>

        {{-- Right: the MCC Hello Future app, where admission happens --}}
        <div class="hero-app-col">
            <div class="hero-devices" data-hero-card aria-hidden="true">
                <span class="hero-devices-glow"></span>
                @include('frontend.landing.partials._phone', ['variant' => 'login', 'class' => 'phone--front', 'eager' => true])
                @include('frontend.landing.partials._phone', ['variant' => 'dash', 'class' => 'phone--back', 'eager' => true])
                <div class="toast toast--hero-1"><i class="fa-solid fa-users"></i><span><b>One app. Every role.</b>Student, Guardian &amp; Teacher portals</span></div>
                <div class="toast toast--hero-2"><i class="fa-solid fa-location-crosshairs"></i><span><b>Live tracking</b>Status updated just now</span></div>
            </div>
            <div class="hero-stores" data-hero-item>
                <p><span class="hero-stores-dot"></span>Apply &amp; track admission in the <strong>{{ $app['name'] }}</strong> app</p>
                @include('frontend.landing.partials._stores')
            </div>
        </div>
    </div>

    <a href="#trust" class="hero-scroll" aria-label="Scroll to learn more">
        <span class="hero-mouse"><span></span></span>
        <span>Scroll</span>
    </a>
</section>
