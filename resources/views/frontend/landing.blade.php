@extends('frontend.layouts.app')

@php
    $mc = config('landing');
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $app = $mc['app'];
    $contact = $mc['contact'];

    // Admission is done only in the MCC Hello Future app: every apply CTA opens Google Play.
    $schoolHref = $app['play_store'];
    $collegeHref = $app['play_store'];
@endphp

@push('styles')
    <link rel="preload" as="image" href="{{ asset('frontend/mcc/img/hero-poster.webp') }}" type="image/webp" fetchpriority="high">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/landing.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
@endpush

@section('content')
    @include('frontend.landing.sections._intro')
    @include('frontend.landing.sections._hero')
    @include('frontend.landing.sections._trust')
    @include('frontend.landing.sections._paths')
    @include('frontend.landing.sections._app')
    @include('frontend.landing.sections._notice-board')
    @include('frontend.landing.sections._journey')
    @include('frontend.landing.sections._about')
    @include('frontend.landing.sections._why')
    @include('frontend.landing.sections._facilities')
    @include('frontend.landing.sections._achievements')
    @include('frontend.landing.sections._process')
    @include('frontend.landing.sections._leaders')
    @include('frontend.landing.sections._notices')
    @include('frontend.landing.sections._gallery')
    @include('frontend.landing.sections._faq')
    @include('frontend.landing.sections._cta')
@endsection

@push('scripts')
    {{-- landing.js first: its core runs without waiting for the CDN libraries (scroll effects start on DOMContentLoaded). --}}
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.13/dist/lenis.min.js" defer></script>
@endpush
