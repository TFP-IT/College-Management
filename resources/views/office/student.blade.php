@extends('office.layout')
@section('title', $student->name)
@section('content')
@php($bdt = fn ($n) => '৳' . number_format($n))
@php($look = ['due' => ['Due', 'warn'], 'partial' => ['Part paid', 'warn'], 'verifying' => ['To verify', 'info'], 'paid' => ['Paid', 'ok']])
<a class="back" href="{{ route('office.students') }}">All students</a>
<div class="page-head">
    <div class="person" style="margin-right:auto">
        <span class="avatar lg" aria-hidden="true">{{ \Illuminate\Support\Str::of($student->name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</span>
        <div><h1>{{ $student->name }}</h1><p class="muted">{{ trim($context->className . ' ' . $context->sectionName) ?: 'Not enrolled' }}, ID {{ $student->student_id ?: 'not issued' }}</p></div>
    </div>
</div>

<section class="panel figures">
    <div><span>Attendance this session</span><b>{{ $attendance['session']['rate'] === null ? '-' : $attendance['session']['rate'] . '%' }}</b><span>{{ $attendance['session']['absent'] }} absent, {{ $attendance['session']['late'] }} late</span></div>
    <div><span>Latest GPA</span><b>{{ isset($results[0]['gpa']) ? number_format($results[0]['gpa'], 2) : '-' }}</b><span>{{ $results[0]['exam'] ?? 'No published result' }}</span></div>
    @if ($ledger)<div><span>Fees due</span><b @if ($ledger['summary']['due']) style="color:var(--crest)" @endif>{{ $bdt($ledger['summary']['due']) }}</b><span>{{ $bdt($ledger['summary']['paid']) }} paid so far</span></div>@endif
    <div><span>Guardian</span><b style="font-size:18px">{{ $guardian->name ?? 'Not linked' }}</b><span>{{ $guardian->phone_number ?? '' }}</span></div>
</section>

@if ($ledger)
<section class="panel">
    <div class="panel-head"><h2>Fee ledger</h2></div>
    @if (empty($ledger['invoices']))
        <div class="empty">No invoices yet.</div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>Invoice</th><th class="num">Total</th><th class="num">Paid</th><th class="num">Due</th><th>Status</th><th class="num"></th></tr></thead>
            <tbody>
            @foreach ($ledger['invoices'] as $invoice)
                <tr>
                    <td><strong>{{ $invoice['title'] }}</strong><span class="small muted">{{ $invoice['number'] }}</span></td>
                    <td class="num">{{ $bdt($invoice['total']) }}</td><td class="num">{{ $bdt($invoice['paid']) }}</td><td class="num"><strong>{{ $bdt($invoice['due']) }}</strong></td>
                    <td><span class="badge {{ $look[$invoice['status']][1] }}">{{ $look[$invoice['status']][0] }}</span></td>
                    <td class="num">@include('office._invoice_actions')</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
    @if (!empty($ledger['receipts']))
        <div class="panel-head" style="border-top:1px solid var(--line)"><h2>Receipts</h2></div>
        <div class="table-wrap"><table><tbody>
            @foreach ($ledger['receipts'] as $r)
                <tr><td><a href="{{ route('office.receipt', $r['id']) }}">{{ $r['receipt_no'] }}</a><span class="small muted">{{ $r['title'] }}</span></td><td>{{ ucfirst($r['method']) }}</td><td>{{ \Carbon\Carbon::parse($r['paid_at'])->timezone('Asia/Dhaka')->format('j M Y, g:i A') }}</td><td class="num"><strong>{{ $bdt($r['amount']) }}</strong></td></tr>
            @endforeach
        </tbody></table></div>
    @endif
</section>
@endif

@php($managed = \App\Modules\Campus\Engine\Access::managed(auth()->user())->filter(fn ($m) => $m->subject() === 'student' && !$m->table()))
@php($records = \Illuminate\Support\Facades\DB::table('campus_records')->where('student_id', $student->id)->whereIn('type', $managed->keys())->orderByDesc('id')->limit(30)->get())
@if ($records->isNotEmpty())
<section class="panel" style="margin-top:20px">
    <div class="panel-head"><h2>Campus record</h2></div>
    <div class="table-wrap"><table><tbody>
        @foreach ($records as $r)
            @php($m = $managed[$r->type])
            <tr><td><a href="{{ route('office.module.show', [$r->type, $r->id]) }}">{{ $r->title }}</a><span class="small muted">{{ $m->get('label') }}</span></td><td class="num"><span class="badge {{ $m->statusLabel($r->status)['tone'] }}">{{ $m->statusLabel($r->status)['label'] }}</span></td></tr>
        @endforeach
    </tbody></table></div>
</section>
@endif

<div class="grid grid-2" style="margin-top:20px">
    <section class="panel">
        <div class="panel-head"><h2>Published results</h2>
            <span>@foreach ($admitCards as $card)<a class="btn quiet small" href="{{ $card['url'] }}" target="_blank" rel="noopener">Admit card: {{ $card['name'] }}</a> @endforeach</span>
        </div>
        @forelse ($results as $result)
            <div class="panel-head" style="padding-top:6px"><div><strong>{{ $result['exam'] }}</strong></div><span><span class="badge {{ $result['passed'] ? 'gold' : 'bad' }}">{{ $result['grade'] }}, GPA {{ number_format($result['gpa'], 2) }}</span> <a class="btn quiet small" href="{{ \App\Modules\Campus\Http\Controllers\Web\DocumentController::marksheetUrl($student->id, $result['id']) }}" target="_blank" rel="noopener">Marksheet</a></span></div>
            <div class="table-wrap"><table><tbody>
                @foreach ($result['subjects'] as $s)<tr><td>{{ $s['subject'] }}</td><td class="num">{{ $s['mark'] }}</td><td class="num"><span class="badge {{ $s['point'] > 0 ? '' : 'bad' }}">{{ $s['grade'] }}</span></td></tr>@endforeach
            </tbody></table></div>
        @empty
            <div class="empty">No result has been published for this student.</div>
        @endforelse
    </section>
    <section class="panel">
        <div class="panel-head"><h2>{{ $attendance['month_label'] }}</h2></div>
        @php($away = collect($attendance['days'])->where('status', '!=', 'present')->reverse())
        <div class="panel-body"><dl class="facts">
            <dt>Present</dt><dd>{{ $attendance['month']['present'] }}</dd><dt>Late</dt><dd>{{ $attendance['month']['late'] }}</dd><dt>Absent</dt><dd>{{ $attendance['month']['absent'] }}</dd>
        </dl></div>
        @if ($away->isNotEmpty())
            <table><tbody>@foreach ($away as $d)<tr><td>{{ \Carbon\Carbon::parse($d['date'])->format('D, j M') }}<span class="small muted">{{ $d['note'] ?: 'No reason recorded' }}</span></td><td class="num"><span class="badge {{ $d['status'] === 'late' ? 'warn' : 'bad' }}">{{ ucfirst($d['status']) }}</span></td></tr>@endforeach</tbody></table>
        @endif
    </section>
</div>
@endsection
