@extends('frontend.layouts.app')

@section('title', 'Message from the Principal | Maestro Crown School & College')
@section('meta_description', 'A message from Md. Shoeb Khan, Principal of Maestro Crown School & College, Savar, on academic excellence, discipline and personal growth.')

@php
    $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
    $focus = [
        ['icon' => 'fa-award',         'title' => 'Academic excellence', 'desc' => 'High expectations in every classroom, supported by dedicated teachers.'],
        ['icon' => 'fa-scale-balanced', 'title' => 'Discipline',         'desc' => 'A focused, respectful campus where every student can do their best work.'],
        ['icon' => 'fa-seedling',      'title' => 'Personal growth',     'desc' => 'Confidence, capability and responsibility, built step by step.'],
    ];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['About', route('frontend.about')], ['Message of Principal']],
    'eyebrow' => 'Message of Principal',
    'title'   => 'Education is the foundation of a <em>brighter future</em>',
    'lead'    => 'A message from our Principal, Md. Shoeb Khan, to every student, parent and friend of Maestro Crown School & College.',
    'actions' => [
        ['label' => 'Read the message', 'href' => '#letter', 'icon' => 'fa-arrow-down', 'style' => 'primary'],
        ['label' => 'Meet our teachers', 'href' => route('frontend.teacher_info'), 'style' => 'outline'],
    ],
    'media'   => ['type' => 'portrait', 'image' => 'campus/principal', 'name' => 'Md. Shoeb Khan', 'caption' => 'Principal'],
])

<section class="letter section-pad" id="letter" aria-label="Message">
    <div class="container letter-grid">
        <article class="letter-body prose">
            <p class="salutation" data-reveal>Dear students, parents and friends,</p>

            <p class="lead-p" data-reveal>We are committed to fostering an environment of <mark>academic excellence, discipline and personal growth</mark>.</p>

            <blockquote class="pull" data-reveal>
                <i class="fa-solid fa-quote-left" aria-hidden="true"></i>
                <p>I believe education is the foundation of a brighter future.</p>
            </blockquote>

            <p data-reveal>Our goal is to nurture students into confident, capable and responsible individuals. Through dedication and innovation, we strive to provide quality education and inspire our students to achieve their full potential.</p>

            <div class="focus-grid" data-stagger>
                @foreach ($focus as $f)
                    <div class="focus-card" data-reveal>
                        <span class="focus-icon"><i class="fa-solid {{ $f['icon'] }}"></i></span>
                        <strong>{{ $f['title'] }}</strong>
                        <span>{{ $f['desc'] }}</span>
                    </div>
                @endforeach
            </div>

            <footer class="signature" data-reveal>
                <span class="signature-script" aria-hidden="true">Shoeb Khan</span>
                <strong>Md. Shoeb Khan</strong>
                <span>Principal, Maestro Crown School & College</span>
            </footer>
        </article>

        <aside class="letter-aside">
            <div class="author-card" data-reveal>
                @include('frontend.partials.portrait', ['name' => 'Md. Shoeb Khan', 'image' => 'campus/principal', 'class' => 'author-photo', 'sizes' => '96px'])
                <strong>Md. Shoeb Khan</strong>
                <span>Principal<br>Maestro Crown School & College</span>
                <a href="{{ route('frontend.teacher_info') }}" class="link-arrow">Our teachers <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="commit-card" data-reveal>
                <h3>Our goal for every student</h3>
                <ul>
                    <li><i class="fa-solid fa-user-check"></i> Confident</li>
                    <li><i class="fa-solid fa-screwdriver-wrench"></i> Capable</li>
                    <li><i class="fa-solid fa-hand-holding-heart"></i> Responsible</li>
                </ul>
            </div>
        </aside>
    </div>
</section>

@include('frontend.partials.next-links')
@endsection

@push('scripts')
    <script src="{{ $ver('frontend/mcc/js/landing.js') }}" defer></script>
@endpush
