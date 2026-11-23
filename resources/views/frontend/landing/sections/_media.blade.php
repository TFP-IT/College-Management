{{--
    MCC Media: videos + official posters in two carousel tabs.
    Video cards look like poster cards; clicking opens the shared lightbox, which
    plays the video in place. Only poster images load with the page; a video file
    downloads when it is played.
--}}
@php
    // Faculty/staff introductions (config/faculty.php) first, then other videos (config/landing.php).
    $people = collect(config('faculty.members', []))->filter(fn ($m) => !empty($m['video']))->map(fn ($m) => [
        'src' => $m['video'], 'poster' => $m['poster'], 'tag' => $m['tag'] ?? 'Meet our faculty',
        'title' => $m['name'], 'subtitle' => $m['role'], 'caption' => $m['bio'],
        'highlights' => collect($m['education'] ?? [])->flatMap(fn ($e) => [$e['degree'], $e['institution']])
            ->push(!empty($m['experience']) ? 'Former ' . $m['experience'] : null)->filter()->unique()->values()->all(),
        'profile' => route($m['group'] === 'staff' ? 'frontend.staff_profile' : 'frontend.teacher_profile', $m['slug']),
    ]);
    $videos = $people->concat($mc['videos'] ?? [])->map(function ($v) {
        $v['ready'] = !empty($v['src']) && file_exists(public_path($v['src']));
        return $v;
    });
    $posters = $mc['posters'] ?? [];
@endphp
<section class="media section-pad" id="media" aria-labelledby="media-title" data-media>
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">MCC Media</span>
                <h2 class="sec-title" id="media-title">Watch, read &amp; <em>stay updated</em></h2>
            </div>
            <div class="media-head-side">
                <div class="media-tabs" role="tablist" aria-label="Media type">
                    <button type="button" role="tab" class="is-active" aria-selected="true" aria-controls="media-videos" id="media-tab-videos" data-media-tab="videos"><i class="fa-solid fa-circle-play"></i> Videos <span>{{ $videos->count() }}</span></button>
                    <button type="button" role="tab" aria-selected="false" aria-controls="media-posters" id="media-tab-posters" data-media-tab="posters" tabindex="-1"><i class="fa-solid fa-bullhorn"></i> Announcements <span>{{ count($posters) }}</span></button>
                </div>
                <div class="carousel-nav" aria-label="Carousel controls">
                    <button type="button" class="carousel-btn" data-carousel-prev aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" class="carousel-btn" data-carousel-next aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
        </header>

        {{-- Videos --}}
        <div class="media-panel is-active" id="media-videos" role="tabpanel" aria-labelledby="media-tab-videos" data-media-panel="videos">
            <div class="media-track" data-carousel-track data-lightbox-scope="videos">
                @foreach ($videos as $v)
                    <button type="button" class="poster poster--video {{ $v['ready'] ? '' : 'is-pending' }}"
                            data-lightbox="{{ asset('frontend/mcc/img/' . $v['poster'] . '.webp') }}"
                            data-video="{{ $v['ready'] ? asset($v['src']) : '' }}"
                            data-caption="{{ $v['title'] }}{{ !empty($v['subtitle']) ? ' · ' . $v['subtitle'] : '' }}"
                            aria-label="{{ $v['ready'] ? 'Play video' : 'Video coming soon' }}: {{ $v['title'] }}">
                        <span class="poster-frame">
                            @include('frontend.landing.partials._img', ['name' => $v['poster'], 'alt' => '', 'sizes' => '(max-width: 768px) 62vw, 260px'])
                            <span class="poster-play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
                            @unless ($v['ready'])<span class="poster-soon">Coming soon</span>@endunless
                        </span>
                        <span class="poster-meta">
                            <span class="poster-tag poster-tag--video"><i class="fa-solid fa-video"></i> {{ $v['tag'] }}</span>
                            <span class="poster-title">{{ $v['title'] }}</span>
                            @if (!empty($v['subtitle']))<span class="poster-sub">{{ $v['subtitle'] }}</span>@endif
                        </span>
                        @if (!empty($v['caption']))
                            {{-- Rendered into the viewer's info panel when this video is opened --}}
                            <template data-lightbox-info>
                                <span class="lb-tag"><i class="fa-solid fa-video"></i> {{ $v['tag'] }}</span>
                                <h3 class="lb-name">{{ $v['title'] }}</h3>
                                @if (!empty($v['subtitle']))<p class="lb-role">{{ $v['subtitle'] }}</p>@endif
                                @if (!empty($v['highlights']))
                                    <ul class="lb-chips">@foreach ($v['highlights'] as $h)<li>{{ $h }}</li>@endforeach</ul>
                                @endif
                                <p class="lb-text">{{ $v['caption'] }}</p>
                                @if (!empty($v['profile']))<a class="lb-link" href="{{ $v['profile'] }}">View full profile <i class="fa-solid fa-arrow-right"></i></a>@endif
                                <span class="lb-sign">Maestro Crown School &amp; College</span>
                            </template>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Announcements (official posters) --}}
        <div class="media-panel" id="media-posters" role="tabpanel" aria-labelledby="media-tab-posters" data-media-panel="posters" hidden>
            <div class="media-track" data-carousel-track data-lightbox-scope="board">
                @foreach ($posters as $poster)
                    <button type="button" class="poster" data-lightbox="{{ asset('frontend/mcc/img/' . $poster['image'] . '.webp') }}" data-caption="{{ $poster['title'] }}">
                        <span class="poster-frame">
                            @include('frontend.landing.partials._img', ['name' => $poster['image'], 'alt' => $poster['title'], 'sizes' => '(max-width: 768px) 62vw, 260px'])
                            <span class="poster-zoom" aria-hidden="true"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
                        </span>
                        <span class="poster-meta">
                            <span class="poster-tag poster-tag--{{ \Illuminate\Support\Str::slug($poster['tag']) }}">{{ $poster['tag'] }}</span>
                            <span class="poster-title">{{ $poster['title'] }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</section>
