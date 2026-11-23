{{--
    MCC Media: one carousel of images (announcements) and videos, newest first.
    Each card renders by its type. Clicking opens the shared lightbox, which zooms
    images and plays videos in place. Only thumbnails load with the page.
    Data: App\Support\MediaFeed (config today, backend later).
--}}
@php
    $feed = \App\Support\MediaFeed::all();
@endphp
<section class="media section-pad" id="media" aria-labelledby="media-title" data-media>
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">MCC Media</span>
                <h2 class="sec-title" id="media-title">Watch, read &amp; <em>stay updated</em></h2>
            </div>
            <div class="carousel-nav" aria-label="Carousel controls">
                <button type="button" class="carousel-btn" data-carousel-prev aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
                <button type="button" class="carousel-btn" data-carousel-next aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </header>

        <div class="media-track" data-carousel-track data-lightbox-scope="media">
            @foreach ($feed as $item)
                @if ($item['type'] === 'video')
                    <button type="button" class="poster poster--video {{ $item['ready'] ? '' : 'is-pending' }}"
                            data-lightbox="{{ asset('frontend/mcc/img/' . $item['thumb'] . '.webp') }}"
                            data-video="{{ $item['ready'] ? asset($item['src']) : '' }}"
                            data-caption="{{ $item['title'] }}{{ !empty($item['subtitle']) ? ' · ' . $item['subtitle'] : '' }}"
                            aria-label="{{ $item['ready'] ? 'Play video' : 'Video coming soon' }}: {{ $item['title'] }}">
                        <span class="poster-frame">
                            @include('frontend.landing.partials._img', ['name' => $item['thumb'], 'alt' => '', 'sizes' => '(max-width: 768px) 62vw, 260px'])
                            <span class="poster-play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
                            @unless ($item['ready'])<span class="poster-soon">Coming soon</span>@endunless
                        </span>
                        <span class="poster-meta">
                            <span class="poster-tag poster-tag--video"><i class="fa-solid fa-video"></i> {{ $item['tag'] }}</span>
                            <span class="poster-title">{{ $item['title'] }}</span>
                            @if (!empty($item['subtitle']))<span class="poster-sub">{{ $item['subtitle'] }}</span>@endif
                        </span>
                        @if (!empty($item['caption']))
                            {{-- Rendered into the viewer's info panel when this video is opened --}}
                            <template data-lightbox-info>
                                <span class="lb-tag"><i class="fa-solid fa-video"></i> {{ $item['tag'] }}</span>
                                <h3 class="lb-name">{{ $item['title'] }}</h3>
                                @if (!empty($item['subtitle']))<p class="lb-role">{{ $item['subtitle'] }}</p>@endif
                                @if (!empty($item['highlights']))
                                    <ul class="lb-chips">@foreach ($item['highlights'] as $h)<li>{{ $h }}</li>@endforeach</ul>
                                @endif
                                <p class="lb-text">{{ $item['caption'] }}</p>
                                @if (!empty($item['profile']))<a class="lb-link" href="{{ $item['profile'] }}">View full profile <i class="fa-solid fa-arrow-right"></i></a>@endif
                                <span class="lb-sign">Maestro Crown School &amp; College</span>
                            </template>
                        @endif
                    </button>
                @else
                    <button type="button" class="poster poster--image"
                            data-lightbox="{{ asset('frontend/mcc/img/' . $item['thumb'] . '.webp') }}"
                            data-caption="{{ $item['title'] }}"
                            aria-label="View announcement: {{ $item['title'] }}">
                        <span class="poster-frame">
                            @include('frontend.landing.partials._img', ['name' => $item['thumb'], 'alt' => $item['title'], 'sizes' => '(max-width: 768px) 62vw, 260px'])
                            <span class="poster-zoom" aria-hidden="true"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
                        </span>
                        <span class="poster-meta">
                            <span class="poster-tag poster-tag--{{ \Illuminate\Support\Str::slug($item['tag']) }}"><i class="fa-solid fa-bullhorn"></i> {{ $item['tag'] }}</span>
                            <span class="poster-title">{{ $item['title'] }}</span>
                        </span>
                    </button>
                @endif
            @endforeach
        </div>
    </div>
</section>
