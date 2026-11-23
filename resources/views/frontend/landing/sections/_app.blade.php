{{-- MCC Hello Future app promotion. Phone screens are HTML mock-ups of the real app UI. --}}
<section class="app-promo section-pad" id="app" aria-labelledby="app-title">
    <div class="container">
        <div class="app-stage">
            <div class="app-stage-bg" aria-hidden="true"><span></span><span></span></div>

            <div class="app-copy">
                <span class="eyebrow eyebrow--light" data-reveal><i class="fa-solid fa-mobile-screen-button"></i> {{ $app['eyebrow'] }}</span>
                <h2 class="sec-title sec-title--light" id="app-title" data-reveal>Your whole campus, <em>in one pocket.</em></h2>
                <p class="app-body" data-reveal>{{ $app['body'] }}</p>

                <ul class="app-portals" data-stagger>
                    @foreach ($app['portals'] as $p)
                        <li data-reveal>
                            <span class="app-portal-icon"><i class="fa-solid {{ $p['icon'] }}"></i></span>
                            <span><strong>{{ $p['title'] }}</strong>{{ $p['desc'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="app-tracking" data-reveal>
                    <span class="app-live"><span></span>Live</span>
                    <div><strong>{{ $app['tracking']['title'] }}</strong><span>{{ $app['tracking']['desc'] }}</span></div>
                </div>

                <div class="app-cta" data-reveal>
                    @include('frontend.landing.partials._stores')
                    <span class="app-note"><i class="fa-solid fa-shield-halved"></i> {{ $app['rating_note'] }}</span>
                </div>
            </div>

            <div class="app-devices" aria-hidden="true">
                @include('frontend.landing.partials._phone', ['variant' => 'login', 'class' => 'phone--front'])
                @include('frontend.landing.partials._phone', ['variant' => 'dash', 'class' => 'phone--back'])

                {{-- Real-time toasts --}}
                <div class="toast toast--1" data-toast><i class="fa-solid fa-circle-check"></i><span><b>Attendance marked</b>Present · 8:02 AM</span></div>
                <div class="toast toast--2" data-toast><i class="fa-solid fa-bus"></i><span><b>School bus</b>5 min away from your stop</span></div>
                <div class="toast toast--3" data-toast><i class="fa-solid fa-file-circle-check"></i><span><b>Application approved</b>MCC-2027-0142</span></div>
            </div>
        </div>
    </div>
</section>
