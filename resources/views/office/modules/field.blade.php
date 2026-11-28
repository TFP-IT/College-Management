@php
    $name = $field['key'];
    $current = old($name, $value ?? '');
    if (in_array($field['type'], ['date', 'datetime']) && $current) {
        $current = \Carbon\Carbon::parse($current)->format($field['type'] === 'date' ? 'Y-m-d' : 'Y-m-d\TH:i');
    }
    $required = $field['required'] && ($creating ?? false);
@endphp
<div class="field {{ $field['type'] === 'textarea' ? 'wide' : '' }}">
    <label for="f-{{ $name }}">{{ $field['label'] }}@if ($required) <span class="req" aria-hidden="true">*</span>@endif</label>
    @if ($field['type'] === 'textarea')
        <textarea id="f-{{ $name }}" name="{{ $name }}" maxlength="5000" @required($required)>{{ $current }}</textarea>
    @elseif ($field['type'] === 'select')
        <select id="f-{{ $name }}" name="{{ $name }}" @required($required)>
            <option value="">Choose</option>
            @foreach ($field['options'] as $o)<option value="{{ $o }}" @selected((string) $current === $o)>{{ ucfirst($o) }}</option>@endforeach
        </select>
    @elseif ($field['type'] === 'section')
        <select id="f-{{ $name }}" name="{{ $name }}" @required($required)>
            <option value="">Choose</option>
            @foreach ($sections as $key => $label)<option value="{{ $key }}" @selected((string) $current === $key)>{{ $label }}</option>@endforeach
        </select>
    @else
        <input id="f-{{ $name }}" name="{{ $name }}" value="{{ $current }}" @required($required)
            type="{{ ['date' => 'date', 'datetime' => 'datetime-local', 'number' => 'number', 'money' => 'number'][$field['type']] ?? 'text' }}"
            @if (in_array($field['type'], ['number', 'money'])) min="0" step="{{ $field['type'] === 'money' ? '1' : 'any' }}" @else maxlength="200" @endif>
    @endif
</div>
