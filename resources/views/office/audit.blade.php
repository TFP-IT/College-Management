@extends('office.layout')
@section('title', 'Audit trail')
@section('content')
<div class="page-head"><div><h1>Audit trail</h1><p class="muted">Who did what, and when. Entries cannot be edited here.</p></div></div>
<section class="panel">
    <form class="toolbar" method="get" style="padding-top:18px" role="search"><input type="search" name="q" value="{{ $search }}" placeholder="Action, person or record number" aria-label="Search the audit trail"><button class="btn quiet">Search</button></form>
    @if ($logs->isEmpty())<div class="empty">No entries.</div>@else
    <div class="table-wrap"><table>
        <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Change</th></tr></thead>
        <tbody>@foreach ($logs as $l)
            <tr><td class="small">{{ \Carbon\Carbon::parse($l->created_at)->timezone('Asia/Dhaka')->format('j M Y, g:i A') }}</td><td>{{ $l->actor ?? 'System' }}</td><td><strong>{{ $l->action }}</strong><span class="small muted">{{ $l->module }}</span></td><td class="small">{{ \Illuminate\Support\Str::limit($l->record_id, 14) }}</td><td class="small muted">{{ \Illuminate\Support\Str::limit(is_array($change = json_decode($l->new_value ?? '', true)) ? collect($change)->map(fn ($v, $k) => str_replace('_', ' ', $k) . ': ' . (is_scalar($v) ? $v : json_encode($v)))->implode(', ') : ($l->new_value ?? ''), 110) }}</td></tr>
        @endforeach</tbody>
    </table></div>
    <div class="pager">@if ($logs->previousPageUrl())<a class="btn quiet small" href="{{ $logs->previousPageUrl() }}">Newer</a>@endif @if ($logs->nextPageUrl())<a class="btn quiet small" href="{{ $logs->nextPageUrl() }}">Older</a>@endif</div>@endif
</section>
@endsection
