<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-slate-500">Administrasi</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Manajemen Agenda</h2>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ session('error') }}</div>
            @endif
            @if (session('warning'))
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">{{ session('warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Daftar Agenda</h3>
                    <p class="text-xs text-slate-500">Pantau jadwal pelaksanaan agenda sekolah di seluruh ruangan.</p>
                </div>
                <button type="button" id="open-create-event-btn" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
                    <svg width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Agenda</span>
                </button>
            </div>

            <form method="GET" action="{{ route('admin.events') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari agenda, ruang, pengaju" aria-label="Cari agenda, ruang, atau pengaju" class="rounded-md border-gray-300 text-sm lg:col-span-2">
                <select name="status" aria-label="Filter status agenda" class="rounded-md border-gray-300 text-sm">
                    <option value="">Semua status</option>
                    <option value="pending" @selected(request('status') === 'pending')>Menunggu</option>
                    <option value="cancellation_pending" @selected(request('status') === 'cancellation_pending')>Menunggu Pembatalan</option>
                    <option value="upcoming" @selected(request('status') === 'upcoming')>Belum Terlaksana</option>
                    <option value="in_progress" @selected(request('status') === 'in_progress')>Sedang Terlaksana</option>
                    <option value="completed" @selected(request('status') === 'completed')>Sudah Terlaksana</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                </select>
                <div class="relative w-full">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari Tanggal" aria-label="Tanggal mulai" class="w-full rounded-md border-gray-300 pl-8 pr-2 py-2 text-sm text-slate-800 focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                </div>
                <div class="relative w-full">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" title="Sampai Tanggal" aria-label="Tanggal akhir" class="w-full rounded-md border-gray-300 pl-8 pr-2 py-2 text-sm text-slate-800 focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Terapkan</button>
                    <a href="{{ route('admin.events') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto min-h-[380px]">
                    <table class="w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="w-[22%] px-5 py-3.5 font-bold">Agenda</th>
                                <th scope="col" class="w-[14%] px-5 py-3.5 font-bold">Ruang</th>
                                <th scope="col" class="w-[15%] px-5 py-3.5 font-bold">Pengaju</th>
                                <th scope="col" class="w-[20%] px-5 py-3.5 font-bold">Jadwal</th>
                                <th scope="col" class="w-[19%] px-5 py-3.5 font-bold">Status</th>
                                <th scope="col" class="w-[10%] px-5 py-3.5 text-right font-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($events as $index => $event)
                                <tr class="align-middle hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 py-3.5 align-middle">
                                        <p class="font-semibold text-slate-900 text-sm sm:text-base leading-snug">{{ $event->event_name }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 align-middle text-slate-700 font-medium">
                                        {{ $event->room_names ?: '-' }}
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
                                            <div class="flex items-center gap-1.5 font-bold text-slate-900 text-base">
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
                                                    <span class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-2.5 py-0.5 text-sm font-bold text-amber-900 shadow-2xs">
                                                        Sesi {{ $startSessionIndex }}{{ $startSessionIndex !== $endSessionIndex ? '–' . $endSessionIndex : '' }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="flex items-center gap-1.5 font-mono text-base font-bold text-slate-900">
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
                                                    data-open-cancel-reason="cancel-reason-modal-{{ $event->id }}"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-purple-200 bg-purple-50/90 hover:bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700 hover:text-purple-900 transition shadow-2xs group"
                                                    title="Buka dialog alasan pembatalan dari guru"
                                                >
                                                    <svg class="h-3.5 w-3.5 shrink-0 text-purple-600 group-hover:scale-110 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                    </svg>
                                                    <span>Lihat Alasan Guru</span>
                                                </button>
                                            </div>
                                            <div class="mt-2 flex items-center gap-1.5">
                                                <form method="POST" action="{{ route('admin.events.approve-cancellation', $event) }}" data-confirm-action="menyetujui pembatalan agenda dan membebaskan jadwal ruangan" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 rounded-md bg-emerald-700 px-2.5 py-1 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-800 transition">ACC Batal</button>
                                                </form>
                                                <button type="button" data-open-admin-reject-cancel="admin-reject-cancel-modal-{{ $event->id }}" class="inline-flex items-center gap-1 rounded-md border border-red-200 bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-100 transition">Tolak Batal</button>
                                            </div>

                                            <!-- Modal Window Alasan Pembatalan Dari Guru -->
                                            <div
                                                id="cancel-reason-modal-{{ $event->id }}"
                                                data-cancel-reason-panel
                                                hidden
                                                class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4 text-left whitespace-normal"
                                                role="dialog"
                                                aria-modal="true"
                                                aria-labelledby="cancel-reason-title-{{ $event->id }}"
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
                                                                <h3 id="cancel-reason-title-{{ $event->id }}" class="text-base font-bold text-slate-900 text-left">{{ $event->event_name }}</h3>
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
                                                                Deskripsi / Alasan Pembatalan:
                                                            </label>
                                                            <div class="rounded-xl border border-purple-200 bg-purple-50/60 p-4 text-sm text-purple-950 font-medium leading-relaxed italic whitespace-pre-wrap break-words">
                                                                "{{ $event->cancellation_reason }}"
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-5 py-3.5 text-left whitespace-normal">
                                                        <button type="button" data-close-cancel-reason class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">Tutup</button>
                                                        <div class="flex items-center gap-2">
                                                            <button 
                                                                type="button" 
                                                                data-close-cancel-reason
                                                                data-open-admin-reject-cancel="admin-reject-cancel-modal-{{ $event->id }}" 
                                                                class="rounded-xl border border-red-200 bg-red-50 px-3.5 py-2 text-xs font-bold text-red-700 hover:bg-red-100 transition"
                                                            >
                                                                Tolak Batal
                                                            </button>
                                                            <form method="POST" action="{{ route('admin.events.approve-cancellation', $event) }}" data-confirm-action="menyetujui pembatalan agenda dan membebaskan jadwal ruangan" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                                @csrf
                                                                <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800 shadow-sm transition">
                                                                    ACC Batal
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Modal Penolakan Pembatalan Admin -->
                                            <div
                                                id="admin-reject-cancel-modal-{{ $event->id }}"
                                                data-admin-reject-cancel-panel
                                                hidden
                                                class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4 text-left whitespace-normal"
                                                role="dialog"
                                                aria-modal="true"
                                            >
                                                <form method="POST" action="{{ route('admin.events.reject-cancellation', $event) }}" class="whitespace-normal mx-auto w-full max-w-lg overflow-hidden rounded-xl border border-slate-300 bg-white text-slate-900 shadow-2xl">
                                                    @csrf
                                                    <div class="flex items-center justify-between border-b border-slate-300 bg-slate-50 px-5 py-4">
                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-600">Konfirmasi Penolakan Batal</p>
                                                            <h3 class="mt-1 text-base font-semibold">Tolak Pembatalan: {{ $event->event_name }}</h3>
                                                        </div>
                                                        <button type="button" data-close-admin-reject-cancel class="rounded-md p-2 text-xl leading-none text-slate-500 hover:bg-slate-200">&times;</button>
                                                    </div>
                                                    <div class="space-y-3 p-5">
                                                        <label for="admin-reject-cancel-reason-{{ $event->id }}" class="block text-sm font-semibold">Alasan Penolakan Pembatalan <span class="text-red-600">*</span></label>
                                                        <textarea id="admin-reject-cancel-reason-{{ $event->id }}" name="cancellation_rejected_reason" rows="4" maxlength="1000" required class="w-full resize-y rounded-md border-slate-300 bg-white text-sm leading-6 focus:border-slate-700 focus:ring-slate-700" placeholder="Tuliskan alasan penolakan pembatalan agenda ini..."></textarea>
                                                        <p class="text-xs text-slate-500">Alasan ini akan disampaikan kepada guru pengaju.</p>
                                                    </div>
                                                    <div class="flex justify-end gap-2 border-t border-slate-300 bg-slate-50 px-5 py-4">
                                                        <button type="button" data-close-admin-reject-cancel class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Batal</button>
                                                        <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Tolak Permintaan Batal</button>
                                                    </div>
                                                </form>
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
                                                        data-open-cancel-reason="cancel-view-modal-{{ $event->id }}"
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
                                                    id="cancel-view-modal-{{ $event->id }}"
                                                    data-cancel-reason-panel
                                                    hidden
                                                    class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4 text-left whitespace-normal"
                                                    role="dialog"
                                                    aria-modal="true"
                                                    aria-labelledby="cancel-view-title-{{ $event->id }}"
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
                                                                    <h3 id="cancel-view-title-{{ $event->id }}" class="text-base font-bold text-slate-900 text-left">{{ $event->event_name }}</h3>
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
                                        @elseif ($event->status === 'pending')
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                <span>Menunggu</span>
                                            </span>
                                            <div class="mt-2 flex items-center gap-1.5">
                                                <form method="POST" action="{{ route('admin.events.approve', $event) }}" data-confirm-action="menyetujui agenda" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                    @csrf
                                                    <button type="submit" class="event-action-primary inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-semibold text-white shadow-2xs transition">ACC Agenda</button>
                                                </form>
                                                <button type="button" data-open-admin-reject="admin-reject-modal-{{ $event->id }}" class="inline-flex items-center gap-1 rounded-md border border-red-300 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-100 transition shadow-2xs">Tolak Agenda</button>
                                            </div>

                                            <!-- Modal Window Penolakan Agenda Oleh Admin -->
                                            <div
                                                id="admin-reject-modal-{{ $event->id }}"
                                                data-admin-reject-panel
                                                hidden
                                                class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4 text-left whitespace-normal"
                                                role="dialog"
                                                aria-modal="true"
                                                aria-labelledby="admin-reject-title-{{ $event->id }}"
                                            >
                                                <div class="mx-auto w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-2xl text-left whitespace-normal">
                                                    <form method="POST" action="{{ route('admin.events.reject', $event) }}" data-confirm-action="menolak pengajuan agenda ini" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                        @csrf
                                                        <div class="flex items-center justify-between border-b border-red-100 bg-red-50/70 px-5 py-4 text-left whitespace-normal">
                                                            <div class="flex items-center gap-2.5 text-left whitespace-normal">
                                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700">
                                                                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </div>
                                                                <div class="text-left whitespace-normal">
                                                                    <p class="text-xs font-semibold uppercase tracking-wider text-red-700">Konfirmasi Penolakan Agenda</p>
                                                                    <h3 id="admin-reject-title-{{ $event->id }}" class="text-base font-bold text-slate-900 text-left truncate max-w-xs sm:max-w-sm">{{ $event->event_name }}</h3>
                                                                </div>
                                                            </div>
                                                            <button type="button" data-close-admin-reject class="rounded-lg p-1.5 text-slate-400 hover:bg-red-100 hover:text-slate-700 transition" aria-label="Tutup dialog">&times;</button>
                                                        </div>

                                                        <div class="space-y-4 p-5 text-left whitespace-normal">
                                                            <div class="grid grid-cols-2 gap-2.5 rounded-xl bg-slate-50 p-3 text-xs text-slate-600 border border-slate-100">
                                                                <div>
                                                                    <span class="block text-slate-400 font-medium">Pengaju Agenda:</span>
                                                                    <span class="font-semibold text-slate-800">{{ $event->pic?->fullname ?? 'Guru' }}</span>
                                                                </div>
                                                                <div>
                                                                    <span class="block text-slate-400 font-medium">Ruangan:</span>
                                                                    <span class="font-semibold text-slate-800">{{ $event->room_names ?: '-' }}</span>
                                                                </div>
                                                                <div class="col-span-2">
                                                                    <span class="block text-slate-400 font-medium">Jadwal Agenda:</span>
                                                                    <span class="font-semibold text-slate-800">{{ $event->date }} ({{ substr($event->start_time, 0, 5) }}–{{ substr($event->end_time, 0, 5) }} WIB)</span>
                                                                </div>
                                                            </div>

                                                            <div>
                                                                <label for="admin-reject-reason-{{ $event->id }}" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                                                    Alasan Penolakan <span class="text-red-600">*</span>:
                                                                </label>
                                                                <textarea 
                                                                    id="admin-reject-reason-{{ $event->id }}" 
                                                                    name="rejection_reason" 
                                                                    rows="3" 
                                                                    maxlength="1000" 
                                                                    required 
                                                                    class="w-full resize-y rounded-xl border-slate-300 text-xs sm:text-sm focus:border-red-600 focus:ring-1 focus:ring-red-600 placeholder:text-slate-400" 
                                                                    placeholder="Tuliskan alasan mengapa pengajuan agenda guru ini ditolak..."
                                                                ></textarea>
                                                                <p class="mt-1 text-[11px] text-slate-500">Alasan ini akan disampaikan kepada guru pengaju di riwayat agenda.</p>
                                                            </div>
                                                        </div>

                                                        <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50 px-5 py-3.5 text-left whitespace-normal">
                                                            <button type="button" data-close-admin-reject class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                                                Batal
                                                            </button>
                                                            <button type="submit" class="rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-700 shadow-sm transition">
                                                                Tolak Agenda
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
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
                                                <p class="mt-1 max-w-[190px] text-xs text-red-600 break-words font-medium" title="{{ $event->rejection_reason }}">
                                                    Alasan: {{ \Illuminate\Support\Str::limit($event->rejection_reason, 40) }}
                                                </p>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap px-5 py-3.5 text-right align-middle">
                                        <div 
                                            x-data="{ open: false }" 
                                            @click.outside="open = false" 
                                            @keydown.escape.window="open = false"
                                            class="relative inline-block text-right"
                                        >
                                            <button 
                                                type="button" 
                                                @click.stop="open = !open" 
                                                :class="open ? 'bg-amber-50 text-amber-900 border-amber-300 ring-2 ring-amber-500/20' : 'text-slate-600 bg-white border-slate-200 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 shadow-2xs'"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border transition-all focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                                                aria-haspopup="true"
                                                :aria-expanded="open"
                                                title="Pilihan tindakan agenda"
                                            >
                                                <span class="sr-only">Menu aksi agenda</span>
                                                <svg width="19" height="19" style="width:19px;height:19px;min-width:19px;max-width:19px;" class="h-4.5 w-4.5 transition-transform duration-200" :class="open ? 'rotate-90 text-amber-800' : 'text-slate-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </button>

                                            <!-- Dropdown Menu Popover -->
                                            <div 
                                                x-show="open" 
                                                x-cloak
                                                x-transition:enter="transition ease-out duration-100" 
                                                x-transition:enter-start="transform opacity-0 scale-95" 
                                                x-transition:enter-end="transform opacity-100 scale-100" 
                                                x-transition:leave="transition ease-in duration-75" 
                                                x-transition:leave-start="transform opacity-100 scale-100" 
                                                x-transition:leave-end="transform opacity-0 scale-95" 
                                                :class="({{ $events->count() }} <= 2 || {{ $index }} < 3) ? 'top-full mt-2 origin-top-right' : 'bottom-full mb-2 origin-bottom-right'"
                                                class="absolute right-0 z-40 min-w-[260px] w-max max-w-xs rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl ring-1 ring-black/5 text-left focus:outline-none"
                                                style="display: none;"
                                            >
                                                @if ($event->status !== 'rejected')
                                                    <a 
                                                        href="{{ route('admin.complaints.create', ['room_id' => $event->room_id]) }}" 
                                                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 whitespace-nowrap transition hover:bg-slate-100 hover:text-slate-900"
                                                    >
                                                        <svg width="16" height="16" style="width:16px;height:16px;" class="text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        <span>Buat Pengaduan Kerusakan</span>
                                                    </a>
                                                    @if ($event->canBeMovedByAdmin())
                                                        <button 
                                                            type="button" 
                                                            @click="open = false; document.getElementById('move-event-{{ $event->id }}')?.showModal()" 
                                                            data-open-move-dialog="move-event-{{ $event->id }}" 
                                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 whitespace-nowrap transition hover:bg-slate-100 hover:text-slate-900"
                                                        >
                                                            <svg width="16" height="16" style="width:16px;height:16px;" class="text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                            </svg>
                                                            <span>Pindahkan Ruangan</span>
                                                        </button>
                                                    @elseif ($event->lifecycle_status === 'completed')
                                                        <div 
                                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-400 cursor-not-allowed select-none"
                                                            title="Agenda yang sudah terlaksana tidak dapat dipindahkan karena sudah expired"
                                                        >
                                                            <svg width="16" height="16" style="width:16px;height:16px;" class="text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            <span>Tidak dapat dipindahkan (Sudah terlaksana)</span>
                                                        </div>
                                                    @endif
                                                    <div class="my-1 border-t border-slate-100"></div>
                                                @endif
                                                @if ($event->canBeCancelledByAdmin())
                                                    <button 
                                                        type="button" 
                                                        @click="open = false; document.getElementById('admin-cancel-modal-{{ $event->id }}')?.showModal()" 
                                                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium text-rose-600 whitespace-nowrap transition hover:bg-rose-50 hover:text-rose-700"
                                                    >
                                                        <svg width="16" height="16" style="width:16px;height:16px;" class="text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span>Batalkan Agenda</span>
                                                    </button>
                                                    <div class="my-1 border-t border-slate-100"></div>
                                                @endif
                                                @if ($event->canBeDeletedByAdmin())
                                                    <form 
                                                        method="POST" 
                                                        action="{{ route('admin.events.destroy', $event) }}" 
                                                        class="block w-full" 
                                                        data-confirm-action="menghapus agenda" 
                                                        data-confirm-item="Agenda: {{ $event->event_name }}"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button 
                                                            type="submit" 
                                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 whitespace-nowrap transition hover:bg-red-50 hover:text-red-700"
                                                        >
                                                            <svg width="16" height="16" style="width:16px;height:16px;" class="text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                            <span>Hapus</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    <div 
                                                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-400 cursor-not-allowed select-none"
                                                        title="Agenda yang sedang berlangsung tidak dapat dihapus"
                                                    >
                                                        <svg width="16" height="16" style="width:16px;height:16px;" class="text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                        </svg>
                                                        <span>Tidak dapat dihapus (Sedang berlangsung)</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($event->move_reason)
                                            <p class="mt-1 text-[11px] text-amber-700 font-medium" title="Alasan: {{ $event->move_reason }}">
                                                Dipindahkan
                                            </p>
                                        @endif

                                        @if ($event->canBeMovedByAdmin())
                                            <dialog id="move-event-{{ $event->id }}" class="whitespace-normal text-left w-[min(94vw,36rem)] rounded-2xl border border-slate-300 bg-transparent p-0 shadow-2xl backdrop:bg-slate-950/50">
                                                <form method="POST" action="{{ route('admin.events.move', $event) }}" class="whitespace-normal overflow-hidden rounded-2xl bg-white text-slate-900 text-left" data-move-form data-move-event-id="{{ $event->id }}" data-confirm-action="memindahkan ruangan untuk agenda" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <header class="border-b border-slate-300 bg-slate-50 px-6 py-4 text-left">
                                                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-600">Surat Pemindahan Ruangan</p>
                                                        <h3 class="mt-1 text-lg font-semibold text-slate-900">Pemindahan Agenda</h3>
                                                    </header>
                                                    <div class="space-y-4 px-6 py-5 text-left max-h-[75vh] overflow-y-auto">
                                                        <div class="grid grid-cols-2 gap-3 text-sm text-left rounded-xl bg-slate-50 p-3.5 border border-slate-200">
                                                            <div class="text-left">
                                                                <span class="block text-xs font-medium text-slate-500">Agenda</span>
                                                                <span class="font-semibold text-slate-800">{{ $event->event_name }}</span>
                                                            </div>
                                                            <div class="text-left">
                                                                <span class="block text-xs font-medium text-slate-500">Tanggal</span>
                                                                <span class="font-semibold text-slate-800"><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></span>
                                                            </div>
                                                            <div class="text-left">
                                                                <span class="block text-xs font-medium text-slate-500">Dari ruangan</span>
                                                                <span class="font-semibold text-slate-800">{{ $event->room_names ?: '-' }}</span>
                                                            </div>
                                                            <div class="text-left">
                                                                <span class="block text-xs font-medium text-slate-500">Waktu</span>
                                                                <span class="font-semibold text-slate-800">{{ date('H:i', strtotime($event->start_time)) }} - {{ date('H:i', strtotime($event->end_time)) }}</span>
                                                            </div>
                                                        </div>

                                                        <!-- Shortcut WhatsApp Koordinasi Peminjam Ruangan -->
                                                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-3.5 text-left">
                                                            <div class="flex items-center justify-between gap-3">
                                                                <div class="min-w-0">
                                                                    <div class="flex items-center gap-1.5">
                                                                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Peminjam Ruangan</span>
                                                                        @if ($event->pic?->role)
                                                                            <span class="inline-flex items-center rounded-md bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800">
                                                                                {{ $event->pic->role->role_name }}
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    <p class="font-bold text-slate-900 text-sm mt-0.5 truncate">{{ $event->pic?->fullname ?? 'Guru' }}</p>
                                                                    <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $event->pic?->phone_number ?: 'Nomor HP belum tersedia' }}</p>
                                                                </div>
                                                                @if ($event->pic?->phone_number)
                                                                    @php
                                                                        $cleanPicPhone = preg_replace('/[^0-9]/', '', $event->pic->phone_number);
                                                                        if (str_starts_with($cleanPicPhone, '0')) {
                                                                            $cleanPicPhone = '62' . substr($cleanPicPhone, 1);
                                                                        }
                                                                        $waCoordMessage = "Halo Bapak/Ibu {$event->pic?->fullname}, kami dari pengelola sarpras ingin berkoordinasi terkait pemindahan ruangan untuk agenda \"{$event->event_name}\" pada tanggal " . (\Illuminate\Support\Carbon::parse($event->date)->format('d/m/Y')) . " (pukul " . substr($event->start_time, 0, 5) . " - " . substr($event->end_time, 0, 5) . ").";
                                                                    @endphp
                                                                    <a 
                                                                        href="https://wa.me/{{ $cleanPicPhone }}?text={{ rawurlencode($waCoordMessage) }}" 
                                                                        target="_blank" 
                                                                        rel="noopener noreferrer" 
                                                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white shadow-2xs hover:bg-emerald-700 transition"
                                                                        title="Hubungi peminjam via WhatsApp untuk koordinasi ruangan"
                                                                    >
                                                                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                                                                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.772.82 2.79.82 3.181 0 5.768-2.587 5.768-5.766.001-3.187-2.575-5.806-5.767-6.006zm3.328 8.163c-.144.405-.837.774-1.17.822-.312.043-.687.078-2.285-.561-1.921-.767-3.136-2.73-3.232-2.858-.095-.128-.775-1.031-.775-1.966 0-.935.489-1.394.664-1.583.174-.189.38-.236.507-.236.126 0 .253.002.364.007.119.006.277-.045.433.329.16.38.547 1.332.595 1.428.048.095.08.207.016.333-.064.127-.096.206-.19.317-.095.111-.2.248-.286.333-.095.095-.195.198-.084.388.111.19.493.813 1.059 1.317.728.647 1.341.848 1.531.943.19.095.302.08.413-.048.111-.127.476-.554.603-.744.127-.19.254-.158.428-.095.174.063 1.111.523 1.301.618.19.095.317.143.364.222.048.08.048.46-.096.865z"/>
                                                                        </svg>
                                                                        <span>Chat WhatsApp</span>
                                                                    </a>
                                                                @else
                                                                    <span class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-400">
                                                                        No WA Kosong
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        @php($destinationRooms = $rooms->whereNotIn('id', $event->reservedRoomIds()))
                                                        <div class="text-left space-y-2">
                                                            <div class="flex items-center justify-between">
                                                                <label class="block text-sm font-semibold text-slate-800 text-left">Ruangan tujuan</label>
                                                                <span class="text-xs text-slate-500 font-medium">Bisa memilih lebih dari 1 ruangan</span>
                                                            </div>
                                                            <div data-move-room-list class="space-y-2.5">
                                                                <div data-move-room-row class="flex items-start gap-2">
                                                                    <div class="min-w-0 flex-1">
                                                                        <div data-move-combobox
                                                                             data-event-id="{{ $event->id }}"
                                                                             data-start-date="{{ is_string($event->date) ? $event->date : $event->date->format('Y-m-d') }}"
                                                                             data-end-date="{{ $event->end_date ? (is_string($event->end_date) ? $event->end_date : $event->end_date->format('Y-m-d')) : (is_string($event->date) ? $event->date : $event->date->format('Y-m-d')) }}"
                                                                             data-start-time="{{ substr($event->start_time ?: '00:00:00', 0, 5) }}"
                                                                             data-end-time="{{ substr($event->end_time ?: '23:59:59', 0, 5) }}"
                                                                             class="relative text-left">
                                                                            <div class="relative">
                                                                                <input type="text"
                                                                                       data-move-combobox-input
                                                                                       autocomplete="off"
                                                                                       placeholder="Pilih atau ketik nama ruangan..."
                                                                                       class="w-full rounded-lg border-gray-300 pr-10 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800 text-left">
                                                                                <button type="button"
                                                                                        data-move-combobox-toggle
                                                                                        tabindex="-1"
                                                                                        aria-label="Buka pilihan ruangan"
                                                                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 focus:outline-none">
                                                                                    <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;min-height:16px;" class="transition-transform duration-200" data-move-combobox-arrow fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                                                    </svg>
                                                                                </button>
                                                                            </div>
                                                                            <select name="room_ids[]"
                                                                                    data-move-room-select
                                                                                    required
                                                                                    @disabled($destinationRooms->isEmpty())
                                                                                    class="sr-only"
                                                                                    tabindex="-1"
                                                                                    style="position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px;">
                                                                                <option value="">Pilih ruangan aktif</option>
                                                                                @foreach ($destinationRooms as $room)
                                                                                    <option value="{{ $room->id }}"
                                                                                            data-name="{{ $room->room_name }}"
                                                                                            data-capacity="{{ $room->capacity }}"
                                                                                            data-active="{{ $room->is_active ? '1' : '0' }}"
                                                                                            data-maintenance-note="{{ $room->maintenance_note ?? '' }}"
                                                                                            data-pic-name="{{ $room->pic_names }}"
                                                                                            data-pic-phone="{{ $room->pic_phone ?? '' }}"
                                                                                            data-pic-wa="{{ $room->pic_whatsapp_url ?? '' }}">
                                                                                        {{ $room->room_name }} (Kapasitas {{ $room->capacity }})
                                                                                    </option>
                                                                                @endforeach
                                                                            </select>
                                                                            <div data-move-combobox-dropdown class="hidden absolute z-50 left-0 right-0 mt-1 max-h-52 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl py-1 text-sm text-left">
                                                                                <ul data-move-combobox-list role="listbox" class="divide-y divide-gray-50 text-left"></ul>
                                                                                <div data-move-combobox-empty class="hidden px-4 py-3 text-center text-xs text-slate-500">
                                                                                    Tidak ada ruangan yang cocok dengan pencarian.
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <p data-move-occupied-warning class="hidden mt-1 text-xs font-semibold text-red-600 flex items-center gap-1 text-left">
                                                                            <svg width="14" height="14" style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                                                            <span data-move-warning-text>Ruangan ini sedang dipakai pada jadwal tersebut.</span>
                                                                        </p>
                                                                    </div>
                                                                    <button type="button" data-remove-move-room class="hidden shrink-0 rounded-lg border border-red-200 bg-red-50 p-2 text-red-600 hover:bg-red-100 hover:text-red-700 transition" title="Hapus ruangan ini">
                                                                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="flex items-center justify-between pt-1">
                                                                <button type="button" data-add-move-room="{{ $event->id }}" @disabled($destinationRooms->isEmpty()) class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs disabled:opacity-50 disabled:cursor-not-allowed">
                                                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                                                    </svg>
                                                                    <span>Tambah Ruangan Tujuan</span>
                                                                </button>
                                                                @if ($destinationRooms->isEmpty())
                                                                    <span class="text-xs text-slate-500">Tidak ada ruangan aktif lain yang dapat dipilih.</span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <!-- Template untuk Menambah Baris Ruangan Baru -->
                                                        <template id="move-room-template-{{ $event->id }}">
                                                            <div data-move-room-row class="flex items-start gap-2">
                                                                <div class="min-w-0 flex-1">
                                                                    <div data-move-combobox
                                                                         data-event-id="{{ $event->id }}"
                                                                         data-start-date="{{ is_string($event->date) ? $event->date : $event->date->format('Y-m-d') }}"
                                                                         data-end-date="{{ $event->end_date ? (is_string($event->end_date) ? $event->end_date : $event->end_date->format('Y-m-d')) : (is_string($event->date) ? $event->date : $event->date->format('Y-m-d')) }}"
                                                                         data-start-time="{{ substr($event->start_time ?: '00:00:00', 0, 5) }}"
                                                                         data-end-time="{{ substr($event->end_time ?: '23:59:59', 0, 5) }}"
                                                                         class="relative text-left">
                                                                        <div class="relative">
                                                                            <input type="text"
                                                                                   data-move-combobox-input
                                                                                   autocomplete="off"
                                                                                   placeholder="Pilih atau ketik nama ruangan..."
                                                                                   class="w-full rounded-lg border-gray-300 pr-10 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800 text-left">
                                                                            <button type="button"
                                                                                    data-move-combobox-toggle
                                                                                    tabindex="-1"
                                                                                    aria-label="Buka pilihan ruangan"
                                                                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 focus:outline-none">
                                                                                <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;min-height:16px;" class="transition-transform duration-200" data-move-combobox-arrow fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                                                </svg>
                                                                            </button>
                                                                        </div>
                                                                        <select name="room_ids[]"
                                                                                data-move-room-select
                                                                                required
                                                                                class="sr-only"
                                                                                tabindex="-1"
                                                                                style="position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px;">
                                                                            <option value="">Pilih ruangan aktif</option>
                                                                            @foreach ($destinationRooms as $room)
                                                                                <option value="{{ $room->id }}"
                                                                                        data-name="{{ $room->room_name }}"
                                                                                        data-capacity="{{ $room->capacity }}"
                                                                                        data-active="{{ $room->is_active ? '1' : '0' }}"
                                                                                        data-maintenance-note="{{ $room->maintenance_note ?? '' }}"
                                                                                        data-pic-name="{{ $room->pic_names }}"
                                                                                        data-pic-phone="{{ $room->pic_phone ?? '' }}"
                                                                                        data-pic-wa="{{ $room->pic_whatsapp_url ?? '' }}">
                                                                                    {{ $room->room_name }} (Kapasitas {{ $room->capacity }})
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                        <div data-move-combobox-dropdown class="hidden absolute z-50 left-0 right-0 mt-1 max-h-52 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl py-1 text-sm text-left">
                                                                            <ul data-move-combobox-list role="listbox" class="divide-y divide-gray-50 text-left"></ul>
                                                                            <div data-move-combobox-empty class="hidden px-4 py-3 text-center text-xs text-slate-500">
                                                                                Tidak ada ruangan yang cocok dengan pencarian.
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <p data-move-occupied-warning class="hidden mt-1 text-xs font-semibold text-red-600 flex items-center gap-1 text-left">
                                                                        <svg width="14" height="14" style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                                                        <span data-move-warning-text>Ruangan ini sedang dipakai pada jadwal tersebut.</span>
                                                                    </p>
                                                                </div>
                                                                <button type="button" data-remove-move-room class="shrink-0 rounded-lg border border-red-200 bg-red-50 p-2 text-red-600 hover:bg-red-100 hover:text-red-700 transition" title="Hapus ruangan ini">
                                                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                                </button>
                                                            </div>
                                                        </template>

                                                        <div class="text-left">
                                                            <div class="flex items-center justify-between gap-3 text-left">
                                                                <span class="block text-sm font-semibold text-slate-800 text-left">Catatan alasan pemindahan</span>
                                                                <button type="button" data-open-note-dialog="move-note-{{ $event->id }}" class="event-action-outline rounded-md px-3 py-1.5 text-xs font-semibold">
                                                                    Buka Catatan
                                                                </button>
                                                            </div>
                                                            <input type="hidden" id="move-reason-{{ $event->id }}" name="move_reason" value="{{ $event->move_reason }}">
                                                            <p id="move-reason-help-{{ $event->id }}" class="mt-2 text-xs text-slate-500 text-left" aria-live="polite">{{ $event->move_reason ? 'Catatan alasan pemindahan sudah tersimpan.' : 'Belum ada catatan. Tekan “Buka Catatan” untuk menulis alasan pemindahan.' }}</p>
                                                        </div>
                                                    </div>
                                                    <footer class="flex justify-end gap-2 border-t border-slate-300 bg-slate-50 px-6 py-4 text-left">
                                                        <button type="button" data-close-move-dialog class="event-action-outline rounded-md px-4 py-2 text-sm font-semibold">Batal</button>
                                                        <button type="submit" @disabled($destinationRooms->isEmpty()) class="event-action-primary rounded-md px-4 py-2 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50">Simpan &amp; Beri Tahu via WhatsApp</button>
                                                    </footer>
                                                </form>
                                            </dialog>
                                            <div id="move-note-{{ $event->id }}" data-note-panel hidden class="whitespace-normal fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="move-note-title-{{ $event->id }}">
                                                <form method="POST" action="{{ route('admin.events.move-reason', $event) }}" data-move-note-form data-event-id="{{ $event->id }}" class="whitespace-normal mx-auto w-full max-w-xl overflow-hidden rounded-xl border border-slate-300 bg-white text-slate-900 shadow-2xl text-left">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="flex items-center justify-between border-b border-slate-300 bg-slate-50 px-5 py-4 text-left">
                                                        <div class="text-left">
                                                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-600">Catatan Pemindahan</p>
                                                            <h3 id="move-note-title-{{ $event->id }}" class="mt-1 text-lg font-semibold text-slate-900">Alasan untuk {{ $event->event_name }}</h3>
                                                        </div>
                                                        <button type="button" data-close-note-dialog class="rounded-md p-2 text-xl leading-none text-slate-500 hover:bg-slate-200" aria-label="Tutup catatan">&times;</button>
                                                    </div>
                                                    <div class="space-y-3 p-5 text-left">
                                                        <label for="move-note-input-{{ $event->id }}" class="block text-sm font-semibold text-slate-800 text-left">Catatan alasan</label>
                                                        <textarea id="move-note-input-{{ $event->id }}" name="move_reason" rows="10" maxlength="2000" required data-note-input-for="move-reason-{{ $event->id }}" placeholder="Tuliskan alasan pemindahan agenda..." class="w-full resize-y rounded-md border-slate-300 bg-white text-sm leading-6 focus:border-slate-700 focus:ring-slate-700 text-left">{{ $event->move_reason }}</textarea>
                                                        <p class="text-xs text-slate-500 text-left">Catatan akan tersimpan pada agenda ini dan ikut dikirim saat pemindahan dilakukan.</p>
                                                    </div>
                                                    <div class="flex justify-end gap-2 border-t border-slate-300 bg-slate-50 px-5 py-4 text-left">
                                                        <button type="button" data-close-note-dialog class="event-action-outline rounded-md px-4 py-2 text-sm font-semibold">Batal</button>
                                                        <button type="submit" class="event-action-primary rounded-md px-4 py-2 text-sm font-semibold">Simpan Catatan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif

                                        @if ($event->canBeCancelledByAdmin())
                                            <dialog id="admin-cancel-modal-{{ $event->id }}" data-admin-cancel-dialog class="whitespace-normal text-left w-[min(92vw,32rem)] rounded-2xl border border-slate-200 bg-transparent p-0 shadow-2xl backdrop:bg-slate-950/60 backdrop:backdrop-blur-xs">
                                                <form method="POST" action="{{ route('admin.events.cancel', $event) }}" class="whitespace-normal overflow-hidden rounded-2xl bg-white text-slate-900 text-left" data-confirm-action="membatalkan agenda dan membebaskan jadwal ruangan" data-confirm-item="Agenda: {{ $event->event_name }}" data-confirm-title="Konfirmasi Pembatalan Agenda" data-confirm-message="Apakah Anda yakin ingin membatalkan agenda ini? Status akan otomatis menjadi Dibatalkan dan ruangan akan langsung dibebaskan.">
                                                    @csrf
                                                    <div class="flex items-center justify-between border-b border-rose-100 bg-rose-50/70 px-5 py-4 text-left">
                                                        <div class="flex items-center gap-2.5">
                                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                                                                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <p class="text-xs font-semibold uppercase tracking-wider text-rose-700">Pembatalan Agenda</p>
                                                                <h3 class="text-base font-bold text-slate-900 truncate max-w-xs sm:max-w-sm">{{ $event->event_name }}</h3>
                                                            </div>
                                                        </div>
                                                        <button type="button" data-close-admin-cancel-dialog class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-100 hover:text-slate-700 transition" aria-label="Tutup dialog">&times;</button>
                                                    </div>

                                                    <div class="space-y-4 p-5 text-left">
                                                        <div class="whitespace-normal rounded-xl border border-amber-200 bg-amber-50/90 p-3.5 text-xs text-amber-900 shadow-2xs">
                                                            <div class="flex items-start gap-2.5">
                                                                <svg class="h-4 w-4 shrink-0 mt-0.5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                <div class="min-w-0 flex-1 leading-relaxed text-left">
                                                                    <span class="font-bold text-amber-950">Auto Approval:</span> Pembatalan dari Admin langsung diproses otomatis, status agenda menjadi <strong class="text-rose-700 font-bold">Dibatalkan</strong>, dan ruangan akan langsung berstatus <strong class="text-emerald-700 font-bold">Tersedia</strong> kembali.
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="whitespace-normal grid grid-cols-2 gap-2.5 rounded-xl bg-slate-50 p-3 text-xs text-slate-600 border border-slate-100">
                                                            <div>
                                                                <span class="block text-slate-400 font-medium">Ruangan:</span>
                                                                <span class="font-semibold text-slate-800 break-words">{{ $event->room_names ?: '-' }}</span>
                                                            </div>
                                                            <div>
                                                                <span class="block text-slate-400 font-medium">Jadwal Agenda:</span>
                                                                <span class="font-semibold text-slate-800 break-words">{{ $event->date }} ({{ substr($event->start_time, 0, 5) }}–{{ substr($event->end_time, 0, 5) }})</span>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <label for="admin-cancel-reason-{{ $event->id }}" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                                                Alasan Pembatalan <span class="text-slate-400 font-normal lowercase">(opsional)</span>:
                                                            </label>
                                                            <textarea 
                                                                id="admin-cancel-reason-{{ $event->id }}" 
                                                                name="cancellation_reason" 
                                                                rows="3" 
                                                                maxlength="1000" 
                                                                class="w-full resize-y rounded-xl border-slate-300 text-xs sm:text-sm focus:border-rose-600 focus:ring-1 focus:ring-rose-600 placeholder:text-slate-400" 
                                                                placeholder="Tuliskan alasan pembatalan agenda ini (opsional)..."
                                                            ></textarea>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50 px-5 py-3.5 text-left">
                                                        <button type="button" data-close-admin-cancel-dialog class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                                            Kembali
                                                        </button>
                                                        <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700 shadow-sm transition">
                                                            Ya, Batalkan Agenda
                                                        </button>
                                                    </div>
                                                </form>
                                            </dialog>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada agenda.</td></tr>
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
        @include('admin.events.partials.create-modal')
    </div>
</x-app-layout>

<script>
    window.bookedEventsData = @json($bookedEvents ?? []);

    function initMoveCombobox(comboboxEl) {
        if (!comboboxEl || comboboxEl.dataset.initialized === 'true') return;
        comboboxEl.dataset.initialized = 'true';

        const input = comboboxEl.querySelector('[data-move-combobox-input]');
        const toggleBtn = comboboxEl.querySelector('[data-move-combobox-toggle]');
        const arrow = comboboxEl.querySelector('[data-move-combobox-arrow]');
        const dropdown = comboboxEl.querySelector('[data-move-combobox-dropdown]');
        const list = comboboxEl.querySelector('[data-move-combobox-list]');
        const emptyEl = comboboxEl.querySelector('[data-move-combobox-empty]');
        const select = comboboxEl.querySelector('[data-move-room-select]');
        const parent = comboboxEl.closest('.text-left') || comboboxEl.parentElement;
        const warningEl = parent.querySelector('[data-move-occupied-warning]');
        const warningText = parent.querySelector('[data-move-warning-text]');

        if (!input || !dropdown || !list || !select) return;

        const eventId = comboboxEl.dataset.eventId;
        const startDate = comboboxEl.dataset.startDate;
        const endDate = comboboxEl.dataset.endDate || startDate;
        const startTime = comboboxEl.dataset.startTime || '00:00';
        const endTime = comboboxEl.dataset.endTime || '23:59';

        function getOccupiedRooms() {
            const occupiedMap = new Map();
            (window.bookedEventsData || []).forEach((ev) => {
                if (String(ev.id) === String(eventId)) return;
                if (ev.lifecycle_status === 'completed' || ev.lifecycle_status === 'rejected' || ev.lifecycle_status === 'cancelled') return;

                const evStartDate = ev.date;
                const evEndDate = ev.end_date || ev.date;
                const evStartTime = ev.start_time;
                const evEndTime = ev.end_time;

                const dateOverlap = evStartDate <= endDate && evEndDate >= startDate;
                const timeOverlap = evStartTime < endTime && evEndTime > startTime;

                if (dateOverlap && timeOverlap) {
                    (ev.room_ids || []).forEach((rId) => {
                        occupiedMap.set(String(rId), {
                            eventName: ev.event_name,
                            time: `${evStartTime} - ${evEndTime}`
                        });
                    });
                }
            });
            return occupiedMap;
        }

        function buildList() {
            const occupiedMap = getOccupiedRooms();
            list.innerHTML = '';
            Array.from(select.options).forEach((opt) => {
                if (opt.value === '') return;

                const li = document.createElement('li');
                li.dataset.value = opt.value;
                li.dataset.text = opt.text.trim();
                li.className = 'flex items-center justify-between px-3 py-2.5 cursor-pointer hover:bg-slate-100 transition-colors select-none text-slate-800 text-xs sm:text-sm';

                const nameWrap = document.createElement('div');
                nameWrap.className = 'flex items-center gap-1.5';
                const nameSpan = document.createElement('span');
                nameSpan.className = 'font-medium';
                nameSpan.textContent = opt.dataset.name || opt.text.split(' (')[0];
                nameWrap.append(nameSpan);

                const rightWrap = document.createElement('div');
                rightWrap.className = 'flex items-center gap-2';

                const statusSpan = document.createElement('span');
                const isActive = opt.dataset.active !== '0';

                if (!isActive) {
                    statusSpan.textContent = 'Dalam Perbaikan';
                    statusSpan.className = 'text-xs px-2 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-800 border border-amber-200';
                    li.classList.add('bg-amber-50/40');
                } else if (occupiedMap.has(String(opt.value))) {
                    statusSpan.textContent = 'Dipakai';
                    statusSpan.className = 'text-xs px-2 py-0.5 rounded-full font-semibold bg-red-100 text-red-700';
                    li.classList.add('bg-red-50/40');
                } else {
                    statusSpan.textContent = 'Tersedia';
                    statusSpan.className = 'text-xs px-2 py-0.5 rounded-full font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60';
                }

                const capSpan = document.createElement('span');
                capSpan.className = 'text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full';
                capSpan.textContent = opt.dataset.capacity
                    ? `Kapasitas ${opt.dataset.capacity}`
                    : (opt.text.includes('Kapasitas') ? opt.text.split('(')[1]?.replace(')', '') : '');

                rightWrap.append(statusSpan, capSpan);
                li.append(nameWrap, rightWrap);

                li.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (li.dataset.disabled === 'true') return;
                    selectOption(opt.value, opt.text.trim(), occupiedMap);
                });

                list.append(li);
            });
        }

        function selectOption(val, text, occupiedMap) {
            const targetOpt = Array.from(select.options).find((o) => String(o.value) === String(val));
            if (targetOpt && targetOpt.dataset.active === '0') {
                closeDropdown();
                if (typeof window.showRoomMaintenanceModal === 'function') {
                    window.showRoomMaintenanceModal({
                        roomName: targetOpt.dataset.name || targetOpt.text.split(' (')[0],
                        note: targetOpt.dataset.maintenanceNote || '',
                        picName: targetOpt.dataset.picName || '',
                        picPhone: targetOpt.dataset.picPhone || '',
                        picWa: targetOpt.dataset.picWa || '',
                    });
                }
                return;
            }

            const dialog = comboboxEl.closest('dialog');
            if (dialog) {
                const otherSelects = Array.from(dialog.querySelectorAll('[data-move-room-select]')).filter((s) => s !== select);
                const isDuplicate = otherSelects.some((s) => s.value && String(s.value) === String(val));
                if (isDuplicate) {
                    if (warningText) {
                        warningText.textContent = 'Ruangan ini sudah dipilih pada baris lain.';
                    }
                    warningEl?.classList.remove('hidden');
                    input.classList.add('border-red-500', 'ring-1', 'ring-red-500');
                    select.value = '';
                    input.value = '';
                    closeDropdown();
                    return;
                }
            }

            select.value = val;
            input.value = text;
            closeDropdown();
            select.dispatchEvent(new Event('change', { bubbles: true }));

            const occ = occupiedMap || getOccupiedRooms();
            if (occ.has(String(val))) {
                const info = occ.get(String(val));
                if (warningText) {
                    warningText.textContent = info?.eventName
                        ? `Ruangan ini berstatus Dipakai (ada agenda "${info.eventName}" pukul ${info.time}).`
                        : 'Ruangan ini berstatus Dipakai pada jadwal tersebut.';
                }
                warningEl?.classList.remove('hidden');
                input.classList.add('border-red-500', 'ring-1', 'ring-red-500');
            } else {
                warningEl?.classList.add('hidden');
                input.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
            }
        }

        function filterOptions(query) {
            const q = query.toLowerCase().trim();
            let matchCount = 0;
            list.querySelectorAll('li').forEach((li) => {
                const match = li.dataset.text.toLowerCase().includes(q);
                li.classList.toggle('hidden', !match);
                if (match) matchCount++;
            });
            emptyEl?.classList.toggle('hidden', matchCount > 0);
        }

        function openDropdown() {
            filterOptions(input.value);
            dropdown.classList.remove('hidden');
            arrow?.classList.add('rotate-180');
        }

        function closeDropdown() {
            dropdown.classList.add('hidden');
            arrow?.classList.remove('rotate-180');
        }

        input.addEventListener('focus', () => {
            buildList();
            openDropdown();
        });

        input.addEventListener('input', () => {
            filterOptions(input.value);
            dropdown.classList.remove('hidden');
            arrow?.classList.add('rotate-180');

            const exactMatch = Array.from(select.options).find(
                (o) => o.text.trim().toLowerCase() === input.value.trim().toLowerCase()
            );
            if (exactMatch) {
                selectOption(exactMatch.value, exactMatch.text.trim());
            } else {
                select.value = '';
                warningEl?.classList.add('hidden');
                input.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
            }
        });

        toggleBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            if (dropdown.classList.contains('hidden')) {
                buildList();
                openDropdown();
                input.focus();
            } else {
                closeDropdown();
            }
        });

        document.addEventListener('click', (e) => {
            if (!comboboxEl.contains(e.target)) {
                closeDropdown();
            }
        });

        buildList();
    }

    function initAllMoveComboboxes() {
        document.querySelectorAll('[data-move-combobox]').forEach(initMoveCombobox);
    }

    document.addEventListener('DOMContentLoaded', initAllMoveComboboxes);
    initAllMoveComboboxes();

    function updateMoveRoomRows(container) {
        if (!container) return;
        const rows = container.querySelectorAll('[data-move-room-row]');
        rows.forEach((row) => {
            const removeBtn = row.querySelector('[data-remove-move-room]');
            if (removeBtn) {
                removeBtn.classList.toggle('hidden', rows.length <= 1);
            }
        });
    }

    document.querySelectorAll('[data-add-move-room]').forEach((button) => {
        button.addEventListener('click', () => {
            const eventId = button.dataset.addMoveRoom;
            const dialog = document.getElementById(`move-event-${eventId}`);
            const template = document.getElementById(`move-room-template-${eventId}`);
            const container = dialog?.querySelector('[data-move-room-list]');
            if (!template || !container) return;

            const clone = template.content.cloneNode(true);
            const newRow = clone.querySelector('[data-move-room-row]');
            container.appendChild(clone);

            const newCombobox = newRow.querySelector('[data-move-combobox]');
            if (newCombobox) {
                initMoveCombobox(newCombobox);
            }

            updateMoveRoomRows(container);
            newRow.querySelector('[data-move-combobox-input]')?.focus();
        });
    });

    document.querySelectorAll('[data-move-room-list]').forEach((container) => {
        container.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('[data-remove-move-room]');
            if (!removeBtn) return;
            const row = removeBtn.closest('[data-move-room-row]');
            const rows = container.querySelectorAll('[data-move-room-row]');
            if (rows.length > 1 && row) {
                row.remove();
                updateMoveRoomRows(container);
            }
        });
    });

    document.querySelectorAll('[data-open-move-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.openMoveDialog);
            dialog?.showModal();
            dialog?.querySelectorAll('[data-move-combobox]').forEach(initMoveCombobox);
            const container = dialog?.querySelector('[data-move-room-list]');
            updateMoveRoomRows(container);
        });
    });

    document.querySelectorAll('[data-close-move-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('dialog')?.close();
        });
    });

    document.querySelectorAll('[data-open-note-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            const noteDialog = document.getElementById(button.dataset.openNoteDialog);
            const noteInput = noteDialog?.querySelector('[data-note-input-for]');
            const reasonField = document.getElementById(noteInput?.dataset.noteInputFor);

            if (noteInput instanceof HTMLTextAreaElement && reasonField instanceof HTMLInputElement) {
                noteInput.value = reasonField.value;
                button.closest('dialog')?.close();
                noteDialog.hidden = false;
                noteDialog.classList.add('flex');
                noteInput.focus();
            }
        });
    });

    document.querySelectorAll('[data-close-note-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            const notePanel = button.closest('[data-note-panel]');

            if (notePanel instanceof HTMLElement) {
                notePanel.hidden = true;
                notePanel.classList.remove('flex');
                document.getElementById(`move-event-${notePanel.id.replace('move-note-', '')}`)?.showModal();
            }
        });
    });

    document.querySelectorAll('[data-move-note-form]').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopImmediatePropagation();

            const eventId = form.dataset.eventId;
            const textarea = form.querySelector('textarea[name="move_reason"]');
            const reasonVal = textarea ? textarea.value.trim() : '';

            if (!reasonVal) {
                alert('Silakan tuliskan alasan pemindahan terlebih dahulu.');
                textarea?.focus();
                return;
            }

            const reasonInput = document.getElementById(`move-reason-${eventId}`);
            const reasonHelp = document.getElementById(`move-reason-help-${eventId}`);

            if (reasonInput) {
                reasonInput.value = reasonVal;
            }
            if (reasonHelp) {
                reasonHelp.textContent = 'Catatan alasan pemindahan sudah tersimpan.';
                reasonHelp.className = 'mt-2 text-xs font-semibold text-emerald-700 text-left';
            }

            // Simpan ke database via AJAX di latar belakang
            try {
                const formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });
            } catch (err) {
                console.error('Gagal menyimpan catatan pemindahan:', err);
            }

            // Tutup popup catatan dan kembali ke dialog pemindahan agenda
            const notePanel = form.closest('[data-note-panel]');
            if (notePanel instanceof HTMLElement) {
                notePanel.hidden = true;
                notePanel.classList.remove('flex');
            }

            const moveDialog = document.getElementById(`move-event-${eventId}`);
            if (moveDialog && typeof moveDialog.showModal === 'function') {
                moveDialog.showModal();
            }
        });
    });

    document.querySelectorAll('[data-move-form]').forEach((moveForm) => {
        moveForm.addEventListener('submit', (event) => {
            const selects = Array.from(moveForm.querySelectorAll('[data-move-room-select]'));
            const emptySelect = selects.find(s => !s.value);
            if (emptySelect) {
                event.preventDefault();
                event.stopImmediatePropagation();
                const emptyRow = emptySelect.closest('[data-move-room-row]');
                const emptyInput = emptyRow?.querySelector('[data-move-combobox-input]');
                emptyInput?.focus();
                alert('Silakan pilih ruangan tujuan pada setiap baris terlebih dahulu.');
                return;
            }

            const reasonField = moveForm.querySelector('input[name="move_reason"]');

            if (!(reasonField instanceof HTMLInputElement) || !reasonField.value.trim()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                const notePanel = document.getElementById(`move-note-${moveForm.dataset.moveEventId}`);

                if (notePanel instanceof HTMLElement) {
                    moveForm.closest('dialog')?.close();
                    notePanel.hidden = false;
                    notePanel.classList.add('flex');
                    notePanel.querySelector('textarea')?.focus();
                }
            }
        });
    });
    document.querySelectorAll('[data-open-admin-reject-cancel]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = document.getElementById(button.dataset.openAdminRejectCancel);
            const textarea = panel?.querySelector('textarea');

            if (panel instanceof HTMLElement) {
                panel.hidden = false;
                panel.classList.add('flex');
                textarea?.focus();
            }
        });
    });

    document.querySelectorAll('[data-close-admin-reject-cancel]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = button.closest('[data-admin-reject-cancel-panel]');

            if (panel instanceof HTMLElement) {
                panel.hidden = true;
                panel.classList.remove('flex');
            }
        });
    });

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

    document.querySelectorAll('[data-open-admin-reject]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const panel = document.getElementById(btn.dataset.openAdminReject);
            if (panel) {
                panel.hidden = false;
                panel.classList.add('flex');
            }
        });
    });

    document.querySelectorAll('[data-close-admin-reject]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const panel = btn.closest('[data-admin-reject-panel]');
            if (panel) {
                panel.hidden = true;
                panel.classList.remove('flex');
            }
        });
    });

    document.querySelectorAll('[data-admin-reject-panel]').forEach((panel) => {
        panel.addEventListener('click', (e) => {
            if (e.target === panel) {
                panel.hidden = true;
                panel.classList.remove('flex');
            }
        });
    });

    document.querySelectorAll('[data-admin-reject-cancel-panel]').forEach((panel) => {
        panel.addEventListener('click', (e) => {
            if (e.target === panel) {
                panel.hidden = true;
                panel.classList.remove('flex');
            }
        });
    });

    document.querySelectorAll('[data-close-admin-cancel-dialog]').forEach((btn) => {
        btn.addEventListener('click', () => {
            btn.closest('dialog')?.close();
        });
    });

    document.querySelectorAll('[data-admin-cancel-dialog]').forEach((dialog) => {
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) {
                dialog.close();
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[data-cancel-reason-panel]:not([hidden])').forEach((panel) => {
                panel.hidden = true;
                panel.classList.remove('flex');
            });
            document.querySelectorAll('[data-admin-reject-cancel-panel]:not([hidden])').forEach((panel) => {
                panel.hidden = true;
                panel.classList.remove('flex');
            });
            document.querySelectorAll('[data-admin-reject-panel]:not([hidden])').forEach((panel) => {
                panel.hidden = true;
                panel.classList.remove('flex');
            });
        }
    });
</script>
