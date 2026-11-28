@extends('office.layout')
@section('title', $view['title'])
@section('content')
<a class="back" href="{{ $manages ? route('office.module', $module->key) : route('office.approvals') }}">{{ $manages ? $module->get('plural') : 'Approvals' }}</a>
<div class="page-head">
    <div><h1>{{ $view['title'] }}</h1>
        <p class="muted">{{ $module->get('label') }}@if ($view['student']), <a href="{{ \App\Modules\Campus\Support\OfficeAccess::allows(auth()->user(), 'students') ? route('office.student', $view['student_id']) : '#' }}">{{ $view['student'] }}</a>@endif @if ($view['staff']), {{ $view['staff'] }}@endif</p></div>
    <span class="badge {{ $view['tone'] }}">{{ $view['status_label'] }}</span>
</div>

<div class="grid grid-2">
    <div class="stack">
        <section class="panel">
            <div class="panel-head"><h2>Details</h2></div>
            <div class="panel-body">
                @if (empty($view['fields']))<p class="muted">No further details.</p>@else
                <dl class="detail">@foreach ($view['fields'] as $f)<dt>{{ $f['label'] }}</dt><dd>{{ $f['value'] }}</dd>@endforeach
                    @if (!empty($record->data['code']))<dt>Verification</dt><dd><a href="{{ route('certificate.verify', $record->data['code']) }}" target="_blank" rel="noopener">{{ $record->data['code'] }}</a></dd>@endif
                </dl>@endif
            </div>
        </section>

        @if ($children->isNotEmpty())
        <section class="panel">
            <div class="panel-head"><h2>Sign-ups ({{ $children->count() }})</h2></div>
            <div class="table-wrap"><table><tbody>
                @foreach ($children as $c)<tr><td><a href="{{ route('office.module.show', [$c['type'], $c['id']]) }}">{{ $c['student'] ?? \App\Models\User::find($c['student_id'])?->name }}</a></td><td class="num"><span class="badge {{ $c['tone'] }}">{{ $c['status_label'] }}</span></td></tr>@endforeach
            </tbody></table></div>
        </section>
        @endif

        @if ($manages)
        <section class="panel">
            <details @if (!$flow) open @endif>
                <summary class="panel-head"><h2>Update</h2></summary>
                <form class="panel-body form-grid" method="post" action="{{ route('office.module.update', [$module->key, $record->id]) }}">
                    @csrf
                    @foreach ($module->fields() as $key => $field)
                        @continue($module->table() && $key === 'period')
                        @include('office.modules.field', ['field' => $field, 'value' => $record->data[$key] ?? $record->{$key} ?? '', 'creating' => false])
                    @endforeach
                    <div class="field wide"><label for="status">Status</label>
                        <select id="status" name="status">
                            @foreach ($module->get('statuses') as $key => $s)
                                {{-- Approval outcomes are set only by the approval buttons. --}}
                                @continue($flow && $key !== $record->status && ($flow->status === 'pending' || in_array($key, ['approved', 'rejected'])))
                                <option value="{{ $key }}" @selected($record->status === $key)>{{ $s[0] }}</option>
                            @endforeach
                        </select></div>
                    <div class="wide form-actions"><button class="btn">Save changes</button></div>
                </form>
            </details>
        </section>
        @endif
    </div>

    <div class="stack">
        @if ($gaps !== null && $record->status === 'pending')
            <section class="panel"><div class="panel-head"><h2>Clearance check</h2></div><div class="panel-body">
                @forelse ($gaps as $gap)<div class="flash warn" style="margin-bottom:8px">{{ ucfirst($gap) }}</div>@empty<div class="flash" style="margin:0">Everything is clear.</div>@endforelse
            </div></section>
        @endif
        @if ($flow)
        <section class="panel">
            <div class="panel-head"><h2>Approval</h2></div>
            <ol class="steps">
                @foreach ($flow->steps as $i => $step)
                    @php($done = $flow->actions->firstWhere('step', $i))
                    <li class="{{ $done ? $done->action : ($flow->status === 'pending' && $flow->step === $i ? 'now' : '') }}">
                        <strong>{{ $step === 'office' ? 'College office' : ucfirst($step) }}</strong>
                        <span class="small muted">
                            @if ($done) {{ ucfirst($done->action) }} by {{ $done->actor }}, {{ \Carbon\Carbon::parse($done->created_at)->timezone('Asia/Dhaka')->format('j M, g:i A') }}@if ($done->note). {{ $done->note }}@endif
                            @elseif ($flow->status === 'pending' && $flow->step === $i) Waiting @else Not reached @endif
                        </span>
                    </li>
                @endforeach
            </ol>
            @if ($canAct)
                <form class="panel-body" method="post" action="{{ route('office.approvals.decide', $flow->id) }}" style="border-top:1px solid var(--line);padding-top:18px">
                    @csrf
                    <div class="field"><label for="note">Note (needed if you do not approve)</label><input id="note" name="note" maxlength="500"></div>
                    <div class="form-actions"><button class="btn soft" name="decision" value="reject">Do not approve</button><button class="btn" name="decision" value="approve">Approve</button></div>
                </form>
            @elseif ($flow->status === 'pending')
                <p class="panel-body small muted">Nothing for you to decide at this step. A request cannot be approved by the person who made it, and one person cannot approve two steps.</p>
            @endif
        </section>
        @endif
    </div>
</div>
@endsection
