@extends('office.layout')
@section('title', 'Messages')
@section('content')
<div class="page-head"><div><h1>Messages</h1><p class="muted">Conversations families started with you</p></div></div>
<div class="grid" style="grid-template-columns:minmax(0,2fr) minmax(0,3fr)">
    <section class="panel">
        @forelse ($threads as $t)
            <a class="thread-link {{ $open?->id === $t->id ? 'on' : '' }}" href="{{ route('office.messages', $t->id) }}">
                <strong>{{ $t->topic }}</strong>@if (($unread[$t->id] ?? 0) > 0)<span class="badge bad">{{ $unread[$t->id] }} new</span>@endif
                <span class="small muted">{{ $names[$t->member_id] ?? '' }}{{ $t->student_id && $t->student_id !== $t->member_id ? ', about ' . ($names[$t->student_id] ?? '') : '' }}</span>
            </a>
        @empty
            <div class="empty">No messages yet.</div>
        @endforelse
    </section>
    <section class="panel">
        @if (!$open)<div class="empty">Choose a conversation.</div>@else
            <div class="panel-head"><h2>{{ $open->topic }}</h2></div>
            <div class="thread">@foreach ($messages as $m)<div class="msg {{ $m->sender_id === auth()->id() ? 'staff' : '' }}"><small>{{ $names[$m->sender_id] ?? '' }}, {{ \Carbon\Carbon::parse($m->created_at)->timezone('Asia/Dhaka')->format('j M, g:i A') }}</small>{{ $m->body }}</div>@endforeach</div>
            <form class="panel-body" method="post" action="{{ route('office.messages.reply', $open->id) }}" style="border-top:1px solid var(--line);padding-top:18px">
                @csrf
                <div class="field"><label for="body">Reply</label><textarea id="body" name="body" maxlength="3000" required></textarea></div>
                <div class="form-actions"><button class="btn">Send</button></div>
            </form>
        @endif
    </section>
</div>
@endsection
