<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Maestro Crown College | Best Education Is Here – Savar, Dhaka')</title>
    <meta name="description" content="Maestro Crown College – Established 2014, Savar, Dhaka. EIIN: 136876. College Code: 1933. Offering Science, Commerce & Arts. HSC Admission 2026 is now open.">
    <meta name="keywords" content="Maestro Crown College, MCC, Savar College, HSC, Higher Secondary, Dhaka, Admission 2026">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:    #1a3760;
            --navy-dk: #0f2347;
            --gold:    #c9a227;
            --gold-lt: #f0c84a;
            --green:   #1d6a3b;
            --white:   #ffffff;
            --off-white: #f6f7fb;
            --gray-lt: #e8ecf2;
            --gray:    #8a94a6;
            --gray-dk: #3d4a5c;
            --text:    #1a2535;
            --radius:  8px;
            --shadow:  0 4px 24px rgba(0,0,0,0.10);
            --shadow-lg: 0 12px 48px rgba(0,0,0,0.15);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text);
            background: var(--white);
            overflow-x: hidden;
            line-height: 1.65;
        }

        /* ── Utility ────────────────────────────────── */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        .section-pad { padding: 80px 0; }
        .section-pad-sm { padding: 56px 0; }

        .tag {
            display: inline-block;
            background: rgba(201,162,39,.15);
            color: var(--gold);
            border: 1px solid rgba(201,162,39,.35);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 4px 14px;
            border-radius: 30px;
            margin-bottom: 14px;
        }

        .section-heading {
            font-family: 'Merriweather', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--navy);
            line-height: 1.3;
            margin-bottom: 12px;
        }

        .section-sub {
            color: var(--gray);
            font-size: 1rem;
            max-width: 600px;
        }

        .divider {
            display: block;
            width: 48px;
            height: 4px;
            background: var(--gold);
            border-radius: 2px;
            margin: 16px 0 28px;
        }

        .divider.center { margin: 16px auto 28px; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: .9rem;
            font-weight: 600;
            padding: 12px 26px;
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all .25s ease;
        }

        .btn-primary {
            background: var(--navy);
            color: var(--white);
        }

        .btn-primary:hover { background: var(--navy-dk); color: var(--white); transform: translateY(-2px); box-shadow: var(--shadow); }

        .btn-gold {
            background: var(--gold);
            color: var(--white);
        }

        .btn-gold:hover { background: #b8901f; color: var(--white); transform: translateY(-2px); }

        .btn-outline {
            background: transparent;
            color: var(--navy);
            border: 2px solid var(--navy);
        }

        .btn-outline:hover { background: var(--navy); color: var(--white); transform: translateY(-2px); }

        .btn-outline-white {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255,255,255,.6);
        }

        .btn-outline-white:hover { background: rgba(255,255,255,.15); color: var(--white); }

        /* ── Top Info Bar ───────────────────────────── */
        .top-bar {
            background: var(--navy-dk);
            color: rgba(255,255,255,.75);
            font-size: .82rem;
            padding: 8px 0;
            border-bottom: 2px solid var(--gold);
        }

        .top-bar .inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .top-bar a { color: var(--gold-lt); text-decoration: none; font-weight: 600; transition: opacity .2s; }
        .top-bar a:hover { opacity: .8; }
        .top-bar .info-items { display: flex; gap: 20px; flex-wrap: wrap; align-items: center; }
        .top-bar .info-items span { display: flex; align-items: center; gap: 6px; }
        .top-bar .admission-cta {
            background: var(--gold);
            color: var(--white) !important;
            padding: 5px 14px;
            border-radius: 4px;
            font-weight: 700;
            font-size: .8rem;
            animation: pulse-gold 2s infinite;
        }

        @keyframes pulse-gold {
            0%, 100% { box-shadow: 0 0 0 0 rgba(201,162,39,.5); }
            50% { box-shadow: 0 0 0 6px rgba(201,162,39,0); }
        }

        /* ── Navigation ─────────────────────────────── */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: var(--white);
            box-shadow: 0 2px 20px rgba(0,0,0,.09);
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 0;
            gap: 16px;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .nav-logo {
            width: 58px;
            height: 58px;
            background: linear-gradient(135deg, var(--navy), var(--navy-dk));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border: 2px solid var(--gold);
            flex-shrink: 0;
        }

        .nav-logo i { font-size: 26px; color: var(--gold); }

        .brand-name {
            font-family: 'Merriweather', serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--navy);
            line-height: 1.2;
        }

        .brand-info {
            font-size: .72rem;
            color: var(--gray);
            font-weight: 500;
            line-height: 1.4;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2px;
            list-style: none;
        }

        .nav-links a {
            display: block;
            padding: 8px 14px;
            font-size: .875rem;
            font-weight: 500;
            color: var(--gray-dk);
            text-decoration: none;
            border-radius: var(--radius);
            white-space: nowrap;
            transition: all .2s;
        }

        .nav-links a:hover { color: var(--navy); background: var(--off-white); }

        .nav-links .has-dropdown { position: relative; }

        .nav-links .has-dropdown:hover > .dropdown { display: block; }

        .dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 200px;
            background: var(--white);
            border: 1px solid var(--gray-lt);
            border-top: 3px solid var(--navy);
            border-radius: 0 0 var(--radius) var(--radius);
            box-shadow: var(--shadow-lg);
            padding: 8px 0;
            z-index: 100;
        }

        .dropdown a {
            padding: 9px 18px;
            border-radius: 0;
            font-size: .84rem;
        }

        .nav-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

        .hamburger {
            display: none;
            background: none;
            border: 1px solid var(--gray-lt);
            border-radius: var(--radius);
            padding: 8px 12px;
            cursor: pointer;
            color: var(--navy);
            font-size: 1.2rem;
        }

        /* ── Internal Page Header ───────────────────────── */
        .page-header {
            background: linear-gradient(135deg, var(--navy-dk) 0%, #1a3760 100%);
            padding: 60px 0;
            text-align: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 60% 60% at 50% 50%, rgba(201,162,39,.12), transparent);
        }

        .page-header h1 {
            font-family: 'Merriweather', serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }

        .page-header p {
            color: rgba(255,255,255,.7);
            font-size: 1.05rem;
            position: relative;
            z-index: 2;
        }

        .page-content {
            padding: 60px 0 100px;
            background: var(--white);
        }

        .content-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 40px;
            border: 1px solid var(--gray-lt);
        }
        
        .content-card h2 {
            font-family: 'Merriweather', serif;
            color: var(--navy);
            margin-bottom: 20px;
        }

        .content-card h3 {
            color: var(--navy);
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .content-card p {
            margin-bottom: 16px;
            color: var(--gray-dk);
        }

        .content-card ul {
            margin-left: 20px;
            margin-bottom: 20px;
            color: var(--gray-dk);
        }
        
        .content-card li { margin-bottom: 8px; }

        /* ── Contact Footer ──────────────────────────── */
        .contact-section { background: var(--off-white); }

        .contact-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            padding-bottom: 40px;
        }

        .contact-brand { font-family: 'Merriweather', serif; font-size: 1.2rem; color: var(--navy); font-weight: 700; margin-bottom: 12px; }
        .contact-desc { color: var(--gray); font-size: .875rem; line-height: 1.7; margin-bottom: 20px; }

        .social-row { display: flex; gap: 10px; }

        .social-btn {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--navy);
            font-size: .9rem;
            text-decoration: none;
            transition: all .2s;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        .social-btn:hover { background: var(--navy); color: var(--white); }

        .footer-heading { font-size: .85rem; font-weight: 700; color: var(--navy); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 16px; }

        .footer-links { list-style: none; }

        .footer-links li { margin-bottom: 10px; }

        .footer-links a {
            color: var(--gray);
            text-decoration: none;
            font-size: .875rem;
            transition: color .2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-links a:hover { color: var(--navy); }

        .contact-info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .contact-info-item i {
            color: var(--gold);
            font-size: .9rem;
            margin-top: 3px;
            width: 16px;
            flex-shrink: 0;
        }

        .contact-info-item span { font-size: .875rem; color: var(--gray); line-height: 1.5; }
        .contact-info-item a { color: var(--navy); font-weight: 600; text-decoration: none; font-size: .875rem; }
        .contact-info-item a:hover { color: var(--gold); }

        .footer-bottom {
            background: var(--navy-dk);
            color: rgba(255,255,255,.5);
            padding: 18px 0;
            font-size: .82rem;
        }

        .footer-bottom .inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }

        .gov-links { display: flex; flex-wrap: wrap; gap: 12px; }

        .gov-link {
            color: rgba(255,255,255,.55);
            text-decoration: none;
            font-size: .78rem;
            transition: color .2s;
        }

        .gov-link:hover { color: var(--gold-lt); }

        /* ── Responsive ──────────────────────────────── */
        @media (max-width: 1024px) {
            .contact-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
        }

        @media (max-width: 768px) {
            .contact-grid { grid-template-columns: 1fr; }
            .nav-links, .nav-actions { display: none; }
            .hamburger { display: block; }
            .section-pad { padding: 56px 0; }
            .page-header h1 { font-size: 2rem; }
            .content-card { padding: 24px; }
            
            /* Mobile Dropdown Fix */
            .nav-links .has-dropdown:hover > .dropdown,
            .nav-links .has-dropdown:focus-within > .dropdown { display: block; }
            .dropdown {
                position: static;
                box-shadow: none;
                border: none;
                padding: 0 0 0 16px;
                margin-top: 4px;
                border-left: 2px solid var(--gray-lt);
                border-radius: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Top Information Bar -->
    <div class="top-bar">
        <div class="container inner">
            <div class="info-items">
                <span><i class="fa-solid fa-location-dot"></i> 107/2, Dogormura, Radio Colony, Savar, Dhaka – 1343</span>
                <span><i class="fa-solid fa-phone"></i> <a href="tel:+8801309136876">+880 1309-136876</a></span>
                <span><i class="fa-solid fa-envelope"></i> <a href="mailto:admin@maestrocrown.edu.bd">admin@maestrocrown.edu.bd</a></span>
            </div>
            <div>
                <a href="{{ route('frontend.admission') }}" class="admission-cta">
                    <i class="fa-solid fa-bolt"></i> HSC Admission 2026 – Apply Now
                </a>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-inner">
                <!-- Brand -->
                <a href="{{ route('landing') }}" class="nav-brand">
                    <div class="nav-logo" style="padding: 4px; background: transparent; border: none;">
                        <img src="{{ asset('assets/uploads/logo/17880717464.png') }}" alt="MCC Logo" style="width: 54px; height: 54px; object-fit: contain; border-radius: 8px;">
                    </div>
                    <div>
                        <div class="brand-name">Maestro Crown College</div>
                        <div class="brand-info">College Code: 1933 &nbsp;|&nbsp; EIIN: 136876 &nbsp;|&nbsp; Est. 2014</div>
                    </div>
                </a>

                <!-- Nav Links -->
                <ul class="nav-links">
                    <li><a href="{{ route('landing') }}">Home</a></li>
                    <li><a href="{{ route('frontend.about') }}">About</a></li>
                    <li class="has-dropdown">
                        <a href="#">Administration ▾</a>
                        <div class="dropdown">
                            <a href="{{ route('frontend.governing_body') }}">Governing Body</a>
                            <a href="{{ route('frontend.chairman_profile') }}">Chairman's Profile</a>
                            <a href="{{ route('frontend.message_chairman') }}">Message of Chairman</a>
                            <a href="{{ route('frontend.message_principal') }}">Message of Principal</a>
                            <a href="{{ route('frontend.teacher_info') }}">Teacher Information</a>
                            <a href="{{ route('frontend.staff_info') }}">Staff Information</a>
                        </div>
                    </li>
                    <li class="has-dropdown">
                        <a href="#">Academic ▾</a>
                        <div class="dropdown">
                            <a href="{{ route('frontend.academic_calendar') }}">Academic Calendar</a>
                            <a href="{{ route('frontend.programs') }}">Programs</a>
                            <a href="{{ route('frontend.notice_board') }}">Notice Board</a>
                        </div>
                    </li>
                    <li><a href="{{ route('frontend.admission') }}">Admission</a></li>
                    <li><a href="{{ route('frontend.gallery') }}">Gallery</a></li>
                    <li><a href="{{ route('frontend.contact') }}">Contact</a></li>
                </ul>

                <!-- Actions -->
                <div class="nav-actions">
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="fa-solid fa-right-to-bracket"></i> EMS Login
                    </a>
                    <a href="{{ route('admissions.index') }}" class="btn btn-gold">
                        <i class="fa-solid fa-pen-to-square"></i> Apply
                    </a>
                </div>

                <button class="hamburger">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

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

    <!-- ── Contact & Footer ───────────────────────────── -->
    <section id="contact" class="contact-section section-pad">
        <div class="container">
            <div class="contact-grid">
                <div>
                    <div class="nav-logo" style="width:64px; height:64px; margin-bottom:16px; padding: 4px; background: transparent; border: none;">
                        <img src="{{ asset('assets/uploads/logo/17880717464.png') }}" alt="MCC Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;">
                    </div>
                    <div class="contact-brand">Maestro Crown College</div>
                    <p class="contact-desc">
                        Dedicated to providing the best educational environment and resources for a brighter future. Established 2014, Savar, Dhaka, Bangladesh.<br>
                        College Code: 1933 &nbsp;|&nbsp; EIIN: 136876
                    </p>
                    <div class="social-row">
                        <a href="https://www.facebook.com/Maestrocrown" target="_blank" class="social-btn" title="Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="mailto:admin@maestrocrown.edu.bd" class="social-btn" title="Email">
                            <i class="fa-solid fa-envelope"></i>
                        </a>
                        <a href="tel:+8801309136876" class="social-btn" title="Call">
                            <i class="fa-solid fa-phone"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <div class="footer-heading">Quick Links</div>
                    <ul class="footer-links">
                        <li><a href="{{ route('landing') }}"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i> Home</a></li>
                        <li><a href="{{ route('frontend.about') }}"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i> About Us</a></li>
                        <li><a href="{{ route('frontend.programs') }}"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i> Programs</a></li>
                        <li><a href="{{ route('frontend.admission') }}"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i> Admission 2026</a></li>
                        <li><a href="{{ route('frontend.gallery') }}"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i> Gallery</a></li>
                        <li><a href="{{ route('login') }}"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i> EMS Portal Login</a></li>
                    </ul>
                </div>
                <div>
                    <div class="footer-heading">Administration</div>
                    <ul class="footer-links">
                        <li><a href="{{ route('frontend.governing_body') }}"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i> Governing Body</a></li>
                        <li><a href="{{ route('frontend.chairman_profile') }}"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i> Chairman's Profile</a></li>
                        <li><a href="{{ route('frontend.message_chairman') }}"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i> Chairman's Message</a></li>
                        <li><a href="{{ route('frontend.message_principal') }}"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i> Principal's Message</a></li>
                        <li><a href="{{ route('frontend.teacher_info') }}"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i> Teacher Information</a></li>
                    </ul>
                </div>
                <div>
                    <div class="footer-heading">Contact Us</div>
                    <div class="contact-info-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>107/2, Dogormura, Radio Colony, Dhaka-Aricha Highway, Savar, Dhaka, Bangladesh – 1343</span>
                    </div>
                    <div class="contact-info-item">
                        <i class="fa-solid fa-phone"></i>
                        <a href="tel:+8801309136876">+880 1309-136876</a>
                    </div>
                    <div class="contact-info-item">
                        <i class="fa-solid fa-envelope"></i>
                        <a href="mailto:admin@maestrocrown.edu.bd">admin@maestrocrown.edu.bd</a>
                    </div>
                    <div class="contact-info-item">
                        <i class="fa-brands fa-facebook"></i>
                        <a href="https://www.facebook.com/Maestrocrown" target="_blank">facebook.com/Maestrocrown</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Bottom Bar -->
    <div class="footer-bottom">
        <div class="container inner">
            <div>&copy; {{ date('Y') }} Maestro Crown College. All Rights Reserved. | EIIN: 136876 | College Code: 1933</div>
            <div class="gov-links">
                <a href="https://dhakaeducationboard.gov.bd/site/" target="_blank" class="gov-link">Dhaka Education Board</a>
                <a href="https://moedu.gov.bd/" target="_blank" class="gov-link">Ministry of Education</a>
                <a href="https://banbeis.gov.bd/" target="_blank" class="gov-link">BANBEIS</a>
                <a href="https://shed.gov.bd/" target="_blank" class="gov-link">DSHE</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS (for responsive toggle) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Hamburger menu toggle
        document.querySelector('.hamburger').addEventListener('click', function () {
            const links = document.querySelector('.nav-links');
            const actions = document.querySelector('.nav-actions');
            const isHidden = links.style.display !== 'flex';
            links.style.display = isHidden ? 'flex' : 'none';
            actions.style.display = isHidden ? 'flex' : 'none';
            links.style.flexDirection = 'column';
            links.style.position = 'absolute';
            links.style.top = '100%';
            links.style.left = '0';
            links.style.right = '0';
            links.style.background = '#fff';
            links.style.padding = '16px 24px';
            links.style.boxShadow = '0 12px 40px rgba(0,0,0,.12)';
            links.style.zIndex = '999';
        });

        // Sticky nav shadow
        window.addEventListener('scroll', function () {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 20) {
                navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,.12)';
            } else {
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,.09)';
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
