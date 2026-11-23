<section class="process section-pad" id="how-to-apply" aria-labelledby="process-title" data-process>
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">How to apply</span>
            <h2 class="sec-title" id="process-title">Apply in four steps, <em>right from the app</em></h2>
            <p class="sec-sub">Admission for School and College is done in the {{ $app['name'] }} app: apply in about ten minutes and follow every update in real time.</p>
        </header>
        <ol class="process-list">
            <span class="process-line" aria-hidden="true"><span data-process-line></span></span>
            @foreach ($mc['process'] as $i => $p)
                <li class="process-step" data-process-step>
                    <span class="process-n"><i class="fa-solid {{ $p['icon'] }}"></i><b>{{ $i + 1 }}</b></span>
                    <h3>{{ $p['title'] }}</h3>
                    <p>{{ $p['desc'] }}</p>
                </li>
            @endforeach
        </ol>
        <div class="process-cta" data-reveal>
            <a href="{{ $app['play_store'] }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg" data-magnetic><i class="fa-brands fa-google-play"></i> Download the app &amp; apply</a>
            <a href="{{ $contact['phone_href'] }}" class="btn btn-outline btn-lg"><i class="fa-solid fa-headset"></i> Need help? {{ $contact['phone'] }}</a>
        </div>
    </div>
</section>
