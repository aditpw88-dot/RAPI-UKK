<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-slate-500">Administrasi</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Manajemen Ruang</h2>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif
            @if (session('warning'))
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">{{ session('warning') }}</div>
            @endif

            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">Tambah Ruang</h3>
                    <p class="mt-1 text-sm text-slate-500">Isi detail ruang dan tentukan penanggung jawabnya.</p>
                </div>
                <form method="POST" action="{{ route('admin.rooms.store') }}" class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4" data-confirm-action="menambahkan ruang" data-confirm-item="Ruang baru">
                    @csrf
                    <label class="grid content-start gap-1.5 text-sm font-medium text-slate-700">
                        Nama Ruangan
                        <input name="room_name" value="{{ old('room_name') }}" placeholder="Contoh: Ruang Wijaya" required class="rounded-md border-gray-300 text-sm font-normal">
                    </label>
                    <label class="grid content-start gap-1.5 text-sm font-medium text-slate-700">
                        Kuantitas / Kapasitas
                        <input name="capacity" type="number" min="1" value="{{ old('capacity') }}" placeholder="Jumlah orang" required class="rounded-md border-gray-300 text-sm font-normal">
                    </label>
                    <label class="grid content-start gap-1.5 text-sm font-medium text-slate-700">
                        Pilih Penanggung Jawab
                        <input type="hidden" name="pic_id" value="{{ old('pic_id') }}" required>
                        <input type="search" data-pic-search list="pic-options" value="{{ old('pic_id') ? ($pics->firstWhere('id', old('pic_id'))?->fullname . ' - ' . $pics->firstWhere('id', old('pic_id'))?->email) : '' }}" placeholder="Ketik atau pilih PIC" autocomplete="off" required aria-label="Cari dan pilih PIC" class="w-full rounded-md border-gray-300 text-sm">
                    </label>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Tambah Ruang</button>
                    </div>
                </form>
            </section>

            <datalist id="pic-options">
                @foreach ($pics as $pic)
                    <option value="{{ $pic->fullname . ' - ' . $pic->email }}" data-pic-id="{{ $pic->id }}"></option>
                @endforeach
            </datalist>

            <form method="GET" action="{{ route('admin.rooms') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_12rem_auto_auto]">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama ruang atau PIC" aria-label="Cari nama ruang atau PIC" class="rounded-md border-gray-300 text-sm">
                <select name="active" aria-label="Filter status ruang" class="rounded-md border-gray-300 text-sm">
                    <option value="">Semua status</option>
                    <option value="active" @selected(request('active') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('active') === 'inactive')>Tidak aktif</option>
                </select>
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cari / Filter</button>
                <a href="{{ route('admin.rooms') }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-gray-50">Reset</a>
            </form>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Detail Ruangan</th>
                                <th class="px-5 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rooms as $room)
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="space-y-2">
                                            <p class="text-base font-semibold text-slate-900">{{ $room->room_name }}</p>
                                            <details class="group">
                                                <summary class="flex w-fit cursor-pointer list-none items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-500 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 [&::-webkit-details-marker]:hidden">
                                                    <span class="text-base text-slate-500 transition group-open:rotate-90" aria-hidden="true">▸</span>
                                                    <span>Lihat Identitas Ruangan</span>
                                                </summary>
                                                <div class="mt-3 space-y-2 rounded-lg bg-slate-50 p-4 text-base text-slate-700">
                                                    <p>Kuantitas: {{ $room->capacity }} orang</p>
                                                    <p>Penanggung jawab: {{ $room->pic?->fullname ?? '-' }}</p>
                                                    <p>WhatsApp: {{ $room->pic?->phone_number ?? 'Belum diisi' }}</p>
                                                    <p>Status: {{ $room->is_active ? 'Aktif' : 'Tidak aktif' }}</p>
                                                </div>
                                            </details>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <details>
                                            <summary class="cursor-pointer text-xs font-semibold text-slate-700">Edit</summary>
                                            <form method="POST" action="{{ route('admin.rooms.update', $room) }}" class="mt-2 grid gap-2" data-confirm-action="memperbarui" data-confirm-item="Ruang: {{ $room->room_name }}">
                                                @csrf
                                                @method('PUT')
                                                <input name="room_name" value="{{ $room->room_name }}" required class="rounded border-gray-300 text-xs">
                                                <input name="capacity" type="number" min="1" value="{{ $room->capacity }}" required class="rounded border-gray-300 text-xs">
                                                <input type="hidden" name="pic_id" value="{{ $room->pic_id }}" required>
                                                <input type="search" data-pic-search list="pic-options" value="{{ $room->pic ? $room->pic->fullname . ' - ' . $room->pic->email : '' }}" placeholder="Ketik atau pilih PIC" autocomplete="off" required aria-label="Cari dan pilih PIC" class="rounded border-gray-300 text-xs">
                                                <label class="text-xs"><input type="checkbox" name="is_active" value="1" @checked($room->is_active)> Aktif</label>
                                                <button class="rounded bg-slate-900 px-3 py-1.5 text-xs text-white">Simpan</button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" class="mt-2" data-confirm-action="menghapus" data-confirm-item="Ruang: {{ $room->room_name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-600">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-5 py-8 text-center text-slate-500">Belum ada ruang.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

<script>
    const picOptions = document.querySelectorAll('#pic-options option');

    document.querySelectorAll('[data-pic-search]').forEach((searchInput) => {
        const form = searchInput.closest('form');
        const picIdInput = form.querySelector('input[name="pic_id"]');

        function syncPicId() {
            const selectedPic = Array.from(picOptions).find((option) => option.value === searchInput.value);
            picIdInput.value = selectedPic?.dataset.picId ?? '';
            searchInput.setCustomValidity(selectedPic ? '' : 'Pilih PIC dari daftar yang tersedia.');
        }

        searchInput.addEventListener('input', syncPicId);
        form.addEventListener('submit', syncPicId);
        syncPicId();
    });
</script>
