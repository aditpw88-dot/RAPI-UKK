<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Laporan PIC</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Laporan Kerusakan {{ match ($period['type']) { 'daily' => 'Harian', 'weekly' => 'Mingguan', default => 'Bulanan' } }}</h2>
            <p class="mt-0.5 text-xs text-slate-500">Rekapitulasi seluruh riwayat pengaduan kerusakan pada ruangan tanggung jawab Anda (tersimpan permanen).</p>
        </div>
    </x-slot>

    <div x-data="{ previewPhoto: null, previewTitle: '' }" class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <!-- Form Filter Pencarian, Ruangan, Status, dan Periode -->
            <form id="report-filter-form" method="GET" action="{{ route('pic.reports.complaints') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm print:hidden space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                        <label for="period_type" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Jenis Laporan</label>
                        <select id="period_type" name="period_type" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                            <option value="daily" @selected($period['type'] === 'daily')>Harian</option>
                            <option value="weekly" @selected($period['type'] === 'weekly')>Mingguan</option>
                            <option value="monthly" @selected($period['type'] === 'monthly')>Bulanan</option>
                        </select>
                    </div>

                    <div>
                        <label id="period_value_label" for="period_value" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Pilih {{ match ($period['type']) { 'daily' => 'Tanggal', 'weekly' => 'Minggu', default => 'Bulan' } }}</label>
                        <input id="period_value" type="{{ match ($period['type']) { 'daily' => 'date', 'weekly' => 'week', default => 'month' } }}" name="period_value" value="{{ $period['value'] }}" required class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    </div>

                    <div>
                        <label for="filter_room" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Ruangan</label>
                        <select id="filter_room" name="room_id" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                            <option value="">Semua Ruangan</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" @selected((string) ($roomId ?? '') === (string) $room->id)>{{ $room->room_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter_status" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Status Perbaikan</label>
                        <select id="filter_status" name="status" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                            <option value="">Semua Status</option>
                            <option value="Belum Diperbaiki" @selected(($status ?? '') === 'Belum Diperbaiki')>Belum Diperbaiki</option>
                            <option value="Sedang Diperbaiki" @selected(($status ?? '') === 'Sedang Diperbaiki')>Sedang Diperbaiki</option>
                            <option value="Menunggu Spare Part" @selected(($status ?? '') === 'Menunggu Spare Part')>Menunggu Spare Part</option>
                            <option value="Sudah Diperbaiki" @selected(($status ?? '') === 'Sudah Diperbaiki')>Sudah Diperbaiki</option>
                        </select>
                    </div>

                    <div>
                        <label for="filter_search" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Cari Laporan</label>
                        <div class="relative">
                            <input 
                                id="filter_search"
                                type="search" 
                                name="search" 
                                value="{{ $search ?? '' }}" 
                                placeholder="Cari kerusakan / pelapor..." 
                                class="w-full rounded-lg border-gray-300 py-2 pl-9 pr-3 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800"
                            >
                            <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-slate-700 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span>Terapkan Filter</span>
                        </button>
                        <a href="{{ route('pic.reports.complaints') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50 transition">
                            Reset
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a id="report-pdf-link" href="#" class="inline-flex items-center gap-1.5 rounded-lg bg-rose-700 px-3.5 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-rose-800 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Unduh PDF</span>
                        </a>
                        <a id="report-csv-link" href="#" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3.5 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-emerald-800 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Unduh CSV</span>
                        </a>
                        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-700 px-3.5 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-slate-600 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Cetak</span>
                        </button>
                    </div>
                </div>
            </form>

            <article class="report-page space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <header class="border-b border-slate-200 pb-5">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-900 uppercase tracking-wide">Sistem RAPI</span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Laporan PIC Ruang</span>
                            </div>
                            <h1 class="mt-2 text-2xl font-black text-slate-900 tracking-tight">Laporan Rekapitulasi Kerusakan Ruangan</h1>
                            <p class="mt-1 text-sm font-medium text-slate-600">
                                Periode: <span class="font-bold text-slate-800">{{ $period['label'] }}</span>
                            </p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100 text-xs text-slate-600 space-y-1">
                            <p><span class="text-slate-400 font-medium">PIC Penanggung Jawab:</span> <span class="font-bold text-slate-800">{{ auth()->user()->fullname }}</span></p>
                            <p><span class="text-slate-400 font-medium">Waktu Cetak Dokumen:</span> <span class="font-semibold text-slate-700">{{ now()->format('d/m/Y H:i') }} WIB</span></p>
                            @if (!empty($search) || !empty($status) || !empty($roomId))
                                <div class="pt-1.5 border-t border-slate-200 text-[11px] text-amber-800">
                                    <span class="font-bold">Filter Aktif:</span>
                                    @if(!empty($roomId)) Ruangan: {{ $rooms->firstWhere('id', $roomId)?->room_name ?? $roomId }}; @endif
                                    @if(!empty($status)) Status: {{ $status }}; @endif
                                    @if(!empty($search)) Kata kunci: "{{ $search }}"; @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </header>

                <!-- 5 Kartu Statistik Ringkasan -->
                <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 shadow-2xs">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Laporan</p>
                        <p class="mt-1 text-2xl font-black text-slate-900">{{ $statistics['total'] }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Seluruh kerusakan</p>
                    </div>
                    <div class="rounded-xl border border-red-200 bg-red-50/70 p-4 shadow-2xs">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-red-700">Belum Diperbaiki</p>
                        <p class="mt-1 text-2xl font-black text-red-700">{{ $statistics['not_repaired'] }}</p>
                        <p class="text-[11px] text-red-500 mt-0.5">Menunggu respon</p>
                    </div>
                    <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-4 shadow-2xs">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-700">Sedang Diperbaiki</p>
                        <p class="mt-1 text-2xl font-black text-blue-700">{{ $statistics['in_repair'] }}</p>
                        <p class="text-[11px] text-blue-500 mt-0.5">Dalam proses teknisi</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 shadow-2xs">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-800">Menunggu Spare Part</p>
                        <p class="mt-1 text-2xl font-black text-amber-800">{{ $statistics['waiting_parts'] }}</p>
                        <p class="text-[11px] text-amber-600 mt-0.5">Komponen dipesan</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 shadow-2xs">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Sudah Diperbaiki</p>
                        <p class="mt-1 text-2xl font-black text-emerald-800">{{ $statistics['repaired'] }}</p>
                        <p class="text-[11px] text-emerald-600 mt-0.5">Tuntas diperbaiki</p>
                    </div>
                </section>

                <!-- Tabel Data Laporan Kerusakan -->
                <section>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-base font-bold text-slate-900">Rincian Data Pengaduan Kerusakan</h2>
                        <span class="text-xs font-semibold text-slate-500">{{ $complaints->count() }} data ditemukan</span>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-2xs">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 whitespace-nowrap">Tanggal & Waktu</th>
                                    <th class="px-4 py-3">Ruangan</th>
                                    <th class="px-4 py-3">Pelapor</th>
                                    <th class="px-4 py-3">Deskripsi & Foto Kerusakan</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Status Perbaikan</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Selesai Diperbaiki</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($complaints as $complaint)
                                    @php
                                        $badge = match ($complaint->status_repair) {
                                            'Sudah Diperbaiki' => [
                                                'bg' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
                                                'dot' => 'bg-emerald-500',
                                            ],
                                            'Sedang Diperbaiki' => [
                                                'bg' => 'bg-blue-50 border-blue-200 text-blue-800',
                                                'dot' => 'bg-blue-500',
                                            ],
                                            'Menunggu Spare Part' => [
                                                'bg' => 'bg-amber-50 border-amber-200 text-amber-800',
                                                'dot' => 'bg-amber-500',
                                            ],
                                            default => [
                                                'bg' => 'bg-red-50 border-red-200 text-red-700',
                                                'dot' => 'bg-red-500',
                                            ],
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                            <div class="font-bold text-slate-900">{{ $complaint->reported_at?->format('d/m/Y') ?? '-' }}</div>
                                            <div class="text-xs text-slate-500 font-mono">{{ $complaint->reported_at?->format('H:i') ?? '-' }} WIB</div>
                                        </td>
                                        <td class="px-4 py-3.5 align-top">
                                            <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-bold text-slate-800">
                                                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                <span>{{ $complaint->room?->room_name ?? '-' }}</span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 align-top">
                                            @if ($complaint->reporter)
                                                <p class="font-bold text-slate-900 text-sm leading-tight">{{ $complaint->reporter->fullname }}</p>
                                                <p class="text-xs text-slate-500 mt-0.5">
                                                    <span class="font-mono text-slate-600">{{ $complaint->reporter->username }}</span> · 
                                                    <span class="font-medium text-amber-700">{{ $complaint->reporter->role?->role_name ?? '-' }}</span>
                                                </p>
                                                @if ($complaint->reporter->phone_number)
                                                    @php
                                                        $cleanPhone = preg_replace('/[^0-9]/', '', $complaint->reporter->phone_number);
                                                        if (str_starts_with($cleanPhone, '0')) {
                                                            $cleanPhone = '62' . substr($cleanPhone, 1);
                                                        }
                                                    @endphp
                                                    <a 
                                                        href="https://wa.me/{{ $cleanPhone }}" 
                                                        target="_blank" 
                                                        rel="noopener noreferrer" 
                                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 hover:underline mt-1 print:hidden"
                                                    >
                                                        <span>WA: {{ $complaint->reporter->phone_number }}</span>
                                                    </a>
                                                @endif
                                            @else
                                                <span class="text-xs text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 align-top">
                                            <p class="text-sm font-medium text-slate-800 leading-relaxed whitespace-pre-wrap max-w-sm">{{ $complaint->description }}</p>
                                            @if ($complaint->photo_path)
                                                <div class="mt-2">
                                                    <button 
                                                        type="button" 
                                                        @click="previewPhoto = '{{ asset('storage/' . $complaint->photo_path) }}'; previewTitle = 'Bukti Kerusakan: {{ addslashes($complaint->room?->room_name ?? 'Ruangan') }}'" 
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:border-slate-300 transition group print:hidden"
                                                    >
                                                        <img src="{{ asset('storage/' . $complaint->photo_path) }}" alt="Thumbnail" class="h-5 w-5 rounded object-cover">
                                                        <span>Lihat Foto Bukti</span>
                                                    </button>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold {{ $badge['bg'] }} shadow-2xs">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $badge['dot'] }}"></span>
                                                <span>{{ $complaint->status_repair }}</span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                            @if ($complaint->status_repair === 'Sudah Diperbaiki' && $complaint->repaired_at)
                                                <div class="font-bold text-emerald-800 text-xs">{{ $complaint->repaired_at->format('d/m/Y') }}</div>
                                                <div class="text-[11px] text-emerald-600 font-mono">{{ $complaint->repaired_at->format('H:i') }} WIB</div>
                                            @elseif ($complaint->status_repair === 'Sudah Diperbaiki')
                                                <span class="text-xs text-emerald-700 font-medium">Selesai (Tercatat)</span>
                                            @else
                                                <span class="text-xs text-slate-400">Belum Selesai</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">
                                            <svg class="mx-auto h-10 w-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="font-bold text-slate-700">Tidak ada laporan kerusakan pada periode dan filter ini.</p>
                                            <p class="text-xs text-slate-400 mt-1">Cobalah mengganti pilihan periode tanggal atau reset filter pencarian.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <footer class="border-t border-slate-200 pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 gap-2">
                    <p>Dokumen rekapitulasi resmi pengaduan kerusakan ruangan · Sistem Informasi Sarpras RAPI.</p>
                    <p>Total Baris Data: {{ $complaints->count() }} Laporan</p>
                </footer>
            </article>
        </div>

        <!-- Modal Dialog Pratinjau Foto Kerusakan -->
        <div 
            x-show="previewPhoto" 
            x-cloak 
            @keydown.escape.window="previewPhoto = null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4"
            style="display: none;"
        >
            <div 
                @click.outside="previewPhoto = null" 
                class="relative max-w-2xl w-full overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900" x-text="previewTitle"></h3>
                    <button type="button" @click="previewPhoto = null" class="rounded-lg p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition">&times;</button>
                </div>
                <div class="p-4 bg-slate-950 flex items-center justify-center">
                    <img :src="previewPhoto" alt="Pratinjau Foto Kerusakan" class="max-h-[75vh] w-auto max-w-full rounded object-contain">
                </div>
                <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-5 py-3">
                    <button type="button" @click="previewPhoto = null" class="rounded-lg border border-slate-300 bg-white px-4 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<style>
    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #fff !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .school-sidebar, body > div > div > header, .print\:hidden { display: none !important; }
        main, .report-page { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; border: 0 !important; box-shadow: none !important; }
        .report-page section { break-inside: avoid; }
    }
</style>

<script>
    const periodType = document.getElementById('period_type');
    const periodValue = document.getElementById('period_value');
    const periodValueLabel = document.getElementById('period_value_label');
    const filterRoom = document.getElementById('filter_room');
    const filterStatus = document.getElementById('filter_status');
    const filterSearch = document.getElementById('filter_search');
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
            period_value: periodValue.value
        };

        if (filterRoom && filterRoom.value) {
            params.room_id = filterRoom.value;
        }
        if (filterStatus && filterStatus.value) {
            params.status = filterStatus.value;
        }
        if (filterSearch && filterSearch.value.trim()) {
            params.search = filterSearch.value.trim();
        }

        const query = new URLSearchParams(params);
        reportPdfLink.href = `{{ route('pic.reports.complaints.pdf') }}?${query.toString()}`;
        reportCsvLink.href = `{{ route('pic.reports.complaints.csv') }}?${query.toString()}`;
        reportPdfLink.removeAttribute('aria-disabled');
        reportCsvLink.removeAttribute('aria-disabled');
        reportPdfLink.classList.remove('pointer-events-none', 'opacity-50');
        reportCsvLink.classList.remove('pointer-events-none', 'opacity-50');
    };

    periodType?.addEventListener('change', () => {
        const inputTypes = { daily: 'date', weekly: 'week', monthly: 'month' };
        const labels = { daily: 'Tanggal', weekly: 'Minggu', monthly: 'Bulan' };
        periodValue.type = inputTypes[periodType.value];
        periodValue.value = '';
        periodValueLabel.textContent = `Pilih ${labels[periodType.value]}`;
        updateReportLinks();
    });

    periodValue?.addEventListener('change', updateReportLinks);
    filterRoom?.addEventListener('change', updateReportLinks);
    filterStatus?.addEventListener('change', updateReportLinks);
    filterSearch?.addEventListener('input', updateReportLinks);

    updateReportLinks();
</script>
