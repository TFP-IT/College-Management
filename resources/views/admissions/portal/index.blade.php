@extends('frontend.layouts.app')

@section('title', 'Admissions 2026–27 | Maestro Crown College')

@push('styles')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .ad-eyebrow { color: var(--gold-lt); font-size: .8rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .ad-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 22px; }
        .ad-card { background: var(--white); border: 1px solid var(--gray-lt); border-radius: 16px; padding: 26px; display: flex; flex-direction: column; transition: transform .2s, box-shadow .2s, border-color .2s; }
        .ad-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); border-color: var(--gold); }
        .ad-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .ad-kind { font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--gray); }
        .ad-title { color: var(--navy); font-size: 1.15rem; line-height: 1.3; margin: 0 0 8px; }
        .ad-meta { color: var(--gray-dk); font-size: .88rem; margin: 0 0 22px; }
        .ad-fee { padding: 18px 0; border-top: 1px solid var(--gray-lt); border-bottom: 1px solid var(--gray-lt); }
        .ad-fee-main { font-size: 2rem; font-weight: 800; color: var(--navy); line-height: 1; }
        .ad-fee-label { font-size: .8rem; color: var(--gray-dk); margin-top: 6px; }
        .ad-fee-sub { font-size: .8rem; color: var(--gray); margin-top: 2px; }
        .ad-cta { margin-top: auto; padding-top: 20px; }
        .ad-open { display: inline-flex; align-items: center; gap: 6px; font-size: .74rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: 5px 12px; border-radius: 999px; background: #d1fae5; color: #065f46; }
        .ad-open::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #10b981; }
        .ad-closed { display: inline-flex; align-items: center; font-size: .74rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: 5px 12px; border-radius: 999px; background: #fee2e2; color: #991b1b; }
        .ad-tabs { display: inline-flex; gap: 4px; padding: 4px; background: var(--white); border: 1px solid var(--gray-lt); border-radius: 10px; }
        .ad-tab { padding: 9px 16px; border-radius: 7px; font-weight: 600; font-size: .9rem; text-decoration: none; color: var(--navy); }
        .ad-tab.on { background: var(--navy); color: var(--white); }
    </style>
@endpush

@section('content')
@php
    $section = strtoupper((string) request('section', 'ALL'));
    $shown = $section === 'ALL' ? $programs : $programs->filter(fn ($p) => $p->section_type->value === $section);
    $school = $shown->filter(fn ($p) => $p->section_type->value === 'SCHOOL');
    $college = $shown->filter(fn ($p) => $p->section_type->value === 'COLLEGE');
@endphp

<section id="programmes" class="page-content" style="padding-top: 44px;">
    <div class="container">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: end; gap: 16px; margin-bottom: 18px;">
            <div>
                <div class="ad-eyebrow" style="color: var(--gold);">Programmes</div>
                <h2 style="color: var(--navy); margin: 4px 0 0; font-size: 1.7rem;">Compare and apply</h2>
            </div>
            <div class="ad-tabs" role="tablist">
                @foreach(['ALL' => 'All', 'SCHOOL' => 'School', 'COLLEGE' => 'College'] as $key => $label)
                    <a href="{{ route('admissions.index', ['section' => $key]) }}#programmes" class="ad-tab {{ $section === $key ? 'on' : '' }}" role="tab">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        @if($shown->isEmpty())
            <div class="content-card" style="text-align: center; padding: 50px 20px;">
                <p style="color: var(--gray); margin: 0;">No programmes are open in this section right now. Please check back soon.</p>
            </div>
        @else
            <div class="ad-cards">
                @foreach($shown as $program)
                    @php $college = $program->section_type->value === 'COLLEGE'; @endphp
                    <article class="ad-card">
                        <div class="ad-top">
                            <span class="ad-kind">{{ $college ? 'College' : 'School' }}</span>
                            <span class="{{ $program->isOpen() ? 'ad-open' : 'ad-closed' }}">{{ $program->isOpen() ? 'Open' : 'Closed' }}</span>
                        </div>

                        <h3 class="ad-title">{{ $program->title }}</h3>
                        <p class="ad-meta">
                            {{ $program->capacity }} seats
                            &middot; Deadline {{ $program->ends_at?->format('d M Y') ?? 'open' }}
                            @if($program->academicGroup) &middot; {{ $program->academicGroup->name }} @endif
                        </p>

                        <div class="ad-fee">
                            <div class="ad-fee-main">৳{{ number_format($program->admission_fee, 0) }}</div>
                            <div class="ad-fee-label">Admission fee</div>
                            <div class="ad-fee-sub">Application fee ৳{{ number_format($program->application_fee, 0) }}</div>
                        </div>

                        <div class="ad-cta">
                            @if($program->isOpen())
                                <a href="{{ route('admissions.guardian.start', ['program' => $program->id]) }}" class="btn btn-primary" style="width: 100%; justify-content: center;">Apply now</a>
                            @else
                                <span class="btn btn-outline" style="width: 100%; justify-content: center; opacity: .5; cursor: not-allowed;">Closed</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <div class="content-card" style="margin: 48px 0 40px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 18px; border-left: 4px solid var(--gold);">
            <div>
                <h3 style="color: var(--navy); margin: 0 0 4px;">Already applied?</h3>
                <p style="color: var(--gray-dk); margin: 0;">Check the status of an application with its number and your phone.</p>
            </div>
            <a href="{{ route('admissions.track') }}" class="btn btn-primary">Track my application</a>
        </div>
    </div>
</section>
@endsection
