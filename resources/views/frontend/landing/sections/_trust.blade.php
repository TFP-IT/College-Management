<section class="trust" id="trust" aria-label="MCC at a glance">
    <div class="container">
        <ul class="trust-grid" data-stagger>
            @foreach ($mc['stats'] as $s)
                <li class="trust-item" data-reveal>
                    <span class="trust-icon"><i class="fa-solid {{ $s['icon'] }}"></i></span>
                    <span class="trust-value">
                        <span data-count="{{ $s['value'] }}" @if(!empty($s['plain'])) data-plain @endif>{{ !empty($s['plain']) ? $s['value'] : number_format($s['value']) }}</span>{{ $s['suffix'] }}
                    </span>
                    <span class="trust-label">{{ $s['label'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>
