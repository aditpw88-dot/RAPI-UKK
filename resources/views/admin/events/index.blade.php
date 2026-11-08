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
                    <option value="upcoming" @selected(request('status') === 'upcoming')>Belum Terlaksana</option>
                    <option value="in_progress" @selected(request('status') === 'in_progress')>Sedang Terlaksana</option>
                    <option value="completed" @selected(request('status') === 'completed')>Sudah Terlaksana</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Tanggal mulai" class="rounded-md border-gray-300 text-sm">
                <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Tanggal akhir" class="rounded-md border-gray-300 text-sm">
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Terapkan</button>
                    <a href="{{ route('admin.events') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto min-h-[320px]">
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
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                                                {{ strtoupper(substr($event->pic?->fullname ?? 'U', 0, 1)) }}
                                            </div>
                                            <span class="truncate max-w-[130px] text-sm">{{ $event->pic?->fullname ?? '-' }}</span>
                                        </div>
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
                                        @if ($event->status === 'pending')
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                <span>Menunggu</span>
                                            </span>
                                            <form method="POST" action="{{ route('admin.events.approve', $event) }}" class="mt-2" data-confirm-action="menyetujui agenda" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-md bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white shadow-2xs hover:bg-slate-700 transition">ACC Agenda</button>
                                            </form>
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
                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ $event->approver && (str_contains(strtolower($event->approver->role?->role_name ?? ''), 'admin') || str_contains(strtolower($event->approver->role?->role_name ?? ''), 'waka')) ? ($event->approved_by === $event->pic_id ? 'Disetujui otomatis (Admin)' : 'Disetujui Admin') : 'Disetujui PIC' }}
                                            </p>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                <span>Ditolak PIC</span>
                                            </span>
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
                                                    <div class="my-1 border-t border-slate-100"></div>
                                                @endif
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
                                            </div>
                                        </div>

                                        @if ($event->move_reason)
                                            <p class="mt-1 text-[11px] text-amber-700 font-medium" title="Alasan: {{ $event->move_reason }}">
                                                Dipindahkan
                                            </p>
                                        @endif

                                        @if ($event->status !== 'rejected')
                                            <dialog id="move-event-{{ $event->id }}" class="w-[min(92vw,32rem)] rounded-lg border border-slate-300 bg-transparent p-0 shadow-2xl backdrop:bg-slate-950/50">
                                                <form method="POST" action="{{ route('admin.events.move', $event) }}" class="overflow-hidden rounded-lg bg-white text-slate-900" data-move-form data-move-event-id="{{ $event->id }}" data-confirm-action="memindahkan ruangan untuk agenda" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <header class="border-b border-slate-300 bg-slate-50 px-6 py-4">
                                                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-600">Surat Pemindahan Ruangan</p>
                                                        <h3 class="mt-1 text-lg font-semibold">Pemindahan Agenda</h3>
                                                    </header>
                                                    <div class="space-y-4 px-6 py-5">
                                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                                            <p><span class="block text-xs text-slate-500">Agenda</span>{{ $event->event_name }}</p>
                                                            <p><span class="block text-xs text-slate-500">Tanggal</span><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></p>
                                                            <p><span class="block text-xs text-slate-500">Dari ruangan</span>{{ $event->room_names ?: '-' }}</p>
                                                            <p><span class="block text-xs text-slate-500">Waktu</span>{{ date('H:i', strtotime($event->start_time)) }} - {{ date('H:i', strtotime($event->end_time)) }}</p>
                                                        </div>
                                                        @php($destinationRooms = $rooms->whereNotIn('id', $event->reservedRoomIds()))
                                                        <div>
                                                            <label for="destination-room-{{ $event->id }}" class="block text-sm font-semibold">Ruangan tujuan</label>
                                                            <select id="destination-room-{{ $event->id }}" name="room_id" required @disabled($destinationRooms->isEmpty()) class="mt-1 w-full rounded-md border-slate-300 bg-white text-sm focus:border-slate-700 focus:ring-slate-700">
                                                                <option value="">Pilih ruangan aktif</option>
                                                                @foreach ($destinationRooms as $room)
                                                                    <option value="{{ $room->id }}">{{ $room->room_name }} (Kapasitas {{ $room->capacity }})</option>
                                                                @endforeach
                                                            </select>
                                                            @if ($destinationRooms->isEmpty())
                                                                <p class="mt-1 text-xs text-slate-600">Tidak ada ruangan aktif lain yang dapat dipilih.</p>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <div class="flex items-center justify-between gap-3">
                                                                <span class="block text-sm font-semibold">Catatan alasan pemindahan</span>
                                                                <button type="button" data-open-note-dialog="move-note-{{ $event->id }}" class="event-action-outline rounded-md px-3 py-1.5 text-xs font-semibold">
                                                                    Buka Catatan
                                                                </button>
                                                            </div>
                                                            <input type="hidden" id="move-reason-{{ $event->id }}" name="move_reason" value="{{ $event->move_reason }}">
                                                            <p id="move-reason-help-{{ $event->id }}" class="mt-2 text-xs text-slate-500" aria-live="polite">{{ $event->move_reason ? 'Catatan alasan pemindahan sudah tersimpan.' : 'Belum ada catatan. Tekan “Buka Catatan” untuk menulis alasan pemindahan.' }}</p>
                                                        </div>
                                                    </div>
                                                    <footer class="flex justify-end gap-2 border-t border-slate-300 bg-slate-50 px-6 py-4">
                                                        <button type="button" data-close-move-dialog class="event-action-outline rounded-md px-4 py-2 text-sm font-semibold">Batal</button>
                                                        <button type="submit" @disabled($destinationRooms->isEmpty()) class="event-action-primary rounded-md px-4 py-2 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50">Simpan &amp; Beri Tahu via WhatsApp</button>
                                                    </footer>
                                                </form>
                                            </dialog>
                                            <div id="move-note-{{ $event->id }}" data-note-panel hidden class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="move-note-title-{{ $event->id }}">
                                                <form method="POST" action="{{ route('admin.events.move-reason', $event) }}" class="mx-auto w-full max-w-xl overflow-hidden rounded-xl border border-slate-300 bg-white text-slate-900 shadow-2xl" data-confirm-action="menyimpan catatan alasan pemindahan" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="flex items-center justify-between border-b border-slate-300 bg-slate-50 px-5 py-4">
                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-600">Catatan Pemindahan</p>
                                                            <h3 id="move-note-title-{{ $event->id }}" class="mt-1 text-lg font-semibold">Alasan untuk {{ $event->event_name }}</h3>
                                                        </div>
                                                        <button type="button" data-close-note-dialog class="rounded-md p-2 text-xl leading-none text-slate-500 hover:bg-slate-200" aria-label="Tutup catatan">&times;</button>
                                                    </div>
                                                    <div class="space-y-3 p-5">
                                                        <label for="move-note-input-{{ $event->id }}" class="block text-sm font-semibold">Catatan alasan</label>
                                                        <textarea id="move-note-input-{{ $event->id }}" name="move_reason" rows="10" maxlength="2000" required data-note-input-for="move-reason-{{ $event->id }}" placeholder="Tuliskan alasan pemindahan agenda..." class="w-full resize-y rounded-md border-slate-300 bg-white text-sm leading-6 focus:border-slate-700 focus:ring-slate-700">{{ $event->move_reason }}</textarea>
                                                        <p class="text-xs text-slate-500">Catatan akan tersimpan pada agenda ini dan ikut dikirim saat pemindahan dilakukan.</p>
                                                    </div>
                                                    <div class="flex justify-end gap-2 border-t border-slate-300 bg-slate-50 px-5 py-4">
                                                        <button type="button" data-close-note-dialog class="event-action-outline rounded-md px-4 py-2 text-sm font-semibold">Batal</button>
                                                        <button type="submit" class="event-action-primary rounded-md px-4 py-2 text-sm font-semibold">Simpan Catatan</button>
                                                    </div>
                                                </form>
                                            </div>
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
    document.querySelectorAll('[data-open-move-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById(button.dataset.openMoveDialog)?.showModal();
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

    document.querySelectorAll('[data-move-form]').forEach((moveForm) => {
        moveForm.addEventListener('submit', (event) => {
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
</script>
