{{--
    Phone mock-up showing an app screen.
    @include('frontend.landing.partials._phone', ['variant' => 'dash'|'login', 'class' => 'phone--front', 'eager' => false])
    Real screenshots win: drop public/frontend/mcc/img/app/screen-{variant}.(webp|png|jpg) and it replaces the HTML mock-up.
--}}
@php
    $shot = null;
    foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
        $candidate = 'frontend/mcc/img/app/screen-' . $variant . '.' . $ext;
        if (file_exists(public_path($candidate))) { $shot = $candidate; break; }
    }
    $session = config('landing.session');
@endphp
<div class="phone {{ $class ?? '' }}" data-phone>
    <div class="phone-notch"></div>
    @if ($shot)
        <div class="phone-screen phone-screen--shot">
            <img src="{{ asset($shot) }}" alt="MCC Hello Future app: {{ $variant === 'dash' ? 'student dashboard' : 'portal sign-in' }}" decoding="async" @if(empty($eager)) loading="lazy" @endif>
        </div>
    @elseif ($variant === 'dash')
        <div class="phone-screen screen-dash">
            <div class="sd-head">
                <div><small>Good afternoon,</small><strong>Nabila</strong></div>
                <img src="{{ asset('frontend/mcc/img/logo-256.webp') }}" alt="" width="34" height="34">
            </div>
            <p class="sd-label">Quick actions</p>
            <div class="sd-actions">
                <span><i class="fa-solid fa-credit-card" style="color:#E45551;background:#FDEEED"></i>Pay Fee</span>
                <span><i class="fa-solid fa-book" style="color:#B8912A;background:#FBF3DC"></i>Library</span>
                <span><i class="fa-solid fa-bus" style="color:#7A6A5A;background:#EFEAE4"></i>Transport</span>
                <span><i class="fa-regular fa-calendar-check" style="color:#2E7D32;background:#E3F1E4"></i>Attendance</span>
            </div>
            <div class="sd-cards">
                <div class="sd-card">
                    <small>Attendance</small>
                    <svg class="sd-ring" viewBox="0 0 44 44"><circle cx="22" cy="22" r="18" /><circle cx="22" cy="22" r="18" /></svg>
                    <b>92%</b>
                </div>
                <div class="sd-card">
                    <small>Next class</small>
                    <em>10:30 AM</em>
                    <b>Physics</b>
                    <small>Room 302</small>
                </div>
            </div>
            <div class="sd-exam"><i class="fa-solid fa-stopwatch"></i><div><small>Mid-term exams</small><b>14 days left</b></div></div>
            <div class="sd-notice"><span>NEW</span>Science Fair registration is open.</div>
            <div class="sd-tabbar"><i class="fa-solid fa-house is-on"></i><i class="fa-solid fa-book-open"></i><i class="fa-solid fa-graduation-cap"></i><i class="fa-solid fa-grip"></i><i class="fa-solid fa-user"></i></div>
        </div>
    @else
        <div class="phone-screen screen-login">
            <img src="{{ asset('frontend/mcc/img/logo-256.webp') }}" alt="" width="64" height="64">
            <small>Welcome to</small>
            <strong>Maestro Crown College</strong>
            <span class="sl-banner"><i class="fa-solid fa-wand-magic-sparkles"></i> Admission {{ $session }}</span>
            <div class="sl-tabs"><span class="is-on"><i class="fa-solid fa-graduation-cap"></i> Student</span><span>Guardian</span><span>Teacher</span></div>
            <span class="sl-field"></span>
            <span class="sl-field"></span>
            <span class="sl-btn">Sign in</span>
        </div>
    @endif
</div>
