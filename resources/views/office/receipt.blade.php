@extends('office.layout')
@section('title', 'Receipt ' . $payment->receipt_no)
@section('content')
@php($verify = url(\Illuminate\Support\Facades\URL::signedRoute('receipt.verify', ['receipt' => $payment->receipt_no], null, false)))
<div class="page-head no-print">
    <div><a class="back" href="{{ $student ? route('office.student', $student->id) : route('office.fees') }}">Back to {{ $student->name ?? 'fees' }}</a><h1>Receipt</h1></div>
    <button class="btn" onclick="window.print()">Print receipt</button>
</div>
<section class="panel" style="max-width:560px;border-top:5px solid var(--gold)">
    <div class="panel-body" style="padding-top:26px">
        <div class="person" style="justify-content:center;margin-bottom:18px">
            <img src="{{ asset('frontend/mcc/img/logo-256.png') }}" alt="" width="58" height="58">
            <div><strong style="font:800 19px var(--sans)">{{ $school->title ?? 'Maestro Crown School and College' }}</strong><div class="small muted">{{ $school->address ?? '' }}</div></div>
        </div>
        <div style="text-align:center;padding:14px;background:var(--paper);border-radius:14px;margin-bottom:20px">
            <div class="small muted">{{ $payment->status === 'confirmed' ? 'Amount received' : 'This receipt is void' }}</div>
            <div style="font:800 38px var(--sans)">৳{{ number_format($payment->amount) }}</div>
        </div>
        <dl class="facts">
            <dt>Receipt number</dt><dd>{{ $payment->receipt_no }}</dd>
            <dt>Date</dt><dd>{{ $payment->paid_at->timezone('Asia/Dhaka')->format('j F Y, g:i A') }}</dd>
            <dt>Student</dt><dd>{{ $student->name ?? '-' }}</dd>
            <dt>Student ID</dt><dd>{{ $student->student_id ?? '-' }}</dd>
            <dt>Paid for</dt><dd>{{ $invoice->title ?? '-' }}</dd>
            <dt>Method</dt><dd>{{ $payment->method === 'bkash' ? 'bKash' : ucfirst($payment->method) }}</dd>
            @if ($payment->reference)<dt>Reference</dt><dd style="word-break:break-all">{{ $payment->reference }}</dd>@endif
            <dt>Received by</dt><dd>{{ $receiver->name ?? 'Online payment' }}</dd>
        </dl>
        <div style="display:flex;gap:16px;align-items:center;margin-top:24px;padding-top:18px;border-top:1px dashed var(--line)">
            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(104)->margin(0)->generate($verify) !!}
            <p class="small muted">Scan to check that this receipt is genuine.</p>
        </div>
    </div>
</section>
@endsection
