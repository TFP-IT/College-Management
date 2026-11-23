@extends('frontend.layouts.app')

@section('title', 'Contact Us | Maestro Crown College, Savar')
@section('meta_description', 'Contact Maestro Crown College, 107/2 Dogormura, Radio Colony, Dhaka–Aricha Highway, Savar, Dhaka 1343. Phone +880 1309-136876, email admin@maestrocrown.edu.bd.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $mc = config('landing');
    $c = $mc['contact'];
    $app = $mc['app'];
    $topics = [
        ['icon' => 'fa-file-signature', 'title' => 'Admission',            'desc' => 'Apply and track your application in the app, or call our admission office for guidance.', 'link' => [$app['play_store'], 'Get the app', true]],
        ['icon' => 'fa-people-roof',    'title' => 'Students & guardians', 'desc' => 'Attendance, results, fees and notices are in your portal in the ' . $app['name'] . ' app.', 'link' => [$app['play_store'], 'Open the app', true]],
        ['icon' => 'fa-bullhorn',       'title' => 'Notices & schedules',  'desc' => 'Exam routines, holidays and announcements are published on our Notice Board.', 'link' => [route('frontend.notice_board'), 'Notice Board', false]],
        ['icon' => 'fa-handshake',      'title' => 'General enquiries',    'desc' => 'Partnerships, media and anything else: send us an email and we’ll get back to you.', 'link' => ['mailto:' . $c['email'], 'Email us', false]],
    ];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['Contact']],
    'eyebrow' => 'Contact us',
    'title'   => 'We’d love to <em>hear from you</em>',
    'lead'    => 'Questions about admission, your child’s progress or a visit to campus? Call, email or drop by. Our office in Savar is always happy to help.',
    'chips'   => ['Savar, Dhaka', 'EIIN 136876', 'College Code 1933'],
    'actions' => [
        ['label' => 'Call ' . $c['phone'], 'href' => $c['phone_href'], 'icon' => 'fa-phone', 'style' => 'primary'],
        ['label' => 'Get directions', 'href' => $c['map_link'], 'icon' => 'fa-diamond-turn-right', 'style' => 'outline', 'external' => true],
    ],
    'media'   => ['type' => 'collage', 'images' => ['campus/campus-front', 'campus/students-3']],
])

{{-- Contact cards --}}
<section class="stat-band-wrap" aria-label="Contact details">
    <div class="container">
        <ul class="ct-cards" data-stagger>
            <li data-reveal>
                <a href="{{ $c['map_link'] }}" target="_blank" rel="noopener">
                    <span class="ct-icon"><i class="fa-solid fa-location-dot"></i></span>
                    <small>Visit us</small>
                    <strong>{{ $c['address'] }}</strong>
                </a>
            </li>
            <li data-reveal>
                <a href="{{ $c['phone_href'] }}">
                    <span class="ct-icon"><i class="fa-solid fa-phone"></i></span>
                    <small>Call us</small>
                    <strong>{{ $c['phone'] }}</strong>
                </a>
            </li>
            <li data-reveal>
                <a href="mailto:{{ $c['email'] }}">
                    <span class="ct-icon"><i class="fa-solid fa-envelope"></i></span>
                    <small>Email us</small>
                    <strong>{{ $c['email'] }}</strong>
                </a>
            </li>
            <li data-reveal>
                <a href="{{ $c['facebook'] }}" target="_blank" rel="noopener">
                    <span class="ct-icon"><i class="fa-brands fa-facebook-f"></i></span>
                    <small>Follow us</small>
                    <strong>@Maestrocrown</strong>
                </a>
            </li>
        </ul>
    </div>
</section>

{{-- Message + map --}}
<section class="ct-main section-pad" aria-label="Send a message and find us">
    <div class="container ct-grid">
        <div class="ct-form-card" data-reveal>
            <span class="eyebrow">Send a message</span>
            <h2 class="sec-title">How can we <em>help?</em></h2>
            <p class="ct-form-note"><i class="fa-solid fa-circle-info"></i> Your message opens in your email app, ready to send to {{ $c['email'] }}.</p>
            <form class="ct-form" action="mailto:{{ $c['email'] }}" method="post" enctype="text/plain" data-mail-form data-to="{{ $c['email'] }}">
                <div class="ct-row">
                    <label><span>Your name</span><input type="text" name="name" required autocomplete="name" placeholder="Full name"></label>
                    <label><span>Phone</span><input type="tel" name="phone" autocomplete="tel" placeholder="01XXX-XXXXXX"></label>
                </div>
                <label><span>I’m asking about</span>
                    <select name="topic">
                        <option>School admission</option>
                        <option>College (HSC) admission</option>
                        <option>Student or guardian portal</option>
                        <option>Fees & scholarships</option>
                        <option>Something else</option>
                    </select>
                </label>
                <label><span>Message</span><textarea name="message" rows="5" required placeholder="Write your question here"></textarea></label>
                <button type="submit" class="btn btn-primary btn-lg" data-magnetic><i class="fa-solid fa-paper-plane"></i> Send message</button>
            </form>
        </div>

        <div class="ct-map-card" data-reveal>
            <div class="ct-map">
                <iframe src="{{ $c['map_embed'] }}" title="Maestro Crown College on Google Maps" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
            <div class="ct-map-foot">
                <div>
                    <strong>Maestro Crown College</strong>
                    <span>{{ $c['address'] }}</span>
                </div>
                <a href="{{ $c['map_link'] }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><i class="fa-solid fa-diamond-turn-right"></i> Directions</a>
            </div>
        </div>
    </div>
</section>

{{-- Help topics --}}
<section class="ct-topics section-pad" aria-labelledby="ct-topics-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">Find the right help</span>
            <h2 class="sec-title" id="ct-topics-title">The fastest way to <em>get an answer</em></h2>
        </header>
        <div class="card-grid card-grid--4" data-stagger>
            @foreach ($topics as $t)
                <article class="num-card topic-card" data-reveal>
                    <span class="num-card-icon"><i class="fa-solid {{ $t['icon'] }}"></i></span>
                    <h3>{{ $t['title'] }}</h3>
                    <p>{{ $t['desc'] }}</p>
                    <a href="{{ $t['link'][0] }}" class="link-arrow" @if($t['link'][2]) target="_blank" rel="noopener" @endif>{{ $t['link'][1] }} <i class="fa-solid fa-arrow-right"></i></a>
                </article>
            @endforeach
        </div>
    </div>
</section>

@include('frontend.partials.cta-band')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
