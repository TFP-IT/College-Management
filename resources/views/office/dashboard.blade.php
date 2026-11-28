@extends('office.layout')
@section('title', 'Today')
@section('content')
@php
    $areas = \App\Modules\Campus\Support\OfficeAccess::areas(auth()->user());
    $pct = fn ($n) => $today['students'] ? $n / $today['students'] * 100 : 0;
    $present = $today['attended'] - $today['late'];
    $bdt = fn ($n) => '৳' . number_format($n);
@endphp
<div class="page-head">
    <div>
        <h1>Today at MCC</h1>
        <p class="muted">{{ $today['date']->format('l, j F Y') }}</p>
    </div>
    @if (in_array('announcements', $areas))<a class="btn" href="{{ route('office.announcements') }}">Send an announcement</a>@endif
</div>

<section class="panel register" aria-label="Attendance today">
    <div class="register-main">
        <p class="muted">{{ $today['recorded'] && $today['recorded'] < $today['students'] ? 'Present among students marked so far' : 'Students in college' }}</p>
        @if ($today['recorded'])
            <p class="big">{{ round($today['attended'] / $today['recorded'] * 100) }}%</p>
            <p>{{ $today['attended'] }} of {{ $today['recorded'] }} marked students, {{ $today['students'] }} enrolled</p>
        @else
            <p class="big">-</p>
            <p>No attendance has been recorded yet. {{ $today['students'] }} students are enrolled.</p>
        @endif
        <div class="bar" role="img" aria-label="{{ $present }} present, {{ $today['late'] }} late, {{ $today['absent'] }} absent, {{ $today['students'] - $today['recorded'] }} not marked">
            <i class="p" style="width:{{ $pct($present) }}%"></i><i class="l" style="width:{{ $pct($today['late']) }}%"></i><i class="a" style="width:{{ $pct($today['absent']) }}%"></i>
        </div>
        <div class="legend">
            <span style="--c:#2e9e6a">{{ $present }} present</span>
            <span style="--c:#e0a526">{{ $today['late'] }} late</span>
            <span style="--c:#e4574b">{{ $today['absent'] }} absent</span>
            <span style="--c:rgba(255,255,255,.35)">{{ $today['students'] - $today['recorded'] }} not marked</span>
        </div>
    </div>
    <ul class="todo" aria-label="Needs attention">
        <li class="{{ $today['unmarked_sections'] ? 'hot' : '' }}"><div><b>{{ $today['unmarked_sections'] }}</b><span>Sections not fully marked<small>{{ $today['unmarked_sections'] ? 'Ask the class teacher to finish attendance' : 'Attendance is complete everywhere' }}</small></span></div></li>
        @if (in_array('results', $areas))<li class="{{ $today['pending_results'] ? 'hot' : '' }}"><a href="{{ route('office.results') }}"><b>{{ $today['pending_results'] }}</b><span>Results waiting for approval<small>Families see marks only after you publish</small></span></a></li>@endif
        @if (in_array('help', $areas))<li class="{{ $today['open_tickets'] ? 'hot' : '' }}"><a href="{{ route('office.tickets') }}"><b>{{ $today['open_tickets'] }}</b><span>Help requests open<small>From students, guardians and staff</small></span></a></li>@endif
        @if ($money)<li><a href="{{ route('office.fees') }}"><b>{{ $money['debtors'] }}</b><span>Students with fees due<small>{{ $bdt($money['due']) }} outstanding</small></span></a></li>@endif
    </ul>
</section>

<div class="grid grid-2" style="margin-top:20px">
    <section class="panel">
        <div class="panel-head"><h2>Attendance by section</h2></div>
        @if ($today['classes']->isEmpty())
            <div class="empty"><strong>No enrolled students</strong>Enrol students to see attendance here.</div>
        @else
            <div class="table-wrap"><table>
                <thead><tr><th>Section</th><th style="width:45%">Today</th><th class="num">Marked</th></tr></thead>
                <tbody>
                @foreach ($today['classes'] as $c)
                    <tr>
                        <td><strong>{{ $c['label'] }}</strong></td>
                        <td><div class="bar" role="img" aria-label="{{ $c['present'] }} present, {{ $c['late'] }} late, {{ $c['absent'] }} absent">
                            @foreach (['p' => 'present', 'l' => 'late', 'a' => 'absent'] as $class => $key)<i class="{{ $class }}" style="width:{{ $c['students'] ? $c[$key] / $c['students'] * 100 : 0 }}%"></i>@endforeach
                        </div></td>
                        <td class="num">@if (!$c['recorded'])<span class="badge warn">Not marked</span>@elseif ($c['recorded'] < $c['students'])<span class="badge warn">{{ $c['recorded'] }} of {{ $c['students'] }}</span>@else{{ $c['recorded'] }} of {{ $c['students'] }}@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </section>

    <div class="stack">
        @if ($money)
        <section class="panel">
            <div class="panel-head"><h2>Collections</h2><a class="small" href="{{ route('office.fees') }}">Open fees</a></div>
            <div class="figures" style="border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
                <div><span>Today</span><b>{{ $bdt($money['today']) }}</b></div>
                <div><span>This month</span><b>{{ $bdt($money['month']) }}</b></div>
            </div>
            @forelse ($payments as $p)
                @if ($loop->first)<table><tbody>@endif
                <tr><td><a href="{{ route('office.receipt', $p->id) }}">{{ $names[$p->student_id] ?? 'Student' }}</a><span class="small muted">{{ $p->receipt_no }}, {{ $p->paid_at->timezone('Asia/Dhaka')->format('j M, g:i A') }}</span></td><td class="num"><strong>{{ $bdt($p->amount) }}</strong></td></tr>
                @if ($loop->last)</tbody></table>@endif
            @empty
                <div class="empty">No payments recorded yet.</div>
            @endforelse
        </section>
        @endif

        <section class="panel">
            <div class="panel-head"><h2>Attendance below 80%</h2></div>
            @forelse ($today['at_risk'] as $s)
                @if ($loop->first)<table><tbody>@endif
                <tr><td><a href="{{ route('office.student', $s->id) }}">{{ $s->name }}</a><span class="small muted">{{ $s->student_id }}, {{ $s->days }} days recorded</span></td><td class="num"><span class="badge bad">{{ $s->rate }}%</span></td></tr>
                @if ($loop->last)</tbody></table>@endif
            @empty
                <div class="empty">No student is below 80%.</div>
            @endforelse
        </section>
    </div>
</div>
@endsection
