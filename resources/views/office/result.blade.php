@extends('office.layout')
@section('title', $publication->exam_name)
@section('content')
<a class="back" href="{{ route('office.results') }}">All results</a>
<div class="page-head">
    <div><h1>{{ $publication->exam_name }}</h1><p class="muted">{{ $publication->section_label }}, sent by {{ $publication->submitter ?? 'unknown' }}</p></div>
    @if ($publication->status === 'submitted')
        <form method="post" action="{{ route('office.result.publish', $publication->id) }}" onsubmit="if (!confirm('Publish these results to students and guardians?')) return false; this.querySelector('button').disabled = true">
            @csrf<button class="btn">Publish results</button>
        </form>
    @elseif ($publication->status === 'published')
        <span class="badge ok">Published {{ $publication->published_at?->timezone('Asia/Dhaka')->format('j M Y') }}</span>
    @endif
</div>
@foreach ($warnings as $warning)<div class="flash warn" role="status">{{ $warning }}</div>@endforeach
@if ($publication->note)<div class="flash bad">Sent back: {{ $publication->note }}</div>@endif

@php($graded = $sheet->whereNotNull('gpa'))
<section class="panel figures">
    <div><span>Students with marks</span><b>{{ $graded->count() }}</b></div>
    <div><span>Passed</span><b>{{ $graded->where('passed', true)->count() }}</b></div>
    <div><span>Failed</span><b @if ($graded->where('passed', false)->count()) style="color:var(--crest)" @endif>{{ $graded->where('passed', false)->count() }}</b></div>
    <div><span>Average GPA</span><b>{{ $graded->count() ? number_format($graded->avg('gpa'), 2) : '-' }}</b></div>
</section>

<section class="panel">
    @if ($sheet->isEmpty())
        <div class="empty">No marks were found for this class.</div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>Student</th>@foreach ($subjects as $s)<th class="num">{{ $s['subject'] }}</th>@endforeach<th class="num">Total</th><th class="num">GPA</th></tr></thead>
            <tbody>
            @foreach ($sheet as $row)
                @php($marks = collect($row['subjects'])->keyBy('subject_id'))
                <tr>
                    <td><strong>{{ $row['name'] }}</strong><span class="small muted">{{ $row['code'] }}</span></td>
                    @foreach ($subjects as $s)
                        @php($m = $marks->get($s['subject_id']))
                        <td class="num">@if (!$m)<span class="badge warn">Missing</span>@elseif ($m['point'] == 0)<span class="badge bad">{{ $m['mark'] }}</span>@else{{ $m['mark'] }}@endif</td>
                    @endforeach
                    <td class="num">{{ $row['total'] }}</td>
                    <td class="num"><strong>{{ $row['gpa'] === null ? '-' : number_format($row['gpa'], 2) }}</strong> <span class="badge {{ $row['passed'] ? 'gold' : 'bad' }}">{{ $row['grade'] }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
</section>

@if (in_array($publication->status, ['submitted', 'published']))
<section class="panel" style="max-width:640px">
    <div class="panel-head"><div><h2>Send back for correction</h2><p class="small muted">{{ $publication->status === 'published' ? 'Families will stop seeing these marks until you publish again.' : 'The teacher can edit the marks and send them again.' }}</p></div></div>
    <form class="panel-body" method="post" action="{{ route('office.result.return', $publication->id) }}">
        @csrf
        <div class="field"><label for="note">What needs to change?</label><input id="note" name="note" maxlength="500" required></div>
        <button class="btn soft">Send back</button>
    </form>
</section>
@endif
@endsection
