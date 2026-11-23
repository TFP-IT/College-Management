{{-- "Continue exploring" cards. @include(..., ['set' => 'about'|'academics']); the current page is left out. --}}
@php
    $aboutPages = [
        ['route' => 'frontend.about',             'kicker' => 'Our story',  'title' => 'About MCC',              'avatar' => ['icon' => 'fa-landmark']],
        ['route' => 'frontend.chairman_profile',  'kicker' => 'Leadership', 'title' => 'Chairman’s Profile',     'avatar' => ['image' => 'campus/chairman-face', 'name' => 'Dr. Kamrul Ahsan']],
        ['route' => 'frontend.message_chairman',  'kicker' => 'Leadership', 'title' => 'Message from the Chairman', 'avatar' => ['image' => 'campus/chairman-face', 'name' => 'Dr. Kamrul Ahsan']],
        ['route' => 'frontend.message_principal', 'kicker' => 'Leadership', 'title' => 'Message from the Principal', 'avatar' => ['image' => 'campus/principal', 'name' => 'Md. Shoeb Khan']],
        ['route' => 'frontend.governing_body',    'kicker' => 'Governance', 'title' => 'Governing Body',         'avatar' => ['icon' => 'fa-sitemap']],
        ['route' => 'frontend.teacher_info',      'kicker' => 'Our people', 'title' => 'Teachers',               'avatar' => ['icon' => 'fa-chalkboard-user']],
        ['route' => 'frontend.staff_info',        'kicker' => 'Our people', 'title' => 'Staff',                  'avatar' => ['icon' => 'fa-id-badge']],
    ];
    $academicPages = [
        ['route' => 'frontend.programs',          'kicker' => 'Academics', 'title' => 'Programs & curriculum', 'avatar' => ['icon' => 'fa-layer-group']],
        ['route' => 'frontend.academic_calendar', 'kicker' => 'Academics', 'title' => 'Academic Calendar',     'avatar' => ['icon' => 'fa-calendar-days']],
        ['route' => 'frontend.notice_board',      'kicker' => 'Academics', 'title' => 'Notice Board',          'avatar' => ['icon' => 'fa-bullhorn']],
        ['route' => 'frontend.admission',         'kicker' => 'Admission', 'title' => 'Admission information', 'avatar' => ['icon' => 'fa-circle-info']],
        ['route' => 'frontend.teacher_info',      'kicker' => 'Our people', 'title' => 'Teachers',             'avatar' => ['icon' => 'fa-chalkboard-user']],
    ];
    $pool = ($set ?? 'about') === 'academics' ? $academicPages : $aboutPages;
    $links = collect($pool)->reject(fn ($p) => request()->routeIs($p['route']))->take($limit ?? 3);
@endphp
<section class="next section-pad" aria-labelledby="next-title">
    <div class="container">
        <h2 class="next-title" id="next-title" data-reveal>Continue exploring</h2>
        <div class="next-grid" data-stagger>
            @foreach ($links as $l)
                <a href="{{ route($l['route']) }}" class="next-card" data-reveal>
                    @if (!empty($l['avatar']['icon']))
                        <span class="next-avatar next-avatar--icon"><i class="fa-solid {{ $l['avatar']['icon'] }}"></i></span>
                    @else
                        @include('frontend.partials.portrait', ['name' => $l['avatar']['name'], 'image' => $l['avatar']['image'], 'class' => 'next-avatar', 'sizes' => '60px'])
                    @endif
                    <span><small>{{ $l['kicker'] }}</small><strong>{{ $l['title'] }}</strong></span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            @endforeach
        </div>
    </div>
</section>
