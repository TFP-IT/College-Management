@extends('frontend.layouts.app')

@section('title', 'Notice Board | Maestro Crown School & College')
@section('meta_description', 'Latest notices, circulars and announcements from Maestro Crown School & College, Savar: admission, examinations, routines, results and campus news.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $mc = config('landing');
    $dateOf = fn ($n) => $n->publish_on ?? $n->created_at;
    $isNew = fn ($n) => $dateOf($n) && $dateOf($n)->gt(now()->subDays(7));
    $newCount = $notices->filter($isNew)->count();
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['Academics', route('frontend.programs')], ['Notice Board']],
    'eyebrow' => 'Notice board',
    'title'   => 'News &amp; notices, <em>straight from campus</em>',
    'lead'    => 'Official circulars, exam routines, results and announcements from the Maestro Crown School & College office, all in one place.',
    'chips'   => array_values(array_filter([$notices->count() . ' ' . \Illuminate\Support\Str::plural('notice', $notices->count()), $newCount ? $newCount . ' new this week' : null, 'Updated by the MCC office'])),
    'actions' => [
        ['label' => 'Latest notices', 'href' => '#notices', 'icon' => 'fa-arrow-down', 'style' => 'primary'],
        ['label' => 'Academic calendar', 'href' => route('frontend.academic_calendar'), 'style' => 'outline'],
    ],
    'media'   => ['type' => 'collage', 'images' => ['campus/admission-poster-2026-27', 'campus/hsc-result-2026'], 'badge' => [$notices->count(), \Illuminate\Support\Str::plural('notice', $notices->count()) . '<br>published']],
])

<section class="nb section-pad" id="notices" aria-label="Notices">
    <div class="container nb-grid">
        <div class="nb-main" data-filterable>
            @if ($notices->isEmpty())
                <div class="dir-empty" data-reveal>
                    <span class="dir-empty-icon"><i class="fa-regular fa-bell"></i></span>
                    <h2>No notices right now</h2>
                    <p>New circulars appear here as soon as they’re published. Turn on notifications in the {{ $mc['app']['name'] }} app to never miss one.</p>
                    <div class="dir-empty-actions">@include('frontend.landing.partials._stores')</div>
                </div>
            @else
                <div class="nb-tools" data-reveal>
                    <label class="dir-search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <span class="sr-only">Search notices</span>
                        <input type="search" placeholder="Search notices" autocomplete="off" data-fsearch>
                    </label>
                    <p class="dir-count"><span data-fcount>{{ $notices->count() }}</span> notices</p>
                </div>

                <ol class="nb-list">
                    @foreach ($notices as $i => $n)
                        @php $d = $dateOf($n); $body = trim((string) $n->body); $long = mb_strlen($body) > 220; @endphp
                        <li class="notice {{ $i === 0 ? 'notice--latest' : '' }}" data-reveal data-fitem data-type="all" data-search="{{ mb_strtolower($n->title . ' ' . $body) }}">
                            <div class="notice-date" aria-hidden="true">
                                <strong>{{ $d?->format('d') }}</strong>
                                <span>{{ $d?->format('M') }}</span>
                                <small>{{ $d?->format('Y') }}</small>
                            </div>
                            <article class="notice-body">
                                <div class="notice-meta">
                                    @if ($i === 0)<span class="notice-tag notice-tag--latest">Latest</span>@endif
                                    @if ($isNew($n))<span class="notice-tag">New</span>@endif
                                    <time datetime="{{ $d?->toDateString() }}">{{ $d?->format('l, d F Y') }}</time>
                                    @if ($n->expires_on)<span class="notice-exp"><i class="fa-regular fa-clock"></i> Until {{ $n->expires_on->format('d M') }}</span>@endif
                                </div>
                                <h2>{{ $n->title }}</h2>
                                @if ($long)
                                    <details class="notice-more">
                                        <summary>
                                            <span class="notice-excerpt">{{ \Illuminate\Support\Str::limit($body, 220) }}</span>
                                            <span class="notice-toggle"><span class="t-open">Read full notice</span><span class="t-close">Show less</span> <i class="fa-solid fa-chevron-down"></i></span>
                                        </summary>
                                        <p class="notice-full">{{ $body }}</p>
                                    </details>
                                @elseif ($body)
                                    <p class="notice-full">{{ $body }}</p>
                                @endif
                            </article>
                        </li>
                    @endforeach
                </ol>

                <div class="dir-none" hidden data-fnone>
                    <i class="fa-regular fa-face-meh" aria-hidden="true"></i>
                    <p>No notices match your search.</p>
                </div>
            @endif
        </div>

        <aside class="nb-aside">
            <div class="nb-app" data-reveal>
                <span class="nb-app-icon"><i class="fa-solid fa-bell"></i><span></span></span>
                <h3>Never miss a notice</h3>
                <p>Get instant notifications for routines, results and announcements in the {{ $mc['app']['name'] }} app.</p>
                @include('frontend.landing.partials._stores')
            </div>

            <div class="nb-posters" data-reveal data-lightbox-scope="posters">
                <h3>Official posters</h3>
                <div class="nb-poster-grid">
                    @foreach ($mc['posters'] as $poster)
                        <button type="button" class="nb-poster" data-lightbox="{{ asset('frontend/mcc/img/' . $poster['image'] . '.webp') }}" data-caption="{{ $poster['title'] }}">
                            @include('frontend.landing.partials._img', ['name' => $poster['image'], 'alt' => $poster['title'], 'sizes' => '160px'])
                            <span>{{ $poster['tag'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="nb-links" data-reveal>
                <a href="{{ route('frontend.academic_calendar') }}"><i class="fa-regular fa-calendar"></i><span><strong>Academic calendar</strong>Exams, holidays &amp; events</span></a>
                <a href="{{ route('frontend.programs') }}"><i class="fa-solid fa-layer-group"></i><span><strong>Programs</strong>Subjects, fees &amp; rules</span></a>
                <a href="{{ route('frontend.contact') }}"><i class="fa-solid fa-headset"></i><span><strong>Contact the office</strong>{{ $mc['contact']['phone'] }}</span></a>
            </div>
        </aside>
    </div>

    <div class="lightbox" data-lightbox-modal role="dialog" aria-modal="true" aria-label="Poster viewer" hidden>
        <button type="button" class="lightbox-close" data-lightbox-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <button type="button" class="lightbox-nav lightbox-prev" data-lightbox-prev aria-label="Previous poster"><i class="fa-solid fa-chevron-left"></i></button>
        <figure><img alt="" data-lightbox-img><figcaption><span data-lightbox-cap></span> <small data-lightbox-count></small></figcaption></figure>
        <button type="button" class="lightbox-nav lightbox-next" data-lightbox-next aria-label="Next poster"><i class="fa-solid fa-chevron-right"></i></button>
    </div>
</section>

@include('frontend.partials.next-links', ['set' => 'academics'])
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
