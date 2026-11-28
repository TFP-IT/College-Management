@extends('office.layout')
@section('title', 'Elections')
@section('content')
@php($look = ['nominations' => ['Nominations open', 'info'], 'open' => ['Voting now', 'warn'], 'counting' => ['Voting closed', 'gold'], 'published' => ['Published', 'ok']])
<div class="page-head"><div><h1>Student elections</h1><p class="muted">Secret ballot: nobody can see how a student voted</p></div></div>
@forelse ($elections as $e)
<section class="panel" style="margin-bottom:20px">
    <div class="panel-head">
        <div><h2>{{ $e->title }}</h2><p class="small muted">{{ $e->position }}, {{ $e->class_id ? ($classes[$e->class_id] ?? 'one class') : 'all students' }}. Voting {{ \Carbon\Carbon::parse($e->opens_at)->timezone('Asia/Dhaka')->format('j M, g:i A') }} to {{ \Carbon\Carbon::parse($e->closes_at)->timezone('Asia/Dhaka')->format('j M, g:i A') }}</p></div>
        <span class="badge {{ $look[$e->state][1] }}">{{ $look[$e->state][0] }}</span>
        @if ($e->state === 'counting')<form method="post" action="{{ route('office.elections.publish', $e->id) }}" onsubmit="return confirm('Publish this result to students?')">@csrf<button class="btn small">Publish result</button></form>@endif
    </div>
    @if (empty($e->candidates))<div class="empty">No nominations yet.</div>@else
    <div class="table-wrap"><table><tbody>
        @foreach ($e->candidates as $c)
            <tr><td><strong>{{ $c['name'] }}</strong><span class="small muted">{{ $c['statement'] }}</span></td>
                <td class="num">
                    @if ($c['votes'] !== null && $c['status'] === 'approved')<strong>{{ $c['votes'] }} {{ \Illuminate\Support\Str::plural('vote', $c['votes']) }}</strong>
                    @elseif ($e->state === 'nominations')
                        <form method="post" action="{{ route('office.elections.candidate', [$e->id, $c['id']]) }}" class="inline-actions">@csrf
                            <span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad', 'pending' => 'warn'][$c['status']] }}">{{ ucfirst($c['status']) }}</span>
                            @if ($c['status'] !== 'approved')<button class="btn small" name="status" value="approved">Approve</button>@endif
                            @if ($c['status'] !== 'rejected')<button class="btn quiet small" name="status" value="rejected">Reject</button>@endif
                        </form>
                    @else<span class="badge">{{ ucfirst($c['status']) }}</span>@endif
                </td></tr>
        @endforeach
    </tbody></table></div>@endif
    @if ($e->state !== 'nominations')<p class="panel-body small muted" style="padding-top:12px">{{ $e->turnout }} {{ \Illuminate\Support\Str::plural('student', $e->turnout) }} voted.</p>@endif
</section>
@empty
<section class="panel"><div class="empty">No elections yet.</div></section>
@endforelse

<section class="panel" style="max-width:820px">
    <div class="panel-head"><h2>New election</h2></div>
    <form class="panel-body form-grid" method="post" action="{{ route('office.elections.create') }}">
        @csrf
        <div class="field"><label for="title">Title</label><input id="title" name="title" value="{{ old('title') }}" placeholder="College prefect election" required></div>
        <div class="field"><label for="position">Position</label><input id="position" name="position" value="{{ old('position') }}" placeholder="College Prefect" required></div>
        <div class="field"><label for="class_id">Who votes</label><select id="class_id" name="class_id"><option value="">All students</option>@foreach ($classes as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
        <div class="field"><label for="opens">Voting opens</label><input id="opens" type="datetime-local" name="opens_at" value="{{ old('opens_at') }}" required></div>
        <div class="field"><label for="closes">Voting closes</label><input id="closes" type="datetime-local" name="closes_at" value="{{ old('closes_at') }}" required></div>
        <div class="field wide"><label for="description">Rules and eligibility</label><textarea id="description" name="description" maxlength="1000">{{ old('description') }}</textarea></div>
        <div class="wide form-actions"><button class="btn">Create election</button></div>
    </form>
</section>
@endsection
