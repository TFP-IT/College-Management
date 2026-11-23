{{--
    Person photo with an elegant initials placeholder.
    @include('frontend.partials.portrait', ['name' => 'Md. Shoeb Khan', 'image' => 'campus/principal', 'url' => null, 'class' => '', 'sizes' => '200px'])
    - image: local file under public/frontend/mcc/img (no extension). Drop the file in later and it appears automatically.
    - url:   an absolute photo URL (e.g. a CMS upload). Wins over image.
--}}
@php
    $localImage = null;
    if (empty($url) && !empty($image)) {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (file_exists(public_path('frontend/mcc/img/' . $image . '.' . $ext))) { $localImage = $image; break; }
        }
    }
    $words = collect(preg_split('/\s+/', trim(preg_replace('/^(Dr|Md|Mrs|Mr|Ms|Prof)\.?\s+/i', '', (string) $name))))->filter();
    $initials = mb_strtoupper($words->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: 'MC';
@endphp
<span class="portrait {{ $class ?? '' }} {{ (empty($url) && !$localImage) ? 'portrait--placeholder' : '' }}">
    @if (!empty($url))
        <img src="{{ $url }}" alt="{{ $name }}" loading="lazy" decoding="async">
    @elseif ($localImage)
        @include('frontend.landing.partials._img', ['name' => $localImage, 'alt' => $name, 'sizes' => $sizes ?? '240px'])
    @else
        <span class="portrait-initials" aria-hidden="true">{{ $initials }}</span>
        <span class="sr-only">{{ $name }}</span>
    @endif
</span>
