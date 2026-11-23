@php
    $g = $mc['gallery'];
    $cats = $g['categories'];
    // Newest uploads from the CMS come first; the curated campus set follows.
    $items = $gallery->filter(fn ($row) => !empty($row->image_url))->map(fn ($row) => [
        'src' => $row->image_url,
        'title' => $row->title ?: 'Campus life',
        'cat' => array_key_exists(\Illuminate\Support\Str::slug((string) $row->category), $cats) ? \Illuminate\Support\Str::slug((string) $row->category) : 'campus',
        'size' => null,
    ])->values()->concat($g['items']);
    $initial = $g['initial'];
@endphp
<section class="gallery section-pad" id="gallery" aria-labelledby="gallery-title" data-gallery data-initial="{{ $initial }}">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Life at MCC</span>
                <h2 class="sec-title" id="gallery-title">Moments that make <em>memories</em></h2>
            </div>
            <p class="sec-sub">Classrooms, sports, festivals and friendships. A look at everyday life on our campus.</p>
        </header>

        <div class="gal-filters" role="toolbar" aria-label="Filter photos" data-reveal>
            <button type="button" class="is-active" aria-pressed="true" data-filter="all">All <span>{{ $items->count() }}</span></button>
            @foreach ($cats as $key => $label)
                @php $n = $items->where('cat', $key)->count(); @endphp
                @if ($n)
                    <button type="button" aria-pressed="false" data-filter="{{ $key }}">{{ $label }} <span>{{ $n }}</span></button>
                @endif
            @endforeach
        </div>

        <div class="gal-grid" data-gal-grid data-lightbox-scope="gallery" data-reveal>
            @foreach ($items as $i => $item)
                <button type="button"
                        class="gal-item gal-item--{{ $item['size'] ?? 'std' }}"
                        data-cat="{{ $item['cat'] }}"
                        data-caption="{{ $item['title'] }}"
                        data-lightbox="{{ isset($item['src']) ? $item['src'] : asset('frontend/mcc/img/' . $item['image'] . '.webp') }}"
                        @if ($i >= $initial) hidden @endif>
                    @isset($item['src'])
                        <img src="{{ $item['src'] }}" alt="{{ $item['title'] }}" loading="lazy" decoding="async">
                    @else
                        @include('frontend.landing.partials._img', ['name' => $item['image'], 'alt' => $item['title'], 'sizes' => ($item['size'] ?? '') === 'wide' ? '(max-width: 768px) 100vw, 50vw' : '(max-width: 768px) 50vw, 25vw'])
                    @endisset
                    <span class="gal-cap"><small>{{ $cats[$item['cat']] ?? '' }}</small>{{ $item['title'] }}</span>
                    <span class="gal-zoom" aria-hidden="true"><i class="fa-solid fa-expand"></i></span>
                </button>
            @endforeach
        </div>

        <div class="gal-foot">
            <button type="button" class="btn btn-outline" data-gal-more>
                <i class="fa-solid fa-images"></i> <span data-gal-more-label>Show more photos</span>
            </button>
            <a href="{{ route('frontend.gallery') }}" class="link-arrow">Visit the full gallery <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>

    <div class="lightbox" data-lightbox-modal role="dialog" aria-modal="true" aria-label="Photo viewer" hidden>
        <button type="button" class="lightbox-close" data-lightbox-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <button type="button" class="lightbox-nav lightbox-prev" data-lightbox-prev aria-label="Previous photo"><i class="fa-solid fa-chevron-left"></i></button>
        <figure><img alt="" data-lightbox-img><figcaption><span data-lightbox-cap></span> <small data-lightbox-count></small></figcaption></figure>
        <button type="button" class="lightbox-nav lightbox-next" data-lightbox-next aria-label="Next photo"><i class="fa-solid fa-chevron-right"></i></button>
    </div>
</section>
