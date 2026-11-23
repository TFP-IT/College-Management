<section class="journey" id="journey" aria-labelledby="journey-title" data-journey>
    <div class="journey-sticky">
        <div class="container">
            <header class="sec-head" data-reveal>
                <span class="eyebrow eyebrow--light">One campus, the whole journey</span>
                <h2 class="sec-title sec-title--light" id="journey-title">From first steps to <em>first degree.</em></h2>
                <p class="sec-sub sec-sub--light">Families stay with MCC from Play Group to HSC because every stage is designed to lead naturally into the next.</p>
            </header>
        </div>
        <div class="journey-viewport">
            <div class="journey-track" data-journey-track>
                <div class="journey-line" aria-hidden="true"><span data-journey-line></span></div>
                @foreach ($mc['journey'] as $j)
                    <article class="journey-step" data-journey-step>
                        <span class="journey-dot" aria-hidden="true"><i class="fa-solid {{ $j['icon'] }}"></i></span>
                        <span class="journey-n">{{ $j['step'] }}</span>
                        <h3>{{ $j['title'] }}</h3>
                        <p class="journey-classes">{{ $j['classes'] }}</p>
                        <p>{{ $j['desc'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
