@extends('frontend.layouts.app')

@section('title', 'Photo Gallery | Maestro Crown School & College')
@section('meta_description', 'Photos of campus life at Maestro Crown School & College, Savar: classrooms and labs, sports, Pohela Boishakh, Pitha Utshob, study tours and more.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $mc = config('landing');
    $total = $items->filter(fn ($i) => !empty($i->image_url))->count() + count($mc['gallery']['items']);
    $catCount = count($mc['gallery']['categories']);
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/gallery.css') }}">
@endpush

@section('content') 
@include('frontend.partials.page-hero', [
    'crumbs'  => [['Gallery']],
    'eyebrow' => 'Photo gallery',
    'title'   => 'Life at MCC, <em>in pictures</em>',
    'lead'    => 'Classrooms and labs, match days, Pohela Boishakh and Pitha Utshob, study tours and friendships that last. Explore the moments that make our campus feel like home.',
    'chips'   => [$total . ' photos', $catCount . ' categories', 'Updated regularly'],
    'actions' => [
        ['label' => 'Browse photos', 'href' => '#photos', 'icon' => 'fa-arrow-down', 'style' => 'primary'],
        ['label' => 'Follow on Facebook', 'href' => $mc['contact']['facebook'], 'icon' => 'fa-brands fa-facebook-f', 'style' => 'outline', 'external' => true],
    ],
    'media'   => ['type' => 'collage', 'images' => ['campus/boishakh-1', 'campus/cricket-team'], 'badge' => [$total, 'campus<br>moments']],
])

<section class="gallery-page section-pad" id="photos" aria-label="Photos" data-gallery data-initial="24">
    <div class="container">
        @include('frontend.partials.gallery-grid', ['gallery' => $items, 'initial' => 24, 'fullLink' => false])
    </div>
</section>

<section class="gp-follow" aria-label="More photos">
    <div class="container">
        <div class="gp-follow-card" data-reveal>
            <span class="gp-follow-icon"><i class="fa-brands fa-facebook-f"></i></span>
            <div>
                <h2>See the latest moments first</h2>
                <p>New photos from events, sports and celebrations are shared on our Facebook page.</p>
            </div>
            <a href="{{ $mc['contact']['facebook'] }}" target="_blank" rel="noopener" class="btn btn-primary" data-magnetic>Follow MCC <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
        </div>
    </div>
</section>

@include('frontend.partials.cta-band')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
