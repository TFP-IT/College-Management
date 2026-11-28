@extends('office.layout')
@section('title', 'Results')
@section('content')
@php($look = ['submitted' => ['Waiting', 'warn'], 'published' => ['Published', 'ok'], 'returned' => ['Sent back', 'bad']])
<div class="page-head"><div><h1>Results</h1><p class="muted">Check each class sheet before families can see it</p></div></div>
@if ($unsent->isNotEmpty())
<section class="panel" style="margin-bottom:20px">
    <div class="panel-head"><div><h2>Entered on the website, not sent yet</h2><p class="small muted">Open one to check it and publish.</p></div></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Examination</th><th>Section</th><th>Students with marks</th><th class="num"></th></tr></thead>
        <tbody>
        @foreach ($unsent as $u)
            <tr><td><strong>{{ $u->exam_name }}</strong></td><td>{{ $u->section_label }}</td><td>{{ $u->students }}</td>
                <td class="num"><form method="post" action="{{ route('office.result.open') }}">@csrf
                    @foreach (['session_id', 'exam_category_id', 'class_id', 'section_id'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $u->$field }}">@endforeach
                    <button class="btn quiet small">Open for review</button>
                </form></td></tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endif
@foreach ([['Waiting for approval', $waiting, 'Nothing is waiting. Results appear here when a teacher sends them.'], ['Published and sent back', $others, 'No results have been handled yet.']] as [$heading, $list, $none])
<section class="panel">
    <div class="panel-head"><h2>{{ $heading }}</h2></div>
    @if ($list->isEmpty())
        <div class="empty">{{ $none }}</div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>Examination</th><th>Section</th><th>Sent by</th><th>Status</th><th class="num"></th></tr></thead>
            <tbody>
            @foreach ($list as $p)
                <tr>
                    <td><strong>{{ $p->exam_name }}</strong></td><td>{{ $p->section_label }}</td>
                    <td>{{ $p->submitter ?? '-' }}<span class="small muted">{{ $p->submitted_at?->timezone('Asia/Dhaka')->format('j M, g:i A') }}</span></td>
                    <td><span class="badge {{ $look[$p->status][1] ?? '' }}">{{ $look[$p->status][0] ?? $p->status }}</span>@if ($p->publisher)<span class="small muted">by {{ $p->publisher }}</span>@endif</td>
                    <td class="num"><a class="btn {{ $p->status === 'submitted' ? '' : 'quiet' }} small" href="{{ route('office.result', $p->id) }}">{{ $p->status === 'submitted' ? 'Review' : 'View' }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
</section>
@endforeach
@endsection
