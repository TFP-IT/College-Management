@extends('frontend.layouts.app')

@section('title', 'Programs & Curriculum | Maestro Crown School & College')
@section('meta_description', 'Explore programs at Maestro Crown School & College, Savar: School from Play Group to Class 9 and HSC in Science, Business Studies and Humanities. Subjects, fees and academic guidelines.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $mc = config('landing');
    $ac = config('academics');
    $school = $mc['school'];
    $college = $mc['college'];
    $isCollege = fn ($p) => $p->section_type === \App\Modules\Admissions\Enums\SectionType::COLLEGE;
    $schoolCount = $programs->reject($isCollege)->count();
    $collegeCount = $programs->filter($isCollege)->count();
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['Academics', route('frontend.programs')], ['Programs']],
    'eyebrow' => 'Programs & curriculum',
    'title'   => 'One journey, from <em>Play Group to HSC</em>',
    'lead'    => 'A clear path through every stage of school and college, following the national curriculum with an international outlook, small-group attention and plenty of life beyond the classroom.',
    'chips'   => ['Play Group – Class 9', 'HSC: 3 groups', 'National curriculum'],
    'actions' => [
        ['label' => 'Explore programs', 'href' => '#stages', 'icon' => 'fa-arrow-down', 'style' => 'primary'],
        ['label' => 'Fees & seats', 'href' => '#fees', 'style' => 'outline'],
    ],
    'media'   => ['type' => 'collage', 'images' => ['campus/classroom-hall-1', 'campus/computer-lab-2'], 'badge' => ['3', 'HSC<br>groups']],
])

{{-- Academic resources --}}
<nav class="res-wrap" aria-label="Academic resources">
    <div class="container">
        <ul class="res-strip" data-stagger>
            <li data-reveal><a href="{{ route('frontend.academic_calendar') }}"><span class="res-icon"><i class="fa-regular fa-calendar"></i></span><span><strong>Academic calendar</strong><small>Exams, holidays &amp; events</small></span></a></li>
            <li data-reveal><a href="#grading"><span class="res-icon"><i class="fa-solid fa-chart-simple"></i></span><span><strong>Grading system</strong><small>Marks, grades &amp; GPA</small></span></a></li>
            <li data-reveal><a href="#rules"><span class="res-icon"><i class="fa-solid fa-scale-balanced"></i></span><span><strong>Rules &amp; guidelines</strong><small>Attendance, uniform, exams</small></span></a></li>
            <li data-reveal><a href="{{ $mc['app']['play_store'] }}" target="_blank" rel="noopener"><span class="res-icon"><i class="fa-solid fa-mobile-screen-button"></i></span><span><strong>Class routine</strong><small>In the {{ $mc['app']['name'] }} app</small></span></a></li>
        </ul>
    </div>
</nav>

{{-- School stages --}}
<section class="pg-stages section-pad" id="stages" aria-labelledby="school-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">School section</span>
                <h2 class="sec-title" id="school-title">Play Group to <em>Class 9</em></h2>
            </div>
            <p class="sec-sub">{{ $school['body'] }}</p>
        </header>
        <div class="card-grid card-grid--3" data-stagger>
            @foreach ($school['stages'] as $i => $st)
                <article class="num-card stage-card" data-reveal>
                    <span class="num-card-n">0{{ $i + 1 }}</span>
                    <span class="num-card-icon"><i class="fa-solid {{ $st['icon'] }}"></i></span>
                    <h3>{{ $st['title'] }}</h3>
                    <p class="stage-classes">{{ $st['classes'] }}</p>
                    <p>{{ $st['desc'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- College groups --}}
<section class="pg-groups section-pad" aria-labelledby="college-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">College section</span>
                <h2 class="sec-title" id="college-title">Higher Secondary <em>(HSC)</em></h2>
            </div>
            <p class="sec-sub">{{ $college['body'] }}</p>
        </header>
        <div class="group-grid" data-stagger>
            @foreach ($college['groups'] as $g)
                <article class="group-card" data-reveal>
                    <header>
                        <span class="group-icon"><i class="fa-solid {{ $g['icon'] }}"></i></span>
                        <h3>{{ $g['title'] }}</h3>
                    </header>
                    <p>{{ $g['desc'] }}</p>
                    <span class="group-label">Group subjects</span>
                    <ul class="group-chips">
                        @foreach ($ac['hsc']['groups'][$g['title']] ?? $g['subjects'] as $sub)<li>{{ $sub }}</li>@endforeach
                    </ul>
                </article>
            @endforeach
        </div>
        <div class="subj-shared" data-reveal>
            <div>
                <span class="group-label">Compulsory for every group</span>
                <ul class="group-chips">@foreach ($ac['hsc']['compulsory'] as $sub)<li>{{ $sub }}</li>@endforeach</ul>
            </div>
            <div>
                <span class="group-label">Optional subject</span>
                <ul class="group-chips group-chips--gold">@foreach ($ac['hsc']['optional'] as $sub)<li>{{ $sub }}</li>@endforeach</ul>
            </div>
        </div>
        <div class="elig" data-reveal>
            <div class="elig-head">
                <span class="elig-icon"><i class="fa-solid fa-user-check"></i></span>
                <div><strong>HSC eligibility, session 2026–27</strong><span>Revised minimum SSC GPA</span></div>
            </div>
            <ul>
                @foreach ($college['eligibility'] as $e)<li><i class="fa-solid fa-circle-check"></i> {{ $e }}</li>@endforeach
            </ul>
        </div>
    </div>
</section>

{{-- Grading system --}}
<section class="pg-grading section-pad" id="grading" aria-labelledby="grading-title">
    <div class="container pg-grading-grid">
        <header class="sec-head" data-reveal>
            <span class="eyebrow">Assessment</span>
            <h2 class="sec-title" id="grading-title">National <em>grading system</em></h2>
            <p class="sec-sub">MCC follows the standard HSC grading system of the Ministry of Education, Bangladesh. Each subject is graded on the scale below, and a student’s GPA is the average of their grade points.</p>
            <p class="grade-pass"><i class="fa-solid fa-circle-check"></i> 33% is the minimum pass mark in every subject.</p>
        </header>
        <div class="grade-table" role="table" aria-label="HSC grading system" data-reveal>
            <div class="grade-row grade-row--head" role="row">
                <span role="columnheader">Marks (%)</span>
                <span role="columnheader">Letter grade</span>
                <span role="columnheader">Grade point</span>
                <span role="columnheader">Remarks</span>
            </div>
            @foreach ($ac['grading'] as $i => $g)
                <div @class(['grade-row', 'grade-row--top' => $i === 0, 'grade-row--fail' => $g['grade'] === 'F']) role="row" style="--lvl: {{ count($ac['grading']) - 1 - $i }}">
                    <span role="cell" class="grade-marks">{{ $g['marks'] }}</span>
                    <span role="cell"><span class="grade-chip">{{ $g['grade'] }}</span></span>
                    <span role="cell" class="grade-point">{{ $g['point'] }}</span>
                    <span role="cell" class="grade-remark">{{ $g['remark'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Live programmes & fees --}}
@if ($programs->isNotEmpty())
<section class="pg-fees section-pad" id="fees" aria-labelledby="fees-title">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Admission 2026–27</span>
                <h2 class="sec-title" id="fees-title">Programmes, seats <em>&amp; fees</em></h2>
            </div>
            <p class="sec-sub">Live from our admission office. To apply, download the {{ $mc['app']['name'] }} app and choose your programme.</p>
        </header>

        <div class="fee-wrap" data-filterable>
            <div class="fee-tools" data-reveal>
                <div class="dir-filters" role="toolbar" aria-label="Filter programmes">
                    <button type="button" class="is-active" aria-pressed="true" data-fbtn="all">All <span>{{ $programs->count() }}</span></button>
                    @if ($schoolCount)<button type="button" aria-pressed="false" data-fbtn="school">School <span>{{ $schoolCount }}</span></button>@endif
                    @if ($collegeCount)<button type="button" aria-pressed="false" data-fbtn="college">College <span>{{ $collegeCount }}</span></button>@endif
                </div>
                @include('frontend.landing.partials._stores', ['class' => 'fee-stores'])
            </div>

            <div class="fee-table" role="table" aria-label="Programmes and fees" data-reveal>
                <div class="fee-row fee-row--head" role="row">
                    <span role="columnheader">Programme</span>
                    <span role="columnheader">Seats</span>
                    <span role="columnheader">Application fee</span>
                    <span role="columnheader">Admission fee</span>
                    <span role="columnheader">Deadline</span>
                </div>
                @foreach ($programs as $program)
                    @php $col = $isCollege($program); $open = $program->isOpen(); @endphp
                    <div class="fee-row" role="row" data-fitem data-type="{{ $col ? 'college' : 'school' }}">
                        <span role="cell" class="fee-prog">
                            <span class="fee-kind fee-kind--{{ $col ? 'college' : 'school' }}"><i class="fa-solid {{ $col ? 'fa-graduation-cap' : 'fa-shapes' }}"></i></span>
                            <span>
                                <strong>{{ $program->title }}</strong>
                                <small>{{ collect([$program->academicClass?->name, $program->academicGroup?->name])->filter()->implode(' · ') ?: ($col ? 'College' : 'School') }}</small>
                            </span>
                        </span>
                        <span role="cell" data-label="Seats">{{ $program->capacity ?: '—' }}</span>
                        <span role="cell" data-label="Application fee">৳{{ number_format($program->application_fee) }}</span>
                        <span role="cell" data-label="Admission fee" class="fee-strong">৳{{ number_format($program->admission_fee) }}</span>
                        <span role="cell" data-label="Deadline">
                            @if ($open)
                                <span class="fee-status fee-status--open">{{ $program->ends_at?->format('d M Y') ?? 'Open' }}</span>
                            @else
                                <span class="fee-status">Closed</span>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
            <p class="fee-note" data-reveal><i class="fa-solid fa-award"></i> {{ $college['scholarship'] }}</p>
        </div>
    </div>
</section>
@endif

{{-- Rules & guidelines --}}
<section class="pg-rules section-pad" id="rules" aria-labelledby="rules-title">
    <div class="container pg-rules-grid">
        <header class="sec-head" data-reveal>
            <span class="eyebrow">Code of conduct</span>
            <h2 class="sec-title" id="rules-title">Rules &amp; <em>guidelines</em></h2>
            <p class="sec-sub">Clear expectations keep our campus focused, safe and fair for everyone. Every student and guardian is asked to read and follow them.</p>
            <a href="{{ route('frontend.academic_calendar') }}" class="link-arrow">See the academic calendar <i class="fa-solid fa-arrow-right"></i></a>
        </header>
        <ol class="rule-list" data-stagger>
            @foreach ($ac['rules'] as $i => $r)
                <li class="rule" data-reveal>
                    <span class="rule-n">{{ $i + 1 }}</span>
                    <div>
                        <h3><i class="fa-solid {{ $r['icon'] }}"></i> {{ $r['title'] }}</h3>
                        <p>{{ $r['desc'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

@include('frontend.partials.cta-band')
@include('frontend.partials.next-links', ['set' => 'academics'])
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
