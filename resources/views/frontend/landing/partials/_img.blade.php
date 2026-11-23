{{--
    Responsive campus image with WebP + JPEG fallback.
    @include('frontend.landing.partials._img', ['name' => 'library', 'alt' => '...', 'class' => '', 'eager' => false])
--}}
@php
    $base = 'frontend/mcc/img/' . $name;
    $jpg = file_exists(public_path($base . '.jpg')) ? $base . '.jpg' : $base . '.jpeg';
    $hasWebp = file_exists(public_path($base . '.webp'));
    [$w, $h] = @getimagesize(public_path($jpg)) ?: [1200, 800];
@endphp
<picture>
    @if ($hasWebp)<source srcset="{{ asset($base . '.webp') }}" type="image/webp">@endif
    <img src="{{ asset($jpg) }}" alt="{{ $alt ?? '' }}" width="{{ $w }}" height="{{ $h }}"
         class="{{ $class ?? '' }}" decoding="async" @if(empty($eager)) loading="lazy" @else fetchpriority="high" @endif>
</picture>
