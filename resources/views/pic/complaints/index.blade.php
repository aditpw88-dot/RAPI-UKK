<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Panel PIC Ruang</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Manajemen Pengaduan Kerusakan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pantau dan perbarui status penanganan laporan kerusakan di ruang tanggung jawab Anda.</p>
        </div>
    </x-slot>

    <div x-data="complaintManager()" class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-2xs" role="alert">{{ session('success') }}</div>
            @endif

            <!-- Statistik Kartu Ringkasan -->
            <section class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-5">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Laporan</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">{{ $statistics['total'] }}</p>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm sm:p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-red-700">Belum Diperbaiki</p>
                    <p class="mt-2 text-2xl font-bold text-red-700 sm:text-3xl">{{ $statistics['not_repaired'] }}</p>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm sm:p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">Sedang Diperbaiki</p>
                    <p class="mt-2 text-2xl font-bold text-blue-700 sm:text-3xl">{{ $statistics['in_repair'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm sm:p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-800">Menunggu Spare Part</p>
                    <p class="mt-2 text-2xl font-bold text-amber-800 sm:text-3xl">{{ $statistics['waiting_parts'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm sm:p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Sudah Diperbaiki</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-800 sm:text-3xl">{{ $statistics['repaired'] }}</p>
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Daftar Laporan Kerusakan</h3>
                        <p class="text-xs text-slate-500">
                            @if ($complaints->total() > 0)
                                Menampilkan {{ $complaints->firstItem() }}–{{ $complaints->lastItem() }} dari {{ $complaints->total() }} laporan pada ruang tanggung jawab Anda.
                            @else
                                Belum ada laporan kerusakan yang tercatat.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Form Pencarian & Filter Status -->
                <form method="GET" action="{{ route('pic.complaints.index') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_16rem_auto_auto]">
                    <div class="relative">
                        <input 
                            type="search" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="Cari laporan, ruang, atau pelapor..." 
                            aria-label="Cari laporan, ruang, atau pelapor" 
                            class="w-full rounded-lg border-gray-300 py-2 pl-9 pr-3 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800"
                        >
                        <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <select name="status" aria-label="Filter status perbaikan" class="rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                        <option value="">Semua status perbaikan</option>
                        <option value="Belum Diperbaiki" @selected(request('status') === 'Belum Diperbaiki')>Belum Diperbaiki</option>
                        <option value="Sedang Diperbaiki" @selected(request('status') === 'Sedang Diperbaiki')>Sedang Diperbaiki</option>
                        <option value="Menunggu Spare Part" @selected(request('status') === 'Menunggu Spare Part')>Menunggu Spare Part</option>
                        <option value="Sudah Diperbaiki" @selected(request('status') === 'Sudah Diperbaiki')>Sudah Diperbaiki</option>
                    </select>
                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow-2xs transition hover:bg-slate-700">Cari / Filter</button>
                    <a href="{{ route('pic.complaints.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-gray-50 transition">Reset</a>
                </form>

                @if ($complaints->isEmpty())
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center text-sm text-slate-500">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-3 font-semibold text-slate-700">Tidak ada laporan kerusakan</p>
                        <p class="mt-1 text-xs text-slate-400">Tidak ditemukan laporan kerusakan yang cocok dengan filter atau belum ada laporan baru.</p>
                    </div>
                @else
                    <!-- Grid Responsif (1 kolom mobile, 2 kolom tablet, 3 kolom desktop) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($complaints as $complaint)
                            @php
                                $statusBadge = match ($complaint->status_repair) {
                                    'Sudah Diperbaiki' => [
                                        'class' => 'bg-emerald-50 text-emerald-900 border-emerald-300',
                                        'dot' => 'bg-emerald-600',
                                        'label' => 'Sudah Diperbaiki',
                                    ],
                                    'Sedang Diperbaiki' => [
                                        'class' => 'bg-blue-50 text-blue-900 border-blue-300',
                                        'dot' => 'bg-blue-600',
                                        'label' => 'Sedang Diperbaiki',
                                    ],
                                    'Menunggu Spare Part' => [
                                        'class' => 'bg-amber-50 text-amber-900 border-amber-300',
                                        'dot' => 'bg-amber-600',
                                        'label' => 'Menunggu Spare Part',
                                    ],
                                    default => [
                                        'class' => 'bg-rose-50 text-rose-900 border-rose-300',
                                        'dot' => 'bg-rose-600',
                                        'label' => 'Belum Diperbaiki',
                                    ],
                                };
                                $complaintData = [
                                    'id' => $complaint->id,
                                    'room' => $complaint->room?->room_name ?? '-',
                                    'date' => $complaint->reported_at?->format('d/m/Y H:i') ?? '-',
                                    'reporter' => $complaint->reporter?->fullname ?? '-',
                                    'reporterUsername' => $complaint->reporter?->username ?? '-',
                                    'reporterRole' => $complaint->reporter?->role?->role_name ?? '-',
                                    'reporterEmail' => $complaint->reporter?->email ?? '-',
                                    'reporterPhone' => $complaint->reporter?->phone_number ?: '-',
                                    'description' => $complaint->description,
                                    'status' => $complaint->status_repair,
                                    'photo' => asset('storage/' . ltrim($complaint->photo_path, '/')),
                                ];
                            @endphp
                            <article id="complaint-card-{{ $complaint->id }}" class="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition duration-200">
                                <!-- Foto Kerusakan Proporsional (Aspect Video) -->
                                <button type="button" @click="openComplaint(@js($complaintData))" class="group/img relative block aspect-video w-full overflow-hidden bg-slate-100 text-left focus:outline-none focus:ring-2 focus:ring-inset focus:ring-slate-800" aria-label="Lihat detail laporan {{ $complaint->room?->room_name }}">
                                    <img src="{{ $complaintData['photo'] }}" alt="Foto kerusakan {{ $complaint->room?->room_name }}" class="h-full w-full object-cover transition duration-300 group-hover/img:scale-105">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-black/10 opacity-70"></div>
                                    
                                    <!-- Badge Status Kontras Tinggi & Minimal text-sm (Aksesibilitas) -->
                                    <span class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full border {{ $statusBadge['class'] }} px-3 py-1 text-sm font-semibold shadow-md backdrop-blur-xs">
                                        <span class="h-2 w-2 rounded-full {{ $statusBadge['dot'] }}"></span>
                                        <span>{{ $statusBadge['label'] }}</span>
                                    </span>

                                    @if ($complaint->is_escalated)
                                        <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-900 shadow-md">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span>Dialihkan Waka</span>
                                        </span>
                                    @endif
                                </button>

                                <!-- Konten Kartu -->
                                <div class="flex flex-1 flex-col p-5">
                                    <!-- Identitas Ruangan & ID Laporan -->
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="text-lg font-bold text-slate-900 group-hover:text-amber-800 transition-colors leading-snug">
                                                {{ $complaint->room?->room_name ?? '-' }}
                                            </h4>
                                            <div class="mt-1 flex items-center gap-1.5 text-xs font-medium text-slate-500">
                                                <svg width="14" height="14" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span>{{ $complaint->reported_at?->format('d M Y, H:i') ?? '-' }}</span>
                                            </div>
                                        </div>
                                        <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">
                                            #{{ $complaint->id }}
                                        </span>
                                    </div>

                                    <!-- Deskripsi Kerusakan -->
                                    <div class="mt-3 min-h-[3.25rem]">
                                        <p class="text-xs font-semibold text-slate-500">Deskripsi Kerusakan:</p>
                                        <p class="mt-0.5 line-clamp-2 text-sm font-medium leading-relaxed text-slate-800">
                                            {{ $complaint->description }}
                                        </p>
                                    </div>

                                    <!-- Ringkasan Pelapor (Terpisah Bersih Tanpa Tabrakan Teks) -->
                                    <div class="mt-4 flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                                                {{ strtoupper(substr($complaint->reporter?->fullname ?? 'P', 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-slate-900">{{ $complaint->reporter?->fullname ?? 'Anonim' }}</p>
                                                <p class="truncate text-xs font-medium text-slate-500">{{ $complaint->reporter?->role?->role_name ?? 'Pelapor' }}</p>
                                            </div>
                                        </div>

                                        @if ($complaint->reporter)
                                            <!-- Dropdown Info Kontak Pelapor (Ikon 'i' Bundar) -->
                                            <details class="group/contact relative">
                                                <summary class="cursor-pointer list-none rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 hover:border-slate-300 transition focus:outline-none shadow-2xs" title="Info kontak pelapor">
                                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </summary>
                                                <div class="absolute bottom-full right-0 mb-2 w-64 rounded-xl border border-slate-200 bg-white p-3.5 text-xs shadow-xl ring-1 ring-black/5 z-20">
                                                    <div class="flex items-center gap-1.5 font-bold text-slate-900 border-b border-gray-100 pb-1.5 mb-2">
                                                        <svg width="14" height="14" class="text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span>Info Kontak Pelapor</span>
                                                    </div>
                                                    <p class="text-slate-600">Username: {{ $complaint->reporter->username }} · {{ $complaint->reporter->role?->role_name ?? '-' }}</p>
                                                    <p class="text-slate-600 break-all mt-1">Email: {{ $complaint->reporter->email }}</p>
                                                    <p class="text-slate-600 mt-1">Telepon: {{ $complaint->reporter->phone_number ?: '-' }}</p>
                                                </div>
                                            </details>
                                        @endif
                                    </div>

                                    <!-- Tombol Aksi Luas & Nyaman Diakses (Poin 3 - Accessibility) -->
                                    <button 
                                        type="button" 
                                        @click="openComplaint(@js($complaintData))" 
                                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-2xs transition hover:border-slate-800 hover:bg-slate-900 hover:text-white focus:outline-none focus:ring-2 focus:ring-slate-800"
                                    >
                                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Detail Laporan</span>
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <!-- Paginasi -->
                    <div class="mt-6">
                        {{ $complaints->links() }}
                    </div>
                @endif
            </section>
        </div>

        <!-- Modal Dialog Detail & Perbarui Status -->
        <div x-cloak x-show="isOpen" x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6" @keydown.escape.window="closeComplaint()" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs" @click="closeComplaint()"></div>
            <div x-show="isOpen" x-transition class="relative mx-auto my-4 max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl sm:my-10 border border-gray-200">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 bg-gray-50/50">
                    <div>
                        <span class="inline-block rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 uppercase tracking-wider">Laporan Kerusakan</span>
                        <h3 class="mt-1 text-lg font-bold text-slate-900">Detail Laporan #<span x-text="selected.id"></span></h3>
                    </div>
                    <button type="button" @click="closeComplaint()" class="rounded-lg p-1.5 text-slate-400 hover:bg-gray-100 hover:text-slate-600 transition focus:outline-none" aria-label="Tutup modal">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="grid gap-6 p-6 md:grid-cols-2 items-start">
                    <!-- Box Foto Menyesuaikan Ukuran Foto (Tanpa Ruang Kosong) -->
                    <div class="self-start overflow-hidden rounded-xl border border-gray-200 bg-slate-100 shadow-xs">
                        <a :href="selected.photo" target="_blank" title="Klik untuk membuka foto ukuran penuh" class="group/img block relative">
                            <img :src="selected.photo" :alt="'Foto kerusakan ' + selected.room" class="w-full h-auto max-h-[30rem] object-contain rounded-xl block transition duration-200 group-hover/img:scale-[1.01]">
                            <div class="absolute bottom-2.5 right-2.5 rounded-lg bg-black/60 px-2.5 py-1 text-[11px] font-medium text-white backdrop-blur-xs flex items-center gap-1 opacity-80 group-hover/img:opacity-100 transition">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                </svg>
                                <span>Buka foto asli</span>
                            </div>
                        </a>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Ruangan</p>
                            <p class="mt-1 text-base font-bold text-slate-900" x-text="selected.room"></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tanggal Laporan</p>
                            <p class="mt-1 text-sm font-medium text-slate-700" x-text="selected.date"></p>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3.5 space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Identitas Pelapor</p>
                            <p class="text-sm font-bold text-slate-900" x-text="selected.reporter"></p>
                            <p class="text-xs text-slate-600">Username: <span class="font-medium" x-text="selected.reporterUsername"></span></p>
                            <p class="text-xs text-slate-600">Peran: <span class="font-medium" x-text="selected.reporterRole"></span></p>
                            <p class="break-all text-xs text-slate-600">Email: <span class="font-medium" x-text="selected.reporterEmail"></span></p>
                            <p class="text-xs text-slate-600">Telepon: <span class="font-medium" x-text="selected.reporterPhone"></span></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Deskripsi Kerusakan:</p>
                            <p class="mt-1 text-sm font-medium leading-relaxed text-slate-800" x-text="selected.description"></p>
                        </div>

                        <form method="POST" :action="statusAction()" class="border-t border-gray-200 pt-4" data-confirm-action="memperbarui status laporan kerusakan" :data-confirm-item="'Laporan #' + selected.id + ' · ' + selected.room" data-confirm-title="Konfirmasi perubahan status">
                            @csrf
                            @method('PATCH')
                            <label for="status_repair" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Perbarui Status Penanganan</label>
                            <div class="flex gap-2">
                                <select id="status_repair" name="status_repair" x-model="selected.status" class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                                    <option>Belum Diperbaiki</option>
                                    <option>Sedang Diperbaiki</option>
                                    <option>Menunggu Spare Part</option>
                                    <option>Sudah Diperbaiki</option>
                                </select>
                                <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-slate-700 transition">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
    <script>
        function complaintManager() {
            return {
                isOpen: false,
                selected: { id: null, room: '', date: '', reporter: '', reporterUsername: '', reporterRole: '', reporterEmail: '', reporterPhone: '', description: '', status: '', photo: '' },
                openComplaint(complaint) { this.selected = complaint; this.isOpen = true; document.body.classList.add('overflow-hidden'); },
                closeComplaint() { this.isOpen = false; document.body.classList.remove('overflow-hidden'); },
                statusAction() { return '{{ url('/pic/complaints') }}/' + this.selected.id + '/status'; },
            };
        }
    </script>
</x-app-layout>
