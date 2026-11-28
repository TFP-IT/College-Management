<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
    <title>Receipt check | Maestro Crown School and College</title>
    <link rel="stylesheet" href="{{ asset('css/mcc-office.css') }}">
</head>
<body style="display:grid;place-items:center;min-height:100vh;padding:20px">
<main class="panel" style="width:min(420px,100%);border-top:5px solid var(--gold)">
    <div class="panel-body" style="padding-top:24px;text-align:center">
        <img src="{{ asset('frontend/mcc/img/logo-256.png') }}" alt="Maestro Crown crest" width="72" height="72">
        @php($valid = $payment->status === 'confirmed')
        <p style="margin:14px 0"><span class="badge {{ $valid ? 'ok' : 'bad' }}">{{ $valid ? 'Genuine receipt' : 'Void receipt' }}</span></p>
        <h1>৳{{ number_format($payment->amount) }}</h1>
        <p class="muted" style="margin-bottom:18px">Maestro Crown School and College</p>
        <dl class="facts" style="text-align:left"><dt>Receipt</dt><dd>{{ $payment->receipt_no }}</dd><dt>Date</dt><dd>{{ $payment->paid_at->timezone('Asia/Dhaka')->format('j F Y') }}</dd><dt>Student ID</dt><dd>{{ $studentCode }}</dd></dl>
    </div>
</main>
</body>
</html>
