@extends('frontend.layouts.app')

@section('title', 'Our Teachers | Maestro Crown School & College')
@section('meta_description', 'Meet the teachers of Maestro Crown School & College, Savar: subject experts from leading universities in science, business studies, humanities and languages.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $depts = $people->pluck('department')->unique()->values();
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['About', route('frontend.about')], ['Teachers']],
    'eyebrow' => 'Our teachers',
    'title'   => 'Teachers who know <em>every student by name</em>',
    'lead'    => 'Meet the educators behind our classrooms and labs: subject experts from Bangladesh’s leading universities, who care about character as much as results. Watch their introductions and explore their profiles.',
    'chips'   => [$people->count() . ' featured teachers', $depts->implode(' · ')],
    'media'   => ['type' => 'collage', 'images' => [$people[0]['poster'] ?? 'campus/classroom-exam', $people[3]['poster'] ?? 'campus/computer-lab-3']],
])

<section class="people section-pad" aria-labelledby="people-title" data-filterable>
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Meet our faculty</span>
                <h2 class="sec-title" id="people-title">Learn from <em>the best</em></h2>
            </div>
            @if ($depts->count() > 1)
                <div class="dir-filters" role="toolbar" aria-label="Filter by department">
                    <button type="button" class="is-active" aria-pressed="true" data-fbtn="all">All <span>{{ $people->count() }}</span></button>
                    @foreach ($depts as $d)
                        <button type="button" aria-pressed="false" data-fbtn="{{ \Illuminate\Support\Str::slug($d) }}">{{ $d }} <span>{{ $people->where('department', $d)->count() }}</span></button>
                    @endforeach
                </div>
            @endif
        </header>
        <div class="pc-grid" data-stagger>
            @foreach ($people as $p)
                <div class="pc-cell" data-fitem data-type="{{ \Illuminate\Support\Str::slug($p['department']) }}">
                    @include('frontend.partials.person-card', ['p' => $p])
                </div>
            @endforeach
        </div>
        <p class="people-note" data-reveal><i class="fa-solid fa-circle-info"></i> More teacher profiles are being added. Every MCC teacher will soon have a profile here.</p>
    </div>
</section>

@include('frontend.partials.cta-band', ['title' => 'Learn from teachers who care.', 'text' => 'Admission for School and College is open. Apply and track your application in the ' . config('landing.app.name') . ' app.'])
@include('frontend.partials.next-links')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
