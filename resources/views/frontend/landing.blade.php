@extends("frontend.layouts.app")

@push('styles')
<style>
    /* ── Hero Section ─────────────────────────── */
    .hero {
        background: linear-gradient(135deg, var(--navy-dk) 0%, #1a3760 60%, #254a7a 100%);
        padding: 80px 0 60px;
        position: relative;
        overflow: hidden;
        color: var(--white);
    }
    .hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 70% 50% at 30% 50%, rgba(201,162,39,.10), transparent 70%),
                    radial-gradient(ellipse 50% 60% at 80% 30%, rgba(255,255,255,.04), transparent);
    }
    .hero::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        height: 60px;
        background: linear-gradient(to top, var(--white), transparent);
    }
    .hero-content { position: relative; z-index: 2; }
    .hero-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 40px;
        align-items: center;
    }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(201,162,39,.2);
        border: 1px solid rgba(201,162,39,.4);
        color: var(--gold-lt);
        font-size: .82rem;
        font-weight: 700;
        padding: 6px 16px;
        border-radius: 30px;
        margin-bottom: 20px;
        animation: pulse-gold 2s infinite;
    }
    .hero-badge .dot {
        width: 8px; height: 8px;
        background: var(--gold-lt);
        border-radius: 50%;
        animation: blink 1.5s infinite;
    }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
    .hero-title {
        font-family: 'Merriweather', serif;
        font-size: 2.8rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 18px;
    }
    .hero-title .highlight {
        display: block;
        color: var(--gold-lt);
        font-size: 1.6rem;
        font-weight: 400;
        margin-top: 4px;
    }
    .hero-desc {
        color: rgba(255,255,255,.72);
        font-size: 1rem;
        line-height: 1.7;
        margin-bottom: 28px;
        max-width: 540px;
    }
    .hero-actions { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 36px; }
    .hero-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        border-top: 1px solid rgba(255,255,255,.1);
        padding-top: 24px;
    }
    .hero-stat-num {
        font-family: 'Merriweather', serif;
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--gold-lt);
    }
    .hero-stat-label {
        font-size: .78rem;
        color: rgba(255,255,255,.5);
        margin-top: 2px;
    }

    /* Hero Card */
    .hero-card {
        background: rgba(255,255,255,.07);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px;
        overflow: hidden;
    }
    .hero-card-head {
        background: rgba(255,255,255,.06);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255,255,255,.07);
    }
    .hero-card-head h3 { font-size: .95rem; font-weight: 600; color: var(--white); margin: 0; }
    .hero-card-head span { font-size: .8rem; color: var(--gold-lt); font-weight: 600; }
    .hero-card-body { padding: 8px 0; }
    .notice-row {
        padding: 14px 20px;
        gap: 14px;
        align-items: flex-start;
        transition: background .2s;
        border-bottom: 1px solid rgba(255,255,255,.04);
    }
    .notice-row:hover { background: rgba(255,255,255,.05); }
    .notice-dot {
        width: 10px; height: 10px;
        background: #3b82f6;
        border-radius: 50%;
        margin-top: 5px;
        flex-shrink: 0;
    }
    .notice-text { color: var(--white); font-size: .875rem; font-weight: 500; line-height: 1.4; }
    .notice-date { color: rgba(255,255,255,.4); font-size: .78rem; margin-top: 4px; }

    /* ── Stats Row ────────────────────────────── */
    .stats-row {
        background: var(--white);
        padding: 0;
        margin-top: -30px;
        position: relative;
        z-index: 5;
    }
    .stats-inner {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0;
        background: var(--white);
        border-radius: 16px;
        box-shadow: 0 8px 40px rgba(0,0,0,.10);
        overflow: hidden;
    }
    .stat-block {
        text-align: center;
        padding: 32px 20px;
        border-right: 1px solid var(--gray-lt);
        transition: background .2s;
    }
    .stat-block:last-child { border-right: none; }
    .stat-block:hover { background: var(--off-white); }
    .stat-icon {
        width: 48px; height: 48px;
        background: rgba(201,162,39,.12);
        color: var(--gold);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin: 0 auto 12px;
    }
    .stat-number {
        font-family: 'Merriweather', serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--navy);
    }
    .stat-label {
        color: var(--gray);
        font-size: .82rem;
        margin-top: 4px;
    }

    /* ── About Section ────────────────────────── */
    .about-grid {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 50px;
        align-items: center;
    }
    .about-visual { position: relative; }
    .about-img-wrap {
        background: linear-gradient(135deg, var(--off-white), var(--gray-lt));
        border-radius: 16px;
        padding: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 340px;
        position: relative;
        overflow: hidden;
    }
    .about-img-wrap img {
        max-width: 220px;
        height: auto;
        filter: drop-shadow(0 8px 24px rgba(0,0,0,.15));
    }
    .about-badge {
        position: absolute;
        bottom: -16px;
        right: -16px;
        background: var(--navy);
        color: var(--white);
        padding: 16px 24px;
        border-radius: 12px;
        text-align: center;
        box-shadow: var(--shadow-lg);
        z-index: 2;
    }
    .about-badge .num { display: block; font-family: 'Merriweather', serif; font-size: 1.6rem; font-weight: 700; color: var(--gold-lt); }
    .about-badge .lbl { font-size: .75rem; color: rgba(255,255,255,.6); }
    .achievement-list { display: flex; flex-direction: column; gap: 16px; margin-top: 10px; }
    .achievement-item {
        display: flex;
        gap: 14px;
        padding: 14px;
        background: var(--off-white);
        border-radius: 10px;
        border: 1px solid var(--gray-lt);
        transition: transform .2s, box-shadow .2s;
    }
    .achievement-item:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
    .ach-icon {
        width: 42px; height: 42px;
        background: rgba(201,162,39,.12);
        color: var(--gold);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .ach-title { font-weight: 600; font-size: .9rem; color: var(--navy); margin-bottom: 3px; }
    .ach-desc { font-size: .82rem; color: var(--gray); line-height: 1.4; }

    /* ── Programs Section ─────────────────────── */
    .programs-bg { background: var(--off-white); }
    .programs-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 28px;
        margin-top: 36px;
    }
    .program-card {
        background: var(--white);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: transform .25s, box-shadow .25s;
        border: 1px solid var(--gray-lt);
    }
    .program-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); }
    .program-card-top {
        padding: 32px 24px;
        text-align: center;
        color: var(--white);
        position: relative;
    }
    .program-card-top.science { background: linear-gradient(135deg, #1a3760, #254a7a); }
    .program-card-top.commerce { background: linear-gradient(135deg, #0f6b3a, #1a8a4e); }
    .program-card-top.arts { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
    .prog-icon {
        width: 56px; height: 56px;
        background: rgba(255,255,255,.15);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 14px;
    }
    .prog-title { font-family: 'Merriweather', serif; font-size: 1.3rem; font-weight: 700; }
    .prog-sub { font-size: .8rem; color: rgba(255,255,255,.6); margin-top: 4px; }
    .program-card-body { padding: 24px; }
    .subject-list {
        list-style: none;
        padding: 0;
        margin: 0 0 20px;
    }
    .subject-list li {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        font-size: .88rem;
        color: var(--gray-dk);
        border-bottom: 1px solid var(--gray-lt);
    }
    .subject-list li:last-child { border-bottom: none; }
    .subject-list li i { color: var(--green); font-size: .8rem; }

    /* ── Leadership Messages ──────────────────── */
    .msg-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 28px;
        margin-top: 28px;
    }
    .msg-card {
        background: var(--white);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow);
        border-top: 4px solid var(--navy);
        transition: transform .25s;
    }
    .msg-card.gold-top { border-top-color: var(--gold); }
    .msg-card:hover { transform: translateY(-4px); }
    .msg-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 24px;
        border-bottom: 1px solid var(--gray-lt);
    }
    .msg-avatar {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, var(--navy), var(--navy-dk));
        color: var(--gold-lt);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .msg-name { font-weight: 700; font-size: 1rem; color: var(--navy); }
    .msg-role { font-size: .82rem; color: var(--gray); }
    .msg-role span { color: var(--gold); font-weight: 600; }
    .msg-body { padding: 24px; }
    .msg-quote {
        font-style: italic;
        color: var(--gray-dk);
        font-size: .9rem;
        line-height: 1.7;
        border-left: 3px solid var(--gold);
        padding-left: 16px;
        margin: 0 0 16px;
    }
    .msg-read-more {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--navy);
        font-weight: 600;
        font-size: .85rem;
        text-decoration: none;
        transition: color .2s;
    }
    .msg-read-more:hover { color: var(--gold); }

    /* ── Notices & Quick Links ────────────────── */
    .notices-bg { background: var(--off-white); }
    .notices-wrapper {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 28px;
        margin-top: 28px;
    }
    .notice-list-card, .quick-links-card {
        background: var(--white);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow);
        border: 1px solid var(--gray-lt);
    }
    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        background: var(--off-white);
        border-bottom: 1px solid var(--gray-lt);
    }
    .card-header h3 { font-size: .9rem; font-weight: 700; color: var(--navy); margin: 0; }
    .card-header a { font-size: .82rem; color: var(--gold); text-decoration: none; font-weight: 600; }
    .notice-list-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 20px;
        border-bottom: 1px solid var(--gray-lt);
        text-decoration: none;
        color: var(--text);
        transition: background .2s;
    }
    .notice-list-item:last-child { border-bottom: none; }
    .notice-list-item:hover { background: var(--off-white); }
    .date-badge {
        width: 52px; height: 52px;
        background: var(--navy);
        color: var(--white);
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .date-badge .month { font-size: .6rem; font-weight: 700; text-transform: uppercase; }
    .date-badge .day { font-size: 1.2rem; line-height: 1; }
    .notice-info { flex: 1; min-width: 0; }
    .notice-title { font-size: .88rem; font-weight: 600; color: var(--navy); line-height: 1.3; }
    .notice-cat { font-size: .78rem; color: var(--gray); margin-top: 3px; }
    .quick-link-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 20px;
        border-bottom: 1px solid var(--gray-lt);
        text-decoration: none;
        color: var(--gray-dk);
        font-size: .88rem;
        font-weight: 500;
        transition: all .2s;
    }
    .quick-link-item:last-child { border-bottom: none; }
    .quick-link-item:hover { background: var(--off-white); color: var(--navy); padding-left: 24px; }
    .quick-link-item i { width: 18px; text-align: center; color: var(--gold); font-size: .85rem; }

    /* ── Gallery ──────────────────────────────── */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
        margin-top: 28px;
    }
    .gallery-item {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: transform .25s;
    }
    .gallery-item:hover { transform: translateY(-4px); }
    .gallery-placeholder {
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: rgba(255,255,255,.8);
        position: relative;
    }
    .gallery-placeholder::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(0,0,0,.2), transparent);
    }
    .g1 { background: linear-gradient(135deg, #1a3760, #3b82f6); }
    .g2 { background: linear-gradient(135deg, #0f6b3a, #22c55e); }
    .g3 { background: linear-gradient(135deg, #7c3aed, #a855f7); }
    .g4 { background: linear-gradient(135deg, #b8901f, #f0c84a); }
    .g5 { background: linear-gradient(135deg, #0f2347, #1a3760); }
    .gallery-caption {
        padding: 10px 14px;
        font-size: .78rem;
        font-weight: 600;
        color: var(--navy);
        background: var(--white);
    }

    /* ── EMS Portals ──────────────────────────── */
    .portals-bg {
        background: linear-gradient(135deg, var(--navy-dk), #1a3760);
        color: var(--white);
    }
    .portals-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
        margin-top: 32px;
    }
    .portal-card {
        background: rgba(255,255,255,.06);
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 14px;
        padding: 24px 16px;
        text-align: center;
        text-decoration: none;
        color: var(--white);
        transition: all .25s;
    }
    .portal-card:hover { background: rgba(255,255,255,.12); transform: translateY(-4px); color: var(--white); }
    .p-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin: 0 auto 12px;
    }
    .p-name { font-weight: 700; font-size: .9rem; margin-bottom: 4px; }
    .p-desc { font-size: .78rem; color: rgba(255,255,255,.45); }

    /* ── CTA ──────────────────────────────────── */
    .cta-section {
        background: linear-gradient(135deg, var(--gold), #b8901f);
        padding: 50px 0;
    }
    .cta-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
        flex-wrap: wrap;
    }
    .cta-text h2 { font-family: 'Merriweather', serif; font-size: 1.6rem; color: var(--white); margin-bottom: 6px; }
    .cta-text p { color: rgba(255,255,255,.8); font-size: .95rem; margin: 0; }

    /* ── Responsive ───────────────────────────── */
    @media (max-width: 1024px) {
        .hero-grid { grid-template-columns: 1fr; }
        .hero-card { max-width: 480px; }
        .programs-grid { grid-template-columns: 1fr 1fr; }
        .portals-grid { grid-template-columns: repeat(3, 1fr); }
        .gallery-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 768px) {
        .hero { padding: 50px 0 40px; }
        .hero-title { font-size: 2rem; }
        .hero-title .highlight { font-size: 1.2rem; }
        .hero-stats { grid-template-columns: repeat(2, 1fr); }
        .stats-inner { grid-template-columns: repeat(2, 1fr); }
        .stat-block:nth-child(2) { border-right: none; }
        .about-grid { grid-template-columns: 1fr; }
        .programs-grid { grid-template-columns: 1fr; }
        .msg-grid { grid-template-columns: 1fr; }
        .notices-wrapper { grid-template-columns: 1fr; }
        .portals-grid { grid-template-columns: repeat(2, 1fr); }
        .gallery-grid { grid-template-columns: repeat(2, 1fr); }
        .cta-inner { flex-direction: column; text-align: center; }
    }
</style>
@endpush

@section("content")
    <!-- ── Hero Section ─────────────────────────────────── -->
    <section id="home" class="hero">
        <div class="container hero-content">
            <div class="hero-grid">
                <div>
                    <div class="hero-badge">
                        <span class="dot"></span> HSC Admission 2026 Now Open
                    </div>
                    <h1 class="hero-title">
                        Maestro Crown College
                        <span class="highlight">Best Education Is Here</span>
                    </h1>
                    <p class="hero-desc">
                        Established in 2014 in Savar, Dhaka, Maestro Crown College is dedicated to nurturing tomorrow's leaders through academic excellence, character development, and a commitment to national progress.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ route('admissions.index') }}" class="btn btn-gold">
                            <i class="fa-solid fa-file-signature"></i> Apply for Admission 2026
                        </a>
                        <a href="#about" class="btn btn-outline-white">
                            <i class="fa-solid fa-circle-info"></i> Learn More
                        </a>
                    </div>
                    <div class="hero-stats">
                        <div>
                            <div class="hero-stat-num">2014</div>
                            <div class="hero-stat-label">Established</div>
                        </div>
                        <div>
                            <div class="hero-stat-num">1933</div>
                            <div class="hero-stat-label">College Code</div>
                        </div>
                        <div>
                            <div class="hero-stat-num">#1</div>
                            <div class="hero-stat-label">Savar Results 2017</div>
                        </div>
                        <div>
                            <div class="hero-stat-num">A+</div>
                            <div class="hero-stat-label">Board Excellence</div>
                        </div>
                    </div>
                </div>

                <!-- Notice Card -->
                <div class="hero-card">
                    <div class="hero-card-head">
                        <h3><i class="fa-solid fa-bell"></i> &nbsp;Latest Notices</h3>
                        <span>2026 Session</span>
                    </div>
                    <div class="hero-card-body">
                        <a href="{{ route('frontend.academic_calendar') }}" class="notice-row" style="text-decoration:none; display:flex;">
                            <div class="notice-dot"></div>
                            <div>
                                <div class="notice-text">Maestro Crown College Academic Calendar – 2026</div>
                                <div class="notice-date"><i class="fa-regular fa-calendar"></i> Academic Notice</div>
                            </div>
                        </a>
                        <a href="{{ route('frontend.notice_board') }}" class="notice-row" style="text-decoration:none; display:flex;">
                            <div class="notice-dot" style="background:#f59e0b;"></div>
                            <div>
                                <div class="notice-text">Routine of Test Examination – 2026: Class XII Students</div>
                                <div class="notice-date"><i class="fa-regular fa-calendar"></i> Exam Notice</div>
                            </div>
                        </a>
                        <a href="{{ route('frontend.admission') }}" class="notice-row" style="text-decoration:none; display:flex;">
                            <div class="notice-dot" style="background:#22c55e;"></div>
                            <div>
                                <div class="notice-text">HSC Admission 2026 is now open – Click to apply online</div>
                                <div class="notice-date"><i class="fa-regular fa-calendar"></i> Admission Notice</div>
                            </div>
                        </a>
                    </div>
                    <div style="padding: 16px 20px; border-top: 1px solid rgba(255,255,255,.07);">
                        <a href="{{ route('login') }}" class="btn btn-primary" style="width:100%; justify-content:center; font-size:.875rem; padding:11px;">
                            <i class="fa-solid fa-right-to-bracket"></i> Access EMS Student Portal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Stats Row ──────────────────────────────────── -->
    <div class="stats-row">
        <div class="container">
            <div class="stats-inner">
                <div class="stat-block">
                    <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="stat-number">A+</div>
                    <div class="stat-label">Board Excellence</div>
                </div>
                <div class="stat-block">
                    <div class="stat-icon"><i class="fa-solid fa-trophy"></i></div>
                    <div class="stat-number">#1</div>
                    <div class="stat-label">Savar Area Rank (2017)</div>
                </div>
                <div class="stat-block">
                    <div class="stat-icon"><i class="fa-solid fa-school"></i></div>
                    <div class="stat-number">3</div>
                    <div class="stat-label">Programs Offered</div>
                </div>
                <div class="stat-block">
                    <div class="stat-icon"><i class="fa-solid fa-medal"></i></div>
                    <div class="stat-number">10+</div>
                    <div class="stat-label">Years of Excellence</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── About Section ──────────────────────────────── -->
    <section id="about" class="section-pad">
        <div class="container">
            <div class="about-grid">
                <div class="about-visual">
                    <div class="about-img-wrap">
                        <img src="{{ asset('assets/uploads/logo/17880717464.png') }}" alt="Maestro Crown College Logo">
                    </div>
                    <div class="about-badge">
                        <span class="num">2014</span>
                        <span class="lbl">Year Founded</span>
                    </div>
                </div>
                <div>
                    <div class="tag">About Us</div>
                    <h2 class="section-heading">Welcome to Maestro Crown College</h2>
                    <span class="divider"></span>
                    <p style="color: var(--gray-dk); font-size: .95rem; margin-bottom: 20px;">
                        Maestro Crown College was established in 2014 with a visionary mission: to deliver quality higher secondary education that builds both academic excellence and moral character. Located in Savar, along the Dhaka–Aricha Highway, our campus provides a vibrant, safe, and stimulating environment for learning.
                    </p>
                    <p style="color: var(--gray-dk); font-size: .95rem; margin-bottom: 28px;">
                        Recognized by the Dhaka Education Board with College Code 1933 and EIIN 136876, Maestro Crown College proudly stood first in the Savar area board exam results in 2017, with the majority of students achieving GPA 5.00 (A+).
                    </p>

                    <div class="achievement-list">
                        <div class="achievement-item">
                            <div class="ach-icon"><i class="fa-solid fa-trophy"></i></div>
                            <div>
                                <div class="ach-title">Top Results in Savar (2017)</div>
                                <div class="ach-desc">Most students achieved A+ in HSC Board Exams — #1 ranked college in Savar area.</div>
                            </div>
                        </div>
                        <div class="achievement-item">
                            <div class="ach-icon"><i class="fa-solid fa-futbol"></i></div>
                            <div>
                                <div class="ach-title">Sports & Extra-Curricular Awards</div>
                                <div class="ach-desc">Champions in inter-school and national competitions, demonstrating excellence beyond academics.</div>
                            </div>
                        </div>
                        <div class="achievement-item">
                            <div class="ach-icon"><i class="fa-solid fa-laptop-code"></i></div>
                            <div>
                                <div class="ach-title">Digital Education Management System</div>
                                <div class="ach-desc">Fully automated EMS portal for teachers, students, parents, and administrative staff.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Programs Section ───────────────────────────── -->
    <section id="programs" class="section-pad programs-bg">
        <div class="container">
            <div style="text-align: center;">
                <div class="tag">Academic Programs</div>
                <h2 class="section-heading">Courses & Departments</h2>
                <span class="divider center"></span>
                <p class="section-sub" style="margin: 0 auto;">
                    Three distinguished streams offering a structured and comprehensive Higher Secondary (HSC) curriculum aligned with the Dhaka Education Board.
                </p>
            </div>

            <div class="programs-grid">
                <!-- Science -->
                <div class="program-card">
                    <div class="program-card-top science">
                        <div class="prog-icon"><i class="fa-solid fa-flask-vial"></i></div>
                        <div class="prog-title">Science</div>
                        <div class="prog-sub">Higher Secondary Certificate (HSC)</div>
                    </div>
                    <div class="program-card-body">
                        <ul class="subject-list">
                            <li><i class="fa-solid fa-check-circle"></i> Physics</li>
                            <li><i class="fa-solid fa-check-circle"></i> Chemistry</li>
                            <li><i class="fa-solid fa-check-circle"></i> Mathematics</li>
                            <li><i class="fa-solid fa-check-circle"></i> Biology</li>
                            <li><i class="fa-solid fa-check-circle"></i> Higher Mathematics</li>
                        </ul>
                        <a href="{{ route('frontend.programs') }}" class="btn btn-primary" style="width:100%; justify-content:center;">
                            View Program Details
                        </a>
                    </div>
                </div>

                <!-- Commerce -->
                <div class="program-card">
                    <div class="program-card-top commerce">
                        <div class="prog-icon"><i class="fa-solid fa-chart-line"></i></div>
                        <div class="prog-title">Commerce</div>
                        <div class="prog-sub">Higher Secondary Certificate (HSC)</div>
                    </div>
                    <div class="program-card-body">
                        <ul class="subject-list">
                            <li><i class="fa-solid fa-check-circle"></i> Accounting</li>
                            <li><i class="fa-solid fa-check-circle"></i> Business Organization</li>
                            <li><i class="fa-solid fa-check-circle"></i> Finance & Banking</li>
                            <li><i class="fa-solid fa-check-circle"></i> Economics</li>
                            <li><i class="fa-solid fa-check-circle"></i> Statistics</li>
                        </ul>
                        <a href="{{ route('frontend.programs') }}" class="btn btn-primary" style="width:100%; justify-content:center;">
                            View Program Details
                        </a>
                    </div>
                </div>

                <!-- Humanities / Arts -->
                <div class="program-card">
                    <div class="program-card-top arts">
                        <div class="prog-icon"><i class="fa-solid fa-palette"></i></div>
                        <div class="prog-title">Humanities & Arts</div>
                        <div class="prog-sub">Higher Secondary Certificate (HSC)</div>
                    </div>
                    <div class="program-card-body">
                        <ul class="subject-list">
                            <li><i class="fa-solid fa-check-circle"></i> Bangla & Literature</li>
                            <li><i class="fa-solid fa-check-circle"></i> History</li>
                            <li><i class="fa-solid fa-check-circle"></i> Civics & Government</li>
                            <li><i class="fa-solid fa-check-circle"></i> Islamic Studies</li>
                            <li><i class="fa-solid fa-check-circle"></i> Geography</li>
                        </ul>
                        <a href="{{ route('frontend.programs') }}" class="btn btn-primary" style="width:100%; justify-content:center;">
                            View Program Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Leadership Messages ────────────────────────── -->
    <section id="administration" class="section-pad">
        <div class="container">
            <div>
                <div class="tag">Leadership</div>
                <h2 class="section-heading">Messages from Our Leaders</h2>
                <span class="divider"></span>
            </div>
            <div class="msg-grid">
                <!-- Chairman -->
                <div class="msg-card">
                    <div class="msg-header">
                        <div class="msg-avatar"><i class="fa-solid fa-user-tie"></i></div>
                        <div>
                            <div class="msg-name">Dr. Kamrul Ahsan</div>
                            <div class="msg-role"><span>Chairman</span>, Maestro Crown College</div>
                        </div>
                    </div>
                    <div class="msg-body">
                        <blockquote class="msg-quote">
                            "Dear Families, from an early age, while many aspired to become doctors, engineers, or business leaders, my ambition was different. I wanted to become a teacher. Shaping minds and guiding young people is one of the most powerful ways to build a nation. At Maestro Crown College, we are committed to that noble mission every single day."
                        </blockquote>
                        <a href="{{ route('frontend.message_chairman') }}" class="msg-read-more">
                            Read Full Message <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Principal -->
                <div class="msg-card gold-top">
                    <div class="msg-header">
                        <div class="msg-avatar" style="background: linear-gradient(135deg, var(--gold), #b8901f);"><i class="fa-solid fa-graduation-cap"></i></div>
                        <div>
                            <div class="msg-name">Md. Khalilur Rahman</div>
                            <div class="msg-role"><span>Principal</span>, Maestro Crown College</div>
                        </div>
                    </div>
                    <div class="msg-body">
                        <blockquote class="msg-quote">
                            "Dear All, I am truly humbled to welcome you to our vibrant learning community. As Principal, I see every day as an opportunity to make a difference in the lives of our students and their families. Our teachers, staff, and students work together to build an environment where excellence is not just expected — it is celebrated."
                        </blockquote>
                        <a href="{{ route('frontend.message_principal') }}" class="msg-read-more">
                            Read Full Message <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Notices & Quick Links ──────────────────────── -->
    <section class="section-pad notices-bg">
        <div class="container">
            <div>
                <div class="tag">Information</div>
                <h2 class="section-heading">Notices & Quick Links</h2>
                <span class="divider"></span>
            </div>
            <div class="notices-wrapper">
                <!-- Notice Board -->
                <div class="notice-list-card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-bullhorn"></i> Official Notice Board</h3>
                        <a href="{{ route('frontend.notice_board') }}">View All →</a>
                    </div>
                    <a href="{{ route('frontend.academic_calendar') }}" class="notice-list-item">
                        <div class="date-badge">
                            <div class="month">2026</div>
                            <div class="day">📅</div>
                        </div>
                        <div class="notice-info">
                            <div class="notice-title">Maestro Crown College Academic Calendar – 2026</div>
                            <div class="notice-cat">📌 Academic Notice</div>
                        </div>
                    </a>
                    <a href="{{ route('frontend.notice_board') }}" class="notice-list-item">
                        <div class="date-badge" style="background: var(--gold);">
                            <div class="month">2026</div>
                            <div class="day">📋</div>
                        </div>
                        <div class="notice-info">
                            <div class="notice-title">Routine of Test Examination – 2026: Class XII Students</div>
                            <div class="notice-cat">📌 Exam Schedule</div>
                        </div>
                    </a>
                    <a href="{{ route('frontend.admission') }}" class="notice-list-item">
                        <div class="date-badge" style="background: var(--green);">
                            <div class="month">2026</div>
                            <div class="day">📝</div>
                        </div>
                        <div class="notice-info">
                            <div class="notice-title">HSC Admission 2026 is Now Open – Apply Online</div>
                            <div class="notice-cat">📌 Admission Notice</div>
                        </div>
                    </a>
                </div>

                <!-- Quick Links -->
                <div class="quick-links-card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-link"></i> Quick Links</h3>
                    </div>
                    <a href="{{ route('login') }}" class="quick-link-item">
                        <i class="fa-solid fa-right-to-bracket"></i> EMS Student Portal Login
                    </a>
                    <a href="{{ route('login') }}" class="quick-link-item">
                        <i class="fa-solid fa-chalkboard-user"></i> EMS Teacher Login
                    </a>
                    <a href="{{ route('login') }}" class="quick-link-item">
                        <i class="fa-solid fa-people-roof"></i> EMS Parent Portal
                    </a>
                    <a href="{{ route('frontend.admission') }}" class="quick-link-item">
                        <i class="fa-solid fa-file-signature"></i> Online Admission Form
                    </a>
                    <a href="https://dhakaeducationboard.gov.bd/site/" target="_blank" class="quick-link-item">
                        <i class="fa-solid fa-landmark"></i> Dhaka Education Board
                    </a>
                    <a href="https://moedu.gov.bd/" target="_blank" class="quick-link-item">
                        <i class="fa-solid fa-building"></i> Ministry of Education
                    </a>
                    <a href="https://banbeis.gov.bd/" target="_blank" class="quick-link-item">
                        <i class="fa-solid fa-chart-pie"></i> BANBEIS
                    </a>
                    <a href="https://shed.gov.bd/" target="_blank" class="quick-link-item">
                        <i class="fa-solid fa-graduation-cap"></i> Directorate of Secondary Ed.
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Gallery ────────────────────────────────────── -->
    <section class="section-pad">
        <div class="container">
            <div>
                <div class="tag">Campus Life</div>
                <h2 class="section-heading">Photo Gallery</h2>
                <span class="divider"></span>
                <p class="section-sub">A glimpse into academic life, sporting achievements, and campus events at Maestro Crown College.</p>
            </div>
            <div class="gallery-grid">
                <div class="gallery-item">
                    <div class="gallery-placeholder g1">
                        <i class="fa-solid fa-graduation-cap" style="position:relative; z-index:1; font-size:3rem;"></i>
                    </div>
                    <div class="gallery-caption">HSC Students – Board Excellence</div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-placeholder g2">
                        <i class="fa-solid fa-futbol" style="position:relative; z-index:1; font-size:3rem;"></i>
                    </div>
                    <div class="gallery-caption">Sports Achievements</div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-placeholder g3">
                        <i class="fa-solid fa-music" style="position:relative; z-index:1; font-size:3rem;"></i>
                    </div>
                    <div class="gallery-caption">Cultural Programs & Events</div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-placeholder g4">
                        <i class="fa-solid fa-microscope" style="position:relative; z-index:1; font-size:3rem;"></i>
                    </div>
                    <div class="gallery-caption">Science Lab Activities</div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-placeholder g5">
                        <i class="fa-solid fa-users" style="position:relative; z-index:1; font-size:3rem;"></i>
                    </div>
                    <div class="gallery-caption">Annual Events & Gatherings</div>
                </div>
            </div>
            <div style="text-align:center; margin-top:28px;">
                <a href="{{ route('frontend.gallery') }}" class="btn btn-outline">
                    <i class="fa-solid fa-images"></i> View Full Gallery
                </a>
            </div>
        </div>
    </section>

    <!-- ── EMS Portals ────────────────────────────────── -->
    <section class="section-pad portals-bg">
        <div class="container">
            <div style="text-align:center;">
                <div class="tag" style="background:rgba(201,162,39,.2); color:var(--gold-lt);">Digital Access</div>
                <h2 class="section-heading" style="color:var(--white);">Educational Management System (EMS)</h2>
                <span class="divider center"></span>
                <p class="section-sub" style="color:rgba(255,255,255,.55); margin: 0 auto;">Secure dedicated portals for all members of the Maestro Crown College community.</p>
            </div>

            <div class="portals-grid">
                <a href="{{ route('login') }}" class="portal-card">
                    <div class="p-icon" style="background:rgba(59,130,246,.15); color:#60a5fa;">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div class="p-name">School Admin</div>
                    <div class="p-desc">Campus & staff management</div>
                </a>
                <a href="{{ route('login') }}" class="portal-card">
                    <div class="p-icon" style="background:rgba(16,185,129,.15); color:#34d399;">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <div class="p-name">Teacher Portal</div>
                    <div class="p-desc">Marks & attendance</div>
                </a>
                <a href="{{ route('login') }}" class="portal-card">
                    <div class="p-icon" style="background:rgba(201,162,39,.15); color:var(--gold-lt);">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <div class="p-name">Student Portal</div>
                    <div class="p-desc">Results, fees & notices</div>
                </a>
                <a href="{{ route('login') }}" class="portal-card">
                    <div class="p-icon" style="background:rgba(245,158,11,.15); color:#fbbf24;">
                        <i class="fa-solid fa-people-roof"></i>
                    </div>
                    <div class="p-name">Parent Portal</div>
                    <div class="p-desc">Child's progress & fees</div>
                </a>
                <a href="{{ route('login') }}" class="portal-card">
                    <div class="p-icon" style="background:rgba(139,92,246,.15); color:#c084fc;">
                        <i class="fa-solid fa-book-bookmark"></i>
                    </div>
                    <div class="p-name">Staff & Accounts</div>
                    <div class="p-desc">Library & fee management</div>
                </a>
            </div>
        </div>
    </section>

    <!-- ── CTA Admission Banner ───────────────────────── -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-inner">
                <div class="cta-text">
                    <h2>HSC Admission 2026 is Now Open!</h2>
                    <p>Join Maestro Crown College – Science, Commerce & Humanities available. Limited seats. Apply online today.</p>
                </div>
                <div style="display:flex; gap:14px; flex-wrap:wrap; flex-shrink:0;">
                    <a href="{{ route('frontend.admission') }}" class="btn btn-primary" style="background:var(--navy); font-size:1rem; padding:14px 28px;">
                        <i class="fa-solid fa-file-signature"></i> Apply Online Now
                    </a>
                    <a href="{{ route('frontend.admission') }}" class="btn btn-outline-white" style="font-size:1rem; padding:14px 28px;">
                        <i class="fa-solid fa-circle-info"></i> Admission Info
                    </a>
                </div>
            </div>
        </div>
    </section>

@endsection
