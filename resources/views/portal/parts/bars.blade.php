{{-- Column chart drawn on the server. $b: items[{label,value,note}], money? --}}
@php
    $items = array_values($b['items']);
    $n = count($items);
    $peak = max(0, ...array_map(fn ($i) => $i['value'], $items ?: [['value' => 0]]));
    $step = $peak <= 0 ? 1 : pow(10, floor(log10($peak)));
    $max = $peak <= 0 ? 4 : ceil($peak / $step * 1.1) * $step;
    // Counts stay whole numbers on every gridline.
    if (empty($b['money'])) { $max = max(4, (int) ceil($max / 4) * 4); }
    $short = function ($v) use ($b) {
        $p = !empty($b['money']) ? '৳' : '';
        return $v >= 100000 ? $p . round($v / 100000, 1) . 'L' : ($v >= 1000 ? $p . round($v / 1000, 1) . 'k' : $p . (int) $v);
    };
    $W = ($b['width'] ?? '') === 'narrow' ? 640 : 900; $H = 240; $L = 48; $R = 12; $T = 26; $B = 30;
    $slot = $n ? ($W - $L - $R) / $n : 0; $bw = min(38, $slot * .56);
    $total = array_sum(array_map(fn ($i) => $i['value'], $items));
    $lastIndex = $n - 1;
@endphp
@if (!$n || $total <= 0)
    <div class="mcc-chart-figure"><strong>{{ !empty($b['money']) ? '৳0' : '0' }}</strong><span>Nothing recorded in this period yet</span></div>
@else
    <div class="mcc-chart-figure"><strong>{{ !empty($b['money']) ? '৳' . number_format($total) : number_format($total) }}</strong><span>In total, {{ $items[0]['note'] ?? $items[0]['label'] }} to {{ $items[$lastIndex]['note'] ?? $items[$lastIndex]['label'] }}</span></div>
@endif
@if ($n)
    <svg class="mcc-chart" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="{{ $b['title'] }}: {{ collect($items)->map(fn ($i) => $i['label'] . ' ' . $i['value'])->implode(', ') }}">
        @foreach ([0, .25, .5, .75, 1] as $f)
            @php $gy = $T + (1 - $f) * ($H - $T - $B); @endphp
            <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $gy }}" y2="{{ $gy }}" class="mc-grid"/>
            <text x="{{ $L - 8 }}" y="{{ $gy + 4 }}" class="axis" text-anchor="end">{{ $short($max * $f) }}</text>
        @endforeach
        @foreach ($items as $k => $i)
            @php
                $h = $i['value'] > 0 ? max(4, $i['value'] / $max * ($H - $T - $B)) : 0;
                $cx = $L + $slot * $k + $slot / 2;
                $mark = $k === $lastIndex || ($i['value'] === $peak && $peak > 0);
            @endphp
            <rect x="{{ $cx - $bw / 2 }}" y="{{ $H - $B - ($H - $T - $B) }}" width="{{ $bw }}" height="{{ $H - $T - $B }}" rx="8" class="mc-track"/>
            @if ($h)
                <rect x="{{ $cx - $bw / 2 }}" y="{{ $H - $B - $h }}" width="{{ $bw }}" height="{{ $h }}" rx="8" class="{{ $k === $lastIndex ? 'mc-bar now' : 'mc-bar' }}"><title>{{ $i['note'] ?? $i['label'] }}: {{ !empty($b['money']) ? '৳' . number_format($i['value']) : number_format($i['value']) }}</title></rect>
            @endif
            @if ($mark && $i['value'] > 0)
                <text x="{{ $cx }}" y="{{ $H - $B - $h - 8 }}" class="value" text-anchor="middle">{{ $short($i['value']) }}</text>
            @endif
            <text x="{{ $cx }}" y="{{ $H - 8 }}" class="axis" text-anchor="middle">{{ $i['label'] }}</text>
        @endforeach
    </svg>
@endif
