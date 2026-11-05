@extends('admissions.layouts.portal')

@section('title', 'Payment Receipt')

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl p-8 text-center">
        @if($payment->isSuccess())
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto mb-4">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900">Payment Successful!</h1>
            <p class="text-xs text-slate-500 mt-1">Thank you. Your fee has been received and verified.</p>
        @else
            <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-3xl mx-auto mb-4">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900">Payment {{ $payment->payment_status->value }}</h1>
            <p class="text-xs text-slate-500 mt-1">The transaction could not be completed successfully.</p>
        @endif

        <div class="mt-6 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-left text-xs space-y-2">
            <div class="flex justify-between">
                <span class="text-slate-400">Fee Type:</span>
                <strong class="text-slate-800">{{ $payment->payment_type->label() }}</strong>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Amount Paid:</span>
                <strong class="text-emerald-700 font-bold text-sm">৳{{ number_format($payment->amount, 2) }}</strong>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Transaction ID:</span>
                <strong class="text-slate-700 font-mono">{{ $payment->transaction_ref }}</strong>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Payment Gateway:</span>
                <strong class="text-slate-700 uppercase">{{ $payment->provider }}</strong>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Timestamp:</span>
                <strong class="text-slate-700">{{ $payment->updated_at->format('d M, Y h:i A') }}</strong>
            </div>
        </div>

        <div class="mt-8">
            <a href="{{ route('admissions.show', ['id' => $payment->application_id]) }}"
               class="inline-flex items-center justify-center w-full py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-sm transition-colors">
                Back to Application Dashboard & QR Pass <i class="fa-solid fa-arrow-right ml-1.5"></i>
            </a>
        </div>
    </div>
</div>
@endsection
