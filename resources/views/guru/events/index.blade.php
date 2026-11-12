<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">Portal Guru/Organisasi</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Manajemen Agenda</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau seluruh jadwal pelaksanaan agenda dan kegiatan sekolah.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-800 shadow-2xs" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800 shadow-2xs" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800 shadow-2xs" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <!-- Header Card dengan Shortcut Buat Agenda Sendiri -->
                <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900">DAFTAR SEMUA AGENDA</h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Menampilkan seluruh agenda kegiatan dan penggunaan ruang di sekolah.
                        </p>
                    </div>
                    <!-- Shortcut Utama: Ajukan Agenda Baru -->
                    <a href="{{ route('events.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Ajukan Agenda Baru</span>
                    </a>
                </div>

                <!-- Form Filter & Pencarian -->
                <form method="GET" action="{{ route('guru.events') }}" class="grid gap-3 border-b border-gray-200 bg-gray-50 px-6 py-4 sm:grid-cols-2 lg:grid-cols-5">
                    <input 
                        type="search" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Cari agenda, ruang, atau peminjam" 
                        aria-label="Cari agenda, ruang, atau peminjam" 
                        class="rounded-lg border-gray-300 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800"
                    >
                    <select name="status" aria-label="Filter status agenda" class="rounded-lg border-gray-300 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                        <option value="">Semua status</option>
                        <option value="upcoming" @selected(request('status') === 'upcoming')>Belum Terlaksana</option>
                        <option value="in_progress" @selected(request('status') === 'in_progress')>Sedang Terlaksana</option>
                        <option value="completed" @selected(request('status') === 'completed')>Sudah Terlaksana</option>
                        <option value="pending" @selected(request('status') === 'pending')>Menunggu Persetujuan</option>
                        <option value="cancellation_pending" @selected(request('status') === 'cancellation_pending')>Menunggu Pembatalan</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                    </select>
                    <div class="relative w-full">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari Tanggal" class="w-full rounded-lg border-gray-300 pl-8 pr-2 py-2 text-sm text-slate-800 focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    </div>
                    <div class="relative w-full">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" title="Sampai Tanggal" class="w-full rounded-lg border-gray-300 pl-8 pr-2 py-2 text-sm text-slate-800 focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition">Terapkan</button>
                        <a href="{{ route('guru.events') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-white transition">Reset</a>
                    </div>
                </form>

                <!-- Tabel Agenda -->
                @if ($events->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="mt-3 font-semibold text-slate-800">Tidak ada agenda yang ditemukan.</p>
                        <p class="mt-1 text-sm text-slate-500">Coba sesuaikan kata kunci pencarian atau ubah filter status.</p>
                        <div class="mt-5">
                            <a href="{{ route('events.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                + Ajukan Agenda Baru
                            </a>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto min-h-[420px]">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 font-bold">Agenda</th>
                                    <th scope="col" class="px-6 py-3.5 font-bold">Ruangan</th>
                                    <th scope="col" class="px-6 py-3.5 font-bold">Peminjam</th>
                                    <th scope="col" class="px-6 py-3.5 font-bold">Jadwal Pelaksanaan</th>
                                    <th scope="col" class="px-6 py-3.5 font-bold">Status</th>
                                    <th scope="col" class="px-6 py-3.5 font-bold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($events as $index => $event)
                                    @php
                                        $isMine = (int) $event->pic_id === (int) auth()->id();
                                        $startDate = \Illuminate\Support\Carbon::parse($event->date)->format('d/m/Y');
                                        $endDate = $event->end_date ? \Illuminate\Support\Carbon::parse($event->end_date)->format('d/m/Y') : null;
                                        $isMultiDay = $endDate && $endDate !== $startDate;
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition {{ $isMine ? 'bg-amber-50/20' : '' }}">
                                        <!-- Agenda -->
                                        <td class="px-6 py-4 align-top">
                                            <div class="flex items-start gap-2">
                                                <div>
                                                    <p class="font-bold text-slate-900">{{ $event->event_name }}</p>
                                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                        @if ($event->event_type)
                                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                                                {{ $event->event_type }}
                                                            </span>
                                                        @endif
                                                        @if ($isMine)
                                                            <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800 border border-amber-200">
                                                                Agenda Saya
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Ruangan -->
                                        <td class="px-6 py-4 align-top">
                                            <div class="flex items-center gap-1.5 text-slate-800 font-medium">
                                                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                <span>{{ $event->room_names ?: ($event->room?->room_name ?? '-') }}</span>
                                            </div>
                                        </td>

                                        <!-- Peminjam -->
                                        <td class="px-6 py-4 align-top">
                                            <div class="flex items-center gap-2">
                                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                                                    {{ strtoupper(substr($event->pic?->fullname ?? 'U', 0, 1)) }}
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-slate-900 leading-tight">{{ $event->pic?->fullname ?? 'Guru' }}</p>
                                                    <p class="text-[11px] text-slate-400">{{ $event->pic?->role?->role_name ?? 'Pengaju' }}</p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Jadwal -->
                                        <td class="px-6 py-4 align-top text-xs text-slate-700">
                                            <p class="font-semibold text-slate-900">
                                                @if ($isMultiDay)
                                                    {{ $startDate }} s.d. {{ $endDate }}
                                                @else
                                                    {{ $startDate }}
                                                @endif
                                            </p>
                                            <p class="mt-0.5 text-slate-500 font-medium">
                                                {{ substr($event->start_time, 0, 5) }} – {{ substr($event->end_time, 0, 5) }} WIB
                                            </p>
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-4 align-top">
                                            @if ($event->status === 'approved')
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
                                                <p class="mt-1 text-[11px] text-slate-500">Disetujui</p>
                                            @elseif ($event->status === 'pending')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                    <span>Menunggu Persetujuan</span>
                                                </span>
                                            @elseif ($event->status === 'rejected')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                    <span>Ditolak</span>
                                                </span>
                                            @elseif ($event->status === 'cancelled')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                    <span>Dibatalkan</span>
                                                </span>
                                            @endif

                                            @if ($event->cancellation_status === 'pending')
                                                <p class="mt-1 text-[11px] font-bold text-purple-700">Menunggu Pembatalan</p>
                                            @endif
                                        </td>

                                        <!-- Aksi -->
                                        <td class="px-6 py-4 align-top text-center">
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
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition-all focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                                                    aria-haspopup="true"
                                                    :aria-expanded="open"
                                                    title="Pilihan tindakan agenda"
                                                >
                                                    <span class="sr-only">Menu aksi agenda</span>
                                                    <svg width="17" height="17" style="width:17px;height:17px;min-width:17px;max-width:17px;" class="transition-transform duration-200" :class="open ? 'rotate-90 text-amber-800' : 'text-slate-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
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
                                                    :class="({{ $events->count() }} <= 2 || {{ $index }} < 3) ? 'top-full mt-1.5 origin-top-right' : 'bottom-full mb-1.5 origin-bottom-right'"
                                                    class="absolute right-0 z-40 min-w-[220px] w-max max-w-xs rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl ring-1 ring-black/5 text-left focus:outline-none"
                                                    style="display: none;"
                                                >
                                                    @if ($event->room_id && $event->status !== 'rejected')
                                                        <a 
                                                            href="{{ route('guru.complaints.create', ['room_id' => $event->room_id]) }}" 
                                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-700 whitespace-nowrap transition hover:bg-slate-100 hover:text-slate-900"
                                                        >
                                                            <svg width="15" height="15" class="text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                            </svg>
                                                            <span>Laporkan Kerusakan Ruangan</span>
                                                        </a>
                                                        <div class="my-1 border-t border-slate-100"></div>
                                                    @endif

                                                    @if ($isMine && $event->canBeCancelledByGuru())
                                                        <button 
                                                            type="button" 
                                                            data-open-cancel-modal="cancel-modal-{{ $event->id }}"
                                                            @click="open = false"
                                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-semibold text-rose-600 whitespace-nowrap transition hover:bg-rose-50 hover:text-rose-700"
                                                        >
                                                            <svg width="15" height="15" class="text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                            <span>Batalkan Agenda</span>
                                                        </button>
                                                    @elseif ($isMine && $event->cancellation_status === 'pending')
                                                        <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-purple-700 bg-purple-50">
                                                            <svg class="h-3.5 w-3.5 text-purple-600 animate-spin shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                            </svg>
                                                            <span>Menunggu Pembatalan</span>
                                                        </div>
                                                    @elseif ($isMine)
                                                        <div class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-400 cursor-not-allowed select-none">
                                                            <svg width="15" height="15" class="text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                            </svg>
                                                            <span>Tidak Dapat Dibatalkan</span>
                                                        </div>
                                                    @else
                                                        <div class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-400 cursor-not-allowed select-none" title="Hanya peminjam yang dapat membatalkan agenda ini">
                                                            <svg width="15" height="15" class="text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                            </svg>
                                                            <span>Bukan Agenda Anda</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @if ($isMine && $event->canBeCancelledByGuru())
                                        <tr>
                                            <td colspan="6" class="p-0">
                                                <div id="cancel-modal-{{ $event->id }}" data-cancel-modal-panel hidden class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="cancel-title-{{ $event->id }}">
                                                    <form method="POST" action="{{ route('guru.events.cancel-request', $event) }}" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" data-confirm-action="mengajukan pembatalan agenda ini" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                        @csrf
                                                        <div class="flex items-start justify-between gap-4">
                                                            <div>
                                                                <h3 id="cancel-title-{{ $event->id }}" class="text-lg font-bold text-slate-900">Pengajuan Pembatalan Agenda</h3>
                                                                <p class="mt-1 text-sm text-slate-500">{{ $event->event_name }}</p>
                                                            </div>
                                                            <button type="button" data-close-cancel-modal class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup dialog">&times;</button>
                                                        </div>
                                                        <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Pembatalan memerlukan persetujuan Admin atau PIC Ruang.</p>
                                                        <label for="cancellation-reason-{{ $event->id }}" class="mt-4 block text-sm font-semibold text-slate-700">Alasan Pembatalan</label>
                                                        <textarea id="cancellation-reason-{{ $event->id }}" name="cancellation_reason" rows="4" maxlength="1000" required minlength="5" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500" placeholder="Tuliskan alasan pembatalan agenda..."></textarea>
                                                        <div class="mt-5 flex justify-end gap-2">
                                                            <button type="button" data-close-cancel-modal class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Kembali</button>
                                                            <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Kirim Pengajuan Batal</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($events->hasPages())
                        <div class="border-t border-gray-200 bg-gray-50/50 px-6 py-3.5">
                            {{ $events->links() }}
                        </div>
                    @endif
                @endif
            </section>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-open-cancel-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.openCancelModal);
                if (modal instanceof HTMLElement) {
                    modal.hidden = false;
                    modal.classList.add('flex');
                    modal.querySelector('textarea')?.focus();
                }
            });
        });
        document.querySelectorAll('[data-close-cancel-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('[data-cancel-modal-panel]');
                if (modal instanceof HTMLElement) {
                    modal.hidden = true;
                    modal.classList.remove('flex');
                }
            });
        });
    </script>
</x-app-layout>
