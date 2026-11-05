@extends($layout ?? 'frontend.layouts.app')

@section('title', 'Application ' . $application->application_no . ' | Maestro Crown College')

@push('styles')
    <style>
        .ap-card { background: var(--white); border: 1px solid var(--gray-lt); border-radius: 14px; padding: 24px; box-shadow: 0 2px 10px rgba(15,35,71,.04); }
        .ap-h { color: var(--navy); font-size: 1rem; font-weight: 800; margin: 0 0 16px; display: flex; align-items: center; gap: 8px; }
        .ap-h::before { content: ''; width: 4px; height: 16px; border-radius: 2px; background: var(--gold); }
        .ap-kv { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 16px 22px; }
        .ap-kv span { display: block; font-size: .72rem; color: var(--gray); text-transform: uppercase; letter-spacing: .06em; font-weight: 700; }
        .ap-kv strong { display: block; color: var(--text); font-size: .98rem; margin-top: 3px; }
        .ap-steps { display: grid; grid-template-columns: repeat(5, 1fr); position: relative; margin-top: 8px; }
        .ap-steps::before { content: ''; position: absolute; top: 15px; left: 10%; right: 10%; height: 3px; background: var(--gray-lt); border-radius: 2px; }
        .ap-step { position: relative; text-align: center; font-size: .78rem; color: var(--gray); font-weight: 600; }
        .ap-step i { display: flex; width: 32px; height: 32px; margin: 0 auto 8px; border-radius: 50%; align-items: center; justify-content: center; background: var(--white); border: 3px solid var(--gray-lt); color: var(--gray); font-style: normal; font-size: .8rem; position: relative; }
        .ap-step.done i { background: var(--green); border-color: var(--green); color: var(--white); }
        .ap-step.now i { border-color: var(--gold); color: var(--navy); }
        .ap-step.done, .ap-step.now { color: var(--navy); }
        .ap-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 13px 0; border-bottom: 1px solid var(--gray-lt); }
        .ap-row:last-child { border-bottom: 0; }
        .ap-pill { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; padding: 4px 10px; border-radius: 999px; background: var(--gray-lt); color: var(--gray-dk); white-space: nowrap; }
        @media (max-width: 720px) { .ap-steps { grid-template-columns: repeat(5, 1fr); } .ap-step { font-size: .68rem; } }
    </style>
@endpush

@section('content')
@php
    $status = $application->application_status->value;
    $order = ['SUBMITTED', 'UNDER_REVIEW', 'EXAM_SCHEDULED', 'ASSESSED', 'APPROVED', 'ADMISSION_FEE_PAID', 'ENROLLED'];
    $pos = match (true) {
        $status === 'ENROLLED' => 6,
        $status === 'ADMISSION_FEE_PAID' => 5,
        $status === 'APPROVED', $status === 'WAITLISTED', $status === 'REJECTED' => 4,
        $status === 'ASSESSED' => 3,
        $status === 'EXAM_SCHEDULED' => 2,
        $status === 'UNDER_REVIEW' => 1,
        $status === 'SUBMITTED' => 0,
        default => -1,
    };
    $steps = ['Submitted', 'Review', 'Assessment', 'Decision', 'Admission fee', 'Enrolled'];
    $tone = match (true) {
        in_array($status, ['APPROVED', 'ADMISSION_FEE_PAID', 'ENROLLED']) => 'background:#d1fae5;color:#065f46;',
        in_array($status, ['REJECTED', 'EXPIRED', 'WITHDRAWN']) => 'background:#fee2e2;color:#991b1b;',
        $status === 'WAITLISTED' => 'background:#ffedd5;color:#9a3412;',
        $status === 'PENDING_APP_FEE' => 'background:#fef9c3;color:#854d0e;',
        default => 'background:#e0e7ff;color:var(--navy);',
    };
@endphp

<div class="page-content" style="padding-top: 32px;">
    <div class="container" style="max-width: 980px;">

        @if(session('success'))
            <div style="padding: 13px 18px; border-radius: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; margin-bottom: 18px;" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div style="padding: 13px 18px; border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; margin-bottom: 18px;" role="alert">{{ $errors->first() }}</div>
        @endif

        {{-- Header --}}
        <div class="ap-card" style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 18px; align-items: flex-start; margin-bottom: 18px;">
            <div style="min-width: 0;">
                <div style="font-size: .8rem; font-family: monospace; color: var(--gray);">{{ $application->application_no }}</div>
                <h1 style="color: var(--navy); font-size: 1.6rem; margin: 6px 0 4px;">{{ $application->candidate_name }}</h1>
                <div style="color: var(--gray-dk);">{{ $application->program?->title }}</div>
            </div>
            <div style="text-align: right;">
                <span style="display: inline-block; {{ $tone }} font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; padding: 7px 16px; border-radius: 999px;">
                    {{ $application->application_status->label() }}
                </span>
                <div style="font-size: .8rem; color: var(--gray); margin-top: 8px;">Submitted {{ $application->created_at->format('d M Y') }}</div>
            </div>
        </div>

        {{-- Progress --}}
        <div class="ap-card" style="margin-bottom: 18px;">
            <div class="ap-steps">
                @foreach($steps as $i => $label)
                    @php $cls = $i < $pos ? 'done' : ($i === $pos ? 'now' : ''); @endphp
                    <div class="ap-step {{ $cls }}">
                        <i>{{ $i < $pos ? '✓' : $i + 1 }}</i>
                        {{ $label }}
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Next action --}}
        @if($status === 'PENDING_APP_FEE')
            <div class="ap-card" style="margin-bottom: 18px; border-left: 4px solid var(--gold); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px;">
                <div>
                    <strong style="color: var(--navy);">Next: pay the application fee</strong>
                    <div style="color: var(--gray-dk); font-size: .92rem; margin-top: 3px;">৳{{ number_format($application->program->application_fee, 2) }} sends your application for review.</div>
                </div>
                <a href="{{ route('admissions.payments.initiate', ['applicationId' => $application->id, 'type' => 'APPLICATION_FEE']) }}" class="btn btn-gold">Pay application fee</a>
            </div>
        @elseif($status === 'APPROVED')
            <div class="ap-card" style="margin-bottom: 18px; border-left: 4px solid var(--green); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px;">
                <div>
                    <strong style="color: var(--navy);">Admission offered</strong>
                    <div style="color: var(--gray-dk); font-size: .92rem; margin-top: 3px;">Pay ৳{{ number_format($application->program->admission_fee, 2) }} to confirm the seat.</div>
                </div>
                <a href="{{ route('admissions.payments.initiate', ['applicationId' => $application->id, 'type' => 'ADMISSION_FEE']) }}" class="btn btn-primary">Pay admission fee</a>
            </div>
        @elseif($status === 'ENROLLED')
            <div class="ap-card" style="margin-bottom: 18px; background: linear-gradient(135deg, var(--green), #0f4d2a); border: 0; color: var(--white);">
                <div style="font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; opacity: .85;">Permanent student ID</div>
                <div style="font-size: 1.7rem; font-weight: 800; margin-top: 4px;">{{ $application->admission_student_id }}</div>
            </div>
        @elseif($status === 'WAITLISTED')
            <div class="ap-card" style="margin-bottom: 18px; border-left: 4px solid #ea580c;">
                <strong style="color: var(--navy);">On the waiting list.</strong>
                <div style="color: var(--gray-dk); font-size: .92rem; margin-top: 3px;">The programme is full. We will contact you if a seat becomes free.</div>
            </div>
        @endif

        {{-- Pass --}}
        @if(in_array($status, ['APPROVED', 'ADMISSION_FEE_PAID', 'ENROLLED']) && $application->qr_token)
            <div class="ap-card" style="margin-bottom: 18px; display: flex; flex-wrap: wrap; gap: 24px; align-items: center;">
                <div style="padding: 10px; border: 1px solid var(--gray-lt); border-radius: 12px;">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(128)->margin(1)->generate(route('admissions.qr.verify', ['token' => $application->qr_token])) !!}
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <h3 class="ap-h" style="margin-bottom: 8px;">Admission pass</h3>
                    <p style="color: var(--gray-dk); font-size: .92rem; margin: 0 0 10px;">Show this code at the gate or the admissions desk.</p>
                    <a href="{{ route('admissions.qr.verify', ['token' => $application->qr_token]) }}" target="_blank" style="color: var(--navy); font-weight: 700; font-size: .9rem;">Open verification page &rarr;</a>
                </div>
            </div>
        @endif

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px; align-items: start;">
            {{-- Details --}}
            <div class="ap-card">
                <h3 class="ap-h">Candidate details</h3>
                <div class="ap-kv">
                    <div><span>Date of birth</span><strong>{{ $application->candidate_dob?->format('d M Y') }}</strong></div>
                    <div><span>Gender</span><strong>{{ ucfirst(strtolower($application->gender)) }}</strong></div>
                    <div><span>Blood group</span><strong>{{ $application->blood_group ?? 'Not given' }}</strong></div>
                    <div><span>Phone</span><strong>{{ $application->phone }}</strong></div>
                    <div><span>Email</span><strong>{{ $application->email ?? 'Not given' }}</strong></div>
                    <div style="grid-column: 1 / -1;"><span>Current address</span><strong>{{ $application->current_address }}</strong></div>
                </div>
            </div>

            {{-- Payments --}}
            <div class="ap-card">
                <h3 class="ap-h">Payments</h3>
                @forelse($application->payments as $payment)
                    <div class="ap-row">
                        <div>
                            <div style="font-weight: 700; color: var(--navy); font-size: .95rem;">{{ $payment->payment_type->label() }}</div>
                            <div style="font-size: .8rem; color: var(--gray); font-family: monospace;">{{ $payment->transaction_ref }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 800; color: var(--text);">৳{{ number_format($payment->amount, 2) }}</div>
                            <span class="ap-pill" style="{{ $payment->payment_status->value === 'SUCCESS' ? 'background:#d1fae5;color:#065f46;' : '' }}">{{ $payment->payment_status->value }}</span>
                        </div>
                    </div>
                @empty
                    <p style="color: var(--gray); margin: 0; font-size: .92rem;">No payments yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Documents --}}
        <div class="ap-card" style="margin-top: 18px;">
            <h3 class="ap-h">Documents</h3>
            @forelse($application->documents as $doc)
                <div class="ap-row">
                    <div>
                        <div style="font-weight: 700; color: var(--navy); font-size: .95rem;">{{ $doc->doc_type->label() }}</div>
                        <div style="font-size: .8rem; color: var(--gray);">{{ number_format($doc->file_size_bytes / 1024, 0) }} KB &middot; {{ ucfirst(strtolower(str_replace('_', ' ', $doc->doc_status->value))) }}</div>
                    </div>
                    <a href="{{ app(\App\Modules\Admissions\Services\DocumentService::class)->getSecureUrl($doc) }}" target="_blank" class="btn btn-outline" style="padding: 7px 14px; font-size: .85rem;">View</a>
                </div>
            @empty
                <p style="color: var(--gray); margin: 0; font-size: .92rem;">No documents uploaded.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
