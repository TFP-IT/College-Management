{{-- Home page shared by every role of the school portal. Figures come from App\Services\PortalDashboard. --}}
@php
    $hour = now()->timezone('Asia/Dhaka')->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $schoolName = auth()->user()->school_id ? \Illuminate\Support\Facades\DB::table('schools')->where('id', auth()->user()->school_id)->value('title') : get_settings('system_name');
@endphp
<div class="mcc-dash">
    <header class="mcc-head">
        <div>
            <h1>{{ $greeting }}, {{ auth()->user()->name }}</h1>
            <p>{{ now()->timezone('Asia/Dhaka')->format('l, j F Y') }}@if ($schoolName), {{ $schoolName }}@endif</p>
        </div>
        @if ($dash['actions'])
            <nav class="mcc-actions" aria-label="Quick actions">
                @foreach ($dash['actions'] as $action)
                    <a class="mcc-btn {{ $loop->first ? 'primary' : '' }}" href="{{ $action['url'] }}">{{ $action['label'] }}</a>
                @endforeach
            </nav>
        @endif
    </header>

    <div class="mcc-stats">
        @foreach ($dash['stats'] as $stat)
            <{{ $stat['url'] ? 'a' : 'div' }} class="mcc-stat {{ $stat['tone'] }}" @if ($stat['url']) href="{{ $stat['url'] }}" @endif>
                <span class="label">{{ $stat['label'] }}</span>
                <strong>{{ $stat['value'] }}</strong>
                <span class="note">{{ $stat['note'] }}</span>
            </{{ $stat['url'] ? 'a' : 'div' }}>
        @endforeach
    </div>

    <div class="mcc-columns {{ $dash['aside'] ? '' : 'single' }}">
        <div class="mcc-main">
            @includeWhen(isset($todayClasses), 'portal.today_classes')

            @foreach ($dash['panels'] as $panel)
                <section class="mcc-panel">
                    <div class="mcc-panel-head">
                        <h2>{{ $panel['title'] }}</h2>
                        @if (!empty($panel['url']))<a href="{{ $panel['url'] }}">Open</a>@endif
                    </div>

                    @if ($panel['type'] === 'bars')
                        @forelse ($panel['items'] as $item)
                            <div class="mcc-bar-row">
                                <span>{{ $item['label'] }}</span>
                                <div class="mcc-bar" role="img" aria-label="{{ $item['label'] }}: {{ $item['percent'] }} percent present"><i style="width: {{ $item['percent'] }}%"></i></div>
                                <em>{{ $item['value'] }}</em>
                            </div>
                        @empty
                            <p class="mcc-empty">{{ $panel['empty'] }}</p>
                        @endforelse
                    @elseif ($panel['type'] === 'figures')
                        <div class="mcc-figures">
                            @foreach ($panel['items'] as $item)
                                <div class="{{ $item['tone'] ?? '' }}"><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}</strong></div>
                            @endforeach
                        </div>
                    @elseif ($panel['type'] === 'columns')
                        @php($peak = max(1, collect($panel['items'])->max('value')))
                        <div class="mcc-columns-chart">
                            @foreach ($panel['items'] as $item)
                                <div>
                                    <em>{{ $item['value'] ? number_format($item['value']) : '' }}</em>
                                    <span class="track"><i style="height: {{ round($item['value'] / $peak * 100) }}%"></i></span>
                                    <span>{{ $item['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($panel['type'] === 'table')
                        @if (empty($panel['rows']))
                            <p class="mcc-empty">{{ $panel['empty'] }}</p>
                        @else
                            <div class="mcc-table-wrap">
                                <table class="mcc-table">
                                    <thead><tr>@foreach ($panel['head'] as $cell)<th>{{ $cell }}</th>@endforeach</tr></thead>
                                    <tbody>@foreach ($panel['rows'] as $row)<tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </section>
            @endforeach
        </div>

        @if ($dash['aside'])
            <aside class="mcc-aside">
                @foreach ($dash['aside'] as $list)
                    <section class="mcc-panel">
                        <div class="mcc-panel-head">
                            <h2>{{ $list['title'] }}</h2>
                            @if ($list['url'])<a href="{{ $list['url'] }}">See all</a>@endif
                        </div>
                        @forelse ($list['items'] as $item)
                            <div class="mcc-item">
                                @if ($item['day'])<time><strong>{{ $item['day'] }}</strong><span>{{ $item['month'] }}</span></time>@endif
                                <div><b>{{ $item['title'] }}</b><span>{{ $item['note'] }}</span></div>
                            </div>
                        @empty
                            <p class="mcc-empty">{{ $list['empty'] }}</p>
                        @endforelse
                    </section>
                @endforeach
            </aside>
        @endif
    </div>
</div>
