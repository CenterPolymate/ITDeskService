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
            <div class="fixed bottom-0 left-0 w-full bg-indigo-600 px-4 py-3 text-white text-center flex items-center justify-center gap-4 z-[100] shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
                <span class="text-sm font-medium">คุณกำลังใช้งานระบบในฐานะ "{{ Auth::user()->name }}" (โหมดจำลองผู้ใช้)</span>
                <form action="{{ route('impersonate.leave') }}" method="POST" class="m-0 p-0">
                    @csrf
                    <button type="submit" class="bg-white text-indigo-600 hover:bg-indigo-50 px-4 py-1.5 rounded-full text-xs font-bold shadow-sm transition-colors border border-transparent hover:border-indigo-200">
                        กลับสู่บัญชีแอดมิน
                    </button>
                </form>
            </div>
        @endif
    </body>
</html>
