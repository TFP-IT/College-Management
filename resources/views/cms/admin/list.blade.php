@extends('admin.navigation')

@push('head')
    @include('admissions.layouts._tailwind_head')
@endpush

@section('title', $res['label'])

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('cms.admin.index') }}" class="text-xs text-brand-700 hover:underline">&larr; Website content</a>
            <h1 class="text-2xl font-black text-slate-900 mt-1">{{ $res['label'] }}</h1>
        </div>
        @if($key !== 'admission-programs')
            <a href="{{ route('cms.admin.create', $key) }}" class="px-4 py-2 rounded-xl bg-brand-600 text-white text-sm font-bold hover:bg-brand-700">New item</a>
        @endif
    </div>

    @if(session('status'))
        <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    @foreach($res['columns'] as $col)
                        <th class="px-4 py-3">{{ str_replace('_', ' ', $col) }}</th>
                    @endforeach
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        @foreach($res['columns'] as $col)
                            <td class="px-4 py-3 text-slate-700">
                                @if($col === 'is_published')
                                    <span class="rounded-full px-2 py-0.5 text-xs {{ $item->is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $item->is_published ? 'Published' : 'Draft' }}</span>
                                @elseif($item->{$col} instanceof \Illuminate\Support\Carbon)
                                    {{ $item->{$col}->toDateString() }}
                                @else
                                    {{ $item->{$col} ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('cms.admin.edit', [$key, $item->id]) }}" class="text-brand-700 font-semibold hover:underline">Edit</a>
                            @if($key !== 'admission-programs')
                                <form action="{{ route('cms.admin.destroy', [$key, $item->id]) }}" method="POST" class="inline ml-3"
                                      onsubmit="return confirm('Delete this item? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 font-semibold hover:underline">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($res['columns']) + 1 }}" class="px-4 py-10 text-center text-slate-500">Nothing here yet. Use <strong>New item</strong> to add one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
