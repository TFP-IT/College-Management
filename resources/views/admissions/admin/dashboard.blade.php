@extends('admin.navigation')

@push('head')
    @include('admissions.layouts._tailwind_head')
@endpush

@section('title', 'Admissions Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stat Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Applications</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($metrics['total']) }}</div>
        </div>

        <!-- Pending Review -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <span class="text-[11px] font-bold text-amber-500 uppercase tracking-wider block">Pending Review</span>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ number_format($metrics['pending_review']) }}</div>
        </div>

        <!-- Approved -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Council Approved</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($metrics['approved']) }}</div>
        </div>

        <!-- Enrolled -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <span class="text-[11px] font-bold text-emerald-500 uppercase tracking-wider block">Enrolled Students</span>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($metrics['enrolled']) }}</div>
        </div>

        <!-- Fees Collected -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <span class="text-[11px] font-bold text-blue-500 uppercase tracking-wider block">Total Fees (BDT)</span>
            <div class="text-2xl font-black text-blue-700 mt-1">৳{{ number_format($metrics['fees_collected'], 0) }}</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <form action="{{ route('admissions.admin.dashboard') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Name, App #, Phone..."
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 py-2 px-3">
            </div>

            <div>
                <select name="program_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 py-2 px-3">
                    <option value="">All Programs</option>
                    @foreach($programs as $p)
                        <option value="{{ $p->id }}" {{ request('program_id') == $p->id ? 'selected' : '' }}>{{ $p->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="status" class="w-full text-xs rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 py-2 px-3">
                    <option value="">All Statuses</option>
                    <option value="DRAFT" {{ request('status') == 'DRAFT' ? 'selected' : '' }}>Draft</option>
                    <option value="SUBMITTED" {{ request('status') == 'SUBMITTED' ? 'selected' : '' }}>Submitted</option>
                    <option value="UNDER_REVIEW" {{ request('status') == 'UNDER_REVIEW' ? 'selected' : '' }}>Under Review</option>
                    <option value="EXAM_SCHEDULED" {{ request('status') == 'EXAM_SCHEDULED' ? 'selected' : '' }}>Exam Scheduled</option>
                    <option value="ASSESSED" {{ request('status') == 'ASSESSED' ? 'selected' : '' }}>Assessed</option>
                    <option value="APPROVED" {{ request('status') == 'APPROVED' ? 'selected' : '' }}>Approved</option>
                    <option value="WAITLISTED" {{ request('status') == 'WAITLISTED' ? 'selected' : '' }}>Waitlisted</option>
                    <option value="REJECTED" {{ request('status') == 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                    <option value="ADMISSION_FEE_PAID" {{ request('status') == 'ADMISSION_FEE_PAID' ? 'selected' : '' }}>Admission Fee Paid</option>
                    <option value="ENROLLED" {{ request('status') == 'ENROLLED' ? 'selected' : '' }}>Enrolled</option>
                </select>
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="flex-1 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-lg transition-colors">
                    Filter Applications
                </button>
                <a href="{{ route('admissions.admin.dashboard') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs rounded-lg transition-colors flex items-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Applications Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800">Applications List</h2>
            <span class="text-xs text-slate-400">Showing {{ $applications->firstItem() ?? 0 }} to {{ $applications->lastItem() ?? 0 }} of {{ $applications->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Application #</th>
                        <th class="py-3 px-4">Candidate</th>
                        <th class="py-3 px-4">Program</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Student ID</th>
                        <th class="py-3 px-4">Submitted</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($applications as $app)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-brand-700">
                                {{ $app->application_number }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">
                                {{ $app->candidate_name }}
                                <span class="block text-[11px] font-normal text-slate-400">{{ $app->phone }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="block font-medium text-slate-800">{{ $app->program?->title }}</span>
                                <span class="text-[10px] text-slate-400 uppercase">{{ $app->program?->section_type->value }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full {{ $app->application_status->badgeClass() }}">
                                    {{ $app->application_status->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-emerald-700">
                                {{ $app->admission_student_id ?? '—' }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-500">
                                {{ $app->created_at->format('d M, Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('admissions.admin.show', ['id' => $app->id]) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-700 font-bold rounded-lg text-xs transition-colors">
                                    Review Dossier <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                No applications found matching the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $applications->links() }}
        </div>
    </div>
</div>
@endsection
