<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Panel PIC Ruang</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Manajemen Agenda PIC</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pantau agenda yang sedang dan sudah terlaksana, serta agenda yang ditolak di ruang tanggung jawab Anda.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-2xs">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-2xs">{{ session('error') }}</div>
            @endif
            @if (session('warning'))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-2xs">{{ session('warning') }}</div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Daftar Agenda</h3>
                    <p class="text-xs text-slate-500">
                        @if ($events->total() > 0)
                            Menampilkan {{ $events->firstItem() }}–{{ $events->lastItem() }} dari {{ $events->total() }} agenda di bawah tanggung jawab Anda.
                        @else
                            Daftar agenda di ruang penugasan Anda.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Form Pencarian & Filter Status (Serupa dengan Admin) -->
            <form method="GET" action="{{ route('pic.events.approved') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
                <input 
                    type="search" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari agenda, ruang, pengaju" 
                    aria-label="Cari agenda, ruang, atau pengaju" 
                    class="rounded-lg border-gray-300 text-sm lg:col-span-2 focus:border-slate-800 focus:ring-1 focus:ring-slate-800"
                >
                <select name="status" aria-label="Filter status agenda" class="rounded-lg border-gray-300 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    <option value="">Semua status</option>
                    <option value="in_progress" @selected(request('status') === 'in_progress')>Sedang Terlaksana</option>
                    <option value="upcoming" @selected(request('status') === 'upcoming')>Belum Terlaksana</option>
                    <option value="completed" @selected(request('status') === 'completed')>Sudah Terlaksana</option>
                    <option value="cancellation_pending" @selected(request('status') === 'cancellation_pending')>Menunggu Pembatalan</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                </select>
                <div class="relative w-full">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari Tanggal" aria-label="Tanggal mulai" class="w-full rounded-lg border-gray-300 pl-8 pr-2 py-2 text-sm text-slate-800 focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                </div>
                <div class="relative w-full">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" title="Sampai Tanggal" aria-label="Tanggal akhir" class="w-full rounded-lg border-gray-300 pl-8 pr-2 py-2 text-sm text-slate-800 focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-slate-700 transition">Terapkan</button>
                    <a href="{{ route('pic.events.approved') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50 transition">Reset</a>
                </div>
            </form>

            <!-- Tabel Terpadu Agenda (Format dan Kerapian Sama Persis dengan Admin) -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto min-h-[380px]">
                    <table class="w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="w-[26%] px-5 py-3.5 font-bold">Agenda</th>
                                <th scope="col" class="w-[16%] px-5 py-3.5 font-bold">Ruang</th>
                                <th scope="col" class="w-[18%] px-5 py-3.5 font-bold">Pengaju</th>
                                <th scope="col" class="w-[24%] px-5 py-3.5 font-bold">Jadwal</th>
                                <th scope="col" class="w-[16%] px-5 py-3.5 font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($events as $index => $event)
                                <tr class="align-middle hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 py-3.5 align-middle">
                                        <p class="font-semibold text-slate-900 text-sm sm:text-base leading-snug">{{ $event->event_name }}</p>
                                        @if ($event->event_type)
                                            <span class="mt-1 inline-block text-[11px] font-medium text-slate-500 uppercase tracking-wide">
                                                {{ config("event_types.{$event->event_type}.label") ?? ucfirst($event->event_type) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 align-middle text-slate-700 font-medium">
                                        <div class="flex items-center gap-1.5">
                                            <svg width="16" height="16" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            <span class="text-sm font-semibold text-slate-800">{{ $event->room_names ?: '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 align-middle text-slate-700">
                                        @if ($event->pic)
                                            <div 
                                                x-data="{ openPopover: false }"
                                                class="relative inline-block text-left"
                                                @mouseenter="openPopover = true"
                                                @mouseleave="openPopover = false"
                                            >
                                                <div 
                                                    @click="openPopover = !openPopover"
                                                    class="flex items-center gap-2 cursor-pointer group select-none py-1"
                                                    title="Arahkan kursor untuk melihat identitas lengkap"
                                                >
                                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700 ring-1 ring-slate-200 transition group-hover:bg-amber-100 group-hover:text-amber-900 group-hover:ring-amber-300">
                                                        {{ strtoupper(substr($event->pic->fullname ?? 'U', 0, 1)) }}
                                                    </div>
                                                    <div class="flex items-center gap-1 min-w-0">
                                                        <span class="truncate max-w-[130px] text-sm font-medium text-slate-800 group-hover:text-amber-800 transition">
                                                            {{ $event->pic->fullname }}
                                                        </span>
                                                        <svg width="12" height="12" style="width:12px;height:12px;" class="text-slate-400 group-hover:text-amber-600 transition shrink-0 opacity-60 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                        </svg>
                                                    </div>
                                                </div>

                                                <!-- Balon Komen Melayang: Detail Lengkap Pengaju -->
                                                <div 
                                                    x-show="openPopover" 
                                                    x-cloak
                                                    x-transition:enter="transition ease-out duration-150" 
                                                    x-transition:enter-start="opacity-0 translate-y-1 scale-95" 
                                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
                                                    x-transition:leave="transition ease-in duration-100" 
                                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
                                                    x-transition:leave-end="opacity-0 translate-y-1 scale-95" 
                                                    :class="({{ $events->count() }} <= 2 || {{ $index }} < 3) ? 'top-full mt-2' : 'bottom-full mb-2'"
                                                    class="absolute left-0 z-50 w-72 rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xl ring-1 ring-black/5 whitespace-normal text-left"
                                                    style="display: none;"
                                                >
                                                    <!-- Ekor Segitiga Balon Komen (Tail/Arrow) -->
                                                    <div 
                                                        class="absolute w-3 h-3 bg-white rotate-45 transform"
                                                        :class="({{ $events->count() }} <= 2 || {{ $index }} < 3) ? '-top-1.5 left-5 border-t border-l border-slate-200' : '-bottom-1.5 left-5 border-b border-r border-slate-200'"
                                                    ></div>

                                                    <div class="relative z-10">
                                                        <div class="flex items-center gap-2.5 pb-2.5 border-b border-slate-100">
                                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-50 text-xs font-bold text-amber-800 ring-1 ring-amber-200">
                                                                {{ strtoupper(substr($event->pic->fullname ?? 'U', 0, 1)) }}
                                                            </div>
                                                            <div class="min-w-0">
                                                                <p class="text-xs font-bold text-slate-900 truncate">{{ $event->pic->fullname }}</p>
                                                                <span class="inline-block mt-0.5 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 uppercase tracking-wide">
                                                                    {{ $event->pic->role?->role_name ?? 'Pengaju' }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="pt-2.5 space-y-1.5 text-xs text-slate-600">
                                                            <div class="flex items-center justify-between gap-2">
                                                                <span class="text-slate-400 font-medium">Username:</span>
                                                                <span class="font-mono text-slate-800 truncate font-medium">{{ $event->pic->username }}</span>
                                                            </div>
                                                            <div class="flex items-center justify-between gap-2">
                                                                <span class="text-slate-400 font-medium">Peran:</span>
                                                                <span class="font-medium text-slate-800 truncate">{{ $event->pic->role?->role_name ?? '-' }}</span>
                                                            </div>
                                                            <div class="flex items-center justify-between gap-2">
                                                                <span class="text-slate-400 font-medium">Email:</span>
                                                                <span class="font-mono text-slate-800 truncate">{{ $event->pic->email }}</span>
                                                            </div>
                                                            <div class="flex items-center justify-between gap-2">
                                                                <span class="text-slate-400 font-medium">WhatsApp / Telp:</span>
                                                                <span class="font-medium text-slate-800">{{ $event->pic->phone_number ?: 'Belum diisi' }}</span>
                                                            </div>
                                                            @if ($event->pic->phone_number)
                                                                <div class="pt-2 border-t border-slate-100 mt-2">
                                                                    @php
                                                                        $cleanPhone = preg_replace('/[^0-9]/', '', $event->pic->phone_number);
                                                                        if (str_starts_with($cleanPhone, '0')) {
                                                                            $cleanPhone = '62' . substr($cleanPhone, 1);
                                                                        }
                                                                    @endphp
                                                                    <a 
                                                                        href="https://wa.me/{{ $cleanPhone }}" 
                                                                        target="_blank" 
                                                                        rel="noopener noreferrer"
                                                                        class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 hover:underline"
                                                                    >
                                                                        <svg class="h-3.5 w-3.5 fill-current text-emerald-500" viewBox="0 0 24 24">
                                                                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.772.82 2.79.82 3.181 0 5.768-2.587 5.768-5.766.001-3.187-2.575-5.806-5.767-6.006zm3.328 8.163c-.144.405-.837.774-1.17.822-.312.043-.687.078-2.285-.561-1.921-.767-3.136-2.73-3.232-2.858-.095-.128-.775-1.031-.775-1.966 0-.935.489-1.394.664-1.583.174-.189.38-.236.507-.236.126 0 .253.002.364.007.119.006.277-.045.433.329.16.38.547 1.332.595 1.428.048.095.08.207.016.333-.064.127-.096.206-.19.317-.095.111-.2.248-.286.333-.095.095-.195.198-.084.388.111.19.493.813 1.059 1.317.728.647 1.341.848 1.531.943.19.095.302.08.413-.048.111-.127.476-.554.603-.744.127-.19.254-.158.428-.095.174.063 1.111.523 1.301.618.19.095.317.143.364.222.048.08.048.46-.096.865z"/>
                                                                        </svg>
                                                                        <span>Chat via WhatsApp</span>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-sm text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 align-middle">
                                        <div class="space-y-1.5 whitespace-nowrap text-left">
                                            <div class="flex items-center gap-1.5 font-bold text-slate-900 text-sm sm:text-base">
                                                <svg width="17" height="17" style="width:17px;height:17px;min-width:17px;" class="text-amber-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></span>
                                            </div>
                                            @php
                                                $startFormatted = date('H:i', strtotime($event->start_time));
                                                $endFormatted = date('H:i', strtotime($event->end_time));
                                                $sessionList = collect(config('schedule.sessions'));
                                                $startSessionIndex = $sessionList->search(fn ($t) => $t['start_time'] === $startFormatted);
                                                $endSessionIndex = $sessionList->search(fn ($t) => $t['end_time'] === $endFormatted);
                                            @endphp
                                            @if ($startSessionIndex !== false && $endSessionIndex !== false)
                                                <div>
                                                    <span class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-900 shadow-2xs">
                                                        Sesi {{ $startSessionIndex }}{{ $startSessionIndex !== $endSessionIndex ? '–' . $endSessionIndex : '' }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="flex items-center gap-1.5 font-mono text-sm sm:text-base font-bold text-slate-900">
                                                <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;" class="text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>{{ $startFormatted }}–{{ $endFormatted }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 align-middle whitespace-nowrap">
                                        @if ($event->cancellation_status === 'pending')
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-purple-200 bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-800 shadow-2xs">
                                                <span class="h-1.5 w-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                                                <span>Pengajuan Batal</span>
                                            </span>
                                            <div class="mt-1.5">
                                                <button 
                                                    type="button" 
                                                    data-open-cancel-reason="pic-cancel-reason-{{ $event->id }}"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-purple-200 bg-purple-50/90 hover:bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700 hover:text-purple-900 transition shadow-2xs group"
                                                    title="Buka dialog alasan pembatalan dari guru"
                                                >
                                                    <svg class="h-3.5 w-3.5 shrink-0 text-purple-600 group-hover:scale-110 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                    </svg>
                                                    <span>Lihat Alasan Guru</span>
                                                </button>
                                            </div>

                                            <!-- Modal Window Alasan Pembatalan Dari Guru -->
                                            <div
                                                id="pic-cancel-reason-{{ $event->id }}"
                                                data-cancel-reason-panel
                                                hidden
                                                class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4 text-left whitespace-normal"
                                                role="dialog"
                                                aria-modal="true"
                                                aria-labelledby="pic-cancel-title-{{ $event->id }}"
                                            >
                                                <div class="mx-auto w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-2xl text-left whitespace-normal">
                                                    <div class="flex items-center justify-between border-b border-slate-200 bg-purple-50/70 px-5 py-4 text-left whitespace-normal">
                                                        <div class="flex items-center gap-2.5 text-left whitespace-normal">
                                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                                </svg>
                                                            </div>
                                                            <div class="text-left whitespace-normal">
                                                                <p class="text-xs font-semibold uppercase tracking-wider text-purple-700">Detail Alasan Pembatalan</p>
                                                                <h3 id="pic-cancel-title-{{ $event->id }}" class="text-base font-bold text-slate-900 text-left">{{ $event->event_name }}</h3>
                                                            </div>
                                                        </div>
                                                        <button type="button" data-close-cancel-reason class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" aria-label="Tutup dialog">&times;</button>
                                                    </div>

                                                    <div class="space-y-4 p-5 text-left whitespace-normal">
                                                        <div class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3.5 text-xs text-slate-600">
                                                            <div>
                                                                <span class="block text-slate-400 font-medium">Pengaju Pembatalan:</span>
                                                                <span class="font-semibold text-slate-800">{{ $event->pic?->fullname ?? 'Guru' }}</span>
                                                            </div>
                                                            <div>
                                                                <span class="block text-slate-400 font-medium">Waktu Pengajuan:</span>
                                                                <span class="font-semibold text-slate-800">{{ $event->cancellation_requested_at ? $event->cancellation_requested_at->format('d/m/Y H:i') : '-' }}</span>
                                                            </div>
                                                            <div>
                                                                <span class="block text-slate-400 font-medium">Ruangan:</span>
                                                                <span class="font-semibold text-slate-800">{{ $event->room_names ?: '-' }}</span>
                                                            </div>
                                                            <div>
                                                                <span class="block text-slate-400 font-medium">Jadwal Agenda:</span>
                                                                <span class="font-semibold text-slate-800">{{ $event->date }} ({{ substr($event->start_time, 0, 5) }}–{{ substr($event->end_time, 0, 5) }})</span>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                                                Deskripsi / Alasan Pembatalan Guru:
                                                            </label>
                                                            <div class="rounded-xl border border-purple-200 bg-purple-50/60 p-4 text-sm text-purple-950 font-medium leading-relaxed italic whitespace-pre-wrap break-words">
                                                                "{{ $event->cancellation_reason }}"
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex justify-end border-t border-slate-100 bg-slate-50 px-5 py-3.5 text-left whitespace-normal">
                                                        <button type="button" data-close-cancel-reason class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif ($event->status === 'cancelled')
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-800 shadow-2xs">
                                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                                <span>Dibatalkan</span>
                                            </span>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Disetujui: {{ $event->cancellationApprover?->fullname ?? 'Admin / PIC' }}
                                            </p>
                                            @if ($event->cancellation_reason)
                                                <div class="mt-1">
                                                    <button 
                                                        type="button" 
                                                        data-open-cancel-reason="pic-cancel-view-modal-{{ $event->id }}"
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50/80 hover:bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:text-rose-900 transition shadow-2xs group"
                                                        title="Buka dialog alasan pembatalan"
                                                    >
                                                        <svg class="h-3.5 w-3.5 shrink-0 text-rose-600 group-hover:scale-110 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                        </svg>
                                                        <span>Lihat Alasan Batal</span>
                                                    </button>
                                                </div>

                                                <!-- Modal Window Alasan Pembatalan (Telah Dibatalkan) -->
                                                <div
                                                    id="pic-cancel-view-modal-{{ $event->id }}"
                                                    data-cancel-reason-panel
                                                    hidden
                                                    class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4 text-left whitespace-normal"
                                                    role="dialog"
                                                    aria-modal="true"
                                                    aria-labelledby="pic-cancel-view-title-{{ $event->id }}"
                                                >
                                                    <div class="mx-auto w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-2xl text-left whitespace-normal">
                                                        <div class="flex items-center justify-between border-b border-slate-200 bg-rose-50/70 px-5 py-4 text-left whitespace-normal">
                                                            <div class="flex items-center gap-2.5 text-left whitespace-normal">
                                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                                    </svg>
                                                                </div>
                                                                <div class="text-left whitespace-normal">
                                                                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-700">Detail Alasan Pembatalan</p>
                                                                    <h3 id="pic-cancel-view-title-{{ $event->id }}" class="text-base font-bold text-slate-900 text-left">{{ $event->event_name }}</h3>
                                                                </div>
                                                            </div>
                                                            <button type="button" data-close-cancel-reason class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" aria-label="Tutup dialog">&times;</button>
                                                        </div>

                                                        <div class="space-y-4 p-5 text-left whitespace-normal">
                                                            <div class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3.5 text-xs text-slate-600">
                                                                <div>
                                                                    <span class="block text-slate-400 font-medium">Pengaju Pembatalan:</span>
                                                                    <span class="font-semibold text-slate-800">{{ $event->pic?->fullname ?? 'Guru' }}</span>
                                                                </div>
                                                                <div>
                                                                    <span class="block text-slate-400 font-medium">Disetujui Oleh:</span>
                                                                    <span class="font-semibold text-slate-800">{{ $event->cancellationApprover?->fullname ?? 'Admin / PIC' }}</span>
                                                                </div>
                                                                <div>
                                                                    <span class="block text-slate-400 font-medium">Ruangan:</span>
                                                                    <span class="font-semibold text-slate-800">{{ $event->room_names ?: '-' }}</span>
                                                                </div>
                                                                <div>
                                                                    <span class="block text-slate-400 font-medium">Jadwal Agenda:</span>
                                                                    <span class="font-semibold text-slate-800">{{ $event->date }} ({{ substr($event->start_time, 0, 5) }}–{{ substr($event->end_time, 0, 5) }})</span>
                                                                </div>
                                                            </div>

                                                            <div>
                                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                                                    Alasan Pembatalan Guru:
                                                                </label>
                                                                <div class="rounded-xl border border-rose-200 bg-rose-50/60 p-4 text-sm text-rose-950 font-medium leading-relaxed italic whitespace-pre-wrap break-words">
                                                                    "{{ $event->cancellation_reason }}"
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="flex justify-end border-t border-slate-100 bg-slate-50 px-5 py-3.5 text-left whitespace-normal">
                                                            <button type="button" data-close-cancel-reason class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">Tutup</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @elseif ($event->status === 'approved')
                                            @if ($event->lifecycle_status === 'completed')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800 shadow-2xs">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                                    <span>{{ $event->lifecycle_status_label }}</span>
                                                </span>
                                            @elseif ($event->lifecycle_status === 'in_progress')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 shadow-2xs">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span>{{ $event->lifecycle_status_label }}</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 shadow-2xs">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                    <span>{{ $event->lifecycle_status_label }}</span>
                                                </span>
                                            @endif
                                            @if ($event->completed_at)
                                                <p class="mt-1 text-[11px] text-slate-400">Selesai {{ $event->completed_at->format('d/m/Y H:i') }}</p>
                                            @endif
                                            @if ($event->cancellation_status === 'rejected')
                                                <p class="mt-0.5 text-[11px] text-red-600 font-medium" title="Alasan: {{ $event->cancellation_rejected_reason }}">
                                                    Batal ditolak
                                                </p>
                                            @endif
                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ $event->approver && (str_contains(strtolower($event->approver->role?->role_name ?? ''), 'admin') || str_contains(strtolower($event->approver->role?->role_name ?? ''), 'waka')) ? ($event->approved_by === $event->pic_id ? 'Disetujui otomatis (Admin)' : 'Disetujui Admin') : 'Disetujui PIC' }}
                                            </p>
                                        @elseif ($event->status === 'rejected')
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 shadow-2xs">
                                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                <span>Ditolak</span>
                                            </span>
                                            @if ($event->rejection_reason)
                                                <p class="mt-1 max-w-[190px] text-xs text-red-600 break-words font-medium">
                                                    Alasan: {{ $event->rejection_reason }}
                                                </p>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">
                                        Tidak ada data agenda yang sesuai dengan pencarian atau filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($events->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4 bg-white">
                        {{ $events->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-open-cancel-reason]').forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-open-cancel-reason') || button.dataset.openCancelReason;
                const modal = document.getElementById(modalId);
                if (modal instanceof HTMLElement) {
                    modal.hidden = false;
                    modal.classList.add('flex');
                }
            });
        });

        document.querySelectorAll('[data-close-cancel-reason]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('[data-cancel-reason-panel]');
                if (modal instanceof HTMLElement) {
                    modal.hidden = true;
                    modal.classList.remove('flex');
                }
            });
        });

        document.querySelectorAll('[data-cancel-reason-panel]').forEach((panel) => {
            panel.addEventListener('click', (e) => {
                if (e.target === panel) {
                    panel.hidden = true;
                    panel.classList.remove('flex');
                }
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('[data-cancel-reason-panel]:not([hidden])').forEach((panel) => {
                    panel.hidden = true;
                    panel.classList.remove('flex');
                });
            }
        });
    </script>
</x-app-layout>
