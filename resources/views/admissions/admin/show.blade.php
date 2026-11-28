@extends('admin.navigation')

@push('head')
    @include('admissions.layouts._tailwind_head')
@endpush

@section('title', 'Dossier #' . $application->application_number)

@section('content')
<div class="space-y-6">
    <!-- Header Summary Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="text-xs font-mono text-slate-400">APP #{{ $application->application_number }}</span>
                <span class="text-xs font-bold uppercase px-2.5 py-0.5 rounded-full {{ $application->application_status->badgeClass() }}">
                    {{ $application->application_status->label() }}
                </span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 mt-1">{{ $application->candidate_name }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Program: <strong>{{ $application->program?->title }}</strong></p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admissions.show', ['id' => $application->id]) }}" target="_blank"
               class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
                <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> Public View & QR
            </a>
            <a href="{{ route('admissions.admin.dashboard') }}"
               class="px-3.5 py-2 bg-brand-50 hover:bg-brand-100 text-brand-700 font-semibold text-xs rounded-xl transition-colors">
                <i class="fa-solid fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Split Screen Dossier Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT COLUMN: Dossier, Guardians, Academic History & Documents (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Candidate Information -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm text-xs space-y-4">
                <h3 class="font-bold text-sm text-slate-900 pb-3 border-b border-slate-100 flex items-center">
                    <i class="fa-solid fa-id-card text-brand-600 mr-2"></i> Candidate Dossier
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <div><span class="text-slate-400 block">Date of Birth</span><strong class="text-slate-800">{{ $application->candidate_dob?->format('d M, Y') }}</strong></div>
                    <div><span class="text-slate-400 block">Gender</span><strong class="text-slate-800">{{ $application->gender }}</strong></div>
                    <div><span class="text-slate-400 block">Blood Group</span><strong class="text-slate-800">{{ $application->blood_group ?? 'N/A' }}</strong></div>
                    <div><span class="text-slate-400 block">Phone</span><strong class="text-slate-800">{{ $application->phone }}</strong></div>
                    <div><span class="text-slate-400 block">Email</span><strong class="text-slate-800">{{ $application->email ?? 'N/A' }}</strong></div>
                    <div><span class="text-slate-400 block">Previous GPA</span><strong class="text-slate-800">{{ $application->previous_gpa ?? 'N/A' }}</strong></div>
                    <div class="col-span-2 md:col-span-3"><span class="text-slate-400 block">Current Address</span><strong class="text-slate-800">{{ $application->current_address }}</strong></div>
                    <div class="col-span-2 md:col-span-3"><span class="text-slate-400 block">Previous School</span><strong class="text-slate-800">{{ $application->previous_school ?? 'N/A' }}</strong></div>
                </div>
            </div>

            <!-- Guardians Information -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm text-xs space-y-4">
                <h3 class="font-bold text-sm text-slate-900 pb-3 border-b border-slate-100 flex items-center">
                    <i class="fa-solid fa-users text-brand-600 mr-2"></i> Guardian Profiles
                </h3>

                <div class="space-y-3">
                    @forelse($application->guardians as $g)
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex justify-between items-center">
                            <div>
                                <span class="font-bold text-slate-900 text-xs">{{ $g->full_name }}</span>
                                <span class="ml-2 text-[10px] font-bold uppercase px-2 py-0.5 rounded {{ $g->is_primary ? 'bg-sky-50 text-blue-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $g->relation }} {{ $g->is_primary ? '(Primary)' : '' }}
                                </span>
                                <div class="text-slate-500 text-[11px] mt-0.5">Phone: {{ $g->phone }} &bull; Occ: {{ $g->occupation ?? 'N/A' }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-400">No guardian records found.</p>
                    @endforelse
                </div>
            </div>

            <!-- Documents with Verification Action -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm text-xs space-y-4">
                <h3 class="font-bold text-sm text-slate-900 pb-3 border-b border-slate-100 flex items-center justify-between">
                    <span class="flex items-center"><i class="fa-solid fa-file-shield text-brand-600 mr-2"></i> Document Verification</span>
                    <span class="text-[11px] text-slate-400 font-normal">Officer Actions</span>
                </h3>

                <div class="space-y-3">
                    @forelse($application->documents as $doc)
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i class="fa-regular fa-file-pdf text-brand-600 text-xl"></i>
                                    <div>
                                        <span class="font-bold text-slate-900">{{ $doc->doc_type->label() }}</span>
                                        <span class="text-[10px] text-slate-400 block">{{ $doc->original_filename }} &bull; {{ number_format($doc->file_size_bytes / 1024, 1) }} KB</span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded {{ $doc->doc_status->value === 'VERIFIED' ? 'bg-emerald-100 text-emerald-800' : ($doc->doc_status->value === 'REJECTED' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ $doc->doc_status->value }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-slate-200">
                                <a href="{{ app(\App\Modules\Admissions\Services\DocumentService::class)->getSecureUrl($doc) }}" target="_blank"
                                   class="px-3 py-1 bg-white border border-slate-300 rounded font-bold text-slate-700 hover:bg-slate-100">
                                    <i class="fa-solid fa-eye mr-1"></i> Inspect File
                                </a>

                                <form action="{{ route('admissions.admin.documents.verify', ['applicationId' => $application->id, 'documentId' => $doc->id]) }}" method="POST" class="flex items-center space-x-2">
                                    @csrf
                                    <button type="submit" name="status" value="VERIFIED" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded">
                                        <i class="fa-solid fa-check mr-1"></i> Verify
                                    </button>
                                    <button type="submit" name="status" value="REJECTED" class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded">
                                        <i class="fa-solid fa-xmark mr-1"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-400">No documents uploaded.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Assessment, Council Decision & Final Enrollment (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
            <!-- 1. Assessment / Exam Scheduling & Marks Entry -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm text-xs space-y-4">
                <h3 class="font-bold text-sm text-slate-900 pb-3 border-b border-slate-100 flex items-center">
                    <i class="fa-solid fa-clipboard-check text-purple-600 mr-2"></i> Assessment / Exam
                </h3>

                @if($application->assessments->count() > 0)
                    @foreach($application->assessments as $assessment)
                        <div class="p-3.5 rounded-xl bg-purple-50/60 border border-purple-200 space-y-2 mb-3">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-purple-900 uppercase text-[11px]">{{ $assessment->assessment_type->value }}</span>
                                <span class="text-slate-500">{{ $assessment->scheduled_at->format('d M, Y h:i A') }}</span>
                            </div>
                            <div class="text-slate-600">Venue: <strong>{{ $assessment->venue ?? 'MCC Main Campus' }}</strong></div>

                            @if($assessment->obtained_marks !== null)
                                <div class="p-2 rounded bg-white border border-purple-100 flex justify-between font-bold text-slate-800">
                                    <span>Score Obtained:</span>
                                    <span class="text-purple-700">{{ $assessment->obtained_marks }} / {{ $assessment->total_marks }}</span>
                                </div>
                            @else
                                <!-- Score input form -->
                                <form action="{{ route('admissions.admin.assessments.score', ['applicationId' => $application->id, 'assessmentId' => $assessment->id]) }}" method="POST" class="pt-2 border-t border-purple-200 flex gap-2">
                                    @csrf
                                    <input type="number" step="0.5" name="obtained_marks" placeholder="Score (0-100)" required
                                           class="w-full text-xs rounded border-purple-300 py-1.5 px-2">
                                    <button type="submit" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded whitespace-nowrap">
                                        Save Score
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                @else
                    <form action="{{ route('admissions.admin.assessments.schedule', ['applicationId' => $application->id]) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Assessment Type</label>
                            <select name="assessment_type" class="w-full text-xs rounded-lg border-slate-300 py-2 px-3">
                                <option value="WRITTEN">Written Exam</option>
                                <option value="VIVA">Viva Voce</option>
                                <option value="INTERVIEW">Candidate & Parent Interview</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Date & Time</label>
                            <input type="datetime-local" name="scheduled_at" required class="w-full text-xs rounded-lg border-slate-300 py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Venue</label>
                            <input type="text" name="venue" placeholder="e.g. Campus Building A, Room 204" class="w-full text-xs rounded-lg border-slate-300 py-2 px-3">
                        </div>
                        <button type="submit" class="w-full py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition-colors">
                            Schedule Assessment
                        </button>
                    </form>
                @endif
            </div>

            <!-- 2. Admission Council Decision -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm text-xs space-y-4">
                <h3 class="font-bold text-sm text-slate-900 pb-3 border-b border-slate-100 flex items-center">
                    <i class="fa-solid fa-gavel text-brand-600 mr-2"></i> Admission Council Decision
                </h3>

                @if($application->latestDecision)
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-semibold">Council Resolution:</span>
                            <span class="font-black text-sm uppercase px-2.5 py-0.5 rounded {{ $application->latestDecision->decision->badgeClass() }}">
                                {{ $application->latestDecision->decision->value }}
                            </span>
                        </div>
                        <div class="text-slate-600">Decided by: <strong>{{ $application->latestDecision->decider?->name ?? 'Council Member' }}</strong></div>
                        <div class="text-slate-500 text-[11px]">Decided at: {{ $application->latestDecision->decided_at->format('d M, Y h:i A') }}</div>
                        @if($application->latestDecision->remarks)
                            <div class="p-2 rounded bg-white border border-slate-200 text-slate-700 italic">"{{ $application->latestDecision->remarks }}"</div>
                        @endif
                    </div>
                @else
                    <form action="{{ route('admissions.admin.decision', ['applicationId' => $application->id]) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Decision</label>
                            <select name="decision" required class="w-full text-xs rounded-lg border-slate-300 py-2 px-3">
                                <option value="APPROVED">APPROVE Admission Offer</option>
                                <option value="WAITLISTED">WAITLIST Candidate</option>
                                <option value="REJECTED">REJECT Application</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Council Remarks / Justification</label>
                            <textarea name="remarks" rows="2" placeholder="Notes from interview or council review..." class="w-full text-xs rounded-lg border-slate-300 py-2 px-3"></textarea>
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg transition-colors">
                            Record Council Resolution
                        </button>
                    </form>
                @endif
            </div>

            <!-- 3. Final Enrollment & Student ID Provisioning -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm text-xs space-y-4">
                <h3 class="font-bold text-sm text-slate-900 pb-3 border-b border-slate-100 flex items-center">
                    <i class="fa-solid fa-graduation-cap text-emerald-600 mr-2"></i> Final Enrollment & Provisioning
                </h3>

                @if($application->application_status->value === 'ENROLLED')
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-center space-y-1">
                        <i class="fa-solid fa-award text-emerald-600 text-3xl mb-1"></i>
                        <div class="font-black text-emerald-900 text-base">Candidate Enrolled</div>
                        <div class="text-xs font-mono font-bold text-emerald-800">Student ID: {{ $application->admission_student_id }}</div>
                    </div>
                @else
                    <form action="{{ route('admissions.admin.enroll', ['applicationId' => $application->id]) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-[11px] leading-relaxed">
                            <i class="fa-solid fa-shield-halved mr-1 text-amber-700"></i>
                            <strong>Separation of Duties Enforced:</strong> The council member who approved this candidate cannot execute enrollment.
                        </div>

                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Allocate Section <span class="text-rose-500">*</span></label>
                            <select name="section_id" required class="w-full text-xs rounded-lg border-slate-300 py-2 px-3">
                                @forelse($availableSections as $sec)
                                    <option value="{{ $sec->id }}">Section {{ $sec->name }} (Available: {{ $sec->availableSeatsCount() }} / {{ $sec->capacity }})</option>
                                @empty
                                    <option disabled>No sections configured for this class</option>
                                @endforelse
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Assign Roll Number (Optional)</label>
                            <input type="text" name="roll_no" placeholder="e.g. 101" class="w-full text-xs rounded-lg border-slate-300 py-2 px-3">
                        </div>

                        <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm transition-colors">
                            <i class="fa-solid fa-user-plus mr-1"></i> Provision Student ID & Enroll
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
