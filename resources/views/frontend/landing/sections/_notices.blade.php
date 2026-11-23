<section class="notices section-pad" id="notices" aria-labelledby="notices-title">
    <div class="container notices-grid">
        <div>
            <header class="sec-head" data-reveal>
                <span class="eyebrow">Latest</span>
                <h2 class="sec-title" id="notices-title">Notices &amp; <em>updates</em></h2>
            </header>
            <ul class="notice-list" data-stagger>
                @forelse ($notices as $n)
                    @php $date = $n->publish_on ?? $n->created_at; @endphp
                    <li data-reveal>
                        <a href="{{ route('frontend.notice_board') }}" class="notice-item">
                            <span class="notice-date"><b>{{ $date?->format('d') }}</b>{{ $date?->format('M') }}</span>
                            <span class="notice-text">
                                @if ($date && $date->gt(now()->subDays(7)))<span class="notice-new">New</span>@endif
                                <strong>{{ $n->title }}</strong>
                                <span>{{ \Illuminate\Support\Str::limit(strip_tags((string) $n->body), 110) }}</span>
                            </span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </li>
                @empty
                    <li data-reveal>
                        <a href="{{ $app['play_store'] }}" target="_blank" rel="noopener" class="notice-item">
                            <span class="notice-date"><b>{{ now()->format('d') }}</b>{{ now()->format('M') }}</span>
                            <span class="notice-text"><span class="notice-new">New</span><strong>Admission {{ $mc['session'] }} is now open</strong><span>Apply for School (Play Group–Class 10) or College (HSC) in the {{ $app['name'] }} app.</span></span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </li>
                    <li data-reveal>
                        <a href="{{ route('frontend.academic_calendar') }}" class="notice-item">
                            <span class="notice-date"><i class="fa-regular fa-calendar"></i></span>
                            <span class="notice-text"><strong>Academic calendar</strong><span>Term dates, exams and holidays for the year.</span></span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </li>
                @endforelse
            </ul>
            <a href="{{ route('frontend.notice_board') }}" class="link-arrow" data-reveal>All notices <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <aside class="quick" data-reveal aria-label="Quick links">
            <h3>Quick links</h3>
            <a href="{{ $app['play_store'] }}" target="_blank" rel="noopener"><i class="fa-solid fa-location-crosshairs"></i><span><strong>Track application</strong>Real-time status in the app</span></a>
            <a href="{{ route('login') }}"><i class="fa-solid fa-arrow-right-to-bracket"></i><span><strong>Portal login</strong>Students · Guardians · Teachers</span></a>
            <a href="{{ route('frontend.academic_calendar') }}"><i class="fa-regular fa-calendar"></i><span><strong>Academic calendar</strong>Terms, exams &amp; holidays</span></a>
            <a href="{{ route('frontend.programs') }}"><i class="fa-solid fa-layer-group"></i><span><strong>Programs</strong>Classes and groups</span></a>
            <a href="{{ $app['play_store'] }}" target="_blank" rel="noopener" class="quick-app"><i class="fa-brands fa-google-play"></i><span><strong>Get the MCC app</strong>Free on Google Play</span></a>
        </aside>
    </div>
</section>
