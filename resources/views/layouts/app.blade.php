<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 pb-12">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        
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
    </body>
</html>
