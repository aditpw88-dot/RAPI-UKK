<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-slate-500">Laporan Peminjaman</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Laporan {{ match ($period['type']) { 'daily' => 'Harian', 'weekly' => 'Mingguan', default => 'Bulanan' } }} Peminjaman Ruang</h2>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <!-- Filter & Pencarian Laporan Peminjaman -->
            <form id="report-period-form" method="GET" action="{{ route('reports.monthly') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm print:hidden space-y-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Pencarian Teks -->
                    <div class="relative w-full">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input 
                            type="text" 
                            id="filter_search"
                            name="search" 
                            value="{{ $search ?? '' }}" 
                            placeholder="Cari agenda, ruang, pengaju..." 
                            class="w-full rounded-lg border-gray-300 pl-9 pr-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-slate-800 focus:ring-1 focus:ring-slate-800"
                        >
                    </div>

                    <!-- Filter Status Pelaksanaan -->
                    <div>
                        <select id="filter_status" name="status" class="w-full rounded-lg border-gray-300 py-2 text-sm text-slate-800 focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                            <option value="">Semua Status Pelaksanaan</option>
                            <option value="completed" @selected(($status ?? '') === 'completed')>Sudah Terlaksana</option>
                            <option value="in_progress" @selected(($status ?? '') === 'in_progress')>Sedang Terlaksana</option>
                            <option value="upcoming" @selected(($status ?? '') === 'upcoming')>Belum Terlaksana</option>
                            <option value="cancelled" @selected(($status ?? '') === 'cancelled')>Dibatalkan</option>
                            <option value="rejected" @selected(($status ?? '') === 'rejected')>Ditolak</option>
                        </select>
                    </div>

                    <!-- Filter Ruangan -->
                    <div>
                        <select id="filter_room" name="room_id" class="w-full rounded-lg border-gray-300 py-2 text-sm text-slate-800 focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                            <option value="">Semua Ruangan</option>
                            @foreach ($rooms as $r)
                                <option value="{{ $r->id }}" @selected(($roomId ?? '') == $r->id)>{{ $r->room_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Pilihan Jenis & Waktu Laporan -->
                    <div class="flex gap-2">
                        <select id="period_type" name="period_type" class="w-1/2 rounded-lg border-gray-300 py-2 text-sm text-slate-800 focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                            <option value="daily" @selected($period['type'] === 'daily')>Harian</option>
                            <option value="weekly" @selected($period['type'] === 'weekly')>Mingguan</option>
                            <option value="monthly" @selected($period['type'] === 'monthly')>Bulanan</option>
                        </select>
                        <input id="period_value" type="{{ match ($period['type']) { 'daily' => 'date', 'weekly' => 'week', default => 'month' } }}" name="period_value" value="{{ $period['value'] }}" required class="w-1/2 rounded-lg border-gray-300 py-2 text-sm text-slate-800 focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    </div>
                </div>

                <!-- Tombol Aksi & Export -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                    <div class="flex items-center gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-slate-700 transition">
                            Tampilkan Laporan
                        </button>
                        <a href="{{ route('reports.monthly') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50 transition">
                            Reset
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a id="report-pdf-link" href="{{ route('reports.monthly.pdf', ['period_type' => $period['type'], 'period_value' => $period['value']]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-red-700 px-3.5 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-red-800 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Unduh PDF</span>
                        </a>
                        <a id="report-csv-link" href="{{ route('reports.monthly.csv', ['period_type' => $period['type'], 'period_value' => $period['value']]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3.5 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-emerald-800 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Unduh CSV</span>
                        </a>
                        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Cetak / Simpan PDF</span>
                        </button>
                    </div>
                </div>
            </form>

            <article class="report-page space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <header class="border-b border-slate-300 pb-4">
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Sistem RAPI</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">Laporan {{ match ($period['type']) { 'daily' => 'Harian', 'weekly' => 'Mingguan', default => 'Bulanan' } }} Peminjaman Ruang</h1>
                    <p class="mt-2 text-sm text-slate-600">Periode: {{ $period['label'] }}</p>
                    <p class="text-sm text-slate-600">Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
                </header>

                <section class="grid gap-3 sm:grid-cols-4">
                    <div class="rounded-lg bg-slate-50 p-4 border border-slate-200/60">
                        <p class="text-xs uppercase font-medium text-slate-500">Total Peminjaman</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ $events->count() }}</p>
                    </div>
                    <div class="rounded-lg bg-yellow-50/70 p-4 border border-yellow-200/60">
                        <p class="text-xs uppercase font-medium text-amber-800">Belum Terlaksana</p>
                        <p class="mt-1 text-2xl font-bold text-amber-900">{{ $events->where('lifecycle_status', 'upcoming')->count() }}</p>
                    </div>
                    <div class="rounded-lg bg-blue-50/70 p-4 border border-blue-200/60">
                        <p class="text-xs uppercase font-medium text-blue-800">Sedang Terlaksana</p>
                        <p class="mt-1 text-2xl font-bold text-blue-900">{{ $events->where('lifecycle_status', 'in_progress')->count() }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50/70 p-4 border border-emerald-200/60">
                        <p class="text-xs uppercase font-medium text-emerald-800">Sudah Terlaksana</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $events->where('lifecycle_status', 'completed')->count() }}</p>
                    </div>
                </section>

                <section>
                    <div class="mb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        <h2 class="text-lg font-bold text-slate-900">Daftar Peminjaman Ruang</h2>
                        <span class="inline-flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                            </svg>
                            <span>Diurutkan: Agenda terbaru dibuat</span>
                        </span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-2xs">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50/90 text-xs font-bold uppercase tracking-wider text-slate-600">
                                <tr>
                                    <th scope="col" class="w-[14%] px-4 py-3.5 font-bold">Tanggal</th>
                                    <th scope="col" class="w-[18%] px-4 py-3.5 font-bold">Agenda</th>
                                    <th scope="col" class="w-[14%] px-4 py-3.5 font-bold">Ruang</th>
                                    <th scope="col" class="w-[28%] px-4 py-3.5 font-bold">Pengaju</th>
                                    <th scope="col" class="w-[13%] px-4 py-3.5 font-bold">Waktu</th>
                                    <th scope="col" class="w-[13%] px-4 py-3.5 font-bold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($events as $event)
                                    <tr class="align-top hover:bg-slate-50/60 transition-colors">
                                        <!-- Tanggal -->
                                        <td class="px-4 py-3.5 whitespace-nowrap text-slate-800 font-semibold text-xs sm:text-sm">
                                            <div class="flex items-center gap-1.5">
                                                <svg width="15" height="15" style="width:15px;height:15px;" class="text-amber-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></span>
                                            </div>
                                        </td>

                                        <!-- Agenda -->
                                        <td class="px-4 py-3.5 text-slate-900">
                                            <p class="font-semibold text-sm leading-snug">{{ $event->event_name }}</p>
                                            @if ($event->event_type)
                                                <span class="mt-1 inline-block text-[11px] font-medium text-slate-500 uppercase tracking-wide">
                                                    {{ config("event_types.{$event->event_type}.label") ?? ucfirst($event->event_type) }}
                                                </span>
                                            @endif
                                            @if ($event->created_at)
                                                <p class="mt-1 text-[11px] text-slate-400">Dibuat: {{ $event->created_at->format('d/m/Y H:i') }}</p>
                                            @endif
                                        </td>

                                        <!-- Ruang -->
                                        <td class="px-4 py-3.5 text-slate-700 font-medium">
                                            <div class="flex items-center gap-1.5">
                                                <svg width="15" height="15" style="width:15px;height:15px;" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                <span class="text-sm font-semibold text-slate-800">{{ $event->room_names ?: '-' }}</span>
                                            </div>
                                        </td>

                                        <!-- Pengaju (RAPI & MUDAH DIPAHAMI) -->
                                        <td class="px-4 py-3.5 text-slate-700">
                                            @if ($event->pic)
                                                <div class="space-y-1.5">
                                                    <div class="flex items-center gap-2">
                                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-50 text-xs font-bold text-amber-800 ring-1 ring-amber-200">
                                                            {{ strtoupper(substr($event->pic->fullname ?? 'U', 0, 1)) }}
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <p class="font-bold text-slate-900 text-sm leading-tight truncate">{{ $event->pic->fullname }}</p>
                                                        </div>
                                                        <span class="inline-block rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 uppercase tracking-wide shrink-0">
                                                            {{ $event->pic->role?->role_name ?? 'Pengaju' }}
                                                        </span>
                                                    </div>
                                                    <div class="rounded-lg bg-slate-50/90 border border-slate-200/70 p-2 text-xs space-y-1 text-slate-600">
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="text-slate-400 font-medium w-16 shrink-0">Username:</span>
                                                            <span class="font-mono text-slate-800 font-semibold truncate">{{ $event->pic->username }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="text-slate-400 font-medium w-16 shrink-0">Email:</span>
                                                            <span class="font-mono text-slate-800 truncate">{{ $event->pic->email }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="text-slate-400 font-medium w-16 shrink-0">Telepon:</span>
                                                            <span class="font-medium text-slate-800">{{ $event->pic->phone_number ?: '-' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-slate-400 italic text-sm">-</span>
                                            @endif
                                        </td>

                                        <!-- Waktu & Sesi -->
                                        <td class="px-4 py-3.5 whitespace-nowrap text-slate-800">
                                            @php
                                                $startFormatted = date('H:i', strtotime($event->start_time));
                                                $endFormatted = date('H:i', strtotime($event->end_time));
                                                $sessionList = collect(config('schedule.sessions'));
                                                $startSessionIndex = $sessionList->search(fn ($t) => $t['start_time'] === $startFormatted);
                                                $endSessionIndex = $sessionList->search(fn ($t) => $t['end_time'] === $endFormatted);

                                                if ($startSessionIndex === false) {
                                                    $startSessionIndex = $sessionList->keys()->first(fn ($k) => $sessionList[$k]['end_time'] > $startFormatted);
                                                }
                                                if ($endSessionIndex === false) {
                                                    $endSessionIndex = $sessionList->keys()->last(fn ($k) => $sessionList[$k]['start_time'] < $endFormatted);
                                                }
                                            @endphp
                                            <div class="space-y-1">
                                                @if ($startSessionIndex !== false && $endSessionIndex !== false && $startSessionIndex !== null && $endSessionIndex !== null)
                                                    <div>
                                                        <span class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-900 shadow-2xs">
                                                            Sesi {{ $startSessionIndex }}{{ $startSessionIndex !== $endSessionIndex ? ' - Sesi ' . $endSessionIndex : '' }}
                                                        </span>
                                                    </div>
                                                @endif
                                                <div class="flex items-center gap-1.5 font-mono text-xs sm:text-sm font-semibold text-slate-700">
                                                    <svg width="14" height="14" style="width:14px;height:14px;" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>{{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }}</span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Status -->
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            @php
                                                $badgeClass = match ($event->lifecycle_status) {
                                                    'completed' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                                    'in_progress' => 'bg-blue-50 text-blue-800 border-blue-200',
                                                    'upcoming' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                    'cancelled' => 'bg-rose-50 text-rose-800 border-rose-200',
                                                    'rejected' => 'bg-red-50 text-red-800 border-red-200',
                                                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                                                };
                                                $dotColor = match ($event->lifecycle_status) {
                                                    'completed' => 'bg-emerald-500',
                                                    'in_progress' => 'bg-blue-500 animate-pulse',
                                                    'upcoming' => 'bg-amber-500',
                                                    'cancelled' => 'bg-rose-500',
                                                    'rejected' => 'bg-red-500',
                                                    default => 'bg-slate-400',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold shadow-2xs {{ $badgeClass }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $dotColor }}"></span>
                                                <span>{{ $event->lifecycle_status_label }}</span>
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                            <div class="mx-auto max-w-sm space-y-1">
                                                <p class="font-semibold text-slate-700">Tidak ada peminjaman ditemukan</p>
                                                <p class="text-xs text-slate-400">Tidak ada data agenda yang cocok dengan filter atau periode yang dipilih.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <footer class="border-t border-slate-300 pt-4 text-xs text-slate-500">Dokumen laporan periode Sistem RAPI.</footer>
            </article>
        </div>
    </div>
</x-app-layout>

<style>
    @media print {
        @page { size: A4 landscape; margin: 12mm; }
        body { background: #fff !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .school-sidebar, body > div > div > header, .print\:hidden { display: none !important; }
        main, .report-page { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; border: 0 !important; box-shadow: none !important; }
        .report-page section { break-inside: avoid; }
    }
</style>

<script>
    const periodType = document.getElementById('period_type');
    const periodValue = document.getElementById('period_value');
    const filterSearch = document.getElementById('filter_search');
    const filterStatus = document.getElementById('filter_status');
    const filterRoom = document.getElementById('filter_room');
    const reportPdfLink = document.getElementById('report-pdf-link');
    const reportCsvLink = document.getElementById('report-csv-link');

    const updateReportLinks = () => {
        if (!periodValue.value) {
            reportPdfLink.removeAttribute('href');
            reportCsvLink.removeAttribute('href');
            reportPdfLink.setAttribute('aria-disabled', 'true');
            reportCsvLink.setAttribute('aria-disabled', 'true');
            reportPdfLink.classList.add('pointer-events-none', 'opacity-50');
            reportCsvLink.classList.add('pointer-events-none', 'opacity-50');

            return;
        }

        const params = {
            period_type: periodType.value,
            period_value: periodValue.value,
        };

        if (filterSearch && filterSearch.value.trim() !== '') {
            params.search = filterSearch.value.trim();
        }
        if (filterStatus && filterStatus.value !== '') {
            params.status = filterStatus.value;
        }
        if (filterRoom && filterRoom.value !== '') {
            params.room_id = filterRoom.value;
        }

        const query = new URLSearchParams(params);
        reportPdfLink.href = `{{ route('reports.monthly.pdf') }}?${query}`;
        reportCsvLink.href = `{{ route('reports.monthly.csv') }}?${query}`;
        reportPdfLink.removeAttribute('aria-disabled');
        reportCsvLink.removeAttribute('aria-disabled');
        reportPdfLink.classList.remove('pointer-events-none', 'opacity-50');
        reportCsvLink.classList.remove('pointer-events-none', 'opacity-50');
    };

    periodType?.addEventListener('change', () => {
        const inputTypes = { daily: 'date', weekly: 'week', monthly: 'month' };
        const type = periodType.value;
        periodValue.type = inputTypes[type];
        periodValue.value = '';
        updateReportLinks();
    });

    periodValue?.addEventListener('change', updateReportLinks);
    filterSearch?.addEventListener('input', updateReportLinks);
    filterStatus?.addEventListener('change', updateReportLinks);
    filterRoom?.addEventListener('change', updateReportLinks);
    updateReportLinks();
</script>
