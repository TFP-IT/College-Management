<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
    <title>Certificate check | Maestro Crown School and College</title>
    <link rel="stylesheet" href="{{ asset('css/mcc-office.css') }}">
</head>
<body style="display:grid;place-items:center;min-height:100vh;padding:20px">
<main class="panel" style="width:min(460px,100%);border-top:5px solid var(--gold)">
    <div class="panel-body" style="padding-top:24px;text-align:center">
        <img src="{{ asset('frontend/mcc/img/logo-256.png') }}" alt="Maestro Crown crest" width="76" height="76">
        <p style="margin:14px 0"><span class="badge {{ $status['tone'] }}">Certificate: {{ $status['label'] }}</span></p>
        <h1>{{ $record->title }}</h1>
        <p class="muted" style="margin-bottom:18px">Maestro Crown School and College</p>
        <dl class="facts" style="text-align:left">
            <dt>Awarded to</dt><dd>{{ $holder }}</dd>
            <dt>Issued</dt><dd>{{ $record->starts_at ? \Carbon\Carbon::parse($record->starts_at)->format('j F Y') : '-' }}</dd>
            <dt>Code</dt><dd>{{ $data['code'] }}</dd>
        </dl>
    </div>
</main>
</body>
</html>
