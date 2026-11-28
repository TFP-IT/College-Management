@extends('office.documents.layout')
@section('title', 'Marksheet')
@section('exam', $result['exam'])
@section('content')
<div class="table-wrap"><table>
    <thead><tr><th>Subject</th><th class="num">Mark</th><th class="num">Grade</th><th class="num">Point</th></tr></thead>
    <tbody>@foreach ($result['subjects'] as $s)
        <tr><td>{{ $s['subject'] }}</td><td class="num">{{ $s['mark'] }}</td><td class="num">{{ $s['grade'] }}</td><td class="num">{{ number_format($s['point'], 2) }}</td></tr>
    @endforeach</tbody>
</table></div>
<div class="sheet-total">
    <div><span>Total</span><b>{{ $result['total'] }}</b></div>
    <div><span>GPA</span><b>{{ $result['gpa'] === null ? '-' : number_format($result['gpa'], 2) }}</b></div>
    <div><span>Grade</span><b>{{ $result['grade'] ?? '-' }}</b></div>
    <div><span>Outcome</span><b>{{ $result['passed'] === null ? '-' : ($result['passed'] ? 'Passed' : 'Not passed') }}</b></div>
</div>
@if ($result['comment'])<p><strong>Teacher's comment:</strong> {{ $result['comment'] }}</p>@endif
@endsection
