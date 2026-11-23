{{--
    Searchable, filterable people directory (Teachers, Staff).
    @include('frontend.partials.people-directory', ['members' => $members, 'noun' => 'teacher', 'groupBy' => 'faculty'|'none'])
    Faculty is derived from the designation because the CMS has no department data yet;
    when `department` is filled in the CMS it takes priority.
--}}
@php
    $facultyLabels = [
        'leadership' => 'Academic Leadership',
        'science'    => 'Science',
        'business'   => 'Business Studies',
        'humanities' => 'Humanities',
        'languages'  => 'Languages',
        'school'     => 'School Section',
        'other'      => 'Other',
    ];
    $facultyIcons = [
        'leadership' => 'fa-crown', 'science' => 'fa-flask', 'business' => 'fa-briefcase',
        'humanities' => 'fa-landmark', 'languages' => 'fa-language', 'school' => 'fa-shapes', 'other' => 'fa-user',
    ];
    $facultyOf = function ($m) {
        $d = mb_strtolower(trim(($m->department ?? '') . ' ' . ($m->designation ?? '')));
        return match (true) {
            str_contains($d, 'head of') || str_contains($d, 'principal') => 'leadership',
            str_contains($d, 'assistant teacher') || str_contains($d, 'school') => 'school',
            (bool) preg_match('/physics|chemistry|biology|math|ict|agricultur|science/', $d) => 'science',
            (bool) preg_match('/accounting|economics|business|finance|management|banking/', $d) => 'business',
            (bool) preg_match('/history|civics|social work|islamic|humanities|governance/', $d) => 'humanities',
            (bool) preg_match('/bangla|english|language/', $d) => 'languages',
            default => 'other',
        };
    };
    $subjectOf = function ($m) {
        $d = (string) $m->designation;
        if (preg_match('/\bin\s+(.+)$/i', $d, $x)) return trim($x[1]);
        if (preg_match('/,\s*(.+)$/', $d, $x)) return trim($x[1]);
        return null;
    };
    $useFaculty = ($groupBy ?? 'faculty') === 'faculty';
    $people = $members->map(fn ($m) => [
        'm' => $m,
        'faculty' => $useFaculty ? $facultyOf($m) : 'other',
        'subject' => $subjectOf($m),
    ]);
    $counts = $people->countBy('faculty');
    $noun = $noun ?? 'member';
@endphp

@if ($people->isEmpty())
    <div class="dir-empty" data-reveal>
        <span class="dir-empty-icon"><i class="fa-solid fa-users"></i></span>
        <h2>Our {{ $collective ?? $noun }} directory is being updated</h2>
        <p>We’re adding profiles for every member of the team. In the meantime, our office is happy to help with any question.</p>
        <div class="dir-empty-actions">
            <a href="{{ config('landing.contact.phone_href') }}" class="btn btn-primary"><i class="fa-solid fa-phone"></i> {{ config('landing.contact.phone') }}</a>
            <a href="{{ route('frontend.contact') }}" class="btn btn-outline">Contact us</a>
        </div>
    </div>
@else
    <div class="dir" data-directory>
        <div class="dir-tools" data-reveal>
            <label class="dir-search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <span class="sr-only">Search {{ $noun }}s</span>
                <input type="search" placeholder="Search by name or subject" autocomplete="off" data-dir-search>
            </label>
            @if ($useFaculty && $counts->count() > 1)
                <div class="dir-filters" role="toolbar" aria-label="Filter by faculty">
                    <button type="button" class="is-active" aria-pressed="true" data-dir-filter="all">All <span>{{ $people->count() }}</span></button>
                    @foreach ($facultyLabels as $key => $label)
                        @if ($counts->get($key))
                            <button type="button" aria-pressed="false" data-dir-filter="{{ $key }}">{{ $label }} <span>{{ $counts->get($key) }}</span></button>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <p class="dir-count" aria-live="polite"><span data-dir-count>{{ $people->count() }}</span> {{ $noun }}s</p>

        <ul class="dir-grid" data-stagger>
            @foreach ($people as $p)
                @php $m = $p['m']; @endphp
                <li class="dir-card dir-card--{{ $p['faculty'] }}" data-reveal data-faculty="{{ $p['faculty'] }}"
                    data-search="{{ mb_strtolower($m->name . ' ' . $m->designation . ' ' . $m->department . ' ' . ($facultyLabels[$p['faculty']] ?? '')) }}">
                    @include('frontend.partials.portrait', ['name' => $m->name, 'url' => $m->photo_url, 'class' => 'dir-avatar', 'sizes' => '72px'])
                    <div class="dir-body">
                        <h3>{{ $m->name }}</h3>
                        @if ($m->designation)<p class="dir-role">{{ $m->designation }}</p>@endif
                        <div class="dir-tags">
                            @if ($useFaculty)
                                <span class="dir-tag"><i class="fa-solid {{ $facultyIcons[$p['faculty']] }}"></i> {{ $facultyLabels[$p['faculty']] }}</span>
                            @endif
                            @if ($m->department)<span class="dir-tag dir-tag--muted">{{ $m->department }}</span>@endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="dir-none" hidden data-dir-none>
            <i class="fa-regular fa-face-meh" aria-hidden="true"></i>
            <p>No {{ $noun }}s match your search.</p>
            <button type="button" class="btn btn-outline btn-sm" data-dir-reset>Clear search</button>
        </div>
    </div>
@endif
