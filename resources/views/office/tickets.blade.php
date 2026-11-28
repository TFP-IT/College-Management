@extends('office.layout')
@section('title', 'Help desk')
@section('content')
@php($look = ['open' => ['New', 'info'], 'in_progress' => ['In progress', 'warn'], 'waiting' => ['Waiting for them', ''], 'resolved' => ['Resolved', 'ok'], 'closed' => ['Closed', '']])
@php($roles = [3 => 'Teacher', 6 => 'Guardian', 7 => 'Student'])
<div class="page-head"><div><h1>Help desk</h1><p class="muted">Requests from the app</p></div>
    <nav class="tabs" aria-label="Request status">@foreach (['active' => 'Open', 'done' => 'Finished', 'all' => 'All'] as $key => $label)<a href="{{ route('office.tickets', ['status' => $key]) }}" @if ($filter === $key) aria-current="true" @endif>{{ $label }}</a>@endforeach</nav>
</div>
<section class="panel">
    @if ($tickets->isEmpty())
        <div class="empty"><strong>No requests here</strong>New questions from students and guardians appear in this list.</div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>Request</th><th>From</th><th>Category</th><th>Status</th><th class="num">Last update</th></tr></thead>
            <tbody>
            @foreach ($tickets as $t)
                <tr>
                    <td><a href="{{ route('office.ticket', $t->id) }}"><strong>{{ $t->subject }}</strong></a><span class="small muted">SR-{{ str_pad($t->id, 5, '0', STR_PAD_LEFT) }}</span></td>
                    <td>{{ $t->requester->name ?? '-' }}<span class="small muted">{{ $roles[$t->requester->role_id ?? 0] ?? 'Staff' }}</span></td>
                    <td>{{ ucfirst(str_replace(['id_card', '_'], ['ID card', ' '], $t->category)) }} @if ($t->priority === 'high')<span class="badge bad">High priority</span>@endif</td>
                    <td><span class="badge {{ $look[$t->status][1] }}">{{ $look[$t->status][0] }}</span></td>
                    <td class="num">{{ $t->updated_at->diffForHumans() }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @if ($tickets->hasPages())<div class="pager">@if ($tickets->previousPageUrl())<a class="btn quiet small" href="{{ $tickets->previousPageUrl() }}">Previous</a>@endif @if ($tickets->nextPageUrl())<a class="btn quiet small" href="{{ $tickets->nextPageUrl() }}">Next</a>@endif</div>@endif
    @endif
</section>
@endsection
