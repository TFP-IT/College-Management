@extends('office.layout')
@section('title', $module->get('plural'))
@section('content')
<a class="back" href="{{ route('office.modules') }}">All modules</a>
<div class="page-head">
    <div><h1>{{ $module->get('plural') }}</h1><p class="muted">{{ $module->group()['label'] }}, {{ number_format($page->total()) }} {{ \Illuminate\Support\Str::plural('record', $page->total()) }}</p></div>
    <a class="btn" href="{{ route('office.module.create', $module->key) }}">Add {{ lcfirst($module->get('label')) }}</a>
</div>
@if (!empty($module->group()['confidential']))<div class="flash warn">Confidential. Opening a record is written to the audit trail.</div>@endif
<section class="panel">
    <form class="toolbar" method="get" style="padding-top:18px" role="search">
        <input type="search" name="q" value="{{ $search }}" placeholder="Name, student ID or title" aria-label="Search">
        <select name="status" aria-label="Status" onchange="this.form.submit()">
            <option value="">Every status</option>
            @foreach ($module->get('statuses') as $key => $s)<option value="{{ $key }}" @selected($status === $key)>{{ $s[0] }}</option>@endforeach
        </select>
        <button class="btn quiet">Search</button>
    </form>
    @if ($rows->isEmpty())
        <div class="empty"><strong>Nothing here yet</strong>Add the first {{ lcfirst($module->get('label')) }}, or change the filter.</div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>{{ $module->get('label') }}</th>@if ($module->subject())<th>{{ $module->subject() === 'student' ? 'Student' : 'Staff member' }}</th>@endif<th>Details</th><th>Status</th><th class="num">Added</th></tr></thead>
            <tbody>
            @foreach ($rows as $r)
                <tr>
                    <td><a href="{{ route('office.module.show', [$module->key, $r['id']]) }}"><strong>{{ $r['title'] }}</strong></a></td>
                    @if ($module->subject())<td>{{ $module->subject() === 'student' ? $r['student'] : $r['staff'] }}</td>@endif
                    <td class="small muted">{{ \Illuminate\Support\Str::limit(collect($r['fields'])->take(3)->map(fn ($f) => \Illuminate\Support\Str::limit($f['value'], 40))->implode(', '), 90) }}</td>
                    <td><span class="badge {{ $r['tone'] }}">{{ $r['status_label'] }}</span></td>
                    <td class="num">{{ $r['created_at'] ? \Carbon\Carbon::parse($r['created_at'])->format('j M Y') : '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @if ($page->hasPages())<div class="pager"><span class="small muted">Page {{ $page->currentPage() }} of {{ $page->lastPage() }}</span>@if ($page->previousPageUrl())<a class="btn quiet small" href="{{ $page->previousPageUrl() }}">Previous</a>@endif @if ($page->nextPageUrl())<a class="btn quiet small" href="{{ $page->nextPageUrl() }}">Next</a>@endif</div>@endif
    @endif
</section>
@endsection
