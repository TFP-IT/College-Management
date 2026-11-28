{{-- Smooth area chart drawn on the server. $b: items[{label,value,note}], max, suffix --}}
@php
    $items = array_values($b['items']);
    $W = ($b['width'] ?? '') === 'narrow' ? 640 : 900; $H = 240; $L = 40; $R = 16; $T = 18; $B = 30;
    $max = $b['max'] ?? max(1, ...array_map(fn ($i) => $i['value'], $items));
    $n = count($items);
    $x = fn ($i) => $n > 1 ? $L + $i * ($W - $L - $R) / ($n - 1) : ($L + $W - $R) / 2;
    $y = fn ($v) => $T + (1 - min($v, $max) / $max) * ($H - $T - $B);
    $pts = array_map(fn ($i, $k) => [$x($k), $y($i['value'])], $items, array_keys($items));
    $line = '';
    foreach ($pts as $k => $p) {
        if ($k === 0) { $line = sprintf('M%.1f %.1f', $p[0], $p[1]); continue; }
        $p0 = $pts[$k - 2] ?? $pts[$k - 1]; $p1 = $pts[$k - 1]; $p3 = $pts[$k + 1] ?? $p;
        $c1 = [$p1[0] + ($p[0] - $p0[0]) / 6, $p1[1] + ($p[1] - $p0[1]) / 6];
        $c2 = [$p[0] - ($p3[0] - $p1[0]) / 6, $p[1] - ($p3[1] - $p1[1]) / 6];
        $clamp = fn ($v) => max($T, min($H - $B, $v));
        $line .= sprintf(' C%.1f %.1f %.1f %.1f %.1f %.1f', $c1[0], $clamp($c1[1]), $c2[0], $clamp($c2[1]), $p[0], $p[1]);
    }
    $area = $n ? $line . sprintf(' L%.1f %.1f L%.1f %.1f Z', end($pts)[0], $H - $B, $pts[0][0], $H - $B) : '';
    $id = 'g' . substr(md5($b['title'] . $n), 0, 8);
    $every = max(1, (int) ceil($n / 7));
    $last = $n ? end($items) : null;
@endphp
@if (!$n)
    <p class="mcc-empty">{{ $b['empty'] ?? 'No data yet.' }}</p>
@else
    @if ($last)
        <div class="mcc-chart-figure"><strong>{{ $last['value'] }}{{ $b['suffix'] ?? '' }}</strong><span>{{ $last['label'] }}@if (!empty($last['note'])), {{ $last['note'] }}@endif</span></div>
    @endif
    <svg class="mcc-chart" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="{{ $b['title'] }}: {{ collect($items)->map(fn ($i) => $i['label'] . ' ' . $i['value'] . ($b['suffix'] ?? ''))->implode(', ') }}">
        <defs><linearGradient id="{{ $id }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c62828" stop-opacity=".22"/><stop offset="1" stop-color="#c62828" stop-opacity="0"/></linearGradient></defs>
        @foreach ([0, .25, .5, .75, 1] as $f)
            @php $gy = $T + (1 - $f) * ($H - $T - $B); @endphp
            <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $gy }}" y2="{{ $gy }}" class="mc-grid"/>
            <text x="{{ $L - 8 }}" y="{{ $gy + 4 }}" class="axis" text-anchor="end">{{ round($max * $f) }}{{ ($b['suffix'] ?? '') === '%' ? '%' : '' }}</text>
        @endforeach
        <path d="{{ $area }}" fill="url(#{{ $id }})"/>
        <path d="{{ $line }}" class="line"/>
        @foreach ($pts as $k => $p)
            <g class="pt"><circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="{{ $k === $n - 1 ? 5 : 3.5 }}" class="{{ $k === $n - 1 ? 'dot now' : 'dot' }}"/><circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="14" class="hit"><title>{{ $items[$k]['label'] }}: {{ $items[$k]['value'] }}{{ $b['suffix'] ?? '' }}{{ !empty($items[$k]['note']) ? ' (' . $items[$k]['note'] . ')' : '' }}</title></circle></g>
            @if ($k % $every === 0 || $k === $n - 1)
                <text x="{{ $p[0] }}" y="{{ $H - 8 }}" class="axis" text-anchor="middle">{{ $items[$k]['label'] }}</text>
            @endif
        @endforeach
    </svg>
@endif
