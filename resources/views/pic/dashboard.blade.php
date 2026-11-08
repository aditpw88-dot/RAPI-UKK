<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Panel PIC Ruang</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-900 tracking-tight">Dashboard PIC</h2>
                <p class="mt-0.5 text-xs sm:text-sm text-slate-500">Pantau pengajuan agenda dan kelola ruang di bawah tanggung jawab Anda.</p>
            </div>
            <div class="hidden sm:flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-xl bg-white border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>Sistem Aktif</span>
                </span>
            </div>
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

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800 shadow-2xs" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- 1. Dua Kartu Ringkasan Statistik Utama -->
            <div class="grid gap-6 sm:grid-cols-2">
                <!-- Card 1: Ruang Tanggung Jawab -->
                <a href="{{ route('pic.rooms') }}" class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-amber-400 hover:shadow-md">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Ruang Tanggung Jawab</p>
                        <p class="mt-2 text-3xl sm:text-4xl font-extrabold text-slate-900 group-hover:text-amber-800 transition-colors">
                            {{ $rooms->count() }}
                        </p>
                        <p class="mt-2 flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-amber-700 group-hover:text-amber-900 transition-colors">
                            <span>Buka Manajemen Ruang</span>
                            <span class="transition-transform group-hover:translate-x-1">&rarr;</span>
                        </p>
                    </div>
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 border border-amber-200 group-hover:bg-amber-100 transition-colors">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </a>

                <!-- Card 2: Menunggu Diproses -->
                <a href="{{ route('pic.approvals') }}" class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-blue-400 hover:shadow-md">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Menunggu Diproses</p>
                        <p class="mt-2 text-3xl sm:text-4xl font-extrabold text-slate-900 group-hover:text-blue-800 transition-colors">
                            {{ $pendingEvents }}
                        </p>
                        <p class="mt-2 flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-blue-700 group-hover:text-blue-900 transition-colors">
                            <span>Tinjau Persetujuan Agenda</span>
                            <span class="transition-transform group-hover:translate-x-1">&rarr;</span>
                        </p>
                    </div>
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 border border-blue-200 group-hover:bg-blue-100 transition-colors">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </a>
            </div>

            <!-- 2. Tiga Shortcut Horizontal (Kerusakan, Laporan Kerusakan, Kalender) -->
            <div class="grid gap-4 sm:grid-cols-3">
                <!-- Shortcut 1: Kerusakan -->
                <a href="{{ route('pic.complaints.index') }}" class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-amber-400 hover:shadow-md hover:-translate-y-0.5">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 border border-amber-200 group-hover:bg-amber-100 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.8L2.7 17a2 2 0 001.7 3h15.2a2 2 0 001.7 3z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-900 group-hover:text-amber-800 transition-colors">
                                Kerusakan
                            </h4>
                            <p class="text-xs text-slate-500">Kelola pengaduan fasilitas</p>
                        </div>
                    </div>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 group-hover:text-amber-700 group-hover:translate-x-0.5 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>

                <!-- Shortcut 2: Laporan Kerusakan -->
                <a href="{{ route('pic.reports.complaints') }}" class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-400 hover:shadow-md hover:-translate-y-0.5">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 group-hover:bg-indigo-100 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8m-8 4h8m-8 4h5m5 5H6a2 2 0 01-2-2V6a2 2 0 012-2h8l5 5v9a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-900 group-hover:text-indigo-800 transition-colors">
                                Laporan Kerusakan
                            </h4>
                            <p class="text-xs text-slate-500">Rekapitulasi PDF &amp; CSV</p>
                        </div>
                    </div>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 group-hover:text-indigo-700 group-hover:translate-x-0.5 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>

                <!-- Shortcut 3: Kalender -->
                <a href="{{ route('pic.calendar') }}" class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-emerald-400 hover:shadow-md hover:-translate-y-0.5">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 group-hover:bg-emerald-100 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-900 group-hover:text-emerald-800 transition-colors">
                                Kalender
                            </h4>
                            <p class="text-xs text-slate-500">Jadwal pemakaian ruang</p>
                        </div>
                    </div>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            </div>

            <!-- 3. Tabel Pengajuan Agenda -->
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5 sm:p-6 bg-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="font-bold text-slate-900 text-lg sm:text-xl">Pengajuan Agenda</h3>
                            <span class="rounded-full bg-slate-100 border border-slate-200 px-3 py-0.5 text-xs font-semibold text-slate-700">
                                Total: {{ $events->count() }} Agenda
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            Daftar agenda pelaksanaan peminjaman yang dijadwalkan pada ruang tanggung jawab Anda.
                        </p>
                    </div>
                </div>

                @if ($events->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h4 class="mt-3 text-sm font-bold text-slate-900">Belum Ada Pengajuan Agenda</h4>
                        <p class="mt-1 text-xs text-slate-500 max-w-sm mx-auto">Saat ini belum ada pengajuan peminjaman ruangan yang terjadwal di ruang tanggung jawab Anda.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-bold">Agenda</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Ruang</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Tanggal</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Waktu</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Status</th>
                                    <th scope="col" class="px-6 py-4 font-bold text-right">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($events as $event)
                                    <tr class="align-middle hover:bg-slate-50/70 transition-colors">
                                        <td class="px-6 py-4 align-middle">
                                            <p class="font-bold text-slate-900 text-sm sm:text-base leading-snug">{{ $event->event_name }}</p>
                                        </td>
                                        <td class="px-6 py-4 align-middle text-slate-800 font-semibold text-sm">
                                            {{ $event->room_names ?: '-' }}
                                        </td>
                                        <td class="px-6 py-4 align-middle text-slate-800 whitespace-nowrap">
                                            <div class="flex items-center gap-1.5 font-semibold text-slate-900 text-xs sm:text-sm">
                                                <svg class="h-4 w-4 text-amber-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-middle whitespace-nowrap">
                                            @php
                                                $startFormatted = date('H:i', strtotime($event->start_time));
                                                $endFormatted = date('H:i', strtotime($event->end_time));
                                                $sessionList = collect(config('schedule.sessions'));
                                                $startSessionIndex = $sessionList->search(fn ($t) => $t['start_time'] === $startFormatted);
                                                $endSessionIndex = $sessionList->search(fn ($t) => $t['end_time'] === $endFormatted);
                                            @endphp
                                            <div class="flex items-center gap-2 whitespace-nowrap">
                                                @if ($startSessionIndex !== false && $endSessionIndex !== false)
                                                    <span class="inline-flex items-center rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-900">
                                                        Sesi {{ $startSessionIndex }}{{ $startSessionIndex !== $endSessionIndex ? '–' . $endSessionIndex : '' }}
                                                    </span>
                                                    <span class="text-slate-300 font-semibold select-none">|</span>
                                                @endif
                                                <span class="inline-flex items-center gap-1.5 font-mono text-xs sm:text-sm font-semibold text-slate-900">
                                                    <svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>{{ $startFormatted }} – {{ $endFormatted }} <span class="text-[11px] font-normal text-slate-500">WIB</span></span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-middle whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $event->lifecycle_badge_class }} border {{ str_contains($event->lifecycle_badge_class, 'amber') ? 'border-amber-200' : (str_contains($event->lifecycle_badge_class, 'emerald') ? 'border-emerald-200' : (str_contains($event->lifecycle_badge_class, 'blue') ? 'border-blue-200' : 'border-red-200')) }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ str_contains($event->lifecycle_badge_class, 'amber') ? 'bg-amber-500' : (str_contains($event->lifecycle_badge_class, 'emerald') ? 'bg-emerald-500 animate-pulse' : (str_contains($event->lifecycle_badge_class, 'blue') ? 'bg-blue-500' : 'bg-red-500')) }}"></span>
                                                <span>{{ $event->lifecycle_status_label }}</span>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 align-middle whitespace-nowrap text-right">
                                            @if ($event->status === 'pending')
                                                <a href="{{ route('pic.approvals') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-slate-700 transition">
                                                    <span>Tinjau</span>
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            @elseif ($event->status === 'rejected')
                                                <span class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Ditolak</span>
                                            @else
                                                <span class="text-xs font-medium text-slate-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
