@extends('frontend.layouts.app')

@section('title', $page->title . ' | Maestro Crown School & College')

@php $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1'); @endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [[$page->title]],
    'eyebrow' => 'Maestro Crown School & College',
    'title'   => e($page->title),
])

<section class="section-pad" aria-label="{{ $page->title }}">
    <div class="container">
        <article class="cms-article prose" data-reveal>
            {!! $body !!}
        </article>
    </div>
</section>

@include('frontend.partials.next-links')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
