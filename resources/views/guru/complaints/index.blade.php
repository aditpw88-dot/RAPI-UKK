<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">Portal Guru/Organisasi</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Manajemen Kerusakan</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau seluruh laporan kerusakan sarana dan prasarana dari seluruh ruang sekolah.</p>
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

            <!-- Ringkasan Statistik Kerusakan Sekolah -->
            <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5" aria-label="Statistik seluruh kerusakan">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Laporan</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">{{ $statistics['total'] }}</p>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-red-700">Belum Diperbaiki</p>
                    <p class="mt-2 text-2xl font-bold text-red-700 sm:text-3xl">{{ $statistics['not_repaired'] }}</p>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">Sedang Diperbaiki</p>
                    <p class="mt-2 text-2xl font-bold text-blue-700 sm:text-3xl">{{ $statistics['in_repair'] }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-800">Menunggu Spare Part</p>
                    <p class="mt-2 text-2xl font-bold text-amber-800 sm:text-3xl">{{ $statistics['waiting_parts'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Sudah Diperbaiki</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-800 sm:text-3xl">{{ $statistics['repaired'] }}</p>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <!-- Header Card dengan Shortcut Buat Laporan Sendiri -->
                <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900">SEMUA LAPORAN KERUSAKAN</h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Menampilkan seluruh laporan kendala fasilitas yang dilaporkan oleh seluruh pihak sekolah.
                        </p>
                    </div>
                    <!-- Shortcut Utama: Buat Laporan Kerusakan Sendiri -->
                    <a href="{{ route('guru.complaints.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Buat Laporan Kerusakan</span>
                    </a>
                </div>

                <!-- Form Filter & Pencarian -->
                <form method="GET" action="{{ route('guru.complaints') }}" class="grid gap-3 border-b border-gray-200 bg-gray-50 px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
                    <input 
                        type="search" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Cari ruang, pelapor, atau kerusakan" 
                        aria-label="Cari ruang, pelapor, atau kerusakan" 
                        class="rounded-lg border-gray-300 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800 sm:col-span-2"
                    >
                    <select name="status" aria-label="Filter status perbaikan" class="rounded-lg border-gray-300 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                        <option value="">Semua status perbaikan</option>
                        <option value="Belum Diperbaiki" @selected(request('status') === 'Belum Diperbaiki')>Belum Diperbaiki</option>
                        <option value="Sedang Diperbaiki" @selected(request('status') === 'Sedang Diperbaiki')>Sedang Diperbaiki</option>
                        <option value="Menunggu Spare Part" @selected(request('status') === 'Menunggu Spare Part')>Menunggu Spare Part</option>
                        <option value="Sudah Diperbaiki" @selected(request('status') === 'Sudah Diperbaiki')>Sudah Diperbaiki</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition">Cari / Filter</button>
                        <a href="{{ route('guru.complaints') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-white transition">Reset</a>
                    </div>
                </form>

                <!-- Tabel Laporan Kerusakan -->
                @if ($complaints->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v4m0 4h.01M10.3 3.8L2.7 17a2 2 0 001.7 3h15.2a2 2 0 001.7-3L13.7 3.8a2 2 0 00-3.4 0z" />
                        </svg>
                        <p class="mt-3 font-semibold text-slate-800">Tidak ada laporan kerusakan yang ditemukan.</p>
                        <p class="mt-1 text-sm text-slate-500">Coba sesuaikan kata kunci pencarian atau reset filter.</p>
                        <div class="mt-5">
                            <a href="{{ route('guru.complaints.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                + Buat Laporan Kerusakan
                            </a>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto min-h-[380px]">
                        <table class="w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5 font-bold">Foto</th>
                                    <th scope="col" class="px-5 py-3.5 font-bold">Ruangan &amp; Kerusakan</th>
                                    <th scope="col" class="px-5 py-3.5 font-bold">Pelapor</th>
                                    <th scope="col" class="px-5 py-3.5 font-bold">Status Perbaikan</th>
                                    <th scope="col" class="px-5 py-3.5 font-bold">Penanggung Jawab (PIC)</th>
                                    <th scope="col" class="px-5 py-3.5 font-bold">Waktu Lapor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($complaints as $complaint)
                                    @php
                                        $isMine = (int) $complaint->reporter_id === (int) auth()->id();
                                        $statusClass = match ($complaint->status_repair) {
                                            'Sudah Diperbaiki' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                            'Sedang Diperbaiki' => 'border-blue-200 bg-blue-50 text-blue-800',
                                            'Menunggu Spare Part' => 'border-amber-200 bg-amber-50 text-amber-800',
                                            default => 'border-red-200 bg-red-50 text-red-700',
                                        };
                                        $dotColor = match ($complaint->status_repair) {
                                            'Sudah Diperbaiki' => 'bg-emerald-500',
                                            'Sedang Diperbaiki' => 'bg-blue-500',
                                            'Menunggu Spare Part' => 'bg-amber-500',
                                            default => 'bg-red-500',
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition {{ $isMine ? 'bg-amber-50/20' : '' }}">
                                        <!-- Foto Bukti -->
                                        <td class="px-5 py-6 align-top">
                                            @if ($complaint->photo_path)
                                                <a href="{{ asset('storage/' . $complaint->photo_path) }}" target="_blank" class="group block relative h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-2xs hover:border-slate-400 transition">
                                                    <img src="{{ asset('storage/' . $complaint->photo_path) }}" alt="Bukti kerusakan" class="h-full w-full object-cover group-hover:scale-105 transition duration-200">
                                                    <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                                        <svg class="h-4 w-4 text-white drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </div>
                                                </a>
                                            @else
                                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 text-[10px] text-slate-400 text-center p-1">
                                                    Tanpa Foto
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Ruangan & Kerusakan (Jelas & Rapi) -->
                                        <td class="px-5 py-6 align-top max-w-sm">
                                            <div class="flex items-center gap-1.5 font-bold text-slate-900 text-sm">
                                                <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                <span>{{ $complaint->room?->room_name ?? 'Ruang Terhapus' }}</span>
                                            </div>
                                            <div class="mt-2.5 rounded-xl border border-slate-200 bg-slate-50/90 p-3 shadow-2xs">
                                                <p class="text-xs font-semibold text-slate-900 leading-relaxed whitespace-pre-line py-0.5">
                                                    {{ $complaint->description }}
                                                </p>
                                            </div>
                                        </td>

                                        <!-- Pelapor (Interaktif dengan Hover Tooltip) -->
                                        <td class="px-5 py-6 align-top">
                                            @if ($complaint->reporter)
                                                <div class="relative inline-block group">
                                                    <div class="flex items-center gap-2 cursor-pointer">
                                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                                                            {{ strtoupper(substr($complaint->reporter->fullname, 0, 1)) }}
                                                        </div>
                                                        <div>
                                                            <div class="flex items-center gap-1">
                                                                <p class="font-semibold text-slate-900 text-xs leading-tight underline underline-offset-4 decoration-dashed decoration-slate-400 group-hover:decoration-amber-600 group-hover:text-amber-800 transition">
                                                                    {{ $complaint->reporter->fullname }}
                                                                </p>
                                                                <svg class="h-3 w-3 text-slate-400 group-hover:text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                            </div>
                                                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $complaint->reporter->role?->role_name ?? 'Warga Sekolah' }}</p>
                                                            @if ($isMine)
                                                                <span class="mt-1 inline-flex items-center rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 border border-amber-200">
                                                                    Laporan Saya
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <!-- Tooltip Popover saat Kursor Mengarah ke Pelapor -->
                                                    <div class="pointer-events-none invisible opacity-0 group-hover:visible group-hover:opacity-100 group-hover:pointer-events-auto transition-all duration-200 ease-out absolute left-0 bottom-full mb-2 z-50 w-72 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-xl text-left">
                                                        <div class="flex items-center gap-2.5 border-b border-slate-100 pb-2">
                                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-100 font-bold text-amber-800 text-xs">
                                                                {{ strtoupper(substr($complaint->reporter->fullname, 0, 1)) }}
                                                            </div>
                                                            <div class="min-w-0">
                                                                <p class="text-xs font-bold text-slate-900 truncate">{{ $complaint->reporter->fullname }}</p>
                                                                <p class="text-[10px] text-amber-700 font-semibold">{{ $complaint->reporter->role?->role_name ?? 'Warga Sekolah' }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2 space-y-1.5 text-xs text-slate-600">
                                                            <div class="flex items-center gap-1.5">
                                                                <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                                </svg>
                                                                <span class="truncate"><strong class="font-medium text-slate-500">Username:</strong> {{ $complaint->reporter->username }}</span>
                                                            </div>
                                                            <div class="flex items-center gap-1.5">
                                                                <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                                </svg>
                                                                <span class="truncate"><strong class="font-medium text-slate-500">Email:</strong> {{ $complaint->reporter->email }}</span>
                                                            </div>
                                                            <div class="flex items-center gap-1.5">
                                                                <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                                </svg>
                                                                <span><strong class="font-medium text-slate-500">Telepon:</strong> {{ $complaint->reporter->phone_number ?: '-' }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="absolute left-6 -bottom-1 h-2.5 w-2.5 rotate-45 border-b border-r border-slate-200 bg-white"></div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-400">Anonim</span>
                                            @endif
                                        </td>

                                        <!-- Status Perbaikan -->
                                        <td class="px-5 py-6 align-top">
                                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold shadow-2xs {{ $statusClass }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $dotColor }}"></span>
                                                <span>{{ $complaint->status_repair }}</span>
                                            </span>
                                            @if ($complaint->repaired_at)
                                                <p class="mt-1 text-[11px] text-emerald-700 font-medium">
                                                    Selesai: {{ $complaint->repaired_at->format('d/m/Y H:i') }}
                                                </p>
                                            @endif
                                        </td>

                                        <!-- Penanggung Jawab (PIC) -->
                                        <td class="px-5 py-6 align-top text-xs text-slate-700">
                                            @if ($complaint->handler)
                                                <p class="font-semibold text-slate-900">{{ $complaint->handler->fullname }}</p>
                                                <p class="text-[11px] text-slate-400">{{ $complaint->handler->phone_number ?: 'PIC Ruang' }}</p>
                                            @else
                                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">
                                                    Menunggu Penanganan
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Waktu Lapor -->
                                        <td class="px-5 py-4 align-top text-xs text-slate-600">
                                            <p class="font-medium text-slate-800">{{ $complaint->reported_at ? $complaint->reported_at->format('d/m/Y') : '-' }}</p>
                                            <p class="text-[11px] text-slate-400">{{ $complaint->reported_at ? $complaint->reported_at->format('H:i') . ' WIB' : '' }}</p>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($complaints->hasPages())
                        <div class="border-t border-gray-200 bg-gray-50/50 px-6 py-3.5">
                            {{ $complaints->links() }}
                        </div>
                    @endif
                @endif
            </section>
        </div>
    </div>
</x-app-layout>

