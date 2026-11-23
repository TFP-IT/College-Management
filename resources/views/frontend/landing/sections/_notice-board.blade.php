{{-- Official posters from the admission office (admission, results, upcoming news). Click to view full size. --}}
<section class="board section-pad" id="notice-board" aria-labelledby="board-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Notice board</span>
                <h2 class="sec-title" id="board-title">Official <em>announcements</em></h2>
            </div>
            <p class="sec-sub">Admission circulars, results and campus news, straight from the MCC office. Tap a poster to read it in full.</p>
        </header>
        <div class="board-track" data-lightbox-scope="board" data-stagger>
            @foreach ($mc['posters'] as $poster)
                @php $base = 'frontend/mcc/img/' . $poster['image']; @endphp
                <button type="button" class="poster" data-reveal data-lightbox="{{ asset($base . '.webp') }}" data-caption="{{ $poster['title'] }}">
                    <span class="poster-frame">
                        @include('frontend.landing.partials._img', ['name' => $poster['image'], 'alt' => $poster['title'], 'sizes' => '(max-width: 768px) 70vw, 280px'])
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
</section>
