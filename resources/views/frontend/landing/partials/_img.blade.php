{{--
    Responsive campus image: WebP (with a 720px "-sm" variant when present) + JPEG fallback.
    @include('frontend.landing.partials._img', [
        'name'  => 'campus/classroom-hall-1',   // path under public/frontend/mcc/img, no extension
        'alt'   => 'Students in a classroom',
        'class' => '',
        'sizes' => '(max-width: 768px) 100vw, 50vw',
        'eager' => false,
    ])
--}}
@php
    $base = 'frontend/mcc/img/' . $name;
    $jpg = file_exists(public_path($base . '.jpg')) ? $base . '.jpg' : $base . '.jpeg';
    $hasWebp = file_exists(public_path($base . '.webp'));
    $hasSm = file_exists(public_path($base . '-sm.webp'));
    [$w, $h] = @getimagesize(public_path($jpg)) ?: [1200, 800];
    $srcset = $hasSm
        ? asset($base . '-sm.webp') . ' 720w, ' . asset($base . '.webp') . ' ' . $w . 'w'
        : ($hasWebp ? asset($base . '.webp') : null);
@endphp
<picture>
    @if ($srcset)<source type="image/webp" srcset="{{ $srcset }}" @if($hasSm) sizes="{{ $sizes ?? '(max-width: 768px) 100vw, 50vw' }}" @endif>@endif
    <img src="{{ asset($jpg) }}" alt="{{ $alt ?? '' }}" width="{{ $w }}" height="{{ $h }}"
         class="{{ $class ?? '' }}" decoding="async" @if(empty($eager)) loading="lazy" @else fetchpriority="high" @endif>
</picture>
