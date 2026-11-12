<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-stone-50/60 text-stone-900 antialiased">
        <header class="sticky top-0 z-40 border-b-2 border-amber-600 bg-gradient-to-r from-[#211c18] via-[#2d221a] to-[#3b2b1b] shadow-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-3.5 py-2.5 sm:px-6 sm:py-3.5 lg:px-8">
                <div class="flex items-center gap-2.5 text-white select-none cursor-default">
                    <img src="{{ asset('images/rapi-logo.png') }}" alt="Logo RAPI" class="h-8 w-8 sm:h-9 sm:w-9 rounded-lg bg-white p-0.5 object-contain shadow-xs">
                    <div>
                        <span class="text-base sm:text-xl font-extrabold tracking-tight text-amber-100">Kalender Sekolah</span>
                        <p class="hidden text-[11px] font-medium text-amber-200/70 sm:block">Sistem Peminjaman & Agenda Ruang RAPI</p>
                    </div>
                </div>
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-[38px] sm:min-h-[42px] items-center justify-center gap-1.5 rounded-lg sm:rounded-xl border border-amber-400/50 bg-amber-600 px-3.5 py-1.5 sm:px-5 sm:py-2 text-xs sm:text-sm font-bold text-white shadow-xs transition hover:bg-amber-700 active:scale-95 touch-manipulation">
                        <span>Dashboard</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex min-h-[38px] sm:min-h-[42px] items-center justify-center gap-1.5 rounded-lg sm:rounded-xl border border-amber-400/50 bg-amber-600 px-3.5 py-1.5 sm:px-5 sm:py-2 text-xs sm:text-sm font-bold text-white shadow-xs transition hover:bg-amber-700 active:scale-95 touch-manipulation">
                        <span>Masuk</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                @endauth
            </div>
        </header>

        <main class="flex-1 w-full">
            @yield('content')
        </main>
        <x-site-footer />
    </body>
</html>