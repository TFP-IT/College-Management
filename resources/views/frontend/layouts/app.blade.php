@php
    $isLanding = request()->routeIs('landing');
    $mc = config('landing');
    $c = $mc['contact'];
    $app = $mc['app'];
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $logo = asset('frontend/mcc/img/logo-256.png');
@endphp
<!DOCTYPE html>
<html lang="en" class="{{ $isLanding ? 'is-landing' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Maestro Crown College | School & College Admission ' . $mc['session'] . ' – Savar, Dhaka')</title>
    <meta name="description" content="@yield('meta_description', 'Maestro Crown College, Savar, Dhaka: a joint UK–Bangladesh institution since 2014. Admission ' . $mc['session'] . ' is open for School (Play Group to Class 10) and College (HSC: Science, Business Studies, Humanities). Apply in the MCC Hello Future app. EIIN 136876 · College Code 1933.')">
    <meta name="theme-color" content="#2A0909">
    <link rel="icon" href="{{ asset('frontend/mcc/img/logo-256.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('frontend/mcc/img/logo-256.png') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Maestro Crown College">
    <meta property="og:title" content="@yield('title', 'Maestro Crown College | Admission ' . $mc['session'] . ' Open')">
    <meta property="og:description" content="School (Play Group–Class 10) and College (HSC) admission {{ $mc['session'] }} is open. Apply in the MCC Hello Future app.">
    <meta property="og:image" content="{{ asset('frontend/mcc/img/students.jpg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Enable JS-only (pre-animation) states before first paint; restore dismissed announcement. --}}
    <script>
        document.documentElement.classList.add('js');
        try { if (localStorage.getItem('mc-announce-hidden') === '{{ $mc['session'] }}') document.documentElement.classList.add('no-announce'); } catch (e) {}
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/site.css') }}">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'EducationalOrganization',
        'name' => 'Maestro Crown College',
        'alternateName' => 'MCC',
        'foundingDate' => '2014',
        'url' => url('/'),
        'logo' => asset('frontend/mcc/img/logo-256.png'),
        'telephone' => '+8801309136876',
        'email' => $c['email'],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => '107/2, Dogormura, Radio Colony, Dhaka–Aricha Highway',
            'addressLocality' => 'Savar',
            'addressRegion' => 'Dhaka',
            'postalCode' => '1343',
            'addressCountry' => 'BD',
        ],
        'sameAs' => [$c['facebook'], $app['play_store']],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @stack('styles')
</head>
<body class="{{ $isLanding ? 'page-landing' : 'page-inner' }}">
    <a class="skip-link" href="#main">Skip to content</a>

    {{-- ── Header ─────────────────────────────────────────────── --}}
    <header class="mc-header {{ $isLanding ? 'is-transparent' : '' }}" data-header data-transparent-start="{{ $isLanding ? '1' : '0' }}">
        <div class="mc-announce" role="region" aria-label="Announcements">
            <span class="mc-live"><span class="mc-live-dot" aria-hidden="true"></span><span>Admission {{ $mc['session'] }}</span></span>
            <div class="mc-marquee">
                <div class="mc-marquee-track">
                    @for ($loopCopy = 0; $loopCopy < 2; $loopCopy++)
                        <div class="mc-marquee-group" @if($loopCopy) aria-hidden="true" @endif>
                            @foreach ($mc['announcements'] as $item)
                                @if ($item['link'] === 'app')
                                    <a href="{{ $app['play_store'] }}" target="_blank" rel="noopener" @if($loopCopy) tabindex="-1" @endif><i class="fa-brands fa-google-play"></i> {{ $item['text'] }}</a>
                                @elseif ($item['link'] === 'tel')
                                    <a href="{{ $c['phone_href'] }}" @if($loopCopy) tabindex="-1" @endif>{{ $item['text'] }}</a>
                                @else
                                    <span>{{ $item['text'] }}</span>
                                @endif
                                <span class="mc-marquee-sep" aria-hidden="true"></span>
                            @endforeach
                        </div>
                    @endfor
                </div>
            </div>
            <button class="mc-announce-close" type="button" data-announce-close aria-label="Hide announcements"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <nav class="mc-nav" aria-label="Main">
            <div class="container mc-nav-inner">
                <a href="{{ route('landing') }}" class="mc-brand" aria-label="Maestro Crown College, home">
                    <img src="{{ $logo }}" alt="" width="46" height="46">
                    <span>
                        <span class="mc-brand-name">Maestro Crown College</span>
                        <span class="mc-brand-sub">School &amp; College · Est. 2014</span>
                    </span>
                </a>

                <ul class="mc-links">
                    <li><a class="mc-link" href="{{ route('landing') }}" @if($isLanding) aria-current="page" @endif>Home</a></li>
                    <li class="has-menu">
                        <button class="mc-link" type="button" aria-haspopup="true" aria-expanded="false">About <i class="fa-solid fa-chevron-down"></i></button>
                        <div class="mc-menu">
                            <a href="{{ route('frontend.about') }}"><i class="fa-solid fa-landmark"></i> About MCC</a>
                            <a href="{{ route('frontend.governing_body') }}"><i class="fa-solid fa-sitemap"></i> Governing Body</a>
                            <a href="{{ route('frontend.chairman_profile') }}"><i class="fa-solid fa-user-tie"></i> Chairman’s Profile</a>
                            <a href="{{ route('frontend.message_chairman') }}"><i class="fa-solid fa-quote-left"></i> Message of Chairman</a>
                            <a href="{{ route('frontend.message_principal') }}"><i class="fa-solid fa-feather"></i> Message of Principal</a>
                            <a href="{{ route('frontend.teacher_info') }}"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a>
                            <a href="{{ route('frontend.staff_info') }}"><i class="fa-solid fa-id-badge"></i> Staff</a>
                        </div>
                    </li>
                    <li class="has-menu">
                        <button class="mc-link" type="button" aria-haspopup="true" aria-expanded="false">Academics <i class="fa-solid fa-chevron-down"></i></button>
                        <div class="mc-menu">
                            <a href="{{ route('frontend.programs') }}"><i class="fa-solid fa-layer-group"></i> Programs</a>
                            <a href="{{ route('frontend.academic_calendar') }}"><i class="fa-regular fa-calendar"></i> Academic Calendar</a>
                            <a href="{{ route('frontend.notice_board') }}"><i class="fa-regular fa-bell"></i> Notice Board</a>
                        </div>
                    </li>
                    <li><a class="mc-link" href="{{ route('frontend.admission') }}" @if(request()->routeIs('frontend.admission')) aria-current="page" @endif>Admission</a></li>
                    <li><a class="mc-link" href="{{ route('frontend.gallery') }}" @if(request()->routeIs('frontend.gallery')) aria-current="page" @endif>Gallery</a></li>
                    <li><a class="mc-link" href="{{ route('frontend.contact') }}" @if(request()->routeIs('frontend.contact')) aria-current="page" @endif>Contact</a></li>
                </ul>

                <div class="mc-actions">
                    <div class="mc-apply" data-apply>
                        <button class="btn btn-primary btn-sm" type="button" aria-haspopup="true" aria-expanded="false" data-apply-toggle>
                            <i class="fa-solid fa-pen-nib"></i><span>Apply Now</span> <i class="fa-solid fa-chevron-down" style="font-size:.6rem"></i>
                        </button>
                        <div class="mc-menu mc-apply-menu" role="menu">
                            <a role="menuitem" href="{{ $app['play_store'] }}" target="_blank" rel="noopener"><i class="fa-solid fa-shapes"></i><span><strong>School Admission</strong><span>Play Group to Class 10 · in the app</span></span></a>
                            <a role="menuitem" href="{{ $app['play_store'] }}" target="_blank" rel="noopener"><i class="fa-solid fa-graduation-cap"></i><span><strong>College Admission</strong><span>HSC: Science · Business · Humanities · in the app</span></span></a>
                            <hr>
                            <a role="menuitem" href="{{ $app['play_store'] }}" target="_blank" rel="noopener"><i class="fa-brands fa-google-play"></i><span><strong>Get the {{ $app['name'] }} app</strong><span>Apply &amp; track your application in real time</span></span></a>
                            <a role="menuitem" href="{{ route('frontend.admission') }}"><i class="fa-solid fa-circle-info"></i><span><strong>Admission info</strong><span>Documents, eligibility &amp; dates</span></span></a>
                        </div>
                    </div>
                    <button class="mc-burger" type="button" aria-label="Open menu" aria-controls="mc-drawer" aria-expanded="false" data-drawer-open>
                        <span></span><span></span><span></span>
                    </button>
                </div>
            </div>
            <div class="mc-progress" data-progress aria-hidden="true"></div>
        </nav>
    </header>

    {{-- ── Mobile drawer ─────────────────────────────────────── --}}
    <div class="mc-drawer" id="mc-drawer" data-drawer aria-hidden="true">
        <div class="mc-drawer-scrim" data-drawer-close></div>
        <div class="mc-drawer-panel" role="dialog" aria-modal="true" aria-label="Menu">
            <div class="mc-drawer-head">
                <a href="{{ route('landing') }}" class="mc-brand">
                    <img src="{{ $logo }}" alt="" width="40" height="40">
                    <span><span class="mc-brand-name">Maestro Crown College</span></span>
                </a>
                <button class="mc-drawer-close" type="button" aria-label="Close menu" data-drawer-close><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="mc-drawer-body">
                <a href="{{ route('landing') }}">Home <i class="fa-solid fa-arrow-right"></i></a>
                <details>
                    <summary>About <i class="fa-solid fa-plus"></i></summary>
                    <div class="mc-drawer-sub">
                        <a href="{{ route('frontend.about') }}">About MCC</a>
                        <a href="{{ route('frontend.governing_body') }}">Governing Body</a>
                        <a href="{{ route('frontend.chairman_profile') }}">Chairman’s Profile</a>
                        <a href="{{ route('frontend.message_chairman') }}">Message of Chairman</a>
                        <a href="{{ route('frontend.message_principal') }}">Message of Principal</a>
                        <a href="{{ route('frontend.teacher_info') }}">Teachers</a>
                        <a href="{{ route('frontend.staff_info') }}">Staff</a>
                    </div>
                </details>
                <details>
                    <summary>Academics <i class="fa-solid fa-plus"></i></summary>
                    <div class="mc-drawer-sub">
                        <a href="{{ route('frontend.programs') }}">Programs</a>
                        <a href="{{ route('frontend.academic_calendar') }}">Academic Calendar</a>
                        <a href="{{ route('frontend.notice_board') }}">Notice Board</a>
                    </div>
                </details>
                <a href="{{ route('frontend.admission') }}">Admission <i class="fa-solid fa-arrow-right"></i></a>
                <a href="{{ route('frontend.gallery') }}">Gallery <i class="fa-solid fa-arrow-right"></i></a>
                <a href="{{ route('frontend.contact') }}">Contact <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="mc-drawer-cta">
                <a href="{{ $app['play_store'] }}" target="_blank" rel="noopener" class="btn btn-primary btn-block"><i class="fa-brands fa-google-play"></i> Apply in the MCC app</a>
                <a href="{{ route('frontend.admission') }}" class="btn btn-outline btn-block"><i class="fa-solid fa-circle-info"></i> Admission info</a>
                <small>Need help? <a href="{{ $c['phone_href'] }}">{{ $c['phone'] }}</a></small>
            </div>
        </div>
    </div>

    @unless ($isLanding)
        <div class="mc-header-spacer" aria-hidden="true"></div>
    @endunless

    <main id="main">
        @if(View::hasSection('guardian_sidebar'))
        <div class="container" style="display: grid; grid-template-columns: 250px minmax(0, 1fr); gap: 24px; align-items: start; padding-top: 30px; padding-bottom: 40px;">
            <aside>@yield('guardian_sidebar')</aside>
            <div style="min-width: 0;">
                @yield('content')
            </div>
        </div>
        @else
        @yield('content')
        @endif
    </main>

    {{-- ── Footer ─────────────────────────────────────────────── --}}
    <footer class="mc-footer" id="contact">
        <div class="container">
            <div class="mc-footer-grid">
                <div class="mc-footer-brand">
                    <img src="{{ $logo }}" alt="Maestro Crown College crest" width="72" height="72" loading="lazy">
                    <h2>Maestro Crown College</h2>
                    <p>A joint UK–Bangladesh institution for School and Higher Secondary education in Savar, Dhaka, since 2014.</p>
                    <div class="mc-footer-codes"><span>EIIN 136876</span><span>College Code 1933</span></div>
                    <div class="mc-social">
                        <a href="{{ $c['facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="{{ $app['play_store'] }}" target="_blank" rel="noopener" aria-label="Get the app on Google Play"><i class="fa-brands fa-google-play"></i></a>
                        <a href="mailto:{{ $c['email'] }}" aria-label="Email"><i class="fa-solid fa-envelope"></i></a>
                        <a href="{{ $c['phone_href'] }}" aria-label="Call"><i class="fa-solid fa-phone"></i></a>
                    </div>
                </div>
                <div>
                    <h3>Explore</h3>
                    <ul>
                        <li><a href="{{ route('frontend.about') }}">About MCC</a></li>
                        <li><a href="{{ route('frontend.programs') }}">Programs</a></li>
                        <li><a href="{{ route('frontend.academic_calendar') }}">Academic Calendar</a></li>
                        <li><a href="{{ route('frontend.notice_board') }}">Notice Board</a></li>
                        <li><a href="{{ route('frontend.gallery') }}">Gallery</a></li>
                        <li><a href="{{ route('frontend.governing_body') }}">Governing Body</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Admission {{ $mc['session'] }}</h3>
                    <ul>
                        <li><a href="{{ $app['play_store'] }}" target="_blank" rel="noopener">School Admission (app)</a></li>
                        <li><a href="{{ $app['play_store'] }}" target="_blank" rel="noopener">College Admission (app)</a></li>
                        <li><a href="{{ $app['play_store'] }}" target="_blank" rel="noopener">Track Application (app)</a></li>
                        <li><a href="{{ route('frontend.admission') }}">Admission Info</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Contact</h3>
                    <ul class="mc-footer-contact">
                        <li><i class="fa-solid fa-location-dot"></i><a href="{{ $c['map_link'] }}" target="_blank" rel="noopener">{{ $c['address'] }}</a></li>
                        <li><i class="fa-solid fa-phone"></i><a href="{{ $c['phone_href'] }}">{{ $c['phone'] }}</a></li>
                        <li><i class="fa-solid fa-envelope"></i><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="mc-footer-word" aria-hidden="true">Maestro Crown</div>
        <div class="mc-footer-bottom">
            <div class="container">
                <div>&copy; {{ date('Y') }} Maestro Crown College. All rights reserved.</div>
                <div class="mc-gov">
                    <a href="https://dhakaeducationboard.gov.bd/site/" target="_blank" rel="noopener">Dhaka Education Board</a>
                    <a href="https://moedu.gov.bd/" target="_blank" rel="noopener">Ministry of Education</a>
                    <a href="https://banbeis.gov.bd/" target="_blank" rel="noopener">BANBEIS</a>
                    <a href="https://shed.gov.bd/" target="_blank" rel="noopener">DSHE</a>
                </div>
            </div>
        </div>
    </footer>

    @if ($isLanding)
        <div class="mc-applybar" data-applybar>
            <span class="mc-applybar-label"><small>Admission {{ $mc['session'] }}</small>School &amp; College</span>
            <a href="{{ $app['play_store'] }}" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa-brands fa-google-play"></i> Apply in the app</a>
        </div>
    @endif

    <script src="{{ $ver('frontend/mcc/js/site.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
