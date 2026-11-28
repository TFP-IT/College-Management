<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
    <title>@yield('title') | {{ $student->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/mcc-office.css') }}?v={{ filemtime(public_path('css/mcc-office.css')) }}">
</head>
<body class="document">
<div class="document-bar no-print"><button class="btn" onclick="window.print()">Print or save as PDF</button></div>
<main class="sheet">
    <header class="sheet-head">
        <img src="{{ asset('frontend/mcc/img/logo-256.png') }}" alt="Maestro Crown crest" width="72" height="72">
        <div><strong>{{ $school->title ?? 'Maestro Crown School and College' }}</strong><span>{{ $school->address ?? '' }}</span></div>
    </header>
    <h1>@yield('title')</h1>
    <p class="sheet-exam">@yield('exam')</p>
    <dl class="facts sheet-who">
        <dt>Student</dt><dd>{{ $student->name }}</dd>
        <dt>Student ID</dt><dd>{{ $student->student_id ?: '-' }}</dd>
        <dt>Class</dt><dd>{{ trim(($class ?? '') . ' ' . ($section ?? '')) ?: '-' }}</dd>
    </dl>
    @yield('content')
    <div class="signatures"><span>Class teacher</span><span>Principal</span></div>
    <p class="small muted">Printed from the college record on {{ now()->timezone('Asia/Dhaka')->format('j F Y, g:i A') }}.</p>
</main>
</body>
</html>
