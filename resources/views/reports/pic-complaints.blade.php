<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-slate-500">Laporan PIC</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Laporan Kerusakan {{ match ($period['type']) { 'daily' => 'Harian', 'weekly' => 'Mingguan', default => 'Bulanan' } }}</h2>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <form id="report-period-form" method="GET" action="{{ route('pic.reports.complaints') }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm print:hidden">
                <div>
                    <label for="period_type" class="block text-sm font-semibold text-slate-700">Jenis laporan</label>
                    <select id="period_type" name="period_type" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="daily" @selected($period['type'] === 'daily')>Harian</option>
                        <option value="weekly" @selected($period['type'] === 'weekly')>Mingguan</option>
                        <option value="monthly" @selected($period['type'] === 'monthly')>Bulanan</option>
                    </select>
                </div>
                <div>
                    <label id="period_value_label" for="period_value" class="block text-sm font-semibold text-slate-700">Pilih {{ match ($period['type']) { 'daily' => 'tanggal', 'weekly' => 'minggu', default => 'bulan' } }}</label>
                    <input id="period_value" type="{{ match ($period['type']) { 'daily' => 'date', 'weekly' => 'week', default => 'month' } }}" name="period_value" value="{{ $period['value'] }}" required class="mt-1 rounded-md border-gray-300 text-sm">
                </div>
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Tampilkan Laporan</button>
                <a id="report-pdf-link" href="{{ route('pic.reports.complaints.pdf', ['period_type' => $period['type'], 'period_value' => $period['value']]) }}" class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Unduh PDF</a>
                <a id="report-csv-link" href="{{ route('pic.reports.complaints.csv', ['period_type' => $period['type'], 'period_value' => $period['value']]) }}" class="rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Unduh CSV</a>
                <button type="button" onclick="window.print()" class="rounded-md bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600">Cetak Laporan</button>
            </form>

            <article class="report-page space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <header class="border-b border-slate-300 pb-4">
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Sistem RAPI · Laporan PIC</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">Laporan Kerusakan {{ match ($period['type']) { 'daily' => 'Harian', 'weekly' => 'Mingguan', default => 'Bulanan' } }}</h1>
                    <p class="mt-2 text-sm text-slate-600">Periode: {{ $period['label'] }}</p>
                    <p class="text-sm text-slate-600">PIC: {{ auth()->user()->fullname }}</p>
                    <p class="text-sm text-slate-600">Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
                </header>

                <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs uppercase text-slate-500">Total Laporan</p><p class="mt-1 text-2xl font-bold">{{ $complaints->count() }}</p></div>
                    <div class="rounded-lg bg-red-50 p-4"><p class="text-xs uppercase text-red-700">Belum Diperbaiki</p><p class="mt-1 text-2xl font-bold text-red-800">{{ $complaints->where('status_repair', 'Belum Diperbaiki')->count() }}</p></div>
                    <div class="rounded-lg bg-amber-50 p-4"><p class="text-xs uppercase text-amber-700">Menunggu Spare Part</p><p class="mt-1 text-2xl font-bold text-amber-800">{{ $complaints->where('status_repair', 'Menunggu Spare Part')->count() }}</p></div>
                    <div class="rounded-lg bg-green-50 p-4"><p class="text-xs uppercase text-green-700">Sudah Diperbaiki</p><p class="mt-1 text-2xl font-bold text-green-800">{{ $complaints->where('status_repair', 'Sudah Diperbaiki')->count() }}</p></div>
                </section>

                <section>
                    <h2 class="mb-3 text-lg font-bold text-slate-900">Daftar Laporan Kerusakan</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-left text-sm">
                            <thead><tr class="border-y border-slate-300 bg-slate-50"><th class="p-2">Tanggal Laporan</th><th class="p-2">Ruang</th><th class="p-2">Deskripsi</th><th class="p-2">Pelapor</th><th class="p-2">Status</th></tr></thead>
                            <tbody>
                                @forelse ($complaints as $complaint)
                                    <tr class="border-b border-slate-200">
                                        <td class="p-2 whitespace-nowrap">{{ $complaint->reported_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td class="p-2">{{ $complaint->room?->room_name ?? '-' }}</td>
                                        <td class="p-2">{{ $complaint->description }}</td>
                                        <td class="p-2">
                                            @if ($complaint->reporter)
                                                <p class="font-semibold">{{ $complaint->reporter->fullname }}</p>
                                                <p class="text-xs text-slate-600">Username: {{ $complaint->reporter->username }} · {{ $complaint->reporter->role?->role_name ?? '-' }}</p>
                                                <p class="break-all text-xs text-slate-600">{{ $complaint->reporter->email }}</p>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="p-2">{{ $complaint->status_repair }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="p-4 text-center text-slate-500">Tidak ada laporan kerusakan pada periode ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
                <footer class="border-t border-slate-300 pt-4 text-xs text-slate-500">Dokumen laporan kerusakan periode Sistem RAPI.</footer>
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
    const periodValueLabel = document.getElementById('period_value_label');
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

        const query = new URLSearchParams({ period_type: periodType.value, period_value: periodValue.value });
        reportPdfLink.href = `{{ route('pic.reports.complaints.pdf') }}?${query}`;
        reportCsvLink.href = `{{ route('pic.reports.complaints.csv') }}?${query}`;
        reportPdfLink.removeAttribute('aria-disabled');
        reportCsvLink.removeAttribute('aria-disabled');
        reportPdfLink.classList.remove('pointer-events-none', 'opacity-50');
        reportCsvLink.classList.remove('pointer-events-none', 'opacity-50');
    };

    periodType?.addEventListener('change', () => {
        const inputTypes = { daily: 'date', weekly: 'week', monthly: 'month' };
        const labels = { daily: 'tanggal', weekly: 'minggu', monthly: 'bulan' };
        periodValue.type = inputTypes[periodType.value];
        periodValue.value = '';
        periodValueLabel.textContent = `Pilih ${labels[periodType.value]}`;
        updateReportLinks();
    });
    periodValue?.addEventListener('change', updateReportLinks);
    updateReportLinks();
</script>
