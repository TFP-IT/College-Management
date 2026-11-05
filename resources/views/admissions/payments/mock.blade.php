@extends('admissions.layouts.portal')

@section('title', 'Payment Gateway Sandbox')

@section('content')
<div class="max-w-lg mx-auto my-8">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <!-- Gateway Header -->
        <div class="bg-gradient-to-r from-slate-900 to-brand-900 p-6 text-white text-center">
            <div class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-400/20 text-amber-300 border border-amber-400/30 mb-2">
                <i class="fa-solid fa-flask mr-1"></i> Interactive Sandbox Simulation
            </div>
            <h1 class="text-xl font-black">MCC Payment Gateway</h1>
            <p class="text-xs text-blue-200 mt-1">Simulating Bangladesh Payment Rails (bKash / Nagad / Cards / SSLCommerz)</p>
        </div>

        <!-- Payment Bill Summary -->
        <div class="p-6 border-b border-slate-100 bg-slate-50">
            <div class="text-xs space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-500">Applicant Name:</span>
                    <strong class="text-slate-900">{{ $payment->application?->candidate_name }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Application Number:</span>
                    <strong class="text-slate-900 font-mono">{{ $payment->application?->application_number }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Fee Category:</span>
                    <strong class="text-brand-700 font-bold">{{ $payment->payment_type->label() }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Transaction Reference:</span>
                    <strong class="text-slate-600 font-mono text-[11px]">{{ $payment->transaction_ref }}</strong>
                </div>
                <div class="pt-3 border-t border-slate-200 flex justify-between items-baseline">
                    <span class="text-sm font-bold text-slate-800">Total Payable Amount:</span>
                    <span class="text-2xl font-black text-emerald-600">৳{{ number_format($payment->amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Simulation Buttons -->
        <div class="p-6 space-y-3">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 text-center mb-2">Select Simulation Action</p>

            <!-- Success -->
            <form action="{{ route('admissions.payments.mock.process', ['payment' => $payment->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="success">
                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition-colors flex items-center justify-center">
                    <i class="fa-solid fa-circle-check mr-2"></i> Simulate Instant Payment Success
                </button>
            </form>

            <!-- Failed -->
            <form action="{{ route('admissions.payments.mock.process', ['payment' => $payment->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="fail">
                <button type="submit" class="w-full py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs rounded-xl transition-colors flex items-center justify-center">
                    <i class="fa-solid fa-circle-xmark mr-2 text-rose-500"></i> Simulate Bank Decline / Card Failure
                </button>
            </form>

            <!-- Cancel -->
            <form action="{{ route('admissions.payments.mock.process', ['payment' => $payment->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs rounded-xl transition-colors flex items-center justify-center">
                    <i class="fa-solid fa-ban mr-2 text-slate-400"></i> Cancel & Return
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
