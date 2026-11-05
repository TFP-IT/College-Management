@extends('frontend.layouts.app')

@section('title', 'Gallery | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Gallery</h1>
        <p>Explore our campus moments and vibrant college life.</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card">
            @if($items->isEmpty())
                <div style="text-align: center; padding: 60px 20px;">
                    <i class="fa-solid fa-images" style="font-size: 4rem; color: var(--gray-lt);"></i>
                    <h3 style="color: var(--navy); margin: 20px 0 10px;">Gallery updating soon</h3>
                    <p style="color: var(--gray);">We are curating our best campus moments. Please check back later.</p>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
                    @foreach($items as $item)
                        <figure style="margin: 0;">
                            <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy" style="width: 100%; height: 180px; object-fit: cover; border-radius: 8px;">
                            <figcaption style="font-size: .9rem; color: var(--gray-dk); margin-top: 6px;">{{ $item->title }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
