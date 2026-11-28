@extends('office.layout')
@section('title', 'Approvals')
@section('content')
<div class="page-head"><div><h1>Approvals</h1><p class="muted">Requests waiting for your decision</p></div></div>
<section class="panel">
    @if ($waiting->isEmpty())
        <div class="empty"><strong>Nothing is waiting for you</strong>New requests that need your role appear here.</div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>Request</th><th>From</th><th>Step</th><th>Asked</th><th class="num"></th></tr></thead>
            <tbody>@foreach ($waiting as $w)
                <tr><td><strong>{{ $w->title }}</strong><span class="small muted">{{ $w->module->get('label') }}</span></td>
                    <td>{{ $names[$w->requested_by] ?? 'System' }}</td>
                    <td>{{ $w->step + 1 }} of {{ count($w->steps) }}</td>
                    <td>{{ \Carbon\Carbon::parse($w->created_at)->diffForHumans() }}</td>
                    <td class="num"><a class="btn small" href="{{ route('office.module.show', [$w->subject_type, $w->subject_id]) }}">Review</a></td></tr>
            @endforeach</tbody>
        </table></div>
    @endif
</section>
@if ($done->isNotEmpty())
<section class="panel">
    <div class="panel-head"><h2>Your recent decisions</h2></div>
    <div class="table-wrap"><table><tbody>@foreach ($done as $d)
        <tr><td>{{ $d->title }}<span class="small muted">{{ config("campus_modules.types.{$d->subject_type}.label") }}</span></td><td><span class="badge {{ $d->action === 'approved' ? 'ok' : 'bad' }}">{{ ucfirst($d->action) }}</span></td><td class="num">{{ \Carbon\Carbon::parse($d->created_at)->timezone('Asia/Dhaka')->format('j M, g:i A') }}</td></tr>
    @endforeach</tbody></table></div>
</section>
@endif
@endsection
