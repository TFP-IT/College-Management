<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admissions Council & Staff') - MCC</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased">
    <!-- Admin Header -->
    <header class="bg-slate-900 text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admissions.admin.dashboard') }}" class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-lg">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <span class="text-lg font-bold text-white tracking-tight">MCC Admission Council</span>
                            <span class="ml-2 text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 bg-blue-500/20 text-blue-300 rounded border border-blue-400/30">Staff Console</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="{{ route('admissions.index') }}" target="_blank" class="text-xs text-slate-300 hover:text-white flex items-center">
                        <i class="fa-solid fa-arrow-up-right-from-square mr-1 text-[10px]"></i> View Public Portal
                    </a>
                    <span class="h-4 w-px bg-slate-700"></span>
                    <div class="flex items-center space-x-2 text-xs text-slate-300">
                        <i class="fa-regular fa-circle-user text-base text-blue-400"></i>
                        <span>{{ auth()->user()?->name ?? 'Staff User' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Layout: sidebar + content -->
    <div class="flex flex-1 w-full max-w-7xl mx-auto">
        @php
            $user = auth()->user();
            $can = fn (string $p) => $user && $user->can($p);
            $link = fn (bool $active) => $active
                ? 'bg-brand-50 text-brand-700 border-brand-600'
                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-transparent';
        @endphp

        <aside class="w-full md:w-60 shrink-0 bg-white border-r border-slate-200 md:min-h-[calc(100vh-4rem)]">
            <nav class="p-4 space-y-6 text-sm" aria-label="Staff navigation">

                @if($can('admission.application.view') || $can('admission.report.view'))
                <div>
                    <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Admissions</p>
                    <a href="{{ route('admissions.admin.dashboard') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg border-l-4 font-medium {{ $link(request()->routeIs('admissions.admin.*')) }}">
                        <i class="fa-solid fa-table-cells w-4"></i> Applications
                    </a>
                    <a href="{{ route('admissions.index') }}" target="_blank"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg border-l-4 border-transparent text-slate-600 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-up-right-from-square w-4"></i> Public admissions page
                    </a>
                </div>
                @endif

                @if($can('cms.manage') || $can('admission.program.manage'))
                <div>
                    <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Website content</p>
                    <a href="{{ route('cms.admin.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg border-l-4 font-medium {{ $link(request()->routeIs('cms.admin.index')) }}">
                        <i class="fa-solid fa-newspaper w-4"></i> Overview
                    </a>
                    @if($can('cms.manage'))
                        @foreach(['notices' => 'Notices', 'gallery' => 'Gallery', 'staff' => 'Governing body & staff', 'pages' => 'Editable pages'] as $key => $label)
                            <a href="{{ route('cms.admin.list', $key) }}"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg border-l-4 {{ $link(request()->is("admin/cms/{$key}*")) }}">
                                <span class="w-4"></span> {{ $label }}
                            </a>
                        @endforeach
                    @endif
                    @if($can('admission.program.manage'))
                        <a href="{{ route('cms.admin.list', 'admission-programs') }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-lg border-l-4 {{ $link(request()->is('admin/cms/admission-programs*')) }}">
                            <span class="w-4"></span> Admission programmes
                        </a>
                    @endif
                </div>
                @endif

                <div>
                    <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Website</p>
                    <a href="{{ url('/') }}" target="_blank"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg border-l-4 border-transparent text-slate-600 hover:bg-slate-50">
                        <i class="fa-solid fa-globe w-4"></i> View school website
                    </a>
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <p class="px-3 text-xs text-slate-500 mb-2">Signed in as <span class="font-semibold text-slate-700">{{ $user?->name ?? 'Staff' }}</span></p>
                    @if($user)
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-lg text-rose-600 hover:bg-rose-50">
                                <i class="fa-solid fa-right-from-bracket w-4"></i> Sign out
                            </button>
                        </form>
                    @endif
                </div>
            </nav>
        </aside>

        <main class="flex-1 min-w-0 px-4 sm:px-6 lg:px-8 py-6">
            <!-- Flash Messages -->
            @if(session('success') || session('status'))
                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center shadow-sm" role="status">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-lg mr-3"></i>
                    <div>{{ session('success') ?? session('status') }}</div>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 flex items-center shadow-sm" role="alert">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg mr-3"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

</body>
</html>
