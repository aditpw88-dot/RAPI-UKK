<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Portal Guru / Organisasi</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900 tracking-tight">Dashboard Guru</h2>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500">Ajukan peminjaman ruang, pantau agenda kegiatan, dan laporkan kendala fasilitas.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-6 sm:px-6 lg:px-8">
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

            <!-- 1. Kartu Statistik Ringkasan -->
            <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
                <!-- Card 1: Total Pengajuan -->
                <a href="{{ route('guru.events.history') }}" class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-xs transition hover:border-amber-400 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Pengajuan</p>
                        <span class="rounded-xl bg-slate-100 p-2 text-slate-600 group-hover:bg-amber-50 group-hover:text-amber-800 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-extrabold text-slate-900 group-hover:text-amber-800 transition-colors">{{ $events->count() }}</p>
                    <p class="mt-1 text-xs font-medium text-slate-400 flex items-center gap-1 group-hover:text-amber-700">
                        <span>Semua riwayat peminjaman</span>
                        <span>&rarr;</span>
                    </p>
                </a>

                <!-- Card 2: Menunggu Review -->
                <a href="{{ route('guru.events.history', ['status' => 'pending']) }}" class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-xs transition hover:border-blue-400 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Menunggu Review</p>
                        <span class="rounded-xl bg-blue-50 p-2 text-blue-700 group-hover:bg-blue-100 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-extrabold text-blue-700">{{ $pendingEvents }}</p>
                    <p class="mt-1 text-xs font-medium text-slate-400 flex items-center gap-1 group-hover:text-blue-700">
                        <span>Menunggu konfirmasi PIC</span>
                        <span>&rarr;</span>
                    </p>
                </a>

                <!-- Card 3: Sedang Terlaksana -->
                <a href="{{ route('guru.events.history', ['status' => 'in_progress']) }}" class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-xs transition hover:border-emerald-400 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Sedang Terlaksana</p>
                        <span class="rounded-xl bg-emerald-50 p-2 text-emerald-700 group-hover:bg-emerald-100 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-extrabold text-emerald-700">{{ $approvedEvents }}</p>
                    <p class="mt-1 text-xs font-medium text-slate-400 flex items-center gap-1 group-hover:text-emerald-700">
                        <span>Agenda aktif saat ini</span>
                        <span>&rarr;</span>
                    </p>
                </a>

                <!-- Card 4: Pengaduan Terbuka -->
                <a href="{{ route('guru.complaints.status') }}" class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-xs transition hover:border-amber-400 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Kerusakan Fasilitas</p>
                        <span class="rounded-xl bg-amber-50 p-2 text-amber-700 group-hover:bg-amber-100 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-extrabold text-amber-700">{{ $openComplaints }}</p>
                    <p class="mt-1 text-xs font-medium text-slate-400 flex items-center gap-1 group-hover:text-amber-700">
                        <span>Laporan belum selesai</span>
                        <span>&rarr;</span>
                    </p>
                </a>
            </div>

            <!-- 3. Tiga Shortcut Aksi Utama -->
            <div class="grid gap-4 sm:grid-cols-3">
                <!-- Shortcut 1: Ajukan Peminjaman Ruang -->
                <a href="{{ route('events.create') }}" class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5 shadow-xs transition hover:border-amber-400 hover:shadow-md hover:-translate-y-0.5">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 border border-amber-200 group-hover:bg-amber-100 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V5l7-3 7 3v16M9 9h2m-2 4h2m2-4h2m-2 4h2" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-900 group-hover:text-amber-800 transition-colors">
                                Ajukan Peminjaman
                            </h4>
                            <p class="text-xs text-slate-500">Pesan ruang rapat, kelas, atau lab</p>
                        </div>
                    </div>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 group-hover:text-amber-700 group-hover:translate-x-0.5 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>

                <!-- Shortcut 2: Kalender Pemakaian -->
                <a href="{{ route('calendar') }}" class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5 shadow-xs transition hover:border-emerald-400 hover:shadow-md hover:-translate-y-0.5">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 group-hover:bg-emerald-100 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-900 group-hover:text-emerald-800 transition-colors">
                                Kalender Ruang
                            </h4>
                            <p class="text-xs text-slate-500">Cek jadwal pemakaian dan slot kosong</p>
                        </div>
                    </div>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>

                <!-- Shortcut 3: Laporkan Kerusakan -->
                <a href="{{ route('guru.complaints.create') }}" class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5 shadow-xs transition hover:border-red-400 hover:shadow-md hover:-translate-y-0.5">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-700 border border-red-200 group-hover:bg-red-100 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.8L2.7 17a2 2 0 001.7 3h15.2a2 2 0 001.7-3L13.7 3.8a2 2 0 00-3.4 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-900 group-hover:text-red-800 transition-colors">
                                Laporkan Kerusakan
                            </h4>
                            <p class="text-xs text-slate-500">Kirim laporan sarana ruang yang rusak</p>
                        </div>
                    </div>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 group-hover:text-red-700 group-hover:translate-x-0.5 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            </div>

            <!-- 4. Tabel Pengajuan Agenda Terbaru Saya -->
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5 sm:p-6 bg-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="font-bold text-slate-900 text-lg sm:text-xl">Pengajuan Agenda Terbaru</h3>
                            <span class="rounded-full bg-slate-100 border border-slate-200 px-3 py-0.5 text-xs font-semibold text-slate-700">
                                5 Terakhir
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            Daftar status pengajuan peminjaman ruangan yang telah Anda buat.
                        </p>
                    </div>
                    <a href="{{ route('guru.events.history') }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-amber-800 hover:text-amber-950 transition">
                        <span>Lihat Semua Riwayat</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                @if ($events->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h4 class="mt-3 text-sm font-bold text-slate-900">Belum Ada Pengajuan Agenda</h4>
                        <p class="mt-1 text-xs text-slate-500 max-w-sm mx-auto">Anda belum pernah mengajukan peminjaman ruangan. Mulai jadwalkan kegiatan Anda sekarang.</p>
                        <div class="mt-4">
                            <a href="{{ route('events.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-slate-700 transition">
                                <span>Ajukan Peminjaman Ruang</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5 font-bold">Agenda</th>
                                    <th scope="col" class="px-5 py-3.5 font-bold">Ruangan</th>
                                    <th scope="col" class="px-5 py-3.5 font-bold">Jadwal Pelaksanaan</th>
                                    <th scope="col" class="px-5 py-3.5 font-bold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($events->take(5) as $event)
                                    <tr class="align-middle hover:bg-slate-50/70 transition-colors">
                                        <td class="px-5 py-4 align-middle">
                                            <p class="font-bold text-slate-900 text-sm leading-snug">{{ $event->event_name }}</p>
                                            @if ($event->event_type)
                                                <span class="mt-0.5 inline-block text-[11px] font-medium text-slate-500 uppercase tracking-wide">
                                                    {{ config("event_types.{$event->event_type}.label") ?? ucfirst($event->event_type) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 align-middle text-slate-800 font-semibold text-sm">
                                            {{ $event->room_names ?: '-' }}
                                        </td>
                                        <td class="px-5 py-4 align-middle whitespace-nowrap">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-1.5 font-semibold text-slate-900 text-xs sm:text-sm">
                                                    <svg class="h-4 w-4 text-amber-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                                                <div class="flex items-center gap-2">
                                                    @if ($startSessionIndex !== false && $endSessionIndex !== false)
                                                        <span class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-900">
                                                            Sesi {{ $startSessionIndex }}{{ $startSessionIndex !== $endSessionIndex ? '–' . $endSessionIndex : '' }}
                                                        </span>
                                                    @endif
                                                    <span class="font-mono text-xs text-slate-600 font-semibold">
                                                        {{ $startFormatted }}–{{ $endFormatted }} WIB
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 align-middle whitespace-nowrap">
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
                                            @elseif ($event->status === 'pending')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-2xs">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                    <span>Menunggu Review</span>
                                                </span>
                                            @elseif ($event->status === 'rejected')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 shadow-2xs">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                    <span>Ditolak</span>
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <!-- 5. Dua Kolom: Laporan Kerusakan Terkini & Panduan Tata Tertib -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Kolom Kiri: Laporan Kerusakan Terkini Saya -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                            <div>
                                <h4 class="font-bold text-slate-900 text-base sm:text-lg">Pengaduan Kerusakan Saya</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Pantau status penanganan sarana yang dilaporkan</p>
                            </div>
                            <a href="{{ route('guru.complaints.status') }}" class="text-xs font-bold text-amber-800 hover:text-amber-950 transition">
                                Semua &rarr;
                            </a>
                        </div>

                        <div class="mt-4 divide-y divide-gray-100">
                            @forelse ($recentComplaints as $complaint)
                                <div class="py-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $complaint->room?->room_name ?? 'Ruang Umum' }}</p>
                                        <p class="text-xs text-slate-500 line-clamp-1 mt-0.5">{{ $complaint->description }}</p>
                                        <p class="text-[11px] text-slate-400 mt-1">{{ $complaint->reported_at?->format('d M Y, H:i') ?? '-' }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold border 
                                        {{ $complaint->status_repair === 'Sudah Diperbaiki' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 
                                          ($complaint->status_repair === 'Sedang Diperbaiki' ? 'border-blue-200 bg-blue-50 text-blue-900' : 
                                          ($complaint->status_repair === 'Menunggu Spare Part' ? 'border-amber-200 bg-amber-50 text-amber-800' : 
                                          'border-red-200 bg-red-50 text-red-800')) }}">
                                        {{ $complaint->status_repair }}
                                    </span>
                                </div>
                            @empty
                                <div class="py-8 text-center text-xs text-slate-500">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400 mb-2">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p class="font-medium text-slate-700">Belum ada pengaduan kerusakan.</p>
                                    <p class="mt-1 text-slate-400">Jika ada fasilitas ruang yang rusak, segera laporkan agar PIC dapat menindaklanjutinya.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <a href="{{ route('guru.complaints.create') }}" class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Buat Laporan Kerusakan Baru</span>
                        </a>
                    </div>
                </div>

                <!-- Kolom Kanan: Panduan Singkat Peminjaman Ruang -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-7 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-3.5 pb-5 border-b border-gray-100">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-800 border border-amber-200">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 text-lg sm:text-xl">Panduan & Tata Tertib</h4>
                                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Ketentuan pemakaian fasilitas & sarana sekolah</p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-4">
                            <!-- Poin 1 -->
                            <div class="flex items-start gap-4 rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 transition hover:bg-amber-50/40 hover:border-amber-200">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-sm font-extrabold text-amber-900 shadow-2xs">
                                    1
                                </span>
                                <div class="min-w-0">
                                    <h5 class="text-sm sm:text-base font-bold text-slate-900">Pengajuan Tepat Waktu</h5>
                                    <p class="mt-1 text-xs sm:text-sm text-slate-600 leading-relaxed">
                                        Lakukan peminjaman minimal <strong class="font-semibold text-slate-800">H-1 sebelum agenda</strong> agar PIC ruang dapat memeriksa dan menyetujui jadwal.
                                    </p>
                                </div>
                            </div>

                            <!-- Poin 2 -->
                            <div class="flex items-start gap-4 rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 transition hover:bg-amber-50/40 hover:border-amber-200">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-sm font-extrabold text-amber-900 shadow-2xs">
                                    2
                                </span>
                                <div class="min-w-0">
                                    <h5 class="text-sm sm:text-base font-bold text-slate-900">Cek Ketersediaan Ruangan</h5>
                                    <p class="mt-1 text-xs sm:text-sm text-slate-600 leading-relaxed">
                                        Periksa kalender ruangan terlebih dahulu untuk memastikan tidak ada agenda bersamaan di sesi yang sama.
                                    </p>
                                </div>
                            </div>

                            <!-- Poin 3 -->
                            <div class="flex items-start gap-4 rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 transition hover:bg-amber-50/40 hover:border-amber-200">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-sm font-extrabold text-amber-900 shadow-2xs">
                                    3
                                </span>
                                <div class="min-w-0">
                                    <h5 class="text-sm sm:text-base font-bold text-slate-900">Kebersihan & Hemat Listrik</h5>
                                    <p class="mt-1 text-xs sm:text-sm text-slate-600 leading-relaxed">
                                        Pastikan AC, LCD Proyektor, lampu, dan pintu telah dimatikan dan dikunci setelah kegiatan berakhir.
                                    </p>
                                </div>
                            </div>

                            <!-- Poin 4 -->
                            <div class="flex items-start gap-4 rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 transition hover:bg-amber-50/40 hover:border-amber-200">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-sm font-extrabold text-amber-900 shadow-2xs">
                                    4
                                </span>
                                <div class="min-w-0">
                                    <h5 class="text-sm sm:text-base font-bold text-slate-900">Tanggap Kerusakan Fasilitas</h5>
                                    <p class="mt-1 text-xs sm:text-sm text-slate-600 leading-relaxed">
                                        Bila menemukan sarana rusak, segera kirim laporan kerusakan melalui sistem agar cepat ditangani teknisi.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
