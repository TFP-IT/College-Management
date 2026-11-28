{{-- A list of rows. A cell is {text, sub?, avatar?, badge+tone?, meter?, num?, strong?, tone?}. --}}
@if (empty($b['rows']))
    <p class="mcc-empty">{{ $b['empty'] ?? 'Nothing to show.' }}</p>
@else
    <div class="mcc-table-wrap">
        <table class="mcc-table {{ !empty($b['compact']) ? 'compact' : '' }}" data-plain>
            <thead><tr>@foreach ($b['head'] as $k => $cell)<th class="{{ !empty($b['rows'][0][$k]['num']) ? 'num' : '' }}">{{ $cell }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach ($b['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            @php $cell = is_array($cell) ? $cell : ['text' => $cell]; @endphp
                            <td class="{{ !empty($cell['num']) ? 'num' : '' }}">
                                @if (isset($cell['badge']))
                                    <span class="mcc-badge {{ $cell['tone'] ?? 'neutral' }}">{{ $cell['badge'] }}</span>
                                @elseif (isset($cell['meter']))
                                    <span class="mcc-meter-inline"><span class="mcc-bar"><i style="width: {{ $cell['meter'] }}%"></i></span><em>{{ $cell['meter'] }}%</em></span>
                                @else
                                    <span class="mcc-cell">
                                        @if (!empty($cell['avatar']))<span class="mcc-avatar" aria-hidden="true">{{ collect(preg_split('/\s+/', trim($cell['avatar'])))->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') }}</span>@endif
                                        <span><span class="{{ !empty($cell['strong']) ? 'strong' : '' }} {{ $cell['tone'] ?? '' }}">{{ $cell['text'] }}</span>@if (!empty($cell['sub']))<small>{{ $cell['sub'] }}</small>@endif</span>
                                    </span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
