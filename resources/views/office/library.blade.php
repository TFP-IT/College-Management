@extends('office.layout')
@section('title', 'Library')
@section('content')
<div class="page-head"><div><h1>Library</h1><p class="muted">Late books are charged ৳{{ config('campus.library_fine_per_day') }} a day to the student's fees</p></div></div>
<div class="grid grid-2">
    <section class="panel">
        <div class="panel-head"><h2>Issue a book</h2></div>
        <form class="panel-body form-grid" method="post" action="{{ route('office.library.issue') }}">
            @csrf
            <div class="field wide"><label for="book">Book</label><select id="book" name="book_id" required><option value="">Choose a book</option>@foreach ($allBooks as $id => $title)<option value="{{ $id }}" @selected(old('book_id') == $id)>{{ $title }}</option>@endforeach</select></div>
            <div class="field"><label for="code">Student ID (scan or type)</label><input id="code" name="student_code" value="{{ old('student_code') }}" required></div>
            <div class="field"><label for="days">Days</label><input id="days" type="number" name="days" value="14" min="1" max="90" required></div>
            <div class="wide form-actions"><button class="btn">Issue book</button></div>
        </form>
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Add to catalogue</h2></div>
        <form class="panel-body form-grid" method="post" action="{{ route('office.library.book') }}">
            @csrf
            <div class="field wide"><label for="title">Title</label><input id="title" name="title" maxlength="200" required></div>
            <div class="field"><label for="author">Author</label><input id="author" name="author" maxlength="160"></div>
            <div class="field"><label for="isbn">ISBN</label><input id="isbn" name="isbn" maxlength="20"></div>
            <div class="field"><label for="category">Category</label><input id="category" name="category" maxlength="60"></div>
            <div class="field"><label for="shelf">Shelf</label><input id="shelf" name="shelf" maxlength="40"></div>
            <div class="field"><label for="copies">Copies</label><input id="copies" type="number" name="copies" value="1" min="1" required></div>
            <div class="wide form-actions"><button class="btn soft">Add book</button></div>
        </form>
    </section>
</div>

<section class="panel">
    <div class="panel-head"><h2>Out and reserved ({{ $loans->count() }})</h2></div>
    @if ($loans->isEmpty())<div class="empty">No books are out.</div>@else
    <div class="table-wrap"><table>
        <thead><tr><th>Book</th><th>Student</th><th>Status</th><th class="num"></th></tr></thead>
        <tbody>@foreach ($loans as $l)
            <tr><td><strong>{{ $l->title }}</strong></td><td>{{ $l->name }}<span class="small muted">{{ $l->code }}</span></td>
                <td>@if ($l->status === 'reserved')<span class="badge info">Reserved</span>@elseif ($l->due_on < now()->toDateString())<span class="badge bad">Overdue since {{ \Carbon\Carbon::parse($l->due_on)->format('j M') }}</span>@else<span class="badge">Due {{ \Carbon\Carbon::parse($l->due_on)->format('j M') }}</span>@endif</td>
                <td class="num"><form method="post" action="{{ route('office.library.close', $l->id) }}" class="inline-actions">@csrf
                    @if ($l->status === 'issued')
                        <input type="number" name="charge" min="0" placeholder="Damage ৳" aria-label="Extra charge">
                        <button class="btn small" name="outcome" value="returned">Returned</button>
                        <button class="btn quiet small" name="outcome" value="lost" onclick="return confirm('Mark this copy as lost?')">Lost</button>
                    @else
                        <button class="btn quiet small" name="outcome" value="cancelled">Cancel</button>
                    @endif
                </form></td></tr>
        @endforeach</tbody>
    </table></div>@endif
</section>

<section class="panel">
    <div class="panel-head"><h2>Catalogue</h2><form method="get" role="search"><input type="search" name="q" value="{{ $search }}" placeholder="Search books" aria-label="Search books"></form></div>
    @if ($books->isEmpty())<div class="empty">No books found.</div>@else
    <div class="table-wrap"><table>
        <thead><tr><th>Title</th><th>Category</th><th>Shelf</th><th class="num">Available</th></tr></thead>
        <tbody>@foreach ($books as $b)<tr><td><strong>{{ $b->title }}</strong><span class="small muted">{{ $b->author }}</span></td><td>{{ $b->category }}</td><td>{{ $b->shelf }}</td><td class="num"><span class="badge {{ $b->available ? 'ok' : 'warn' }}">{{ $b->available }} of {{ $b->copies }}</span></td></tr>@endforeach</tbody>
    </table></div>@endif
</section>
@endsection
