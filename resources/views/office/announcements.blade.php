@extends('office.layout')
@section('title', 'Announcements')
@section('content')
<div class="page-head"><div><h1>Announcements</h1><p class="muted">Delivered as app notifications</p></div></div>
<div class="grid grid-2">
    <section class="panel">
        <div class="panel-head"><h2>New announcement</h2></div>
        <form class="panel-body" method="post" action="{{ route('office.announce') }}" onsubmit="if (!confirm('Send this announcement now?')) return false; this.querySelector('button').disabled = true">
            @csrf
            <div class="field"><label for="audience">Who should receive it?</label>
                <select id="audience" name="audience">
                    <option value="everyone">Everyone: students, guardians and teachers (also on the noticeboard)</option>
                    <option value="families">All students and guardians</option>
                    <option value="teachers">Teachers</option>
                    <optgroup label="One section">@foreach ($sections as $key => $label)<option value="{{ $key }}" @selected(old('audience') === $key)>{{ $label }}</option>@endforeach</optgroup>
                </select>
            </div>
            <div class="field"><label for="title">Title</label><input id="title" name="title" value="{{ old('title') }}" maxlength="160" required></div>
            <div class="field"><label for="body">Message</label><textarea id="body" name="body" maxlength="3000" required>{{ old('body') }}</textarea></div>
            <p class="small muted" style="margin:10px 0">Do not include private details: notifications can show on a locked phone.</p>
            <button class="btn">Send announcement</button>
        </form>
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Noticeboard</h2></div>
        @forelse ($notices as $n)
            <div class="panel-body" style="border-top:1px solid var(--line);padding-top:14px;padding-bottom:14px">
                <strong>{{ $n->notice_title }}</strong>
                <div class="small muted">{{ is_numeric($n->start_date) ? date('j M Y', (int) $n->start_date) : $n->start_date }}</div>
                <p class="small">{{ \Illuminate\Support\Str::limit(html_entity_decode(strip_tags($n->notice), ENT_QUOTES), 160) }}</p>
            </div>
        @empty
            <div class="empty">Nothing on the noticeboard yet.</div>
        @endforelse
    </section>
</div>
@endsection
