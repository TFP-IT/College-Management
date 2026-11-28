@php
    // Stroke icons, 24px grid. Kept here so every dashboard tile uses one consistent set.
    $paths = [
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18.5 20a6.5 6.5 0 0 0-3-5.5"/>',
        'user-plus' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M19 8v6M16 11h6"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2.5"/><path d="M8 7V5.5A1.5 1.5 0 0 1 9.5 4h5A1.5 1.5 0 0 1 16 5.5V7M3 12.5h18"/>',
        'check' => '<rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2.5v3M16 2.5v3M8.5 14l2.5 2.5 4.5-5"/>',
        'wallet' => '<path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H18v3"/><rect x="3" y="8" width="18" height="12" rx="2.5"/><path d="M16 14h2"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'award' => '<circle cx="12" cy="9" r="6"/><path d="M8.5 14 7 22l5-3 5 3-1.5-8"/>',
        'stamp' => '<path d="M9 13V9.5a3 3 0 1 1 6 0V13M5 13h14v4H5zM6 20h12"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 9.5h8M8 12.5h5"/>',
        'book' => '<path d="M4 4.5A1.5 1.5 0 0 1 5.5 3H19v15H5.5A1.5 1.5 0 0 0 4 19.5z"/><path d="M4 19.5A1.5 1.5 0 0 0 5.5 21H19v-3"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'alert' => '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17h.01"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'school' => '<path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2.5v3M16 2.5v3M3 10h18"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'trend-up' => '<path d="m3 17 6-6 4 4 8-8M15 7h6v6"/>',
        'trend-down' => '<path d="m3 7 6 6 4-4 8 8M15 17h6v-6"/>',
        'chevron' => '<path d="m9 6 6 6-6 6"/>',
    ];
@endphp
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mcc-icon">{!! $paths[$name] ?? $paths['grid'] !!}</svg>
