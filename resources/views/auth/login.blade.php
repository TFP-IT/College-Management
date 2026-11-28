<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | Maestro Crown School and College</title>
    <link rel="icon" href="{{ asset('frontend/mcc/img/logo-256.png') }}">
    <link rel="stylesheet" href="{{ asset('css/mcc-office.css') }}?v={{ filemtime(public_path('css/mcc-office.css')) }}">
    <style>
        /* The same sign-in the app opens with: crest, welcome, admission, then the form. */
        .signin { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; padding: 32px 18px; }
        .signin-column { width: min(420px, 100%); }
        .signin-head { text-align: center; margin-bottom: 20px; }
        .signin-head img { width: 132px; height: 132px; object-fit: contain; }
        .signin-head p { color: var(--muted); font-size: 15px; font-weight: 500; margin-top: 10px; }
        .signin-head h1 { color: var(--crest); font-size: 27px; }
        .admission { display: flex; justify-content: center; align-items: center; gap: 10px; min-height: 54px; margin-bottom: 16px; border-radius: 16px; color: #fff; font-weight: 800; font-size: 16px; background: linear-gradient(90deg, var(--crest), var(--gold)); box-shadow: 0 8px 18px rgba(198, 40, 40, .25); }
        .admission:hover { text-decoration: none; color: #fff; filter: brightness(.96); }
        .signin form { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-lg); box-shadow: var(--shadow); padding: 24px; }
        .signin input:not([type=checkbox]) { min-height: 48px; border-radius: 12px; }
        .password { position: relative; } .password input { padding-right: 76px; }
        .password button { position: absolute; right: 7px; top: 7px; height: 34px; }
        .signin-row { display: flex; justify-content: space-between; align-items: center; margin: 14px 0 20px; font-size: 13px; }
        .signin-row label { display: flex; align-items: center; gap: 8px; margin: 0; font-weight: 600; } .signin-row input { width: 17px; min-height: 17px; accent-color: var(--crest); }
        .signin .btn.wide { width: 100%; min-height: 50px; border-radius: 12px; font-size: 15px; box-shadow: 0 4px 10px rgba(198, 40, 40, .22); }
        .signin-foot { text-align: center; margin-top: 18px; }
    </style>
</head>
<body>
<main class="signin">
    <div class="signin-column">
        <header class="signin-head">
            <img src="{{ asset('frontend/mcc/img/logo-256.png') }}" alt="Maestro Crown crest">
            <p>Welcome to</p>
            <h1>Maestro Crown College</h1>
        </header>
        <a class="admission" href="{{ url('/admissions') }}">Admission 2026-2027</a>
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
                    <button type="button" class="btn quiet small" onclick="const i = document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password'; this.textContent = i.type === 'password' ? 'Show' : 'Hide'">Show</button>
                </div>
            </div>
            <div class="signin-row">
                <label><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <button class="btn wide">Sign in</button>
            </form>
        <div class="signin-foot small muted">
            <p>For college staff. Students and guardians can also use the Hello Future app.</p>
            <p style="margin-top:8px"><a href="{{ url('/') }}">Back to the college website</a></p>
        </div>
    </div>
</main>
</body>
</html>
