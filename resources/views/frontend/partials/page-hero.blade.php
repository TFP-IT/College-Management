{{--
    Inner-page hero.
    @include('frontend.partials.page-hero', [
        'crumbs'  => [['About', route('frontend.about')], ['Teachers']],
        'eyebrow' => 'Our people',
        'title'   => 'Teachers who <em>know you by name</em>',   // trusted HTML (em only)
        'lead'    => '...',
        'chips'   => ['EIIN 136876', ...],
        'actions' => [['label' => '...', 'href' => '...', 'icon' => 'fa-...', 'style' => 'primary|outline']],
        'media'   => ['type' => 'collage', 'images' => ['campus/a', 'campus/b']] | ['type' => 'portrait', 'image' => 'campus/x', 'name' => '...', 'caption' => '...'] | ['type' => 'crest'],
    ])
--}}
<section class="ph" aria-labelledby="ph-title">
    <div class="ph-bg" aria-hidden="true"><span></span><span></span></div>
    <div class="container ph-grid {{ empty($media) ? 'ph-grid--solo' : '' }}">
        <div class="ph-copy">
            <nav class="ph-crumbs" aria-label="Breadcrumb" data-reveal>
                <a href="{{ route('landing') }}">Home</a>
                @foreach ($crumbs as $crumb)
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    @if (!empty($crumb[1]) && !$loop->last)
                        <a href="{{ $crumb[1] }}">{{ $crumb[0] }}</a>
                    @else
                        <span aria-current="page">{{ $crumb[0] }}</span>
                    @endif
                @endforeach
            </nav>
            <span class="eyebrow" data-reveal>{{ $eyebrow }}</span>
            <h1 class="ph-title" id="ph-title" data-split>{!! $title !!}</h1>
            @if (!empty($lead))<p class="ph-lead" data-reveal>{{ $lead }}</p>@endif
            @if (!empty($chips))
                <ul class="ph-chips" data-reveal>@foreach ($chips as $chip)<li>{{ $chip }}</li>@endforeach</ul>
            @endif
            @if (!empty($actions))
                <div class="ph-actions" data-reveal>
                    @foreach ($actions as $a)
                        <a href="{{ $a['href'] }}" class="btn btn-{{ $a['style'] ?? 'primary' }} btn-lg" @if(($a['style'] ?? 'primary') === 'primary') data-magnetic @endif @if(!empty($a['external'])) target="_blank" rel="noopener" @endif>
                            @if (!empty($a['icon']))<i class="{{ str_contains($a['icon'], 'fa-brands') ? $a['icon'] : 'fa-solid ' . $a['icon'] }}"></i>@endif {{ $a['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if (!empty($media))
            @if ($media['type'] === 'collage')
                <div class="ph-collage" aria-hidden="true">
                    <div class="ph-collage-a" data-clip>@include('frontend.landing.partials._img', ['name' => $media['images'][0], 'alt' => '', 'sizes' => '(max-width: 1023px) 80vw, 420px', 'eager' => true])</div>
                    <div class="ph-collage-b" data-clip>@include('frontend.landing.partials._img', ['name' => $media['images'][1], 'alt' => '', 'sizes' => '(max-width: 1023px) 50vw, 260px', 'eager' => true])</div>
                    @if (!empty($media['badge']))
                        <div class="ph-badge" data-reveal><strong>{{ $media['badge'][0] }}</strong><span>{!! $media['badge'][1] !!}</span></div>
                    @endif
                </div>
            @elseif ($media['type'] === 'portrait')
                <figure class="ph-portrait" data-clip>
                    @include('frontend.partials.portrait', ['name' => $media['name'], 'image' => $media['image'] ?? null, 'class' => 'portrait--hero', 'sizes' => '(max-width: 1023px) 80vw, 420px'])
                    @if (!empty($media['caption']))
                        <figcaption class="ph-portrait-card">
                            <img src="{{ asset('frontend/mcc/img/logo-256.webp') }}" alt="" width="44" height="44">
                            <span><strong>{{ $media['name'] }}</strong>{{ $media['caption'] }}</span>
                        </figcaption>
                    @endif
                </figure>
            @elseif ($media['type'] === 'crest')
                <div class="ph-crest" aria-hidden="true" data-reveal>
                    <span class="ph-crest-ring"></span>
                    <img src="{{ asset('frontend/mcc/img/logo-512.webp') }}" alt="" width="260" height="260">
                </div>
            @endif
        @endif
    </div>
</section>
