{{-- Google Play + App Store badges. App Store shows "Coming soon" until config('landing.app.app_store') is set. --}}
@php $appCfg = config('landing.app'); @endphp
<div class="store-row {{ $class ?? '' }}">
    <a class="store-badge" href="{{ $appCfg['play_store'] }}" target="_blank" rel="noopener" data-magnetic aria-label="Get {{ $appCfg['name'] }} on Google Play">
        <svg viewBox="0 0 512 512" width="26" height="26" aria-hidden="true"><path fill="#34A853" d="M48 59.5v393c0 7.6 4.3 14.2 10.7 17.4L285 256 58.7 42.1C52.3 45.3 48 51.9 48 59.5z"/><path fill="#FBBC04" d="M369.6 340.6 285 256l84.6-84.6 95.6 54.6c19.7 11.3 19.7 39.6 0 50.9l-95.6 54.6z"/><path fill="#4285F4" d="M285 256 58.7 469.9c5.9 3 13.1 3.1 19.4-.5l291.5-128.8L285 256z"/><path fill="#EA4335" d="M285 256 369.6 171.4 78.1 42.6c-6.3-3.6-13.5-3.5-19.4-.5L285 256z"/></svg>
        <span><small>GET IT ON</small>Google Play</span>
    </a>
    @if (!empty($appCfg['app_store']))
        <a class="store-badge" href="{{ $appCfg['app_store'] }}" target="_blank" rel="noopener" data-magnetic aria-label="Download {{ $appCfg['name'] }} on the App Store">
            <i class="fa-brands fa-apple" aria-hidden="true"></i>
            <span><small>Download on the</small>App Store</span>
        </a>
    @else
        <span class="store-badge is-soon" aria-label="{{ $appCfg['name'] }} on the App Store: coming soon">
            <i class="fa-brands fa-apple" aria-hidden="true"></i>
            <span><small>Coming soon on the</small>App Store</span>
        </span>
    @endif
</div>
