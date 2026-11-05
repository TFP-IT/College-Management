@extends('frontend.layouts.app')

@section('title', $page->title . ' | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>{{ $page->title }}</h1>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card cms-body">
            {!! $body !!}
        </div>
    </div>
</div>
@endsection
