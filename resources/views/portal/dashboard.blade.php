{{-- Home page shared by every role of the school portal. Figures come from App\Services\PortalDashboard. --}}
@php
    $now = now()->timezone('Asia/Dhaka');
    $greeting = $now->hour < 12 ? 'Good morning' : ($now->hour < 17 ? 'Good afternoon' : 'Good evening');
    $schoolName = auth()->user()->school_id ? \Illuminate\Support\Facades\DB::table('schools')->where('id', auth()->user()->school_id)->value('title') : get_settings('system_name');
    $tones = ['crest', 'gold', 'ok', 'info', 'neutral'];
@endphp
<div class="mcc-dash">
    <section class="mcc-hero">
        <div class="mcc-hero-main">
            <img src="{{ asset('frontend/mcc/img/logo-256.png') }}" alt="" class="mcc-hero-crest">
            <div>
                <p class="mcc-hero-kicker">{{ $greeting }}</p>
                <h1>{{ auth()->user()->name }}</h1>
                <p class="mcc-hero-meta"><span class="mcc-chip">{{ $dash['role'] }}</span>{{ $schoolName }}</p>
            </div>
        </div>
        <div class="mcc-hero-side">
            <div class="mcc-clock" aria-hidden="true"><strong data-clock>{{ $now->format('h:i') }}</strong><span data-ampm>{{ $now->format('A') }}</span></div>
            <p class="mcc-hero-date">{{ $now->format('l, j F Y') }}</p>
        </div>
        @if ($dash['actions'])
            <nav class="mcc-actions" aria-label="Quick actions">
                @foreach ($dash['actions'] as $action)
                    <a class="mcc-btn {{ $loop->first ? 'primary' : '' }}" href="{{ $action['url'] }}">@include('portal.icon', ['name' => $action['icon']]){{ $action['label'] }}</a>
                @endforeach
            </nav>
        @endif
    </section>

    <div class="mcc-kpis">
        @foreach ($dash['kpis'] as $kpi)
            <{{ $kpi['url'] ? 'a' : 'div' }} class="mcc-kpi" @if ($kpi['url']) href="{{ $kpi['url'] }}" @endif>
                <span class="mcc-tile {{ $kpi['tone'] }}">@include('portal.icon', ['name' => $kpi['icon']])</span>
                <span class="mcc-kpi-body">
                    <span class="label">{{ $kpi['label'] }}</span>
                    <strong>{{ $kpi['value'] }}</strong>
                    <span class="note">
                        @if ($kpi['delta'])<em class="{{ $kpi['delta']['up'] ? 'up' : 'down' }}">@include('portal.icon', ['name' => $kpi['delta']['up'] ? 'trend-up' : 'trend-down']){{ $kpi['delta']['value'] }}</em> {{ $kpi['delta']['note'] }}@else{{ $kpi['note'] }}@endif
                    </span>
                </span>
            </{{ $kpi['url'] ? 'a' : 'div' }}>
        @endforeach
    </div>

    @if ($dash['modules'])
        <section class="mcc-panel mcc-modules" aria-label="Today across the college">
            <div class="mcc-panel-head"><div><h2>Today across the college</h2></div><span class="mcc-date">{{ $now->format('D, j M Y') }}</span></div>
            <div class="mcc-module-grid">
                @foreach ($dash['modules'] as $m)
                    <{{ $m['url'] ? 'a' : 'div' }} class="mcc-module" @if ($m['url']) href="{{ $m['url'] }}" @endif>
                        <span class="mcc-tile small {{ $m['tone'] }}">@include('portal.icon', ['name' => $m['icon']])</span>
                        <span class="name">{{ $m['label'] }}</span>
                        <span class="figure"><b>{{ $m['value'] }}</b> {{ $m['unit'] }}</span>
                        <span class="mcc-badge {{ $m['tone'] }}">{{ $m['chip'] }}</span>
                    </{{ $m['url'] ? 'a' : 'div' }}>
                @endforeach
            </div>
        </section>
    @endif

    @foreach ($dash['rows'] as $row)
        <div class="mcc-row">
            @foreach ($row as $b)
                @if ($b['type'] === 'periods')
                    <div class="mcc-col wide">@include('portal.today_classes')</div>
                    @continue
                @endif
                <section class="mcc-panel mcc-col {{ $b['width'] }}">
                    <div class="mcc-panel-head">
                        <div>
                            <h2>{{ $b['title'] }}</h2>
                            @if (!empty($b['subtitle']))<p>{{ $b['subtitle'] }}</p>@endif
                        </div>
                        @if (!empty($b['url']))<a href="{{ $b['url'] }}" class="mcc-link">View all @include('portal.icon', ['name' => 'chevron'])</a>@endif
                    </div>

                    @switch($b['type'])
                        @case('area') @include('portal.parts.area') @break
                        @case('bars') @include('portal.parts.bars') @break
                        @case('donut') @include('portal.parts.donut') @break
                        @case('table') @include('portal.parts.table') @break
                        @case('meters')
                            @forelse ($b['items'] as $item)
                                <div class="mcc-meter">
                                    <span>{{ $item['label'] }}</span><em>{{ $item['value'] }}</em>
                                    <div class="mcc-bar {{ $item['tone'] ?? 'ok' }}" role="img" aria-label="{{ $item['label'] }}: {{ $item['percent'] }} percent"><i style="width: {{ $item['percent'] }}%"></i></div>
                                </div>
                            @empty
                                <p class="mcc-empty">{{ $b['empty'] }}</p>
                            @endforelse
                            @break
                        @case('list')
                            @forelse ($b['items'] as $item)
                                <div class="mcc-item">
                                    @if ($item['day'])<time><strong>{{ $item['day'] }}</strong><span>{{ $item['month'] }}</span></time>@endif
                                    <div><b>{{ $item['title'] }}</b><span>{{ $item['note'] }}</span></div>
                                </div>
                            @empty
                                <p class="mcc-empty">{{ $b['empty'] }}</p>
                            @endforelse
                            @break
                        @case('children')
                            @if (empty($b['items']))
                                <p class="mcc-empty">{{ $b['empty'] }}</p>
                            @else
                                <div class="mcc-children">
                                    @foreach ($b['items'] as $child)
                                        @php $rate = $child['attendance']; @endphp
                                        <article class="mcc-child">
                                            <header>
                                                <span class="mcc-avatar lg">{{ collect(preg_split('/\s+/', trim($child['name'])))->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') }}</span>
                                                <div><b>{{ $child['name'] }}</b><span>{{ $child['class'] }}@if ($child['code']), {{ $child['code'] }}@endif</span></div>
                                            </header>
                                            <div class="mcc-child-figures">
                                                <div>
                                                    <svg viewBox="0 0 44 44" class="mcc-ring" aria-hidden="true"><circle cx="22" cy="22" r="18" class="ring"/><circle cx="22" cy="22" r="18" class="fill" stroke-dasharray="{{ ($rate ?? 0) / 100 * 113.1 }} 113.1" transform="rotate(-90 22 22)"/></svg>
                                                    <span><b>{{ $rate === null ? '-' : $rate . '%' }}</b>Attendance</span>
                                                </div>
                                                <div><span><b>{{ $child['gpa'] ?? '-' }}</b>{{ $child['exam'] ? 'GPA, ' . $child['exam'] : 'No result yet' }}</span></div>
                                                <div><span><b class="{{ $child['due'] ? 'due' : 'ok' }}">{{ $child['dueText'] }}</b>Fees due</span></div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                            @break
                    @endswitch
                </section>
            @endforeach
        </div>
    @endforeach
</div>
<script>
    (function () {
        var clock = document.querySelector('[data-clock]'), ampm = document.querySelector('[data-ampm]');
        if (!clock) return;
        function tick() {
            var d = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Dhaka' })), h = d.getHours();
            clock.textContent = String(h % 12 || 12).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
            ampm.textContent = h < 12 ? 'AM' : 'PM';
        }
        tick(); setInterval(tick, 15000);
    })();
</script>
