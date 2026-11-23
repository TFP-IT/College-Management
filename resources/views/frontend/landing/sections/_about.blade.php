@php $about = $mc['about']; @endphp
<section class="about section-pad" id="about" aria-labelledby="about-title">
    <div class="container about-grid">
        <div class="about-collage" aria-hidden="true">
            <div class="about-img about-img--a" data-clip data-parallax="-8">
                @include('frontend.landing.partials._img', ['name' => $about['image_main'], 'alt' => '', 'sizes' => '(max-width: 1023px) 90vw, 40vw'])
            </div>
            <div class="about-img about-img--b" data-clip data-parallax="10">
                @include('frontend.landing.partials._img', ['name' => $about['image_small'], 'alt' => '', 'sizes' => '(max-width: 1023px) 60vw, 30vw'])
            </div>
            <div class="about-badge" data-reveal>
                <strong>{{ now()->year - 2014 }}+</strong>
                <span>years of<br>excellence</span>
            </div>
        </div>
        <div class="about-copy">
            <span class="eyebrow" data-reveal>{{ $about['eyebrow'] }}</span>
            <h2 class="sec-title" id="about-title" data-reveal>{{ \Illuminate\Support\Str::beforeLast($about['title'], ' ') }} <em>{{ \Illuminate\Support\Str::afterLast($about['title'], ' ') }}</em></h2>
            <p class="about-body" data-reveal>{{ $about['body'] }}</p>
            <figure class="about-quote" data-reveal>
                <blockquote>“{{ $about['quote'] }}”</blockquote>
                <figcaption>
                    @include('frontend.landing.partials._img', ['name' => 'chairman-smiling-face', 'alt' => $about['quote_by'], 'class' => 'about-quote-img'])
                    <span><strong>{{ $about['quote_by'] }}</strong>{{ $about['quote_role'] }}</span>
                </figcaption>
            </figure>
            <a href="{{ route('frontend.about') }}" class="link-arrow" data-reveal>More about MCC <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</section>
