@extends('office.documents.layout')
@section('title', 'Admit card')
@section('exam', $exam)
@section('content')
<div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Time</th><th>Subject</th><th>Room</th><th class="num">Marks</th></tr></thead>
    <tbody>@foreach ($papers as $p)
        @php($at = \Carbon\Carbon::parse($p['starts_at'])->timezone('Asia/Dhaka'))
        <tr><td>{{ $at->format('D, j M Y') }}</td><td>{{ $at->format('g:i A') }}</td><td>{{ $p['subject'] ?? $p['name'] }}</td><td>{{ $p['room'] ?? '-' }}</td><td class="num">{{ $p['total_marks'] }}</td></tr>
    @endforeach</tbody>
</table></div>
@endsection
