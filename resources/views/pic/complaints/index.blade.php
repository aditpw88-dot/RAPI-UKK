<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">PIC Ruang</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Manajemen Pengaduan Kerusakan</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau dan perbarui laporan dari ruang yang menjadi tanggung jawab Anda.</p>
        </div>
    </x-slot>

    <div x-data="complaintManager()" class="min-h-screen bg-gray-100 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="alert">{{ session('success') }}</div>
            @endif
            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Laporan</p><p class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">{{ $statistics['total'] }}</p></div>
                <div class="rounded-xl border border-red-100 bg-red-50 p-4 shadow-sm sm:p-5"><p class="text-xs font-medium uppercase tracking-wide text-red-700">Belum Diperbaiki</p><p class="mt-2 text-2xl font-bold text-red-700 sm:text-3xl">{{ $statistics['not_repaired'] }}</p></div>
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 shadow-sm sm:p-5"><p class="text-xs font-medium uppercase tracking-wide text-amber-700">Menunggu Spare Part</p><p class="mt-2 text-2xl font-bold text-amber-700 sm:text-3xl">{{ $statistics['waiting_parts'] }}</p></div>
                <div class="rounded-xl border border-green-100 bg-green-50 p-4 shadow-sm sm:p-5"><p class="text-xs font-medium uppercase tracking-wide text-green-700">Sudah Diperbaiki</p><p class="mt-2 text-2xl font-bold text-green-700 sm:text-3xl">{{ $statistics['repaired'] }}</p></div>
            </section>

            <section>
                <div class="mb-4 flex items-end justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-900">Daftar Laporan Kerusakan</h3><p class="mt-1 text-sm text-slate-500">{{ $complaints->total() }} laporan pada ruang tanggung jawab Anda.</p></div></div>
                <form method="GET" action="{{ route('pic.complaints.index') }}" class="mb-4 grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_16rem_auto_auto]">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari laporan, ruang, atau pelapor" aria-label="Cari laporan, ruang, atau pelapor" class="rounded-md border-gray-300 text-sm">
                    <select name="status" aria-label="Filter status perbaikan" class="rounded-md border-gray-300 text-sm">
                        <option value="">Semua status</option>
                        <option value="Belum Diperbaiki" @selected(request('status') === 'Belum Diperbaiki')>Belum Diperbaiki</option>
                        <option value="Menunggu Spare Part" @selected(request('status') === 'Menunggu Spare Part')>Menunggu Spare Part</option>
                        <option value="Sudah Diperbaiki" @selected(request('status') === 'Sudah Diperbaiki')>Sudah Diperbaiki</option>
                    </select>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cari / Filter</button>
                    <a href="{{ route('pic.complaints.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-gray-50">Reset</a>
                </form>
                @if ($complaints->isEmpty())
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center text-sm text-slate-500">Belum ada laporan kerusakan untuk ruang Anda.</div>
                @else
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($complaints as $complaint)
                            @php
                                $statusClass = match ($complaint->status_repair) {
                                    'Sudah Diperbaiki' => 'bg-green-100 text-green-700',
                                    'Menunggu Spare Part' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-red-100 text-red-700',
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
                            <article id="complaint-card-{{ $complaint->id }}" class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <button type="button" @click="openComplaint(@js($complaintData))" class="group relative block aspect-[4/3] w-full overflow-hidden bg-slate-100 text-left" aria-label="Lihat detail laporan {{ $complaint->room?->room_name }}">
                                    <img src="{{ $complaintData['photo'] }}" alt="Foto kerusakan {{ $complaint->room?->room_name }}" class="h-full w-full object-cover transition duration-200 group-hover:scale-105">
                                    <span class="absolute right-3 top-3 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $complaint->status_repair }}</span>
                                    @if ($complaint->is_escalated)
                                        <span class="absolute left-3 top-3 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Dialihkan Waka</span>
                                    @endif
                                </button>
                                <div class="flex flex-1 flex-col p-4">
                                    <div class="flex items-start justify-between gap-3"><div><h4 class="font-semibold text-slate-900">{{ $complaint->room?->room_name ?? '-' }}</h4><p class="mt-1 text-xs text-slate-500">{{ $complaint->reported_at?->format('d M Y, H:i') ?? '-' }}</p></div><span class="text-xs text-slate-400">#{{ $complaint->id }}</span></div>
                                    <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ $complaint->description }}</p>
                                    <div class="mt-4 border-t border-gray-100 pt-3">
                                        <p class="text-xs font-semibold text-slate-700">Pelapor: {{ $complaint->reporter?->fullname ?? '-' }}</p>
                                        @if ($complaint->reporter)
                                            <p class="mt-1 text-xs text-slate-500">Username: {{ $complaint->reporter->username }} · {{ $complaint->reporter->role?->role_name ?? '-' }}</p>
                                            <p class="mt-1 break-all text-xs text-slate-500">{{ $complaint->reporter->email }}</p>
                                            <p class="mt-1 text-xs text-slate-500">Telepon: {{ $complaint->reporter->phone_number ?: '-' }}</p>
                                        @endif
                                        <button type="button" @click="openComplaint(@js($complaintData))" class="mt-2 text-xs font-semibold text-slate-700 hover:text-slate-900">Detail laporan</button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-6">{{ $complaints->links() }}</div>
                @endif
            </section>
        </div>

        <div x-cloak x-show="isOpen" x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6" @keydown.escape.window="closeComplaint()" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/60" @click="closeComplaint()"></div>
            <div x-show="isOpen" x-transition class="relative mx-auto my-4 max-w-3xl overflow-hidden rounded-xl bg-white shadow-2xl sm:my-10">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4"><div><h3 class="font-semibold text-slate-900">Detail Laporan Kerusakan</h3><p class="mt-1 text-xs text-slate-500">ID laporan #<span x-text="selected.id"></span></p></div><button type="button" @click="closeComplaint()" class="rounded-md p-2 text-xl leading-none text-slate-500 hover:bg-gray-100" aria-label="Tutup modal">&times;</button></div>
                <div class="grid gap-5 p-5 md:grid-cols-2">
                    <div class="overflow-hidden rounded-lg bg-slate-100"><img :src="selected.photo" :alt="'Foto kerusakan ' + selected.room" class="max-h-[28rem] w-full object-contain"></div>
                    <div class="space-y-4"><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ruangan</p><p class="mt-1 font-semibold text-slate-900" x-text="selected.room"></p></div><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Laporan</p><p class="mt-1 text-sm text-slate-700" x-text="selected.date"></p></div><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Identitas Pelapor</p><p class="mt-1 text-sm font-semibold text-slate-900" x-text="selected.reporter"></p><p class="mt-1 text-xs text-slate-600">Username: <span x-text="selected.reporterUsername"></span></p><p class="mt-1 text-xs text-slate-600">Peran: <span x-text="selected.reporterRole"></span></p><p class="mt-1 break-all text-xs text-slate-600">Email: <span x-text="selected.reporterEmail"></span></p><p class="mt-1 text-xs text-slate-600">Telepon: <span x-text="selected.reporterPhone"></span></p></div><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Deskripsi</p><p class="mt-1 text-sm leading-6 text-slate-700" x-text="selected.description"></p></div>
                        <form method="POST" :action="statusAction()" class="border-t border-gray-200 pt-4" data-confirm-action="memperbarui status laporan kerusakan" :data-confirm-item="'Laporan #' + selected.id + ' · ' + selected.room" data-confirm-title="Konfirmasi perubahan status">@csrf @method('PATCH')<label for="status_repair" class="block text-sm font-semibold text-slate-700">Perbarui Status</label><div class="mt-2 flex gap-2"><select id="status_repair" name="status_repair" x-model="selected.status" class="min-w-0 flex-1 rounded-md border-gray-300 text-sm"><option>Belum Diperbaiki</option><option>Menunggu Spare Part</option><option>Sudah Diperbaiki</option></select><button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Simpan</button></div></form>
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
