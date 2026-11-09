<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body @class([
        'font-sans antialiased text-slate-800 bg-gray-100 min-h-screen',
        'admin-theme' => auth()->check() && (
            str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'admin')
            || str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'waka')
            || str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'guru')
            || str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'pic')
        ),
    ])>
        <div 
            x-data="{ 
                sidebarOpen: window.innerWidth >= 1024,
                isMobile: window.innerWidth < 1024
            }" 
            @resize.window="
                isMobile = window.innerWidth < 1024;
                if (!isMobile) sidebarOpen = true;
            " 
            class="min-h-screen bg-gray-100 flex flex-col relative"
        >
            <!-- Header Navigasi Khusus Mobile (< 1024px) -->
            <header class="sticky top-0 z-30 flex h-14 w-full items-center justify-between border-b border-[#3d2b1c] bg-[#211c18] px-4 text-white shadow-md lg:hidden">
                <div class="flex items-center gap-3">
                    <button 
                        @click="sidebarOpen = !sidebarOpen" 
                        type="button" 
                        class="inline-flex items-center justify-center rounded-lg p-2 text-stone-200 hover:bg-stone-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-amber-500" 
                        aria-label="Buka Menu Navigasi"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <a href="{{ route('home') }}" class="flex items-center gap-2">
                        <img src="{{ asset('images/rapi-logo.png') }}" alt="Logo RAPI" class="h-8 w-8 rounded bg-white p-0.5 object-contain">
                        <span class="text-sm font-bold tracking-wide text-amber-100">RAPI Sekolah</span>
                    </a>
                </div>
                @auth
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-amber-500/20 border border-amber-500/40 px-2.5 py-0.5 text-xs font-semibold text-amber-200">
                            {{ auth()->user()->role?->role_name ?? 'User' }}
                        </span>
                    </div>
                @endauth
            </header>

            <!-- Backdrop Gelap saat Sidebar Terbuka di Mobile -->
            <div 
                x-show="sidebarOpen && isMobile" 
                x-transition:enter="transition-opacity ease-linear duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="sidebarOpen = false" 
                class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs lg:hidden"
                style="display: none;"
            ></div>

            @include('layouts.navigation')

            <div class="flex min-h-screen flex-1 flex-col transition-all duration-300" :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-4 px-4 sm:py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1 w-full">
                    @if (isset($slot))
                        {{ $slot }}
                    @else
                        @yield('content')
                    @endif
                </main>
                <x-site-footer />
            </div>
        </div>
        <dialog id="action-confirmation" class="w-[min(92vw,30rem)] rounded-xl border border-gray-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60 backdrop:backdrop-blur-md">
            <div class="border-b border-gray-200 px-6 py-4">
                <p id="action-confirmation-eyebrow" class="text-xs font-semibold uppercase tracking-widest text-slate-500">Konfirmasi tindakan</p>
                <h2 id="action-confirmation-title" class="mt-1 text-lg font-semibold">Lanjutkan?</h2>
            </div>
            <div class="space-y-2 px-6 py-5">
                <p id="action-confirmation-message" class="text-sm leading-6 text-slate-600"></p>
                <p id="action-confirmation-item" class="rounded-md bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800"></p>
                <p class="text-xs text-slate-500">Periksa kembali sebelum melanjutkan. Tindakan ini baru dijalankan setelah Anda mengonfirmasi.</p>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" id="action-confirmation-cancel" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-100">Batal</button>
                <button type="button" id="action-confirmation-continue" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Lanjutkan</button>
            </div>
        </dialog>
    </body>
</html>
