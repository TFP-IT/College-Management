@extends('frontend.layouts.app')

@section('title', 'Notice Board | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Notice Board</h1>
        <p>Latest announcements, updates, and news from Maestro Crown College.</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card">
            <div style="border-bottom: 2px solid var(--gold); padding-bottom: 15px; margin-bottom: 25px;">
                <h2 style="color: var(--navy); margin: 0;">Latest Notices</h2>
            </div>

            @forelse($notices as $notice)
                <article style="border: 1px solid var(--gray-lt); border-radius: 8px; padding: 20px; margin-bottom: 20px; display: flex; gap: 20px;">
                    <div style="flex-shrink: 0; width: 70px; height: 70px; background: var(--navy); color: white; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <span style="font-size: 1.5rem; font-weight: 700; line-height: 1;">{{ ($notice->publish_on ?? $notice->created_at)->format('d') }}</span>
                        <span style="font-size: .9rem; text-transform: uppercase;">{{ ($notice->publish_on ?? $notice->created_at)->format('M') }}</span>
                    </div>
                    <div style="flex-grow: 1;">
                        <h3 style="margin: 0 0 10px 0; font-size: 1.2rem; color: var(--navy);">{{ $notice->title }}</h3>
                        <p style="margin: 0; color: var(--gray-dk); white-space: pre-line;">{{ $notice->body }}</p>
                    </div>
                </article>
            @empty
                <p style="text-align: center; color: var(--gray); padding: 40px 0;">No notices right now. Please check back soon.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
