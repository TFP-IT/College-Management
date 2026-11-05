<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admissions') - MCC Hello Future</title>
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
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admissions.index') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-800 to-blue-600 flex items-center justify-center text-white font-bold text-xl shadow-md group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div>
                            <span class="text-xl font-black tracking-tight text-slate-900">MCC</span>
                            <span class="ml-1 text-xs font-semibold uppercase tracking-wider px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full">Hello Future</span>
                            <p class="text-[11px] text-slate-500 font-medium">Admission & Onboarding Engine</p>
                        </div>
                    </a>
                </div>

                <!-- Nav links -->
                <nav class="flex items-center space-x-4">
                    <a href="{{ route('admissions.index') }}" class="text-sm font-medium text-slate-700 hover:text-brand-600 transition-colors">Programs</a>
                    <a href="{{ route('admissions.track') }}" class="text-sm font-medium text-slate-700 hover:text-brand-600 transition-colors">
                        <i class="fa-solid fa-magnifying-glass mr-1 text-xs text-slate-400"></i> Track Application
                    </a>
                    <a href="{{ route('admissions.admin.dashboard') }}" class="inline-flex items-center px-3.5 py-1.5 text-xs font-medium rounded-lg text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 transition-colors">
                        <i class="fa-solid fa-lock mr-1.5"></i> Council / Staff Portal
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 w-full">
        @if(session('success'))
            <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center shadow-sm">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg mr-3"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 flex items-center shadow-sm">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg mr-3"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 shadow-sm">
                <div class="flex items-center font-semibold mb-1">
                    <i class="fa-solid fa-circle-xmark text-rose-600 text-lg mr-2"></i> Please correct the following errors:
                </div>
                <ul class="list-disc list-inside ml-5 space-y-1 text-xs">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-auto py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center text-xs text-slate-500 gap-4">
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 rounded-md bg-brand-800 flex items-center justify-center text-white text-xs">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <span>&copy; {{ date('Y') }} MCC — Modern Cambridge College & School. All rights reserved.</span>
            </div>
            <div class="flex items-center space-x-6">
                <span>Admissions Hotline: <strong class="text-slate-800">+880 1700-000000</strong></span>
                <span>Support: <strong class="text-slate-800">admissions@mcc.edu.bd</strong></span>
            </div>
        </div>
    </footer>
</body>
</html>
