@php $ach = $mc['achievements']; @endphp
<section class="achieve" id="results" aria-labelledby="achieve-title">
    <div class="achieve-bg" aria-hidden="true"></div>
    <div class="container achieve-grid">
        <div class="achieve-copy">
            <span class="eyebrow eyebrow--light" data-reveal>{{ $ach['eyebrow'] }}</span>
            <h2 class="sec-title sec-title--light" id="achieve-title" data-reveal>Excellence, <em>year after year.</em></h2>
            <div class="achieve-stats" data-stagger>
                @foreach ($ach['items'] as $item)
                    <div class="achieve-stat {{ !empty($item['ring']) ? 'achieve-stat--ring' : '' }}" data-reveal>
                        @if (!empty($item['ring']))
                            <span class="achieve-ring-wrap">
                                <svg class="achieve-ring" viewBox="0 0 120 120" aria-hidden="true">
                                    <circle cx="60" cy="60" r="52" />
                                    <circle cx="60" cy="60" r="52" data-ring="{{ $item['value'] }}" />
                                </svg>
                                <strong><span data-count="{{ $item['value'] }}">{{ $item['value'] }}</span>{{ $item['suffix'] ?? '' }}</strong>
                            </span>
                        @else
                            <strong>{{ $item['prefix'] ?? '' }}<span data-count="{{ $item['value'] }}">{{ $item['value'] }}</span>{{ $item['suffix'] ?? '' }}</strong>
                        @endif
                        <span>{{ $item['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <p class="achieve-note" data-reveal><i class="fa-solid fa-trophy"></i> {{ $ach['note'] }}</p>
        </div>
        <div class="achieve-photo" data-clip>
            @include('frontend.landing.partials._img', ['name' => 'award', 'alt' => 'MCC students and faculty at an award ceremony'])
            <span class="achieve-shine" aria-hidden="true"></span>
        </div>
    </div>
</section>
