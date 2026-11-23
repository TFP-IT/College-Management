@extends('frontend.layouts.app')

@section('title', 'Our Staff | Maestro Crown School & College')
@section('meta_description', 'Meet the administrative and support staff of Maestro Crown School & College, Savar, who keep every school day safe, organised and welcoming.')

@php $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1'); @endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['About', route('frontend.about')], ['Staff']],
    'eyebrow' => 'Our staff',
    'title'   => 'The team that keeps <em>MCC running</em>',
    'lead'    => 'From the front office to the library, labs and transport, our staff make every school day safe, organised and welcoming for students and families.',
    'chips'   => array_values(array_filter([$members->count() ? $members->count() . ' team members' : null, 'Administration', 'Student support'])),
    'media'   => ['type' => 'collage', 'images' => ['campus/students-3', 'campus/campus-front']],
])

<section class="dir-section section-pad" aria-label="Staff directory">
    <div class="container">
        @include('frontend.partials.people-directory', ['members' => $members, 'noun' => 'staff member', 'collective' => 'staff', 'groupBy' => 'none'])
    </div>
</section>

@include('frontend.partials.next-links')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
