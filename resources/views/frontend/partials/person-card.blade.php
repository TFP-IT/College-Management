{{-- Faculty / staff card linking to the profile page. @include('frontend.partials.person-card', ['p' => $member]) --}}
@php
    $href = route(($p['group'] ?? 'teacher') === 'staff' ? 'frontend.staff_profile' : 'frontend.teacher_profile', $p['slug']);
    $icon = config('faculty.department_icons.' . ($p['department'] ?? ''), 'fa-user');
    $hasVideo = !empty($p['video']) && file_exists(public_path($p['video']));
@endphp
<a href="{{ $href }}" class="pc" data-reveal>
    <span class="pc-photo">
        @include('frontend.landing.partials._img', ['name' => $p['poster'], 'alt' => $p['name'], 'sizes' => '(max-width: 768px) 90vw, 380px'])
        <span class="pc-shade" aria-hidden="true"></span>
        @if ($hasVideo)<span class="pc-video"><i class="fa-solid fa-play"></i> Intro video</span>@endif
        @if (!empty($p['alumnus']))<span class="pc-alumni"><i class="fa-solid fa-graduation-cap"></i> MCC alumnus</span>@endif
    </span>
    <span class="pc-body">
        <span class="pc-dept"><i class="fa-solid {{ $icon }}"></i> {{ $p['department'] }}</span>
        <strong class="pc-name">{{ $p['name'] }}</strong>
        <span class="pc-role">{{ $p['role'] }}</span>
        @if (!empty($p['education'][0]))
            <span class="pc-edu"><i class="fa-solid fa-building-columns"></i> {{ $p['education'][0]['institution'] }}</span>
        @endif
        <span class="pc-more">View profile <i class="fa-solid fa-arrow-right"></i></span>
    </span>
</a>
