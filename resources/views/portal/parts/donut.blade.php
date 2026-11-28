{{-- Ring chart with its legend. $b: segments[{label,value,tone,note}], center, centerLabel --}}
@php
    $colors = ['crest' => '#c62828', 'gold' => '#d4af37', 'ok' => '#3a9a4a', 'info' => '#3d6fd1', 'neutral' => '#c3c7cd'];
    $segments = $b['segments'];
    $sum = array_sum(array_column($segments, 'value'));
    $r = 52; $c = 2 * M_PI * $r; $offset = 0; $gap = count($segments) > 1 ? 3 : 0;
@endphp
@if (!$sum)
    <p class="mcc-empty">{{ $b['empty'] ?? 'No data yet.' }}</p>
@else
    <div class="mcc-donut">
        <svg viewBox="0 0 140 140" role="img" aria-label="{{ $b['title'] }}: {{ collect($segments)->map(fn ($s) => $s['label'] . ' ' . $s['value'])->implode(', ') }}">
            <circle cx="70" cy="70" r="{{ $r }}" class="ring"/>
            @foreach ($segments as $s)
                @php $len = $s['value'] / $sum * $c; @endphp
                <circle cx="70" cy="70" r="{{ $r }}" fill="none" stroke="{{ $colors[$s['tone']] ?? $colors['neutral'] }}" stroke-width="16" stroke-linecap="butt"
                    stroke-dasharray="{{ max(0.1, $len - $gap) }} {{ $c }}" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 70 70)"><title>{{ $s['label'] }}: {{ $s['value'] }}</title></circle>
                @php $offset += $len; @endphp
            @endforeach
            <text x="70" y="68" text-anchor="middle" class="center">{{ $b['center'] }}</text>
            <text x="70" y="86" text-anchor="middle" class="center-label">{{ $b['centerLabel'] }}</text>
        </svg>
        <ul>
            @foreach ($segments as $s)
                <li><i style="background: {{ $colors[$s['tone']] ?? $colors['neutral'] }}"></i><span>{{ $s['label'] }}</span><b>{{ $s['value'] }}</b>@if ($s['note'])<small>{{ $s['note'] }}</small>@endif</li>
            @endforeach
        </ul>
    </div>
@endif
