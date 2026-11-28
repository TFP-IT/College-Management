@extends('office.layout')
@section('title', 'Students')
@section('content')
<div class="page-head"><div><h1>Students</h1><p class="muted">{{ number_format($students->total()) }} {{ \Illuminate\Support\Str::plural('student', $students->total()) }}</p></div></div>
<section class="panel">
    <form class="toolbar" method="get" style="padding-top:18px" role="search">
        <input type="search" name="q" value="{{ $search }}" placeholder="Name, student ID or phone" aria-label="Search students">
        <select name="section" aria-label="Section">
            <option value="">All sections</option>
            @foreach ($sections as $key => $label)<option value="{{ $key }}" @selected($section === $key)>{{ $label }}</option>@endforeach
        </select>
        <button class="btn quiet">Search</button>
    </form>
    @if ($students->isEmpty())
        <div class="empty"><strong>No student found</strong>Try another name or clear the filter.</div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>Student</th><th>Student ID</th><th>Class</th><th>Account</th></tr></thead>
            <tbody>
            @foreach ($students as $s)
                <tr>
                    <td><div class="person"><span class="avatar" aria-hidden="true">{{ \Illuminate\Support\Str::of($s->name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</span><a href="{{ route('office.student', $s->id) }}"><strong>{{ $s->name }}</strong></a></div></td>
                    <td>{{ $s->student_id ?: 'Not issued' }}</td>
                    <td>{{ trim($s->class_name . ' ' . $s->section_name) ?: 'Not enrolled' }}</td>
                    <td><span class="badge {{ $s->status ? 'ok' : '' }}">{{ $s->status ? 'Active' : 'Inactive' }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @if ($students->hasPages())
            <div class="pager">
                <span class="small muted">Page {{ $students->currentPage() }} of {{ $students->lastPage() }}</span>
                @if ($students->previousPageUrl())<a class="btn quiet small" href="{{ $students->previousPageUrl() }}">Previous</a>@endif
                @if ($students->nextPageUrl())<a class="btn quiet small" href="{{ $students->nextPageUrl() }}">Next</a>@endif
            </div>
        @endif
    @endif
</section>
@endsection
