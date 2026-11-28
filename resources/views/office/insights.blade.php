@extends('office.layout')
@section('title', 'Insights')
@section('content')
<div class="page-head"><div><h1>Insights</h1><p class="muted">Simple rules that point to who may need help. People make the decisions.</p></div></div>
<section class="panel figures">@foreach ($counts as $label => $n)<div><span>{{ $label }}</span><b>{{ $n }}</b></div>@endforeach</section>
<section class="panel">
    <div class="panel-head"><h2>Students needing attention ({{ count($students) }})</h2></div>
    @if (!$students)<div class="empty">No student matches an alert rule right now.</div>@else
    <div class="table-wrap"><table>
        <thead><tr><th>Student</th><th>Why</th><th class="num">Overall</th></tr></thead>
        <tbody>@foreach ($students as $s)
            <tr><td><a href="{{ route('office.student', $s['id']) }}"><strong>{{ $s['name'] }}</strong></a><span class="small muted">{{ $s['class'] }}, {{ $s['code'] }}</span></td>
                <td>@foreach ($s['alerts'] as $a)<div class="small">{{ $a }}</div>@endforeach</td><td class="num">{{ $s['overall'] === null ? '-' : $s['overall'] . '%' }}</td></tr>
        @endforeach</tbody>
    </table></div>@endif
</section>
<section class="panel">
    <div class="panel-head"><h2>Syllabus progress</h2></div>
    @if ($sections->isEmpty())<div class="empty">No syllabus topics have been recorded.</div>@else
    <div class="table-wrap"><table>
        <thead><tr><th>Class and subject</th><th>Teacher</th><th style="width:34%">Covered</th><th class="num"></th></tr></thead>
        <tbody>@foreach ($sections as $s)<tr><td><strong>{{ $s->subject }}</strong><span class="small muted">{{ $s->class }}</span></td><td>{{ $s->teacher }}</td><td><div class="bar"><i class="p" style="width:{{ $s->percent }}%"></i></div></td><td class="num">{{ $s->percent }}%</td></tr>@endforeach</tbody>
    </table></div>@endif
</section>
@endsection
