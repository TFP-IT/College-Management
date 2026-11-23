<section class="faq section-pad" id="faq" aria-labelledby="faq-title">
    <div class="container faq-grid">
        <header class="sec-head" data-reveal>
            <span class="eyebrow">FAQ</span>
            <h2 class="sec-title" id="faq-title">Questions parents <em>often ask</em></h2>
            <p class="sec-sub">Can’t find your answer? Our admission team is happy to help.</p>
            <a href="{{ $contact['phone_href'] }}" class="btn btn-outline"><i class="fa-solid fa-phone"></i> {{ $contact['phone'] }}</a>
        </header>
        <div class="faq-list" data-stagger>
            @foreach ($mc['faq'] as $i => $f)
                <details class="faq-item" data-faq data-reveal @if($i === 0) open @endif>
                    <summary><span>{{ $f['q'] }}</span><i class="faq-icon" aria-hidden="true"></i></summary>
                    <div class="faq-a"><p>{{ $f['a'] }}</p></div>
                </details>
            @endforeach
        </div>
    </div>
</section>
