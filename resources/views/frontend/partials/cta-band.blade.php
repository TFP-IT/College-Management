{{-- Admission call-to-action band shared by inner pages. Admission happens in the MCC Hello Future app. --}}
@php $mcApp = config('landing.app'); $mcContact = config('landing.contact'); @endphp
<section class="cta-band" aria-labelledby="cta-band-title">
    <div class="container">
        <div class="cta-band-card" data-reveal>
            <div class="cta-band-glow" aria-hidden="true"><span></span><span></span></div>
            <div class="cta-band-copy">
                <span class="eyebrow eyebrow--light">Admission {{ config('landing.session') }}</span>
                <h2 id="cta-band-title">{{ $title ?? 'Become part of the MCC family.' }}</h2>
                <p>{{ $text ?? 'School (Play Group–Class 10) and College (HSC) admission is open. Apply and track your application in the ' . $mcApp['name'] . ' app.' }}</p>
            </div>
            <div class="cta-band-actions">
                @include('frontend.landing.partials._stores')
                <a href="{{ $mcContact['phone_href'] }}" class="cta-band-phone"><i class="fa-solid fa-headset"></i> {{ $mcContact['phone'] }}</a>
            </div>
        </div>
    </div>
</section>
