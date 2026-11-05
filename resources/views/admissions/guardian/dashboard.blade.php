@extends('admissions.layouts.guardian')

@section('title', 'Guardian dashboard')

@push('styles')
    @include('admissions.guardian._styles')
@endpush

@section('content')
@php
    $count = fn (array $s) => $applications->whereIn('status', $s)->count();
    $pill = fn (string $s) => match (true) {
        in_array($s, ['APPROVED', 'ADMISSION_FEE_PAID', 'ENROLLED']) => 'background:#d1fae5;color:#065f46;',
        in_array($s, ['REJECTED', 'EXPIRED', 'WITHDRAWN']) => 'background:#fee2e2;color:#991b1b;',
        $s === 'WAITLISTED' => 'background:#ffedd5;color:#9a3412;',
        $s === 'PENDING_APP_FEE' => 'background:#fef9c3;color:#854d0e;',
        default => 'background:#e0e7ff;color:var(--navy);',
    };
@endphp

{{-- Greeting --}}
<div style="background: linear-gradient(135deg, var(--navy), #163a6e); color: var(--white); border-radius: 14px; padding: 26px 30px; margin-bottom: 20px;">
    <div style="font-size: .85rem; opacity: .8;">Guardian portal</div>
    <h1 style="color: var(--white); margin: 4px 0 4px; font-size: 1.6rem;">Welcome, {{ $name }}</h1>
    <p style="margin: 0; opacity: .85;">Manage your child's admission from here.</p>
</div>

@if(session('status'))<div class="g-notice g-ok">{{ session('status') }}</div>@endif
@if(session('success'))<div class="g-notice g-ok">{{ session('success') }}</div>@endif
@if(session('error'))<div class="g-notice g-bad">{{ session('error') }}</div>@endif

{{-- Stats --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 20px;">
    @foreach([
        ['Applications', $applications->count(), 'fa-folder-open'],
        ['Awaiting payment', $count(['PENDING_APP_FEE', 'APPROVED']), 'fa-credit-card'],
        ['In review', $count(['SUBMITTED', 'UNDER_REVIEW', 'EXAM_SCHEDULED', 'ASSESSED']), 'fa-hourglass-half'],
        ['Admitted', $count(['ADMISSION_FEE_PAID', 'ENROLLED']), 'fa-award'],
    ] as [$label, $value, $icon])
        <div class="g-card" style="padding: 18px 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: .8rem; color: var(--gray); font-weight: 600; text-transform: uppercase; letter-spacing: .05em;">{{ $label }}</span>
                <i class="fa-solid {{ $icon }}" style="color: var(--gold);"></i>
            </div>
            <div style="font-size: 1.9rem; font-weight: 800; color: var(--navy); margin-top: 6px;">{{ $value }}</div>
        </div>
    @endforeach
</div>

{{-- Apply --}}
<div class="g-card" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; border-left: 4px solid var(--gold);">
    <div>
        <h3 style="color: var(--navy); margin: 0 0 4px; font-size: 1.15rem;">Apply for a new admission</h3>
        <p style="color: var(--gray-dk); margin: 0; font-size: .93rem;">Choose a programme for your child. Your details are already filled in.</p>
    </div>
    <a href="{{ $program ? route('guardian.apply', ['program' => $program->id]) : route('admissions.index').'#programmes' }}" class="btn btn-primary">Apply now</a>
</div>

{{-- Applications --}}
<div class="g-card">
    <h3 style="color: var(--navy); margin: 0 0 18px; font-size: 1.2rem;">My applications</h3>

    @forelse($applications as $application)
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; padding: 16px 18px; border: 1px solid var(--gray-lt); border-radius: 12px; margin-bottom: 12px;">
            <div style="min-width: 0;">
                <div style="font-weight: 700; color: var(--navy); font-size: 1.02rem;">{{ $application->candidate_name }}</div>
                <div style="font-size: .85rem; color: var(--gray); margin-top: 2px;">
                    {{ $application->application_no }} &middot; {{ $application->program?->title }}
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="{{ $pill($application->status) }} font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: 5px 12px; border-radius: 999px;">
                    {{ $application->application_status->label() }}
                </span>
                <a href="{{ route('admissions.show', ['id' => $application->id]) }}" class="btn btn-outline" style="padding: 7px 16px; font-size: .85rem;">View</a>
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 28px 10px; color: var(--gray);">
            <i class="fa-regular fa-folder-open" style="font-size: 2rem; color: var(--gray-lt);"></i>
            <p style="margin: 10px 0 0;">You have no applications yet.</p>
        </div>
    @endforelse
</div>
@endsection
