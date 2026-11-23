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
    'chips'   => array_values(array_filter([$people->count() ? $people->count() . ' team members' : null, 'Administration', 'Accounts', 'Support services'])),
    'media'   => ['type' => 'collage', 'images' => ['campus/students-3', 'campus/campus-front']],
])

<section class="people section-pad" aria-label="Staff">
    <div class="container">
        @if ($people->isEmpty())
            @include('frontend.partials.people-directory', ['members' => collect(), 'noun' => 'staff member', 'collective' => 'staff', 'groupBy' => 'none'])
        @else
            <header class="sec-head sec-head--split" data-reveal>
                <div>
                    <span class="eyebrow">Meet the team</span>
                    <h2 class="sec-title">The people <em>behind the scenes</em></h2>
                </div>
                <p class="sec-sub">Administration, accounts, front office and support services, keeping every school day running smoothly.</p>
            </header>
            <div class="pc-grid pc-grid--staff" data-stagger>
                @foreach ($people as $p)
                    <div class="pc-cell">@include('frontend.partials.person-card', ['p' => $p])</div>
                @endforeach
            </div>
            <p class="people-note" data-reveal><i class="fa-solid fa-circle-info"></i> Staff photos are being added.</p>
        @endif
    </div>
</section>

@include('frontend.partials.next-links')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
