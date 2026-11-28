@extends('office.layout')
@section('title', 'Campus modules')
@section('content')
<div class="page-head"><div><h1>Campus</h1><p class="muted">Every service and record your role manages</p></div></div>

@if ($expiring->isNotEmpty())
<section class="panel" style="margin-bottom:20px">
    <div class="panel-head"><h2>Coming due</h2></div>
    <div class="table-wrap"><table><tbody>
        @foreach ($expiring as $e)
            @php($late = \Carbon\Carbon::parse($e['ends_at'])->endOfDay()->isPast())
            <tr><td><a href="{{ route('office.module.show', [$e['module']->key, $e['id']]) }}">{{ $e['title'] }}</a><span class="small muted">{{ $e['module']->get('label') }}</span></td>
                <td class="num"><span class="badge {{ $late ? 'bad' : 'warn' }}">{{ $late ? 'Overdue since' : 'Due' }} {{ \Carbon\Carbon::parse($e['ends_at'])->format('j M Y') }}</span></td></tr>
        @endforeach
    </tbody></table></div>
</section>
@endif

@foreach ($groups as $group => $modules)
    <h2 style="margin:26px 0 12px">{{ $group }}</h2>
    <div class="tiles">
        @foreach ($modules as $m)
            <a class="tile" href="{{ route('office.module', $m->key) }}">
                <strong>{{ $m->get('plural') }}</strong>
                <span class="muted small">{{ $m->table() ? 'Staff records' : number_format($counts[$m->key] ?? 0) . ' ' . \Illuminate\Support\Str::plural('record', $counts[$m->key] ?? 0) }}</span>
            </a>
        @endforeach
    </div>
@endforeach
@endsection
