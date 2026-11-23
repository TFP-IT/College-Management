@extends('frontend.layouts.app')

@section('title', 'Academic Calendar ' . config('academics.calendar.year') . ' | Maestro Crown College')
@section('meta_description', 'The ' . config('academics.calendar.year') . ' academic calendar of Maestro Crown College, Savar: term dates, examinations, holidays, national days and campus events.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $cal = config('academics.calendar');
    $types = config('academics.event_types');
    $year = $cal['year'];
    $nowMonth = now()->year == $year ? now()->month : null;
    $isNow = fn ($p) => $nowMonth && $nowMonth >= $p['month'] && $nowMonth <= ($p['until'] ?? $p['month']);
    $events = collect($cal['periods'])->flatMap(fn ($p) => $p['events']);
    $typeCounts = $events->countBy('type');
    $current = collect($cal['periods'])->first($isNow);
    $next = $nowMonth ? collect($cal['periods'])->first(fn ($p) => $p['month'] > $nowMonth) : null;
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['Academics', route('frontend.programs')], ['Academic Calendar']],
    'eyebrow' => 'Academic calendar ' . $year,
    'title'   => 'Plan your year, <em>month by month</em>',
    'lead'    => 'Term dates, examinations, holidays and campus events at a glance, so students and families can plan ahead with confidence.',
    'chips'   => [$events->count() . ' key dates', ($typeCounts['exam'] ?? 0) . ' examinations', ($typeCounts['holiday'] ?? 0) . ' holiday periods'],
    'actions' => [
        ['label' => 'View calendar', 'href' => '#calendar', 'icon' => 'fa-arrow-down', 'style' => 'primary'],
        ['label' => 'Notice Board', 'href' => route('frontend.notice_board'), 'style' => 'outline'],
    ],
    'media'   => ['type' => 'collage', 'images' => ['campus/boishakh-1', 'campus/classroom-exam'], 'badge' => [$year, 'academic<br>year']],
])

@if ($current || $next)
<section class="stat-band-wrap" aria-label="This month">
    <div class="container">
        <div class="cal-now" data-reveal>
            @if ($current)
                <div class="cal-now-item">
                    <span class="cal-now-tag"><span class="cal-live"></span> This month · {{ $current['label'] }}</span>
                    <strong>{{ collect($current['events'])->pluck('title')->implode(' · ') }}</strong>
                </div>
            @endif
            @if ($next)
                <div class="cal-now-item">
                    <span class="cal-now-tag cal-now-tag--next">Up next · {{ $next['label'] }}</span>
                    <strong>{{ collect($next['events'])->pluck('title')->implode(' · ') }}</strong>
                </div>
            @endif
        </div>
    </div>
</section>
@endif

<section class="cal section-pad" id="calendar" aria-labelledby="cal-title" data-filterable>
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">{{ $year }}</span>
                <h2 class="sec-title" id="cal-title">The year <em>at a glance</em></h2>
            </div>
            <button type="button" class="btn btn-outline" onclick="window.print()"><i class="fa-solid fa-print"></i> Print calendar</button>
        </header>

        <div class="cal-tools" data-reveal>
            <div class="dir-filters" role="toolbar" aria-label="Filter events">
                <button type="button" class="is-active" aria-pressed="true" data-fbtn="all">All <span>{{ $events->count() }}</span></button>
                @foreach ($types as $key => $t)
                    @if ($typeCounts->get($key))
                        <button type="button" aria-pressed="false" data-fbtn="{{ $key }}"><i class="fa-solid {{ $t['icon'] }}"></i> {{ $t['label'] }} <span>{{ $typeCounts->get($key) }}</span></button>
                    @endif
                @endforeach
            </div>
        </div>

        <ol class="cal-list">
            @foreach ($cal['periods'] as $p)
                <li class="cal-month {{ $isNow($p) ? 'is-now' : '' }} {{ $nowMonth && ($p['until'] ?? $p['month']) < $nowMonth ? 'is-past' : '' }}" data-reveal data-fgroup>
                    <div class="cal-month-label">
                        <span class="cal-month-n">{{ str_pad($p['month'], 2, '0', STR_PAD_LEFT) }}</span>
                        <strong>{{ $p['label'] }}</strong>
                        @if ($isNow($p))<span class="cal-badge">Now</span>@endif
                    </div>
                    <ul class="cal-events">
                        @foreach ($p['events'] as $e)
                            <li class="cal-event cal-event--{{ $e['type'] }}" data-fitem data-type="{{ $e['type'] }}">
                                <span class="cal-event-icon"><i class="fa-solid {{ $types[$e['type']]['icon'] }}"></i></span>
                                <span class="cal-event-body">
                                    <strong>{{ $e['title'] }}</strong>
                                    <span>{{ $e['date'] }}</span>
                                </span>
                                <span class="cal-event-type">{{ \Illuminate\Support\Str::singular($types[$e['type']]['label']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ol>

        <div class="cal-foot" data-reveal>
            <i class="fa-solid fa-circle-info"></i>
            <p>{{ $cal['note'] }} <a href="{{ route('frontend.notice_board') }}">Go to the Notice Board</a>, or get instant updates in the <a href="{{ config('landing.app.play_store') }}" target="_blank" rel="noopener">{{ config('landing.app.name') }} app</a>.</p>
        </div>
    </div>
</section>

@include('frontend.partials.next-links', ['set' => 'academics'])
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
