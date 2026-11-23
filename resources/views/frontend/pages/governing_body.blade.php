@extends('frontend.layouts.app')

@section('title', 'Governing Body | Maestro Crown School & College')
@section('meta_description', 'Meet the Governing Body of Maestro Crown School & College, Savar: the leaders who set strategic direction, safeguard academic standards and keep MCC accountable to families.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    // Local photo fallbacks until a photo is uploaded in the CMS.
    $photoMap = config('landing.people_photos', []);
    $photoFor = fn ($m) => $photoMap[$m->name] ?? null;
    $roleIcon = function ($designation) {
        $d = mb_strtolower((string) $designation);
        return match (true) {
            str_contains($d, 'chair') => 'fa-crown',
            str_contains($d, 'secretary') || str_contains($d, 'principal') => 'fa-pen-nib',
            str_contains($d, 'donor') => 'fa-hand-holding-heart',
            default => 'fa-user-tie',
        };
    };
    $featured = $members->first(fn ($m) => str_contains(mb_strtolower((string) $m->designation), 'chair'));
    $others = $featured ? $members->reject(fn ($m) => $m->is($featured))->values() : $members;

    $roles = [
        ['icon' => 'fa-compass',        'title' => 'Strategic direction', 'desc' => 'Sets long-term priorities so MCC keeps improving year after year.'],
        ['icon' => 'fa-book-open-reader', 'title' => 'Academic standards', 'desc' => 'Safeguards teaching quality, discipline and fair, merit-based assessment.'],
        ['icon' => 'fa-scale-balanced', 'title' => 'Accountability',      'desc' => 'Oversees resources responsibly and keeps every decision focused on students.'],
    ];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['About', route('frontend.about')], ['Governing Body']],
    'eyebrow' => 'Governing Body',
    'title'   => 'Guided by experience, <em>accountable to families</em>',
    'lead'    => 'The Governing Body sets Maestro Crown School & College’s strategic direction, safeguards academic standards and makes sure every decision serves our students.',
    'chips'   => [$members->count() . ' members', 'EIIN 136876', 'College Code 1933'],
    'media'   => ['type' => 'crest'],
])

<section class="gb-roles section-pad" aria-labelledby="gb-roles-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">Our role</span>
            <h2 class="sec-title" id="gb-roles-title">What the Governing Body <em>does</em></h2>
        </header>
        <div class="card-grid card-grid--3" data-stagger>
            @foreach ($roles as $i => $r)
                <article class="num-card" data-reveal>
                    <span class="num-card-n">0{{ $i + 1 }}</span>
                    <span class="num-card-icon"><i class="fa-solid {{ $r['icon'] }}"></i></span>
                    <h3>{{ $r['title'] }}</h3>
                    <p>{{ $r['desc'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="gb-members section-pad" aria-labelledby="gb-members-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Members</span>
                <h2 class="sec-title" id="gb-members-title">Meet the <em>board</em></h2>
            </div>
            <p class="sec-sub">Leaders from education, business and our community, working together for MCC’s students.</p>
        </header>

        @if ($members->isEmpty())
            <div class="dir-empty" data-reveal>
                <span class="dir-empty-icon"><i class="fa-solid fa-sitemap"></i></span>
                <h2>Member information is being updated</h2>
                <p>Please check back soon, or contact our office for details about the Governing Body.</p>
            </div>
        @else
            @if ($featured)
                <article class="gb-featured" data-reveal>
                    @include('frontend.partials.portrait', ['name' => $featured->name, 'url' => $featured->photo_url, 'image' => $photoFor($featured), 'class' => 'gb-featured-photo', 'sizes' => '220px'])
                    <div class="gb-featured-body">
                        <span class="gb-role"><i class="fa-solid {{ $roleIcon($featured->designation) }}"></i> {{ $featured->designation }}</span>
                        <h3>{{ $featured->name }}</h3>
                        <p>Founder of Kamrul Group and Maestro Crown School & College, leading the board’s vision for an institution built on mastery, honour and fairness.</p>
                        <div class="gb-featured-links">
                            <a href="{{ route('frontend.chairman_profile') }}" class="link-arrow">Profile <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="{{ route('frontend.message_chairman') }}" class="link-arrow">Message <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                </article>
            @endif
            <ul class="gb-grid" data-stagger>
                @foreach ($others as $m)
                    <li class="gb-card" data-reveal>
                        @include('frontend.partials.portrait', ['name' => $m->name, 'url' => $m->photo_url, 'image' => $photoFor($m), 'class' => 'gb-photo', 'sizes' => '112px'])
                        <h3>{{ $m->name }}</h3>
                        @if ($m->designation)<span class="gb-role"><i class="fa-solid {{ $roleIcon($m->designation) }}"></i> {{ $m->designation }}</span>@endif
                        @if ($m->department)<p>{{ $m->department }}</p>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>

@include('frontend.partials.next-links')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
