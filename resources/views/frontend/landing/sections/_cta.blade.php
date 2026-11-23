<section class="final-cta" id="final-cta" aria-labelledby="cta-title">
    <div class="container">
        <div class="cta-card">
            <div class="cta-mesh" aria-hidden="true"><span></span><span></span><span></span></div>
            <div class="cta-copy">
                <span class="eyebrow eyebrow--light" data-reveal>Admission {{ $mc['session'] }}</span>
                <h2 class="cta-title" id="cta-title" data-split>Admission for the {{ $mc['session'] }} session <em>is now open.</em></h2>
                <p data-reveal>Seats are limited in every class. Download the {{ $app['name'] }} app to apply today, or visit our campus in Savar for a guided tour.</p>
                <div class="cta-actions" data-reveal>
                    <a href="{{ $app['play_store'] }}" target="_blank" rel="noopener" class="btn btn-gold btn-lg" data-magnetic><i class="fa-brands fa-google-play"></i> Apply in the app</a>
                    <a href="{{ $contact['phone_href'] }}" class="btn btn-outline-white btn-lg" data-magnetic><i class="fa-solid fa-phone"></i> Call admission office</a>
                </div>
                <ul class="cta-contact" data-reveal>
                    <li><a href="{{ $contact['phone_href'] }}"><i class="fa-solid fa-phone"></i> {{ $contact['phone'] }}</a></li>
                    <li><a href="mailto:{{ $contact['email'] }}"><i class="fa-solid fa-envelope"></i> {{ $contact['email'] }}</a></li>
                    <li><a href="{{ route('frontend.admission') }}"><i class="fa-solid fa-circle-info"></i> Admission info</a></li>
                </ul>
            </div>
            <div class="cta-map" data-reveal>
                <iframe src="{{ $contact['map_embed'] }}" title="Maestro Crown School & College on Google Maps" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                <a href="{{ $contact['map_link'] }}" target="_blank" rel="noopener" class="cta-map-label"><i class="fa-solid fa-location-dot"></i> {{ $contact['address'] }}</a>
            </div>
        </div>
    </div>
</section>
