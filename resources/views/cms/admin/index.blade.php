@extends('admin.navigation')

@push('head')
    @include('admissions.layouts._tailwind_head')
@endpush

@section('title', 'Website CMS')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-black text-slate-900">Website content</h1>
    <p class="text-sm text-slate-500 mt-1">Edit what visitors see on the public website. Changes appear straight away.</p>

    <div class="grid gap-4 md:grid-cols-2 mt-6">
        @foreach($resources as $key => $res)
            <a href="{{ route('cms.admin.list', $key) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-brand-500">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">{{ $res['label'] }}</h2>
                    <span class="text-xs font-semibold text-brand-700 bg-brand-50 rounded-full px-3 py-1">{{ $counts[$key] }} items</span>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
