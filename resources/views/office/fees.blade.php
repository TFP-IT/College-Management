@extends('office.layout')
@section('title', 'Fees')
@section('content')
@php($bdt = fn ($n) => '৳' . number_format($n))
@php($look = ['due' => ['Due', 'warn'], 'partial' => ['Part paid', 'warn'], 'verifying' => ['To verify', 'info'], 'paid' => ['Paid', 'ok']])
<div class="page-head">
    <div><h1>Fees</h1><p class="muted">Bill a class, collect payments and issue receipts</p></div>
    <a class="btn quiet" href="{{ route('office.fees.export') }}">Download CSV</a>
</div>

<section class="panel figures">
    <div><span>Collected today</span><b>{{ $bdt($money['today']) }}</b></div>
    <div><span>Collected this month</span><b>{{ $bdt($money['month']) }}</b></div>
    <div><span>Billed in total</span><b>{{ $bdt($money['billed']) }}</b></div>
    <div><span>Outstanding</span><b style="color:var(--crest)">{{ $bdt($money['due']) }}</b></div>
</section>

<section class="panel">
    <div class="panel-head"><div><h2>Bill a section</h2><p class="small muted">Creates one invoice per student. Students who already have an invoice with the same title are skipped.</p></div></div>
    <form class="panel-body" method="post" action="{{ route('office.fees.bill') }}" onsubmit="if (!confirm('Create these invoices and notify the families?')) return false; this.querySelector('button').disabled = true">
        @csrf
        <div class="fields">
            <div class="field"><label for="section">Section</label><select id="section" name="section" required>@foreach ($sections as $key => $label)<option value="{{ $key }}" @selected(old('section') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="title">Invoice title</label><input id="title" name="title" value="{{ old('title', 'Tuition, ' . now('Asia/Dhaka')->format('F Y')) }}" maxlength="120" required></div>
            <div class="field"><label for="amount">Amount (৳)</label><input id="amount" type="number" name="amount" min="1" value="{{ old('amount') }}" required></div>
            <div class="field"><label for="discount">Discount (৳)</label><input id="discount" type="number" name="discount" min="0" value="{{ old('discount') }}" placeholder="0"></div>
            <button class="btn">Create invoices</button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-head"><h2>Invoices</h2><span class="small muted">{{ number_format($matched) }} found{{ $matched > 100 ? ', first 100 shown' : '' }}</span></div>
    <div class="toolbar">
        <nav class="tabs" aria-label="Invoice status">
            @foreach (['due' => 'Due', 'verifying' => 'To verify', 'paid' => 'Paid', 'all' => 'All'] as $key => $label)
                <a href="{{ route('office.fees', ['status' => $key, 'q' => $search ?: null]) }}" @if ($filter === $key) aria-current="true" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <form method="get" role="search" style="display:flex;gap:10px;margin-left:auto">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="search" name="q" value="{{ $search }}" placeholder="Student or invoice title" aria-label="Search invoices">
            <button class="btn quiet">Search</button>
        </form>
    </div>
    @if ($invoices->isEmpty())
        <div class="empty"><strong>No invoices here</strong>Change the filter, or bill a section above.</div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>Student</th><th>Invoice</th><th class="num">Total</th><th class="num">Paid</th><th class="num">Due</th><th>Status</th><th class="num"></th></tr></thead>
            <tbody>
            @foreach ($invoices as $invoice)
                @php($s = $students->get($invoice['student_id']))
                <tr>
                    <td>@if ($s)<a href="{{ route('office.student', $s->id) }}"><strong>{{ $s->name }}</strong></a><span class="small muted">{{ $s->student_id }}</span>@else <span class="muted">Removed student</span>@endif</td>
                    <td>{{ $invoice['title'] }}<span class="small muted">{{ $invoice['number'] }}</span></td>
                    <td class="num">{{ $bdt($invoice['total']) }}</td>
                    <td class="num">{{ $bdt($invoice['paid']) }}</td>
                    <td class="num"><strong>{{ $bdt($invoice['due']) }}</strong></td>
                    <td><span class="badge {{ $look[$invoice['status']][1] }}">{{ $look[$invoice['status']][0] }}</span></td>
                    <td class="num">@include('office._invoice_actions')</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
</section>
@endsection
