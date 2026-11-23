<section class="leaders section-pad" id="leadership" aria-labelledby="leaders-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">Leadership</span>
            <h2 class="sec-title" id="leaders-title">A word from our <em>leaders</em></h2>
        </header>
        <div class="leader-grid">
            @foreach ($mc['leaders'] as $i => $l)
                <figure class="leader-card" data-reveal="{{ $i % 2 ? 'right' : 'left' }}">
                    <i class="fa-solid fa-quote-left leader-mark" aria-hidden="true"></i>
                    <blockquote>{{ $l['quote'] }}</blockquote>
                    <figcaption>
                        <span class="leader-avatar">
                            @if ($l['image'])
                                @include('frontend.landing.partials._img', ['name' => $l['image'], 'alt' => $l['name']])
                            @else
                                <span class="leader-initials">{{ collect(explode(' ', str_replace(['Dr. ', 'Md. '], '', $l['name'])))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</span>
                            @endif
                        </span>
                        <span><strong>{{ $l['name'] }}</strong>{{ $l['role'] }}, Maestro Crown College</span>
                        <a href="{{ route($l['route']) }}" class="leader-link" aria-label="Read the full message from {{ $l['name'] }}"><i class="fa-solid fa-arrow-right"></i></a>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
