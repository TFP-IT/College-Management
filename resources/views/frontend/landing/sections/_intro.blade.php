{{-- Branded intro curtain: pure CSS, plays once per session (see inline script). --}}
<div class="mc-intro" aria-hidden="true">
    <div class="mc-intro-panel"></div>
    <div class="mc-intro-panel mc-intro-panel--gold"></div>
    <div class="mc-intro-mark">
        <img src="{{ asset('frontend/mcc/img/logo-256.png') }}" alt="" width="96" height="96">
        <span>Maestro Crown School & College</span>
    </div>
</div>
<script>
    (function () {
        var d = document.documentElement;
        try {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || sessionStorage.getItem('mc-intro')) return;
            sessionStorage.setItem('mc-intro', '1');
            d.classList.add('intro-play');
        } catch (e) {}
    })();
</script>
