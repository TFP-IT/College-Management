@extends('office.layout')
@section('title', $ticket->subject)
@section('content')
<a class="back" href="{{ route('office.tickets') }}">All requests</a>
<div class="page-head"><div><h1>{{ $ticket->subject }}</h1><p class="muted">From {{ $ticket->requester->name ?? '-' }}{{ $ticket->requester?->phone_number ? ', ' . $ticket->requester->phone_number : '' }}@if ($student), about <a href="{{ route('office.student', $student->id) }}">{{ $student->name }}</a>@endif</p></div></div>
<section class="panel" style="max-width:760px">
    <div class="thread" style="padding-top:22px">
        @foreach ($ticket->messages as $m)
            <div class="msg {{ $m->from_staff ? 'staff' : '' }}"><small>{{ $m->author->name ?? '-' }}, {{ $m->created_at->timezone('Asia/Dhaka')->format('j M, g:i A') }}</small>{{ $m->body }}</div>
        @endforeach
    </div>
    <form class="panel-body" method="post" action="{{ route('office.ticket.reply', $ticket->id) }}" style="border-top:1px solid var(--line);padding-top:18px">
        @csrf
        <div class="field"><label for="message">Reply</label><textarea id="message" name="message" maxlength="3000" placeholder="Leave empty to change only the status">{{ old('message') }}</textarea></div>
        <div class="fields" style="margin-top:14px">
            <div><label for="status">Status after saving</label><select id="status" name="status">@foreach (['in_progress' => 'In progress', 'waiting' => 'Waiting for them', 'resolved' => 'Resolved', 'closed' => 'Closed', 'open' => 'New'] as $v => $l)<option value="{{ $v }}" @selected(($ticket->status === 'open' ? 'in_progress' : $ticket->status) === $v)>{{ $l }}</option>@endforeach</select></div>
            <button class="btn">Save</button>
        </div>
    </form>
</section>
@endsection
