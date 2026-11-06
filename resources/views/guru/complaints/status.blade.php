<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">Portal Guru/Organisasi</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Riwayat Pengaduan Saya</h2>
            <p class="mt-1 text-sm text-slate-500">Daftar ini hanya menampilkan pengaduan yang dikirim dari akun Anda.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Ringkasan pengaduan Anda">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Pengaduan Saya</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $statistics['total'] }}</p>
                </div>
                <div class="rounded-xl border border-red-100 bg-red-50 p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-red-700">Belum Diperbaiki</p>
                    <p class="mt-2 text-2xl font-bold text-red-800">{{ $statistics['not_repaired'] }}</p>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Menunggu Spare Part</p>
                    <p class="mt-2 text-2xl font-bold text-amber-800">{{ $statistics['waiting_parts'] }}</p>
                </div>
                <div class="rounded-xl border border-green-100 bg-green-50 p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-green-700">Sudah Diperbaiki</p>
                    <p class="mt-2 text-2xl font-bold text-green-800">{{ $statistics['repaired'] }}</p>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-900">Riwayat Pengaduan</h3>
                        <p class="mt-1 text-sm text-slate-500">Menampilkan {{ $complaints->total() }} pengaduan milik akun Anda.</p>
                    </div>
                    <a href="{{ route('guru.complaints.create') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Buat Laporan Baru</a>
                </div>
                <form method="GET" action="{{ route('guru.complaints.status') }}" class="grid gap-3 border-b border-gray-200 bg-gray-50 px-6 py-4 sm:grid-cols-[1fr_14rem_auto_auto]">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari laporan, ruang, atau penangan" aria-label="Cari laporan, ruang, atau penangan" class="rounded-md border-gray-300 text-sm">
                    <select name="status" aria-label="Filter status perbaikan" class="rounded-md border-gray-300 text-sm">
                        <option value="">Semua status</option>
                        <option value="Belum Diperbaiki" @selected(request('status') === 'Belum Diperbaiki')>Belum Diperbaiki</option>
                        <option value="Menunggu Spare Part" @selected(request('status') === 'Menunggu Spare Part')>Menunggu Spare Part</option>
                        <option value="Sudah Diperbaiki" @selected(request('status') === 'Sudah Diperbaiki')>Sudah Diperbaiki</option>
                    </select>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cari / Filter</button>
                    <a href="{{ route('guru.complaints.status') }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-white">Reset</a>
                </form>
                @if ($complaints->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="font-semibold text-slate-800">{{ request()->filled('search') || request()->filled('status') ? 'Pengaduan tidak ditemukan' : 'Anda belum memiliki pengaduan' }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ request()->filled('search') || request()->filled('status') ? 'Coba ubah kata kunci atau status filter.' : 'Pengaduan yang Anda kirim akan muncul di halaman ini.' }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-6 py-3">Tanggal Laporan</th>
                                    <th class="px-6 py-3">Ruang &amp; Kerusakan</th>
                                    <th class="px-6 py-3">Status Terakhir</th>
                                    <th class="px-6 py-3">PIC Penangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($complaints as $complaint)
                                    @php
                                        $statusStyle = match ($complaint->status_repair) {
                                            'Sudah Diperbaiki' => 'bg-green-100 text-green-800',
                                            'Menunggu Spare Part' => 'bg-amber-100 text-amber-800',
                                            default => 'bg-red-100 text-red-800',
                                        };
                                        $statusExplanation = match ($complaint->status_repair) {
                                            'Sudah Diperbaiki' => 'Perbaikan telah selesai.',
                                            'Menunggu Spare Part' => 'Perbaikan menunggu ketersediaan suku cadang.',
                                            default => 'Laporan diterima dan belum ditandai selesai.',
                                        };
                                    @endphp
                                    <tr class="align-top">
                                        <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ $complaint->reported_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td class="max-w-lg px-6 py-4">
                                            <p class="font-semibold text-slate-900">{{ $complaint->room?->room_name ?? 'Ruang tidak tersedia' }}</p>
                                            <p class="mt-1 whitespace-pre-line text-slate-600">{{ $complaint->description }}</p>
                                            <p class="mt-2 text-xs text-slate-400">Nomor laporan #{{ $complaint->id }}</p>
                                            @if ($complaint->reporter)
                                                <div class="mt-3 border-t border-gray-100 pt-2 text-xs text-slate-500">
                                                    <p class="font-semibold text-slate-700">Identitas Pelapor: {{ $complaint->reporter->fullname }}</p>
                                                    <p class="mt-1">Username: {{ $complaint->reporter->username }} · {{ $complaint->reporter->role?->role_name ?? '-' }}</p>
                                                    <p class="mt-1 break-all">Email: {{ $complaint->reporter->email }}</p>
                                                    <p class="mt-1">Telepon: {{ $complaint->reporter->phone_number ?: '-' }}</p>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="min-w-56 px-6 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyle }}">{{ $complaint->status_repair }}</span>
                                            <p class="mt-2 text-xs leading-5 text-slate-500">{{ $statusExplanation }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">{{ $complaint->handler?->fullname ?? 'Belum ditentukan' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-200 px-6 py-4">{{ $complaints->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
