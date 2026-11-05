@extends('frontend.layouts.app')

@section('title', 'Guardian details | ' . $program->title)

@push('styles')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    @include('admissions.guardian._styles')
    <style>
        [x-cloak] { display: none !important; }
        .gs-wrap { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(260px, 1fr); gap: 26px; align-items: start; }
        .gs-side { background: linear-gradient(160deg, var(--navy-dk), var(--navy)); color: var(--white); border-radius: 16px; padding: 26px; position: sticky; top: 24px; }
        .gs-side .row { display: flex; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.15); font-size: .92rem; }
        .gs-side .row span:first-child { opacity: .75; }
        .gs-tl { list-style: none; padding: 0; margin: 14px 0 0; position: relative; }
        .gs-tl li { position: relative; padding: 0 0 16px 30px; font-size: .92rem; line-height: 1.5; }
        .gs-tl li:last-child { padding-bottom: 0; }
        .gs-tl li::before { content: ''; position: absolute; left: 7px; top: 6px; width: 12px; height: 12px; border-radius: 50%; background: var(--gold); box-shadow: 0 0 0 4px rgba(201,162,39,.25); }
        .gs-tl li::after { content: ''; position: absolute; left: 12px; top: 22px; bottom: -2px; width: 2px; background: rgba(255,255,255,.2); }
        .gs-tl li:last-child::after { display: none; }
        .gs-tl b { color: var(--gold-lt); }
        .gs-eyebrow { font-size: .72rem; letter-spacing: .1em; text-transform: uppercase; color: var(--gold-lt); font-weight: 800; }
        .gs-hint { font-size: .8rem; margin-top: 6px; display: flex; align-items: center; gap: 6px; min-height: 1.2em; }
        .gs-ok { color: #047857; }
        .gs-bad { color: #b91c1c; }
        .gs-input-ok { border-color: #10b981 !important; }
        .gs-input-bad { border-color: #f87171 !important; }
        .gs-link { background: none; border: 0; padding: 0; color: var(--navy); font-weight: 600; cursor: pointer; text-decoration: none; font-size: .92rem; }
        .gs-spin { display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: gsspin .7s linear infinite; vertical-align: -2px; margin-right: 8px; }
        @keyframes gsspin { to { transform: rotate(360deg); } }
        .gs-stack { display: grid; gap: 18px; }
        @media (max-width: 860px) { .gs-wrap { grid-template-columns: 1fr; } .gs-side { position: static; } }
    </style>
@endpush

@section('content')
<div class="page-content" style="padding-top: 40px;">
    <div class="container" style="max-width: 1080px;">

        <div class="g-steps" aria-label="Progress">
            <span class="{{ $pending ? '' : 'on' }}"><b>1</b>&nbsp; Guardian details</span>
            <span style="opacity:.5;">→</span>
            <span class="{{ $pending ? 'on' : '' }}"><b>2</b>&nbsp; Verify OTP</span>
            <span style="opacity:.5;">→</span>
            <span><b>3</b>&nbsp; Student application</span>
        </div>

        @if(session('status'))
            <div class="g-notice g-ok" role="status">{{ session('status') }}</div>
        @endif
        @if(session('debug_code'))
            <div class="g-notice g-dev">Development only: your OTP is <strong>{{ session('debug_code') }}</strong></div>
        @endif
        @if($errors->any())
            <div class="g-notice g-bad" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="gs-wrap">
            <div class="gs-stack">

            @if($pending)
                {{-- Step 2: details sent, enter the OTP here --}}
                <div class="g-card" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
                    <div>
                        <div class="gs-eyebrow" style="color: var(--navy); opacity: .7;">Guardian details</div>
                        <div style="font-weight: 700; color: var(--navy); font-size: 1.05rem; margin-top: 4px;">{{ $pending['guardian_name'] }}</div>
                        <div style="color: var(--gray-dk); font-size: .92rem;">{{ $pending['email'] }} &middot; {{ $pending['phone'] }} &middot; {{ ucfirst(strtolower($pending['relation'])) }}</div>
                    </div>
                    <a href="{{ route('admissions.guardian.start', ['program' => $program->id, 'reset' => 1]) }}" class="gs-link">Change details</a>
                </div>

                <div class="g-card" style="text-align: center;" x-data="{ code: '' }">
                    <div style="width: 60px; height: 60px; margin: 0 auto 12px; border-radius: 50%; background: var(--gray-lt); display: flex; align-items: center; justify-content: center; color: var(--navy); font-size: 1.5rem;">
                        <i class="fa-solid fa-envelope-open-text"></i>
                    </div>
                    <h2 style="color: var(--navy); margin: 0 0 6px; font-size: 1.3rem;">Enter the 6-digit OTP</h2>
                    <p style="color: var(--gray-dk); margin: 0 0 22px;">We sent it to <strong>{{ $pending['email'] }}</strong>. It is valid for 10 minutes.</p>

                    <form action="{{ route('admissions.guardian.verify.post') }}" method="POST" style="display: grid; gap: 12px; max-width: 360px; margin: 0 auto;">
                        @csrf
                        <input type="text" name="code" x-model="code" @input="code = code.replace(/\D/g, '').slice(0, 6)"
                               required maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="······" aria-label="6-digit OTP"
                               class="g-input" style="text-align: center; font-size: 1.5rem; letter-spacing: .5em; font-weight: 700;"
                               :class="code.length === 6 ? 'gs-input-ok' : ''">
                        <div class="gs-hint" style="justify-content: center;">
                            <span :class="code.length === 6 ? 'gs-ok' : 'gs-hint-text'" style="color: var(--gray-dk);"
                                  x-text="code.length === 6 ? 'Ready to verify' : (6 - code.length) + ' digits left'">6 digits left</span>
                        </div>
                        @error('code')<p class="g-error" role="alert">{{ $message }}</p>@enderror
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;"
                                :disabled="code.length !== 6" :style="code.length !== 6 ? 'opacity:.55;cursor:not-allowed;' : ''">Verify OTP</button>
                    </form>

                    <form action="{{ route('admissions.guardian.send', ['program' => $program->id]) }}" method="POST" style="margin-top: 20px;">
                        @csrf
                        <input type="hidden" name="guardian_name" value="{{ $pending['guardian_name'] }}">
                        <input type="hidden" name="relation" value="{{ $pending['relation'] }}">
                        <input type="hidden" name="phone" value="{{ $pending['phone'] }}">
                        <input type="hidden" name="email" value="{{ $pending['email'] }}">
                        <span style="color: var(--gray-dk); font-size: .9rem;">Did not get it? </span>
                        <button type="submit" class="gs-link">Resend OTP</button>
                    </form>
                </div>
            @else
                {{-- Step 1: guardian details --}}
                <div class="g-card">
                    <h2 style="color: var(--navy); margin: 0 0 6px; font-size: 1.4rem;">Tell us about the parent or guardian</h2>
                    <p style="color: var(--gray-dk); margin: 0 0 24px;">Enter your details and click <strong>Send OTP</strong>. We will email a 6-digit code to confirm the address. Your details create the guardian account that manages this application.</p>

                    <form action="{{ route('admissions.guardian.send', ['program' => $program->id]) }}" method="POST"
                          style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px;"
                          x-data="{
                            name: @js(old('guardian_name', '')),
                            relation: @js(old('relation', 'FATHER')),
                            phone: @js(old('phone', '')),
                            email: @js(old('email', '')),
                            submitting: false,
                            get nameOk() { return this.name.trim().length >= 3; },
                            get phoneOk() { return /^01[3-9]\d{8}$/.test(this.phone); },
                            get emailOk() { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(this.email.trim()); },
                            get valid() { return this.nameOk && this.phoneOk && this.emailOk; },
                            onPhone(e) { this.phone = e.target.value.replace(/\D/g, '').slice(0, 11); },
                          }"
                          @submit="submitting = true">
                        @csrf

                        <div style="grid-column: 1 / -1;">
                            <label class="g-label" for="guardian_name">Guardian's full name *</label>
                            <input id="guardian_name" class="g-input" type="text" name="guardian_name" x-model="name" required maxlength="255" autocomplete="name"
                                   :class="name.length ? (nameOk ? 'gs-input-ok' : 'gs-input-bad') : ''">
                            <div class="gs-hint" x-show="name.length" x-cloak>
                                <span :class="nameOk ? 'gs-ok' : 'gs-bad'" x-text="nameOk ? 'Looks good' : 'Enter at least 3 characters'"></span>
                            </div>
                            @error('guardian_name')<p class="g-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="g-label" for="relation">Relation to the candidate *</label>
                            <select id="relation" class="g-input" name="relation" x-model="relation" required>
                                <option value="FATHER">Father</option>
                                <option value="MOTHER">Mother</option>
                                <option value="GUARDIAN">Other guardian</option>
                            </select>
                        </div>

                        <div>
                            <label class="g-label" for="phone">Phone number *</label>
                            <input id="phone" class="g-input" type="text" name="phone" :value="phone" @input="onPhone($event)" required
                                   placeholder="01XXXXXXXXX" inputmode="numeric" autocomplete="tel" maxlength="11"
                                   :class="phone.length ? (phoneOk ? 'gs-input-ok' : 'gs-input-bad') : ''">
                            <div class="gs-hint">
                                <template x-if="phone.length && !phoneOk"><span class="gs-bad">Enter 11 digits starting with 01</span></template>
                                <template x-if="phoneOk"><span class="gs-ok">Valid mobile number</span></template>
                                <template x-if="!phone.length"><span style="color: var(--gray);">The student's application uses this number.</span></template>
                            </div>
                            @error('phone')<p class="g-error">{{ $message }}</p>@enderror
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label class="g-label" for="email">Email address *</label>
                            <input id="email" class="g-input" type="email" name="email" x-model="email" required maxlength="255" autocomplete="email"
                                   :class="email.length ? (emailOk ? 'gs-input-ok' : 'gs-input-bad') : ''">
                            <div class="gs-hint">
                                <template x-if="email.length && !emailOk"><span class="gs-bad">Enter a valid email address</span></template>
                                <template x-if="emailOk"><span class="gs-ok">The OTP will be sent here</span></template>
                                <template x-if="!email.length"><span style="color: var(--gray);">We will send a 6-digit OTP to this address.</span></template>
                            </div>
                            @error('email')<p class="g-error">{{ $message }}</p>@enderror
                        </div>

                        <div style="grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 6px; padding-top: 18px; border-top: 1px solid var(--gray-lt);">
                            <a href="{{ route('admissions.index') }}" style="color: var(--navy); font-weight: 600; text-decoration: none;">&larr; Back to programmes</a>
                            <button type="submit" class="btn btn-primary" :disabled="!valid || submitting" :style="(!valid || submitting) ? 'opacity:.55;cursor:not-allowed;' : ''">
                                <span x-show="submitting" x-cloak class="gs-spin"></span>
                                <span x-text="submitting ? 'Sending OTP…' : 'Send OTP'">Send OTP</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            </div>

            {{-- Summary --}}
            <aside class="gs-side">
                <div class="gs-eyebrow">Your programme</div>
                <h3 style="color: var(--white); margin: 6px 0 14px; font-size: 1.15rem; line-height: 1.3;">{{ $program->title }}</h3>
                <div class="row"><span>Application fee</span><span>৳{{ number_format($program->application_fee, 0) }}</span></div>
                <div class="row"><span>Admission fee</span><span>৳{{ number_format($program->admission_fee, 0) }}</span></div>
                <div class="row"><span>Seats</span><span>{{ $program->capacity }}</span></div>
                <div class="row" style="border-bottom: 0;"><span>Deadline</span><span>{{ $program->ends_at?->format('d M Y') ?? 'Open' }}</span></div>

                <div class="gs-eyebrow" style="margin-top: 24px;">What happens next</div>
                <ul class="gs-tl">
                    <li><b>Enter details.</b> Tell us the guardian's name and contact.</li>
                    <li><b>Send OTP.</b> A 6-digit code is emailed to you.</li>
                    <li><b>Verify.</b> Enter the code here to confirm your account.</li>
                    <li><b>Student form.</b> Fill in the student's details and upload documents.</li>
                </ul>
            </aside>
        </div>
    </div>
</div>
@endsection
