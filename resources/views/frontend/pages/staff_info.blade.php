@extends('frontend.layouts.app')

@section('title', 'Our Staff | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Our Staff</h1>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card">
            @if($members->isEmpty())
                <p style="text-align: center; color: var(--gray); padding: 40px 0;">Information is being updated. Please check back soon.</p>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                    @foreach($members as $member)
                        <div style="background: var(--off-white); border: 1px solid var(--gray-lt); border-radius: 12px; padding: 24px; text-align: center;">
                            @if($member->photo_url)
                                <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin: 0 auto 16px;">
                            @else
                                <div style="width: 80px; height: 80px; background: var(--navy); color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            @endif
                            <h3 style="margin: 0 0 4px; font-size: 1.1rem;">{{ $member->name }}</h3>
                            @if($member->designation)
                                <p style="color: var(--gold); font-weight: 600; margin: 0; font-size: .9rem;">{{ $member->designation }}</p>
                            @endif
                            @if($member->department)
                                <p style="color: var(--gray-dk); margin: 4px 0 0; font-size: .85rem;">{{ $member->department }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
