<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | Maestro Crown School and College</title>
    <link rel="icon" href="{{ asset('frontend/mcc/img/logo-256.png') }}">
    <link rel="stylesheet" href="{{ asset('css/mcc-office.css') }}?v={{ filemtime(public_path('css/mcc-office.css')) }}">
    <style>
        /* Two halves: the college on the left, the sign-in card on the right. Colours follow the app:
           light surfaces, crest red for actions, and the red-to-gold edge as the one flourish. */
        .signin { display: grid; grid-template-columns: minmax(0, 13fr) minmax(0, 12fr); min-height: 100vh; }

        .story { position: relative; overflow: hidden; display: flex; flex-direction: column; padding: clamp(28px, 5vh, 56px) clamp(28px, 4.5vw, 76px) 28px;
            background-color: #fffaf7;
            background-image: radial-gradient(rgba(198, 40, 40, .09) 1px, transparent 1.6px),
                radial-gradient(60% 55% at 100% 100%, rgba(212, 175, 55, .28), transparent 70%),
                radial-gradient(55% 50% at 0% 0%, rgba(198, 40, 40, .09), transparent 70%);
            background-size: 22px 22px, auto, auto; }
        .story::after { content: ''; position: absolute; inset: 0 0 0 auto; width: 4px; background: linear-gradient(180deg, var(--crest), var(--gold)); }
        .watermark { position: absolute; right: -70px; top: 6%; width: min(46%, 400px); opacity: .08; pointer-events: none; }
        .story > :not(.watermark) { position: relative; }

        .lockup { display: flex; align-items: center; gap: 22px; }
        .lockup img { width: clamp(76px, 8vw, 120px); height: auto; }
        .lockup span { display: block; padding-left: 22px; border-left: 1px solid var(--gold-line); }
        .lockup strong { display: block; font-size: clamp(28px, 3vw, 44px); line-height: 1.1; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
        .lockup small { display: block; margin-top: 4px; font-size: clamp(15px, 1.3vw, 19px); font-weight: 600; color: var(--muted); }

        .pitch { margin: auto 0; padding: 32px 0; max-width: 680px; }
        .pill { display: inline-flex; align-items: center; gap: 9px; padding: 7px 14px; border-radius: 99px; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow); font-size: 13px; font-weight: 700; color: var(--ink-2); }
        .pill i { width: 8px; height: 8px; border-radius: 50%; background: var(--gold); }
        .pitch h2 { margin: 22px 0 14px; font-size: clamp(27px, 2.5vw, 42px); line-height: 1.22; font-weight: 800; letter-spacing: -.025em; }
        .pitch h2 span { display: block; padding-bottom: .1em; color: var(--crest); background: linear-gradient(90deg, var(--crest), #b9891c); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .pitch > p { max-width: 50ch; font-size: 16px; line-height: 1.65; color: var(--muted); }
        .features { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin: 30px 0 0; padding: 0; list-style: none; max-width: 540px; }
        .features li { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border-radius: 14px; background: rgba(255, 255, 255, .82); border: 1px solid var(--line); box-shadow: var(--shadow); }
        .features svg { flex: none; width: 40px; height: 40px; padding: 10px; border-radius: 10px; background: var(--crest-wash); color: var(--crest); }
        .features b { display: block; font-size: 14.5px; }
        .features small { display: block; font-size: 12.5px; color: var(--muted); }
        .legal { font-size: 12.5px; color: var(--muted); }

        .entry { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 32px 20px; background: var(--paper); }
        .card { position: relative; overflow: hidden; width: min(460px, 100%); padding: 40px clamp(22px, 3vw, 40px) 36px; border-radius: 24px; background: var(--surface); border: 1px solid var(--line); box-shadow: 0 2px 4px rgba(0, 0, 0, .03), 0 24px 60px rgba(20, 24, 32, .08); }
        .card::before { content: ''; position: absolute; inset: 0 0 auto; height: 4px; background: linear-gradient(90deg, var(--crest), var(--gold)); }
        .card .lockup { justify-content: center; gap: 16px; }
        .card .lockup img { width: 72px; }
        .card .lockup span { padding-left: 16px; }
        .card .lockup strong { font-size: 23px; color: var(--crest); }
        .card .lockup small { font-size: 13.5px; margin-top: 2px; }
        .card h1 { margin: 28px 0 4px; font-size: 22px; text-align: center; }
        .card > p { margin-bottom: 24px; text-align: center; color: var(--muted); }
        .card input:not([type=checkbox]) { min-height: 50px; border-radius: 12px; font-size: 15px; }
        .password { position: relative; } .password input { padding-right: 78px; }
        .password button { position: absolute; right: 8px; top: 8px; height: 34px; }
        .signin-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 16px 0 22px; font-size: 13.5px; }
        .signin-row label { display: flex; align-items: center; gap: 9px; margin: 0; font-size: 13.5px; font-weight: 500; }
        .signin-row input { width: 18px; min-height: 18px; accent-color: var(--crest); }
        .signin-row a { font-weight: 700; }
        .card .btn.wide { width: 100%; min-height: 50px; border-radius: 12px; font-size: 15.5px; box-shadow: 0 8px 18px rgba(198, 40, 40, .24); }
        .back-link { margin-top: 20px; font-size: 13.5px; font-weight: 600; }

        @media (max-height: 760px) and (min-width: 981px) {
            .story { padding-top: 24px; padding-bottom: 18px; } .pitch { padding: 14px 0; } .pitch h2 { margin: 12px 0 8px; font-size: 28px; } .pitch > p { font-size: 14.5px; } .features { margin-top: 14px; gap: 10px; } .features li { padding: 10px 14px; } .story .lockup img { width: 66px; } .story .lockup strong { font-size: 28px; }
        }
        @media (max-width: 980px) {
            .signin { grid-template-columns: minmax(0, 1fr); }
            .story { display: none; }
        }
        @media (max-width: 380px) { .card .lockup { flex-direction: column; text-align: center; gap: 8px; } .card .lockup span { padding-left: 0; border-left: 0; } .signin-row { flex-wrap: wrap; } }
    </style>
</head>
<body>
<main class="signin">
    <section class="story" aria-label="About the college portal">
        <img class="watermark" src="{{ asset('frontend/mcc/img/logo-512.webp') }}" alt="">
        <div class="lockup">
            <img src="{{ asset('frontend/mcc/img/logo-256.png') }}" alt="Maestro Crown crest">
            <span><strong>Maestro Crown</strong><small>School and College</small></span>
        </div>

        <div class="pitch">
            <span class="pill"><i></i>Hello Future school portal</span>
            <h2>Quality education through <span>discipline, knowledge and technology.</span></h2>
            <p>The school day of every student, kept in one secure place for the college, students and guardians.</p>
            <ul class="features">
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18M9 15l2 2 4-4"/></svg>
                    <div><b>Attendance</b><small>Marked every day</small></div>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="6"/><path d="M8.5 13.5 7 22l5-3 5 3-1.5-8.5"/></svg>
                    <div><b>Results</b><small>Exams and marksheets</small></div>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h13v4"/><rect x="3" y="7" width="18" height="13" rx="3"/><path d="M16 13.5h2"/></svg>
                    <div><b>Fees</b><small>Invoices and receipts</small></div>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
                    <div><b>Campus services</b><small>Library, clubs and support</small></div>
                </li>
            </ul>
        </div>

        <p class="legal">&copy; {{ date('Y') }} Maestro Crown School and College, Savar</p>
    </section>

    <div class="entry">
        <div class="card">
            <div class="lockup">
                <img src="{{ asset('frontend/mcc/img/logo-256.png') }}" alt="">
                <span><strong>Maestro Crown</strong><small>School and College</small></span>
            </div>
            <h1>Sign in to your portal</h1>
            <p>Use the email address the college gave you.</p>

            <form method="post" action="{{ route('login') }}" onsubmit="this.querySelector('.wide').disabled = true">
                @csrf
                @if (session('error') || $errors->any())<div class="flash bad" role="alert">{{ session('error') === 'Email-Address And Password Are Wrong.' ? 'That email and password do not match. Check both and try again.' : (session('error') ?: $errors->first()) }}</div>@endif
                @if (session('status') || session('message'))<div class="flash" role="status">{{ session('status') ?: session('message') }}</div>@endif
                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password">
                        <input id="password" type="password" name="password" autocomplete="current-password" required>
                        <button type="button" class="btn quiet small" aria-controls="password" onclick="const i = document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password'; this.textContent = i.type === 'password' ? 'Show' : 'Hide'">Show</button>
                    </div>
                </div>
                <div class="signin-row">
                    <label><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                    <a href="{{ route('password.request') }}">Forgot password?</a>
                </div>
                <button class="btn wide">Sign in</button>
            </form>
        </div>
        <a class="back-link" href="{{ url('/') }}">Back to the college website</a>
    </div>
</main>
</body>
</html>
