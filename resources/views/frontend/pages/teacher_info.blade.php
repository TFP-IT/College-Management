@extends('frontend.layouts.app')

@section('title', 'Our Teachers | Maestro Crown School & College')
@section('meta_description', 'Meet the teachers of Maestro Crown School & College, Savar: subject experts in science, business studies, humanities and languages, many from Bangladesh’s leading public universities.')

@php $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1'); @endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['About', route('frontend.about')], ['Teachers']],
    'eyebrow' => 'Our teachers',
    'title'   => 'Teachers who know <em>every student by name</em>',
    'lead'    => 'Meet the educators behind our classrooms, labs and libraries: subject experts, many from Bangladesh’s leading public universities, who care about character as much as results.',
    'chips'   => array_values(array_filter([$members->count() ? $members->count() . ' educators' : null, 'Science · Business · Humanities', 'School & College'])),
    'media'   => ['type' => 'collage', 'images' => ['campus/classroom-exam', 'campus/computer-lab-3']],
])

<section class="dir-section section-pad" aria-label="Teacher directory">
    <div class="container">
        @include('frontend.partials.people-directory', ['members' => $members, 'noun' => 'teacher', 'groupBy' => 'faculty'])
    </div>
</section>

@include('frontend.partials.cta-band', ['title' => 'Learn from teachers who care.', 'text' => 'Admission for School and College is open. Apply and track your application in the ' . config('landing.app.name') . ' app.'])
@include('frontend.partials.next-links')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
