<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body @class([
        'font-sans antialiased',
        'admin-theme' => auth()->check() && (
            str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'admin')
            || str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'waka')
            || str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'guru')
            || str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'pic')
        ),
    ])>
        <div x-data="{ sidebarOpen: true }" class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <div class="flex min-h-screen flex-col transition-all duration-300" :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1">
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
