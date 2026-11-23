<section class="why section-pad" id="why" aria-labelledby="why-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">Why families choose MCC</span>
            <h2 class="sec-title" id="why-title">Built for how children <em>actually</em> learn</h2>
        </header>
        <div class="bento" data-stagger>
            @foreach ($mc['why'] as $w)
                <article class="bento-card {{ !empty($w['wide']) ? 'bento-card--wide' : '' }}" data-reveal="scale" data-spotlight>
                    <span class="bento-icon"><i class="fa-solid {{ $w['icon'] }}"></i></span>
                    <h3>{{ $w['title'] }}</h3>
                    <p>{{ $w['desc'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
