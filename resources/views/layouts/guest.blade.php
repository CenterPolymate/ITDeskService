<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ITDeskService') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
        <style>
            .bg-animated-gradient {
                background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
                background-size: 400% 400%;
                animation: gradientBG 15s ease infinite;
            }
            @keyframes gradientBG {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            .glassmorphism {
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.3);
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased bg-animated-gradient">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative overflow-hidden">
            <!-- Decorative circles -->
            <div class="absolute top-0 left-0 w-64 h-64 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-3xl transform -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-indigo-400 opacity-20 rounded-full mix-blend-overlay filter blur-3xl transform translate-x-1/3 translate-y-1/3"></div>

            <div class="relative z-10 w-full sm:max-w-md mt-6 px-8 py-10 glassmorphism shadow-2xl overflow-hidden sm:rounded-2xl">
                <div class="flex justify-center mb-6">
                    <a href="/">
                        <x-application-logo class="h-24 w-auto max-w-full object-contain mx-auto drop-shadow-md" />
                    </a>
                </div>
                
                <h2 class="text-center text-2xl font-bold text-gray-800 mb-2">ITDeskService</h2>
                <p class="text-center text-sm text-gray-500 mb-8">
                    @if(request()->routeIs('register'))
                        สมัครสมาชิกเพื่อเข้าใช้งานระบบ
                    @else
                        เข้าสู่ระบบเพื่อดำเนินการต่อ
                    @endif
                </p>
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
