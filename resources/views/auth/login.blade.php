<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | Maestro Crown School and College</title>
    <link rel="icon" href="{{ asset('frontend/mcc/img/logo-256.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/mcc-office.css') }}?v={{ filemtime(public_path('css/mcc-office.css')) }}">
    <style>
        .signin { display: grid; grid-template-columns: minmax(0, 11fr) minmax(0, 9fr); min-height: 100vh; }
        .signin-brand { position: relative; color: #fff; padding: 48px clamp(24px, 5vw, 72px); display: flex; flex-direction: column; justify-content: flex-end; min-height: 280px;
            background: linear-gradient(180deg, rgba(74, 14, 18, .35), rgba(74, 14, 18, .94)), url('{{ asset('frontend/mcc/img/some-students.webp') }}') center / cover; border-right: 4px solid var(--gold); }
        .signin-brand img { width: 92px; height: 92px; border-radius: 50%; background: #fff; padding: 6px; object-fit: contain; border: 3px solid var(--gold); margin-bottom: auto; }
        .signin-brand h1 { font-size: clamp(32px, 4vw, 52px); max-width: 14ch; margin-bottom: 12px; }
        .signin-brand p { color: var(--gold-soft); max-width: 46ch; }
        .signin-form { display: grid; place-items: center; padding: 32px 20px; }
        .signin-form form { width: min(400px, 100%); }
        .signin-form h2 { font: 600 28px var(--serif); margin-bottom: 4px; }
        .password { position: relative; } .password input { padding-right: 76px; }
        .password button { position: absolute; right: 6px; top: 5px; height: 32px; }
        .signin-row { display: flex; justify-content: space-between; align-items: center; margin: 14px 0 20px; font-size: 13px; }
        .signin-row label { display: flex; align-items: center; gap: 8px; margin: 0; font-weight: 600; } .signin-row input { width: 17px; min-height: 17px; accent-color: var(--crest); }
        .signin .btn.wide { width: 100%; min-height: 48px; }
        @media (max-width: 820px) { .signin { grid-template-columns: minmax(0, 1fr); grid-template-rows: auto 1fr; } .signin-brand { border-right: 0; border-bottom: 4px solid var(--gold); padding-top: 24px; } .signin-brand img { width: 64px; height: 64px; margin-bottom: 40px; } .signin-form { align-items: start; } }
    </style>
</head>
<body>
<div class="signin">
    <section class="signin-brand">
        <img src="{{ asset('frontend/mcc/img/logo-256.png') }}" alt="Maestro Crown crest">
        <h1>Maestro Crown School and College</h1>
        <p>The staff entrance to Hello Future. Students and guardians use the MCC - Hello Future app.</p>
    </section>
    <main class="signin-form">
        <form method="post" action="{{ route('login') }}" onsubmit="this.querySelector('.wide').disabled = true">
            @csrf
            <h2>Sign in</h2>
            <p class="muted" style="margin-bottom:22px">Use the email address the college gave you.</p>
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
                    <button type="button" class="btn quiet small" onclick="const i = document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password'; this.textContent = i.type === 'password' ? 'Show' : 'Hide'">Show</button>
                </div>
            </div>
            <div class="signin-row">
                <label><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <button class="btn wide">Sign in</button>
            <p class="small muted" style="margin-top:22px;text-align:center"><a href="{{ url('/') }}">Back to the college website</a></p>
        </form>
    </main>
</div>
</body>
</html>
