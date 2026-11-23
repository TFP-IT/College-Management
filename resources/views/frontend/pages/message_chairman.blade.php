@extends('frontend.layouts.app')

@section('title', 'Message from the Chairman | Maestro Crown College')
@section('meta_description', 'A message from Dr. Kamrul Ahsan, Founder and Chairman of Maestro Crown College, on why MCC was founded and what it stands for: mastery, honour, character and fairness.')

@php $ver = fn (string $path) => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1'); @endphp

@push('styles')
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/motion.css') }}">
    <link rel="stylesheet" href="{{ $ver('frontend/mcc/css/pages.css') }}">
@endpush

@section('content')
@include('frontend.partials.page-hero', [
    'crumbs'  => [['About', route('frontend.about')], ['Message of Chairman']],
    'eyebrow' => 'Message of Chairman',
    'title'   => 'A message from our <em>Founder</em>',
    'lead'    => 'Why someone who dreamed of becoming a teacher built a college, and what that promise means for every student who walks through our doors.',
    'actions' => [
        ['label' => 'Read the message', 'href' => '#letter', 'icon' => 'fa-arrow-down', 'style' => 'primary'],
        ['label' => 'Chairman’s profile', 'href' => route('frontend.chairman_profile'), 'style' => 'outline'],
    ],
    'media'   => ['type' => 'portrait', 'image' => 'campus/chairman', 'name' => 'Dr. Kamrul Ahsan', 'caption' => 'Founder & Chairman'],
])

<section class="letter section-pad" id="letter" aria-label="Message">
    <div class="container letter-grid">
        <article class="letter-body prose">
            <p class="dropcap" data-reveal>From an early age, while many aspired to become doctors, engineers or business leaders, my ambition was different. I wanted to become a teacher.</p>

            <blockquote class="pull" data-reveal>
                <i class="fa-solid fa-quote-left" aria-hidden="true"></i>
                <p>Shaping minds and guiding young people is one of the most powerful ways to build a nation.</p>
            </blockquote>

            <p data-reveal>It is from this conviction that Maestro Crown College was established. Our name carries our purpose:</p>

            <div class="name-cards" data-reveal>
                <div><strong>Maestro</strong><span>represents mastery and excellence.</span></div>
                <div><strong>Crown</strong><span>symbolises honour and distinction.</span></div>
            </div>

            <p data-reveal>Our vision is simple yet profound: teachers who possess true expertise in their subjects earn the highest respect and serve as guiding lights in their students’ lives.</p>

            <h2 data-reveal>Teachers who shape character</h2>
            <p data-reveal>At Maestro Crown College, we have assembled a dynamic team of talented, dedicated educators, many of whom graduated from leading public universities. Beyond academic instruction, our focus extends to character formation, ethical values, discipline and responsible citizenship. <mark>Education must shape both intellect and integrity.</mark></p>

            <h2 data-reveal>A campus built on fairness</h2>
            <p data-reveal>We are committed to providing a modern learning environment supported by strong infrastructure and contemporary facilities. At the same time, we maintain a firm stance on academic integrity. Education must be transparent, disciplined and free from corruption. Our institution stands against practices that undermine merit and fairness, and we remain unwavering in our commitment to discipline and accountability.</p>

            <p data-reveal>Maestro Crown College continues its journey with determination and purpose, striving to prepare students who are confident, principled and capable of contributing meaningfully to society and the nation.</p>

            <footer class="signature" data-reveal>
                <span class="signature-script" aria-hidden="true">Kamrul Ahsan</span>
                <strong>Dr. Kamrul Ahsan</strong>
                <span>Founder &amp; Chairman, Maestro Crown College</span>
            </footer>
        </article>

        <aside class="letter-aside">
            <div class="author-card" data-reveal>
                @include('frontend.partials.portrait', ['name' => 'Dr. Kamrul Ahsan', 'image' => 'campus/chairman-face', 'class' => 'author-photo', 'sizes' => '96px'])
                <strong>Dr. Kamrul Ahsan</strong>
                <span>Founder &amp; Chairman<br>Kamrul Group · Maestro Crown College</span>
                <a href="{{ route('frontend.chairman_profile') }}" class="link-arrow">Full profile <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="commit-card" data-reveal>
                <h3>Our commitments</h3>
                <ul>
                    <li><i class="fa-solid fa-chalkboard-user"></i> Teachers with true subject mastery</li>
                    <li><i class="fa-solid fa-heart"></i> Character, ethics and discipline</li>
                    <li><i class="fa-solid fa-building-columns"></i> Modern infrastructure and facilities</li>
                    <li><i class="fa-solid fa-scale-balanced"></i> Zero tolerance for unfair practices</li>
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
