{{--
    Faculty / staff card. @include('frontend.partials.person-card', ['p' => $member, 'compact' => false])
    - No photo yet → elegant initials placeholder (drop a photo into config/faculty.php → poster).
    - Teachers always link to their profile; staff link only when there is something to show.
--}}
@php
    $isStaff = ($p['group'] ?? 'teacher') === 'staff';
    $hasPoster = !empty($p['poster']);
    $hasVideo = !empty($p['video']) && file_exists(public_path($p['video']));
    $linkable = !$isStaff || $hasPoster || $hasVideo || !empty($p['bio']);
    $href = $linkable ? route($isStaff ? 'frontend.staff_profile' : 'frontend.teacher_profile', $p['slug']) : null;
    $dept = $p['department'] ?? null;
    $icon = $dept ? config('faculty.department_icons.' . $dept, 'fa-user') : ($isStaff ? 'fa-id-badge' : 'fa-chalkboard-user');
    $tagName = $href ? 'a' : 'div';
@endphp
<{{ $tagName }} @if($href) href="{{ $href }}" @endif class="pc {{ $hasPoster ? '' : 'pc--placeholder' }} {{ $href ? '' : 'pc--static' }}" data-reveal>
    <span class="pc-photo">
        @if ($hasPoster)
            @include('frontend.landing.partials._img', ['name' => $p['poster'], 'alt' => $p['name'], 'sizes' => '(max-width: 768px) 90vw, 380px'])
        @else
            @include('frontend.partials.portrait', ['name' => $p['name'], 'class' => 'pc-portrait'])
            <span class="pc-soon">Photo coming soon</span>
        @endif
        <span class="pc-shade" aria-hidden="true"></span>
        @if ($hasVideo)<span class="pc-video"><i class="fa-solid fa-play"></i> Intro video</span>@endif
        @if (!empty($p['alumnus']))<span class="pc-alumni"><i class="fa-solid fa-graduation-cap"></i> MCC alumnus</span>@endif
    </span>
    <span class="pc-body">
        <span class="pc-dept"><i class="fa-solid {{ $icon }}"></i> {{ $dept ?? ($isStaff ? 'Staff' : 'Faculty') }}</span>
        <strong class="pc-name">{{ $p['name'] }}</strong>
        <span class="pc-role">{{ $p['role'] }}</span>
        @if (!empty($p['education'][0]))
            <span class="pc-edu"><i class="fa-solid fa-building-columns"></i> {{ $p['education'][0]['institution'] }}</span>
        @endif
        @if ($href)<span class="pc-more">View profile <i class="fa-solid fa-arrow-right"></i></span>@endif
    </span>
</{{ $tagName }}>
