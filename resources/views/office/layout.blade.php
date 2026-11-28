@php
    use App\Modules\Campus\Support\OfficeAccess;
    $areas = OfficeAccess::areas(auth()->user());
    $school = (int) (auth()->user()->school_id ?: 1);
    // [label, route, icon path], grouped by the kind of work.
    $nav = [
        'Daily' => [
            'dashboard' => ['Today', 'office.dashboard', 'M3 11.5 12 4l9 7.5M5 10v10h5v-6h4v6h5V10'],
            'approvals' => ['Approvals', 'office.approvals', 'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            'messages' => ['Messages', 'office.messages', 'M4 5h16v12H8l-4 4V5Z'],
            'help' => ['Help desk', 'office.tickets', 'M21 12a8 8 0 0 1-8 8H4l2-3a8 8 0 1 1 15-5ZM8.5 12h.01M12.5 12h.01M16 12h.01'],
        ],
        'College' => [
            'students' => ['Students', 'office.students', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8'],
            'results' => ['Results', 'office.results', 'M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12Zm-3.5-1.2L7 22l5-3 5 3-1.5-8.2'],
            'fees' => ['Fees', 'office.fees', 'M3 7h15a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7Zm0 0V6a2 2 0 0 1 2-2h11v3M16.5 13.5h.01'],
            'library' => ['Library', 'office.library', 'M4 4h6a2 2 0 0 1 2 2v14a3 3 0 0 0-3-2H4V4Zm16 0h-6a2 2 0 0 0-2 2v14a3 3 0 0 1 3-2h5V4Z'],
            'modules' => ['Campus modules', 'office.modules', 'M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z'],
            'elections' => ['Elections', 'office.elections', 'M4 20h16M6 16V9h12v7M9 9V5h6v4M10 13l1.5 1.5L15 11'],
        ],
        'Oversight' => [
            'announcements' => ['Announcements', 'office.announcements', 'M3 10v4h4l10 5V5L7 10H3Zm17-1a4 4 0 0 1 0 6M7 14v5h3v-4'],
            'insights' => ['Insights', 'office.insights', 'M4 20V10m6 10V4m6 16v-7m4 7H2'],
            'audit' => ['Audit trail', 'office.audit', 'M12 3l8 3v6c0 5-3.5 8-8 9-4.500-1-8-4-8-9V6l8-3Zm-3 9 2 2 4-4'],
        ],
    ];
    $current = [
        'dashboard' => ['office.dashboard'], 'students' => ['office.student*'], 'fees' => ['office.fees*', 'office.receipt'],
        'results' => ['office.result*'], 'announcements' => ['office.announce*'], 'help' => ['office.ticket*'],
        'approvals' => ['office.approvals*'], 'messages' => ['office.messages*'], 'library' => ['office.library*'],
        'modules' => ['office.modules', 'office.module*'], 'elections' => ['office.elections*'], 'insights' => ['office.insights'], 'audit' => ['office.audit'],
    ];
    $counts = [
        'results' => in_array('results', $areas) ? \App\Modules\Campus\Models\ResultPublication::where('school_id', $school)->where('status', 'submitted')->count() : 0,
        'help' => in_array('help', $areas) ? \App\Modules\Campus\Models\ServiceTicket::where('school_id', $school)->where('status', 'open')->count() : 0,
        'approvals' => in_array('approvals', $areas) ? app(\App\Modules\Campus\Engine\Workflow::class)->inbox(auth()->user())->count() : 0,
    ];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Office') | Maestro Crown School and College</title>
    <link rel="icon" href="{{ asset('frontend/mcc/img/logo-256.png') }}">
    <link rel="stylesheet" href="{{ asset('css/mcc-office.css') }}?v={{ filemtime(public_path('css/mcc-office.css')) }}">
</head>
<body>
<a class="skip" href="#content">Skip to content</a>
<div class="shell">
    <aside class="side">
        <a class="brand" href="{{ route('office.dashboard') }}">
            <img src="{{ asset('frontend/mcc/img/logo-256.png') }}" alt="">
            <div><strong>MCC Office</strong><span>Hello Future</span></div>
        </a>
        <nav class="nav" aria-label="Office">
            @foreach ($nav as $group => $items)
                @continue(!array_intersect(array_keys($items), $areas))
                <span class="nav-group">{{ $group }}</span>
                @foreach ($items as $area => [$label, $route, $icon])
                    @continue(!in_array($area, $areas))
                    <a href="{{ route($route) }}" @if (request()->routeIs(...$current[$area])) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                        {{ $label }}
                        @if (!empty($counts[$area]))<span class="count">{{ $counts[$area] }}</span>@endif
                    </a>
                @endforeach
            @endforeach
        </nav>
        <div class="side-foot">
            <div><b>{{ auth()->user()->name }}</b></div>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                @if ((int) auth()->user()->role_id === 2)<a href="{{ route('admin.dashboard') }}">School setup</a>@endif
                @if ((int) auth()->user()->role_id === 4)<a href="{{ route('accountant.dashboard') }}">Accounts</a>@endif
                <button type="submit">Sign out</button>
            </form>
        </div>
    </aside>
    <main class="main" id="content">
        @if (session('ok'))<div class="flash" role="status">{{ session('ok') }}</div>@endif
        @if ($errors->any())<div class="flash bad" role="alert">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</div>
</body>
</html>
