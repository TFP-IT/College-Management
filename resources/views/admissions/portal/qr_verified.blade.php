@extends('admissions.layouts.portal')

@section('title', 'Admission Pass Check')

@section('content')
<div class="max-w-md mx-auto text-center bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
    @if($valid)
        <div class="text-emerald-600 text-5xl mb-3"><i class="fa-solid fa-circle-check"></i></div>
        <h1 class="text-xl font-black text-slate-900">{{ $message }}</h1>
    @else
        <div class="text-rose-600 text-5xl mb-3"><i class="fa-solid fa-circle-xmark"></i></div>
        <h1 class="text-xl font-black text-slate-900">{{ $message }}</h1>
    @endif
    @if($applicationNumber)
        <p class="text-xs text-slate-500 mt-3">Application {{ $applicationNumber }}</p>
    @endif
    <p class="text-[11px] text-slate-400 mt-6">This check shows validity only. It does not show personal details.</p>
</div>
@endsection
