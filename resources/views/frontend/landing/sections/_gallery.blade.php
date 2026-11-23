@php
    $live = $gallery->filter(fn ($g) => !empty($g->image_url))->values();
    $items = $live->isNotEmpty()
        ? $live->map(fn ($g) => ['src' => $g->image_url, 'webp' => null, 'title' => $g->title ?: 'Campus life'])
        : collect($mc['gallery_fallback'])->map(function ($g) {
            $base = 'frontend/mcc/img/' . $g['image'];
            $jpg = file_exists(public_path($base . '.jpg')) ? $base . '.jpg' : $base . '.jpeg';
            return ['src' => asset($jpg), 'webp' => asset($base . '.webp'), 'title' => $g['title']];
        });
    $rowA = $items->values();
    $rowB = $items->reverse()->values();
@endphp
<section class="gallery section-pad" id="gallery" aria-labelledby="gallery-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Life at MCC</span>
                <h2 class="sec-title" id="gallery-title">Moments that make <em>memories</em></h2>
            </div>
            <a href="{{ route('frontend.gallery') }}" class="btn btn-outline">Full gallery <i class="fa-solid fa-arrow-right"></i></a>
        </header>
    </div>
    <div class="gallery-rows" data-lightbox-group>
        @foreach ([$rowA, $rowB] as $r => $row)
            <div class="gallery-row {{ $r ? 'gallery-row--reverse' : '' }}">
                <div class="gallery-track">
                    @for ($copy = 0; $copy < 2; $copy++)
                        @foreach ($row as $item)
                            <button type="button" class="gallery-item" data-lightbox="{{ $item['src'] }}" data-caption="{{ $item['title'] }}" @if($copy) tabindex="-1" aria-hidden="true" @endif>
                                <picture>
                                    @if ($item['webp'])<source srcset="{{ $item['webp'] }}" type="image/webp">@endif
                                    <img src="{{ $item['src'] }}" alt="{{ $copy ? '' : $item['title'] }}" loading="lazy" decoding="async" width="480" height="340">
                                </picture>
                                <span class="gallery-cap">{{ $item['title'] }}</span>
                            </button>
                        @endforeach
                    @endfor
                </div>
            </div>
        @endforeach
    </div>

    <div class="lightbox" data-lightbox-modal role="dialog" aria-modal="true" aria-label="Photo viewer" hidden>
        <button type="button" class="lightbox-close" data-lightbox-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <button type="button" class="lightbox-nav lightbox-prev" data-lightbox-prev aria-label="Previous photo"><i class="fa-solid fa-chevron-left"></i></button>
        <figure><img alt="" data-lightbox-img><figcaption data-lightbox-cap></figcaption></figure>
        <button type="button" class="lightbox-nav lightbox-next" data-lightbox-next aria-label="Next photo"><i class="fa-solid fa-chevron-right"></i></button>
    </div>
</section>
