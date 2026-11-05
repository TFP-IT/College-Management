@extends($layout ?? 'admissions.layouts.portal')

@section('title', 'Student application | ' . $program->title)

@section('content')
@php
    $guardianMode = $guardianMode ?? false;
    $in = 'w-full text-[13px] rounded-md border border-slate-300 bg-white py-2 px-2.5 focus:border-blue-600 focus:ring-1 focus:ring-blue-600';
    $lb = 'block text-[11px] font-semibold uppercase tracking-wide text-slate-600 mb-1';
    $err = 'text-[11px] text-rose-600 mt-1';
    $sec = [
        'candidate' => 'Candidate',
        'academic' => 'Academic record',
        'guardian' => 'Guardian',
        'documents' => 'Documents',
        'declaration' => 'Declaration',
    ];
@endphp

<div class="max-w-6xl mx-auto">
    {{-- Header bar --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
        <div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $program->section_type->value === 'COLLEGE' ? 'College section' : 'School section' }}</div>
            <h1 class="text-lg font-black text-slate-900 leading-tight">{{ $program->title }}</h1>
        </div>
        <div class="flex gap-4 text-[12px] text-slate-600">
            <div>Application <strong class="text-slate-900">৳{{ number_format($program->application_fee, 0) }}</strong></div>
            <div>Admission <strong class="text-slate-900">৳{{ number_format($program->admission_fee, 0) }}</strong></div>
            <div>Seats <strong class="text-slate-900">{{ $program->capacity }}</strong></div>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-3 rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-[13px] text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if(session('debug_code'))
        <div class="mb-3 rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-[13px] text-amber-800">Development only: your code is <strong>{{ session('debug_code') }}</strong></div>
    @endif
    @if($errors->any())
        <div class="mb-3 rounded-md bg-rose-50 border border-rose-200 px-3 py-2 text-[13px] text-rose-800" role="alert">{{ $errors->first() }}</div>
    @endif

    @if(!$verifiedPhone)
        <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-sm max-w-xl mx-auto space-y-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Verify your mobile number first</h2>
                <p class="text-[12px] text-slate-500">We use it to identify your application and to let you track it later.</p>
            </div>
            <form action="{{ route('admissions.otp.send') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="phone" value="{{ old('phone', session('otp_phone')) }}" required placeholder="01XXXXXXXXX" inputmode="numeric" class="{{ $in }}" aria-label="Mobile number">
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-[13px] font-semibold rounded-md whitespace-nowrap">Send code</button>
            </form>
            @error('phone')<p class="{{ $err }}" role="alert">{{ $message }}</p>@enderror
            <form action="{{ route('admissions.otp.verify') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="hidden" name="phone" value="{{ old('phone', session('otp_phone')) }}">
                <input type="text" name="code" required maxlength="6" pattern="[0-9]{6}" placeholder="6-digit code" inputmode="numeric" class="{{ $in }}" aria-label="Verification code">
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-[13px] font-semibold rounded-md whitespace-nowrap">Verify</button>
            </form>
            @error('code')<p class="{{ $err }}" role="alert">{{ $message }}</p>@enderror
        </div>
    @else

        <form action="{{ route('admissions.store', ['program' => $program->id]) }}" method="POST" enctype="multipart/form-data"
              class="bg-white rounded-lg border border-slate-200 shadow-sm"
              onsubmit="this.querySelector('[data-submit]').disabled = true; this.querySelector('[data-submit]').textContent = 'Submitting…';">
            @csrf
            <input type="hidden" name="phone" value="{{ $verifiedPhone }}">

            {{-- 1. Candidate --}}
            <section id="sec-candidate" class="p-5 border-b border-slate-200">
                <h2 class="text-[13px] font-bold text-slate-900 mb-3 flex items-center gap-2"><span class="w-5 h-5 rounded bg-slate-800 text-white text-[11px] flex items-center justify-center">1</span> Candidate</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3">
                    <div class="md:col-span-2">
                        <label class="{{ $lb }}" for="candidate_name">Full name *</label>
                        <input id="candidate_name" name="candidate_name" value="{{ old('candidate_name') }}" required maxlength="255" class="{{ $in }}">
                        @error('candidate_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $lb }}" for="candidate_dob">Date of birth *</label>
                        <input id="candidate_dob" type="date" name="candidate_dob" value="{{ old('candidate_dob') }}" required class="{{ $in }}">
                        @error('candidate_dob')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $lb }}" for="gender">Gender *</label>
                        <select id="gender" name="gender" required class="{{ $in }}">
                            <option value="">Select</option>
                            @foreach(['MALE' => 'Male', 'FEMALE' => 'Female', 'OTHER' => 'Other'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('gender') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                        @error('gender')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $lb }}" for="blood_group">Blood group</label>
                        <select id="blood_group" name="blood_group" class="{{ $in }}">
                            <option value="">Not known</option>
                            @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                <option value="{{ $bg }}" @selected(old('blood_group') === $bg)>{{ $bg }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lb }}" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255" class="{{ $in }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $lb }}" for="current_address">Current address *</label>
                        <input id="current_address" name="current_address" value="{{ old('current_address') }}" required maxlength="500" class="{{ $in }}">
                        @error('current_address')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $lb }}" for="permanent_address">Permanent address</label>
                        <input id="permanent_address" name="permanent_address" value="{{ old('permanent_address') }}" maxlength="500" class="{{ $in }}">
                    </div>
                </div>
            </section>

            {{-- 2. Academic --}}
            <section id="sec-academic" class="p-5 border-b border-slate-200">
                <h2 class="text-[13px] font-bold text-slate-900 mb-3 flex items-center gap-2"><span class="w-5 h-5 rounded bg-slate-800 text-white text-[11px] flex items-center justify-center">2</span> Academic record</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3">
                    <div class="md:col-span-2">
                        <label class="{{ $lb }}" for="previous_school">Previous school or college</label>
                        <input id="previous_school" name="previous_school" value="{{ old('previous_school') }}" maxlength="255" class="{{ $in }}">
                    </div>
                    <div>
                        <label class="{{ $lb }}" for="previous_gpa">GPA (0.00–5.00)</label>
                        <input id="previous_gpa" type="number" step="0.01" min="0" max="5" name="previous_gpa" value="{{ old('previous_gpa') }}" class="{{ $in }}">
                        @error('previous_gpa')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- 3. Guardian --}}
            <section id="sec-guardian" class="p-5 border-b border-slate-200">
                <h2 class="text-[13px] font-bold text-slate-900 mb-3 flex items-center gap-2"><span class="w-5 h-5 rounded bg-slate-800 text-white text-[11px] flex items-center justify-center">3</span> Guardian</h2>
                @if($guardianMode)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-2 rounded-md bg-slate-50 border border-slate-200 p-3 text-[13px]">
                        <div><span class="{{ $lb }}">Name</span><span class="font-semibold text-slate-900">{{ $guardian['name'] }}</span></div>
                        <div><span class="{{ $lb }}">Relation</span><span class="font-semibold text-slate-900">{{ ucfirst(strtolower($guardian['relation'])) }}</span></div>
                        <div><span class="{{ $lb }}">Phone</span><span class="font-semibold text-slate-900">{{ $guardian['phone'] }}</span></div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">Taken from your guardian sign-in. Not asked again.</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
                        <div>
                            <label class="{{ $lb }}" for="father_name">Father's name *</label>
                            <input id="father_name" name="father_name" value="{{ old('father_name') }}" required maxlength="255" class="{{ $in }}">
                        </div>
                        <div>
                            <label class="{{ $lb }}" for="father_phone">Father's phone *</label>
                            <input id="father_phone" name="father_phone" value="{{ old('father_phone') }}" required placeholder="01XXXXXXXXX" inputmode="numeric" class="{{ $in }}">
                            @error('father_phone')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lb }}" for="mother_name">Mother's name</label>
                            <input id="mother_name" name="mother_name" value="{{ old('mother_name') }}" maxlength="255" class="{{ $in }}">
                        </div>
                        <div>
                            <label class="{{ $lb }}" for="mother_phone">Mother's phone</label>
                            <input id="mother_phone" name="mother_phone" value="{{ old('mother_phone') }}" placeholder="01XXXXXXXXX" inputmode="numeric" class="{{ $in }}">
                        </div>
                    </div>
                @endif
            </section>

            {{-- 4. Documents --}}
            <section id="sec-documents" class="p-5 border-b border-slate-200">
                <h2 class="text-[13px] font-bold text-slate-900 mb-1 flex items-center gap-2"><span class="w-5 h-5 rounded bg-slate-800 text-white text-[11px] flex items-center justify-center">4</span> Documents</h2>
                <p class="text-[11px] text-slate-500 mb-3">PDF, JPG, PNG or WEBP. Photo up to 1 MB, other files up to 5 MB.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3">
                    <div>
                        <label class="{{ $lb }}" for="photo">Photo *</label>
                        <input id="photo" type="file" name="photo" accept="image/*" class="block w-full text-[12px]">
                        @error('photo')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $lb }}" for="birth_cert">Birth certificate</label>
                        <input id="birth_cert" type="file" name="birth_cert" accept="application/pdf,image/*" class="block w-full text-[12px]">
                        @error('birth_cert')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $lb }}" for="transcript">Previous transcript</label>
                        <input id="transcript" type="file" name="transcript" accept="application/pdf,image/*" class="block w-full text-[12px]">
                        @error('transcript')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                </div>
                @error('file')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </section>

            {{-- 5. Declaration --}}
            <section id="sec-declaration" class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <label class="flex items-start gap-2 text-[12px] text-slate-700 max-w-2xl">
                    <input type="checkbox" name="declaration" value="1" required class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-700">
                    <span>I confirm that all information and documents are true and complete. False information leads to cancellation of admission and forfeiture of fees.</span>
                </label>
                <button type="submit" data-submit class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-[13px] font-bold rounded-md whitespace-nowrap">Submit application</button>
            </section>
            @error('declaration')<p class="px-5 pb-4 {{ $err }}">{{ $message }}</p>@enderror
        </form>
    @endif
</div>
@endsection
