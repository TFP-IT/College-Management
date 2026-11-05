@extends('admissions.layouts.portal')

@section('title', 'Track Application')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Track Your Admission Application</h1>
        <p class="text-xs text-slate-500 mt-1">Enter your application number and phone number. We will send a code to confirm it is you.</p>
    </div>

    @if(session('status'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if(session('debug_code'))
        <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">Development only: your code is <strong>{{ session('debug_code') }}</strong></div>
    @endif

    @if(!$application)
        @if($pending)
            <div class="bg-white rounded-2xl border border-slate-200 p-6 md:p-8 shadow-sm">
                <form action="{{ route('admissions.track.verify') }}" method="POST" class="space-y-4">
                    @csrf
                    <label class="block text-xs font-semibold text-slate-700" for="code">6-digit code</label>
                    <input id="code" type="text" name="code" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                           class="w-full text-sm rounded-lg border-slate-300 py-2.5 px-3">
                    @error('code')<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
                    <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl">Verify and view</button>
                </form>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 p-6 md:p-8 shadow-sm">
                <form action="{{ route('admissions.track.send') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1" for="application_number">Application number</label>
                        <input id="application_number" type="text" name="application_number" value="{{ old('application_number') }}" required placeholder="APP-2026-000001"
                               class="w-full text-sm rounded-lg border-slate-300 py-2.5 px-3">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1" for="phone">Phone number</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required placeholder="017XXXXXXXX" inputmode="numeric"
                               class="w-full text-sm rounded-lg border-slate-300 py-2.5 px-3">
                    </div>
                    @error('phone')<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
                    <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl">Send me a code</button>
                </form>
            </div>
        @endif
    @else
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                <div>
                    <span class="text-xs text-slate-400 font-mono">APPLICATION {{ $application->application_no }}</span>
                    <h3 class="text-lg font-bold text-slate-900 mt-0.5">{{ $application->applicant_name }}</h3>
                </div>
                <span class="text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full {{ $application->application_status->badgeClass() }}">
                    {{ $application->application_status->label() }}
                </span>
            </div>
            <p class="text-xs text-slate-600 mb-4">Programme: <strong>{{ $application->program?->title }}</strong></p>
            <a href="{{ route('admissions.show', ['id' => $application->id]) }}"
               class="inline-flex items-center justify-center w-full py-2.5 bg-brand-50 border border-brand-200 text-brand-700 hover:bg-brand-100 text-xs font-bold rounded-lg">
                Open full application and pass <i class="fa-solid fa-arrow-right ml-1.5"></i>
            </a>
        </div>
    @endif
</div>
@endsection
