@extends('frontend.layouts.app')

@push('styles')
    @include('admissions.layouts._tailwind_head')
@endpush

@include('admissions.guardian._styles')
@section('guardian_sidebar')
@php
    $here = fn (string $pattern) => request()->is($pattern) ? 'background: var(--navy); color: var(--white);' : 'color: var(--navy);';
@endphp
<div class="g-card" style="padding: 16px;">
    <div style="font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray); padding: 6px 10px 10px;">Guardian portal</div>
    <div style="font-size: .9rem; color: var(--gray-dk); padding: 0 10px 14px; border-bottom: 1px solid var(--gray-lt); margin-bottom: 10px;">
        Signed in as <strong style="color: var(--navy);">{{ session(\App\Modules\Admissions\Http\Controllers\Web\GuardianPortalController::GUARDIAN_NAME) }}</strong>
    </div>
    <nav style="display: grid; gap: 4px;" aria-label="Guardian navigation">
        <a href="{{ route('guardian.dashboard') }}" class="nav-item" style="{{ $here('guardian') }} padding: 10px 12px; border-radius: 8px; font-weight: 600; text-decoration: none;"><i class="fa-solid fa-gauge"></i> &nbsp;Dashboard</a>
        <a href="{{ route('admissions.index') }}" style="{{ $here('admissions') }} padding: 10px 12px; border-radius: 8px; font-weight: 600; text-decoration: none;"><i class="fa-solid fa-list-check"></i> &nbsp;Available programmes</a>
        <a href="{{ route('admissions.track') }}" style="{{ $here('admissions/track*') }} padding: 10px 12px; border-radius: 8px; font-weight: 600; text-decoration: none;"><i class="fa-solid fa-magnifying-glass"></i> &nbsp;Track application</a>
        <form method="POST" action="{{ route('guardian.logout') }}" style="margin-top: 10px; border-top: 1px solid var(--gray-lt); padding-top: 10px;">
            @csrf
            <button type="submit" style="width: 100%; text-align: left; padding: 10px 12px; border-radius: 8px; font-weight: 600; color: #b91c1c; background: none; border: 0; cursor: pointer;"><i class="fa-solid fa-right-from-bracket"></i> &nbsp;Sign out</button>
        </form>
    </nav>
</div>
@endsection
