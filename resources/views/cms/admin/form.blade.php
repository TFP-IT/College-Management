@extends('admin.navigation')

@push('head')
    @include('admissions.layouts._tailwind_head')
@endpush

@section('title', ($item ? 'Edit' : 'New') . ' · ' . $res['label'])

@section('content')
@php
    $hasFile = in_array('file', $res['fields'], true);
    $action = $item ? route('cms.admin.update', [$key, $item->id]) : route('cms.admin.store', $key);
@endphp
<div class="max-w-3xl mx-auto px-4 py-8">
    <a href="{{ route('cms.admin.list', $key) }}" class="text-xs text-brand-700 hover:underline">&larr; {{ $res['label'] }}</a>
    <h1 class="text-2xl font-black text-slate-900 mt-1">{{ $item ? 'Edit item' : 'New item' }}</h1>

    @if($errors->any())
        <div class="mt-4 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800" role="alert">Please fix the highlighted fields.</div>
    @endif

    <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if($item) @method('PUT') @endif

        @foreach($res['fields'] as $name => $type)
            @php
                $value = old($name, $item?->{$name});
                $id = "f_{$name}";
            @endphp
            <div>
                @if($type === 'checkbox')
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="{{ $name }}" value="1" class="rounded border-slate-300" @checked(old($name, $item?->{$name} ?? false))>
                        {{ $name === 'is_active' ? 'Open for applications (visible on the website)' : 'Published (visible on the website)' }}
                    </label>
                @else
                    <label for="{{ $id }}" class="block text-sm font-semibold text-slate-700 mb-1">{{ ucfirst(str_replace('_', ' ', $name)) }}</label>

                    @if($type === 'textarea' || $type === 'html')
                        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $type === 'html' ? 12 : 4 }}"
                                  class="w-full rounded-lg border-slate-300 text-sm">{{ $value }}</textarea>
                        @if($type === 'html')
                            <p class="text-xs text-slate-500 mt-1">Allowed: paragraphs, headings (h2–h4), bold, italics, lists, links and quotes. Scripts are removed.</p>
                        @endif
                    @elseif($type === 'select')
                        <select id="{{ $id }}" name="{{ $name }}" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach(\App\Modules\Cms\Models\CmsStaffMember::GROUPS as $group)
                                <option value="{{ $group }}" @selected($value === $group)>{{ str_replace('_', ' ', $group) }}</option>
                            @endforeach
                        </select>
                    @elseif($type === 'file')
                        <input id="{{ $id }}" type="file" name="{{ $name === 'image' ? 'image' : 'photo' }}" accept="image/*" class="block text-sm">
                        @if($item && ($item->image_url ?? $item->photo_url ?? null))
                            <img src="{{ $item->image_url ?? $item->photo_url }}" alt="Current image" class="mt-2 h-24 rounded-lg border border-slate-200">
                        @endif
                    @elseif($type === 'number')
                        <input id="{{ $id }}" type="number" min="0" step="any" name="{{ $name }}" value="{{ $value ?? 0 }}" class="w-full rounded-lg border-slate-300 text-sm">
                    @elseif($type === 'datetime')
                        <input id="{{ $id }}" type="datetime-local" name="{{ $name }}" value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i') : '' }}" class="w-full rounded-lg border-slate-300 text-sm">
                    @elseif($type === 'date')
                        <input id="{{ $id }}" type="date" name="{{ $name }}" value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->toDateString() : '' }}" class="w-full rounded-lg border-slate-300 text-sm">
                    @else
                        <input id="{{ $id }}" type="text" name="{{ $name }}" value="{{ $value }}" class="w-full rounded-lg border-slate-300 text-sm"
                               @if($name === 'slug' && $item) readonly @endif>
                    @endif
                @endif
                @error($name === 'image' ? 'image' : ($name === 'photo' ? 'photo' : $name))
                    <p class="text-xs text-rose-600 mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('cms.admin.list', $key) }}" class="px-4 py-2 text-sm font-semibold text-slate-600">Cancel</a>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold hover:bg-brand-700" data-once>Save</button>
        </div>
    </form>
</div>
@endsection
