<section class="facilities section-pad" id="campus" aria-labelledby="campus-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Campus &amp; facilities</span>
                <h2 class="sec-title" id="campus-title">A campus that keeps <em>curiosity</em> busy</h2>
            </div>
            <a href="{{ route('frontend.gallery') }}" class="link-arrow">Explore the gallery <i class="fa-solid fa-arrow-right"></i></a>
        </header>
        <div class="fac-grid">
            @foreach ($mc['facilities'] as $f)
                @if (!empty($f['image']))
                    <figure class="fac-tile fac-tile--{{ $f['size'] ?? 'std' }}" data-clip>
                        @include('frontend.landing.partials._img', ['name' => $f['image'], 'alt' => $f['title']])
                        <figcaption>
                            <h3>{{ $f['title'] }}</h3>
                            <p>{{ $f['desc'] }}</p>
                        </figcaption>
                    </figure>
                @else
                    <article class="fac-tile fac-tile--icon" data-reveal="scale">
                        <span class="fac-icon"><i class="fa-solid {{ $f['icon'] }}"></i></span>
                        <h3>{{ $f['title'] }}</h3>
                        <p>{{ $f['desc'] }}</p>
                    </article>
                @endif
            @endforeach
        </div>
    </div>
</section>
