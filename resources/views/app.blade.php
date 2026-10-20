<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        <title inertia>{{ $branding['app_name'] ?? config('app.name', 'ITDeskService') }}</title>
        <link rel="icon" type="image/png" href="{{ $branding['app_favicon'] ?? asset('images/favicon.png') }}">
        <link rel="shortcut icon" href="{{ $branding['app_favicon'] ?? asset('favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ $branding['app_favicon'] ?? asset('images/favicon.png') }}">

        <!-- PWA & Mobile Web App -->
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="{{ $branding['brand_primary_color'] ?? '#4f46e5' }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ $branding['app_name'] ?? 'ITDesk' }}">

        <style>
            :root {
                --brand-primary: {{ $branding['brand_primary_color'] ?? '#4f46e5' }};
                --brand-primary-hover: color-mix(in srgb, var(--brand-primary, #4f46e5) 85%, black);
                --brand-primary-light: color-mix(in srgb, var(--brand-primary, #4f46e5) 12%, transparent);
            }

            .nav-brand-title {
                color: var(--brand-primary, #4f46e5) !important;
            }

            /* Dynamically adapt primary action buttons to brand color */
            .bg-indigo-600,
            .bg-blue-600 {
                background-color: var(--brand-primary, #4f46e5) !important;
            }
            .hover\:bg-indigo-700:hover,
            .hover\:bg-blue-700:hover {
                background-color: var(--brand-primary-hover, #4338ca) !important;
            }
            .text-indigo-600,
            .text-blue-600 {
                color: var(--brand-primary, #4f46e5) !important;
            }
            .border-indigo-500,
            .border-indigo-600,
            .border-blue-500,
            .border-blue-600 {
                border-color: var(--brand-primary, #4f46e5) !important;
            }
            .focus\:ring-indigo-500:focus,
            .focus\:ring-blue-500:focus {
                --tw-ring-color: var(--brand-primary, #4f46e5) !important;
            }
        </style>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=prompt:300,400,500,600,700|noto-sans-thai:300,400,500,600,700|inter:400,500,600,700|figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <!-- Offline Network Indicator -->
        <div id="offline-network-banner" style="display: none;" class="fixed top-0 left-0 right-0 z-[9999] bg-gradient-to-r from-amber-500 to-orange-500 text-white text-xs font-bold py-2 px-4 shadow-md text-center flex items-center justify-center gap-2 transition-all duration-300">
            <svg class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>⚠️ สัญญาณอินเทอร์เน็ตขาดหาย กำลังรอเชื่อมต่อใหม่... (Offline Reconnecting)</span>
        </div>
        <script>
            (function() {
                function updateOnlineStatus() {
                    var el = document.getElementById('offline-network-banner');
                    if (!el) return;
                    if (navigator.onLine) {
                        el.style.display = 'none';
                    } else {
                        el.style.display = 'flex';
                    }
                }
                window.addEventListener('online', updateOnlineStatus);
                window.addEventListener('offline', updateOnlineStatus);
                if (!navigator.onLine) {
                    updateOnlineStatus();
                }
            })();
        </script>
        @auth
            @if(Auth::user()->hasVerifiedEmail())
                <div class="min-h-screen bg-gray-100 pb-12">
                    @include('layouts.navigation')

                    <!-- Page Content -->
                    <main>
                        @inertia
                    </main>
                </div>
            @else
                @inertia
            @endif

            @if(session()->has('impersonated_by'))
                <div class="fixed bottom-4 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 bg-slate-900/95 backdrop-blur-md px-4 py-2.5 rounded-full shadow-2xl border border-slate-700 w-max max-w-[90vw]">
                    <div class="flex items-center gap-2 truncate">
                        <span class="relative flex h-2 w-2 shrink-0">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="text-xs font-medium text-slate-200 truncate">จำลอง: <span class="text-white font-bold">{{ Auth::user()->name }}</span></span>
                    </div>
                    <div class="w-px h-4 bg-slate-700 shrink-0"></div>
                    <form action="{{ route('impersonate.leave') }}" method="POST" class="m-0 p-0 shrink-0">
                        @csrf
                        <button type="submit" class="text-rose-400 hover:text-rose-300 text-xs font-bold transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            ออก
                        </button>
                    </form>
                </div>
            @endif
        @else
            @inertia
        @endauth
    </body>
</html>
