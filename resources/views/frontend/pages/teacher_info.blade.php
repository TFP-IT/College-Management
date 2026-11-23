@extends('frontend.layouts.app')

@section('title', 'Our Teachers | Maestro Crown School & College')
@section('meta_description', 'Meet the Principal and teachers of Maestro Crown School & College, Savar: subject experts in science, ICT, business studies, humanities and languages.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $leaders = collect(config('faculty.leadership', []));
    $depts = $people->pluck('department')->filter()->unique()->values();
    $withVideo = $people->filter(fn ($p) => !empty($p['video']))->count();
    $heroImgs = $people->pluck('poster')->filter()->values();
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
    'lead'    => 'Meet the educators behind our classrooms and labs: subject experts from Bangladesh’s leading universities, who care about character as much as results.',
    'chips'   => array_values(array_filter([$people->count() . ' lecturers', $withVideo ? $withVideo . ' video introductions' : null, $depts->implode(' · ')])),
    'media'   => ['type' => 'collage', 'images' => [$heroImgs[0] ?? 'campus/classroom-exam', $heroImgs[3] ?? 'campus/computer-lab-3']],
])

{{-- Academic leadership --}}
@if ($leaders->isNotEmpty())
<section class="lead-strip" aria-label="Academic leadership">
    <div class="container">
        @foreach ($leaders as $l)
            <a href="{{ route($l['route']) }}" class="lead-card" data-reveal>
                @include('frontend.partials.portrait', ['name' => $l['name'], 'image' => $l['poster'] ?? null, 'class' => 'lead-photo', 'sizes' => '160px'])
                <span class="lead-body">
                    <span class="eyebrow eyebrow--plain">Academic leadership</span>
                    <strong>{{ $l['name'] }}</strong>
                    <span class="lead-role">{{ $l['role'] }}, Maestro Crown School &amp; College</span>
                    @if (!empty($l['quote']))<span class="lead-quote">“{{ $l['quote'] }}”</span>@endif
                </span>
                <span class="lead-cta">Read the Principal’s message <i class="fa-solid fa-arrow-right"></i></span>
            </a>
        @endforeach
    </div>
</section>
@endif

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
                <div class="pc-cell" data-fitem data-type="{{ $p['department'] ? \Illuminate\Support\Str::slug($p['department']) : 'other' }}">
                    @include('frontend.partials.person-card', ['p' => $p])
                </div>
            @endforeach
        </div>
        <p class="people-note" data-reveal><i class="fa-solid fa-circle-info"></i> Photos and detailed profiles are being added for every teacher.</p>
    </div>
</section>

@include('frontend.partials.cta-band', ['title' => 'Learn from teachers who care.', 'text' => 'Admission for School and College is open. Apply and track your application in the ' . config('landing.app.name') . ' app.'])
@include('frontend.partials.next-links')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
