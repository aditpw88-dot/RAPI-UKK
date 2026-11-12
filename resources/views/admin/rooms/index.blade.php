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

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Daftar Ruangan</h3>
                    <p class="text-xs text-slate-500">
                        @if ($rooms->total() > 0)
                            Menampilkan {{ $rooms->firstItem() }}–{{ $rooms->lastItem() }} dari {{ $rooms->total() }} ruangan (Urut: Ruang Wijaya, DAK, Lab 1–21, Ruang 1–seterusnya).
                        @else
                            Kelola ketersediaan ruang sekolah dan penanggung jawabnya.
                        @endif
                    </p>
                </div>
                <button type="button" id="open-create-room-btn" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
                    <svg width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Ruang</span>
                </button>
            </div>

            <form method="GET" action="{{ route('admin.rooms') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_11rem_11rem_auto_auto]">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama ruang atau PIC" aria-label="Cari nama ruang atau PIC" class="rounded-md border-gray-300 text-sm">
                <select name="condition" aria-label="Filter kondisi ruang" class="rounded-md border-gray-300 text-sm">
                    <option value="">Semua kondisi</option>
                    <option value="active" @selected(request('condition', request('active')) === 'active')>Kondisi: Aktif</option>
                    <option value="inactive" @selected(request('condition', request('active')) === 'inactive')>Kondisi: Tidak aktif</option>
                </select>
                <select name="status" aria-label="Filter status ruang" class="rounded-md border-gray-300 text-sm">
                    <option value="">Semua status</option>
                    <option value="available" @selected(request('status') === 'available')>Status: Tersedia</option>
                    <option value="unavailable" @selected(request('status') === 'unavailable')>Status: Tidak tersedia</option>
                </select>
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cari / Filter</button>
                <a href="{{ route('admin.rooms') }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-gray-50">Reset</a>
            </form>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-6 py-3 w-full">Detail Ruangan</th>
                                <th class="px-6 py-3 text-right whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rooms as $room)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-base font-semibold text-slate-900">{{ $room->room_name }}</p>
                                                @if ($room->is_active)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                        Kondisi: Aktif
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 border border-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                        Kondisi: Tidak aktif
                                                    </span>
                                                @endif
                                                @if ($room->is_available)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 border border-teal-200 px-2 py-0.5 text-xs font-semibold text-teal-800">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>
                                                        Status: Tersedia
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 border border-rose-200 px-2 py-0.5 text-xs font-semibold text-rose-800">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                                        Status: Tidak tersedia
                                                    </span>
                                                @endif
                                            </div>
                                            <details class="group">
                                                <summary class="flex w-fit cursor-pointer list-none items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-500 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 [&::-webkit-details-marker]:hidden">
                                                    <span class="text-base text-slate-500 transition group-open:rotate-90" aria-hidden="true">▸</span>
                                                    <span>Lihat Identitas Ruangan</span>
                                                </summary>
                                                <div class="mt-3 space-y-2.5 rounded-lg bg-slate-50 p-4 text-base text-slate-700">
                                                    <p class="font-medium text-slate-800">Kuantitas: {{ $room->capacity }} orang</p>
                                                    @if ($room->all_pics->isNotEmpty())
                                                        @foreach ($room->all_pics as $pic)
                                                            <div class="space-y-1.5 {{ !$loop->first ? 'pt-2.5 border-t border-slate-200' : '' }}">
                                                                <p class="font-medium text-slate-800">Penanggung jawab : {{ $pic->fullname }}</p>
                                                                <div class="space-y-1 pl-2.5 border-l-2 border-slate-300">
                                                                    <p class="font-semibold text-slate-900 text-sm">Data Diri</p>
                                                                    <p class="text-sm text-slate-600">Nomor whatsapp: {{ $pic->phone_number ?: 'Belum diisi' }}</p>
                                                                    <p class="text-sm text-slate-600">gmail: {{ $pic->email }}</p>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <p class="font-medium text-slate-800">Penanggung jawab : Belum ditentukan</p>
                                                    @endif
                                                </div>
                                            </details>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2.5">
                                            <button type="button"
                                                data-open-edit-room
                                                data-room-id="{{ $room->id }}"
                                                data-room-name="{{ $room->room_name }}"
                                                data-capacity="{{ $room->capacity }}"
                                                data-is-active="{{ $room->is_active ? '1' : '0' }}"
                                                data-pic-id="{{ $room->pic_id ?? $room->all_pics->first()?->id }}"
                                                data-pic-ids="{{ json_encode(array_filter([$room->pic_id ?? $room->all_pics->first()?->id])) }}"
                                                data-update-url="{{ route('admin.rooms.update', $room) }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-slate-500 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400">
                                                <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit</span>
                                            </button>
                                            <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" class="inline-block" data-confirm-action="menghapus" data-confirm-item="Ruang: {{ $room->room_name }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 hover:text-red-800 transition">
                                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <span>Hapus</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-6 py-8 text-center text-slate-500">Belum ada ruang.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($rooms->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4 bg-white">
                        {{ $rooms->links() }}
                    </div>
                @endif
            </section>
        </div>

        <!-- Modal Window Tambah Ruang (Backdrop Blur) -->
        <div id="create-room-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="create-room-modal-title">
            <div id="create-room-modal-backdrop" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity duration-200" style="backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"></div>

            <div class="relative z-10 w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all my-auto">
                <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                    <div>
                        <span class="inline-block rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 uppercase tracking-wider">Ruang Baru</span>
                        <h3 id="create-room-modal-title" class="mt-1 text-lg font-bold text-slate-900">Tambah Ruang</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Isi detail ruangan dan tentukan penanggung jawabnya (1 PIC per ruangan).</p>
                    </div>
                    <button type="button" id="create-room-modal-close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition focus:outline-none" aria-label="Tutup modal">
                        <svg width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="create-room-form" method="POST" action="{{ route('admin.rooms.store') }}" class="space-y-4 pt-4" data-confirm-action="menambahkan ruangan baru" data-confirm-item="Ruangan baru" data-confirm-title="Konfirmasi Penambahan Ruangan" data-confirm-message="Apakah Anda yakin ingin menambahkan ruangan baru ini? Pastikan detail ruangan dan penanggung jawab (PIC) sudah sesuai.">
                    @csrf

                    <div>
                        <label for="create_room_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Nama Ruangan</label>
                        <input id="create_room_name" name="room_name" type="text" value="{{ old('room_name') }}" required placeholder="Contoh: Ruang Wijaya" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    </div>

                    <div>
                        <label for="create_capacity" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Kapasitas (Orang)</label>
                        <input id="create_capacity" name="capacity" type="number" min="1" value="{{ old('capacity') }}" required placeholder="Jumlah orang" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    </div>

                    <div class="relative grid content-start gap-1.5 text-sm font-medium text-slate-700" data-pic-select id="create-pic-select">
                        <span class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-700">Pilih Penanggung Jawab (PIC)</span>
                            <span class="text-xs font-normal text-slate-400">1 PIC per ruangan</span>
                        </span>
                        <input type="hidden" name="pic_id" data-pic-id-input value="{{ old('pic_id', old('pic_ids.0')) }}">
                        <button type="button" data-pic-dropdown-toggle class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm hover:border-slate-400 focus:border-slate-800 focus:outline-none" style="min-height: 40px;">
                            <span data-pic-summary class="truncate text-slate-400 font-medium">Pilih PIC penanggung jawab...</span>
                            <svg width="16" height="16" style="width: 16px; height: 16px; min-width: 16px; min-height: 16px;" class="text-slate-400 transition-transform duration-200" data-pic-arrow fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div data-pic-dropdown-menu class="hidden absolute top-full left-0 right-0 z-50 mt-1 max-h-56 overflow-y-auto rounded-xl border border-gray-200 bg-white p-2 shadow-2xl text-xs space-y-1">
                            <input type="text" data-pic-filter placeholder="Cari nama atau email PIC..." class="w-full rounded-lg border-gray-200 px-2.5 py-1.5 text-xs mb-1.5 focus:border-slate-800 focus:ring-0">
                            <div data-pic-list class="space-y-1">
                                @foreach ($pics as $pic)
                                    <label class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer select-none transition" data-pic-item data-search-text="{{ strtolower($pic->fullname . ' ' . $pic->email) }}">
                                        <input type="radio" name="create_pic_radio" value="{{ $pic->id }}" data-pic-name="{{ $pic->fullname }}" data-pic-email="{{ $pic->email }}" class="text-slate-900 border-gray-300 focus:ring-slate-800" @checked(old('pic_id', old('pic_ids.0')) == $pic->id)>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-slate-800 text-sm truncate">{{ $pic->fullname }}</p>
                                            <p class="text-xs text-slate-400 truncate">{{ $pic->email }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 mt-6">
                        <button type="button" id="create-room-modal-cancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700 shadow transition">
                            Simpan Ruangan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Window Edit Ruangan (Backdrop Blur) -->
        <div id="edit-room-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="edit-room-modal-title">
            <!-- Backdrop Blur Layer -->
            <div id="edit-room-modal-backdrop" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity duration-200" style="backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"></div>

            <!-- Modal Card Content -->
            <div class="relative z-10 w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all my-auto">
                <!-- Header -->
                <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                    <div>
                        <span class="inline-block rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 uppercase tracking-wider">Manajemen Ruang</span>
                        <h3 id="edit-room-modal-title" class="mt-1 text-lg font-bold text-slate-900">Edit Ruangan</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Perbarui detail ruangan dan tentukan PIC penanggung jawabnya.</p>
                    </div>
                    <button type="button" id="edit-room-modal-close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition focus:outline-none" aria-label="Tutup modal">
                        <svg width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form id="edit-room-form" method="POST" action="" class="space-y-4 pt-4" data-confirm-action="memperbarui data ruangan" data-confirm-item="" data-confirm-title="Konfirmasi Perubahan Ruangan" data-confirm-message="Apakah Anda yakin ingin menyimpan perubahan pada ruangan ini? Pastikan data ruangan dan PIC sudah sesuai.">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="modal-room-name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Nama Ruangan</label>
                        <input id="modal-room-name" name="room_name" type="text" required placeholder="Contoh: Ruang Wijaya" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    </div>

                    <div>
                        <label for="modal-capacity" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Kapasitas (Orang)</label>
                        <input id="modal-capacity" name="capacity" type="number" min="1" required placeholder="Jumlah orang" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    </div>

                    <div class="relative grid content-start gap-1.5 text-sm font-medium text-slate-700" data-pic-select id="modal-pic-select">
                        <span class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-700">Penanggung Jawab (PIC)</span>
                            <span class="text-xs font-normal text-slate-400">1 PIC per ruangan</span>
                        </span>
                        <input type="hidden" name="pic_id" data-pic-id-input value="">
                        <button type="button" data-pic-dropdown-toggle class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm hover:border-slate-400 focus:border-slate-800 focus:outline-none" style="min-height: 40px;">
                            <span data-pic-summary class="truncate text-slate-400 font-medium">Pilih PIC penanggung jawab...</span>
                            <svg width="16" height="16" style="width: 16px; height: 16px; min-width: 16px; min-height: 16px;" class="text-slate-400 transition-transform duration-200" data-pic-arrow fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div data-pic-dropdown-menu class="hidden absolute top-full left-0 right-0 z-50 mt-1 max-h-56 overflow-y-auto rounded-xl border border-gray-200 bg-white p-2 shadow-2xl text-xs space-y-1">
                            <input type="text" data-pic-filter placeholder="Cari nama atau email PIC..." class="w-full rounded-lg border-gray-200 px-2.5 py-1.5 text-xs mb-1.5 focus:border-slate-800 focus:ring-0">
                            <div data-pic-list class="space-y-1">
                                @foreach ($pics as $pic)
                                    <label class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer select-none transition" data-pic-item data-search-text="{{ strtolower($pic->fullname . ' ' . $pic->email) }}">
                                        <input type="radio" name="edit_pic_radio" value="{{ $pic->id }}" data-pic-name="{{ $pic->fullname }}" data-pic-email="{{ $pic->email }}" class="text-slate-900 border-gray-300 focus:ring-slate-800">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-slate-800 text-sm truncate">{{ $pic->fullname }}</p>
                                            <p class="text-xs text-slate-400 truncate">{{ $pic->email }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input id="modal-is-active" type="checkbox" name="is_active" value="1" class="rounded text-slate-900 border-gray-300 focus:ring-slate-800">
                            <span class="text-sm font-medium text-slate-700">Ruangan Aktif (Dapat dipinjam)</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 mt-6">
                        <button type="button" id="edit-room-modal-cancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit" class="rounded-lg bg-amber-800 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-900 shadow transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    function initPicSelect(container) {
        const toggleBtn = container.querySelector('[data-pic-dropdown-toggle]');
        const arrow = container.querySelector('[data-pic-arrow]');
        const menu = container.querySelector('[data-pic-dropdown-menu]');
        const summary = container.querySelector('[data-pic-summary]');
        const radios = Array.from(container.querySelectorAll('input[type="radio"]'));
        const idInput = container.querySelector('[data-pic-id-input]');
        const filterInput = container.querySelector('[data-pic-filter]');
        const picItems = container.querySelectorAll('[data-pic-item]');
        const form = container.closest('form');

        function updateDisplay() {
            const selected = radios.find(r => r.checked);
            if (selected) {
                summary.innerHTML = `<span class="text-slate-900 font-semibold">${selected.dataset.picName}</span> <span class="text-slate-400 text-xs">(${selected.dataset.picEmail})</span>`;
                if (idInput) idInput.value = selected.value;
            } else if (idInput && idInput.value) {
                const match = radios.find(r => String(r.value) === String(idInput.value));
                if (match) {
                    match.checked = true;
                    summary.innerHTML = `<span class="text-slate-900 font-semibold">${match.dataset.picName}</span> <span class="text-slate-400 text-xs">(${match.dataset.picEmail})</span>`;
                } else {
                    summary.textContent = 'Pilih PIC penanggung jawab...';
                    summary.className = 'truncate text-slate-400 font-medium';
                }
            } else {
                summary.textContent = 'Pilih PIC penanggung jawab...';
                summary.className = 'truncate text-slate-400 font-medium';
                if (idInput) idInput.value = '';
            }
        }

        toggleBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = menu.classList.contains('hidden');
            document.querySelectorAll('[data-pic-dropdown-menu]').forEach(m => {
                if (m !== menu) {
                    m.classList.add('hidden');
                    m.closest('[data-pic-select]')?.querySelector('[data-pic-arrow]')?.classList.remove('rotate-180');
                }
            });
            menu.classList.toggle('hidden', !isHidden);
            arrow?.classList.toggle('rotate-180', isHidden);
            if (isHidden && filterInput) {
                setTimeout(() => filterInput.focus(), 50);
            }
        });

        filterInput?.addEventListener('input', () => {
            const query = (filterInput.value || '').toLowerCase().trim();
            picItems.forEach(item => {
                const text = item.dataset.searchText || '';
                item.classList.toggle('hidden', query !== '' && !text.includes(query));
            });
        });

        filterInput?.addEventListener('click', (e) => e.stopPropagation());

        radios.forEach(radio => {
            radio.addEventListener('change', () => {
                updateDisplay();
                menu.classList.add('hidden');
                arrow?.classList.remove('rotate-180');
            });
        });

        form?.addEventListener('submit', (e) => {
            if (!idInput || !idInput.value) {
                e.preventDefault();
                e.stopImmediatePropagation();
                e.stopPropagation();
                alert('Pilih penanggung jawab (PIC) untuk ruangan ini.');
                toggleBtn?.focus();
                menu.classList.remove('hidden');
                arrow?.classList.add('rotate-180');
            }
        });

        container._setSelectedPic = function(picId) {
            const idStr = String(picId || '');
            radios.forEach(r => {
                r.checked = (String(r.value) === idStr);
            });
            if (idInput) idInput.value = idStr;
            updateDisplay();
        };

        container._resetPic = function() {
            radios.forEach(r => { r.checked = false; });
            if (idInput) idInput.value = '';
            updateDisplay();
        };

        updateDisplay();
        container._updatePicDisplay = updateDisplay;
    }

    document.querySelectorAll('[data-pic-select]').forEach(initPicSelect);

    document.addEventListener('click', (e) => {
        document.querySelectorAll('[data-pic-select]').forEach(container => {
            if (!container.contains(e.target)) {
                container.querySelector('[data-pic-dropdown-menu]')?.classList.add('hidden');
                container.querySelector('[data-pic-arrow]')?.classList.remove('rotate-180');
            }
        });
    });

    // Edit Room Modal logic
    const editModal = document.getElementById('edit-room-modal');
    const editForm = document.getElementById('edit-room-form');
    const modalRoomName = document.getElementById('modal-room-name');
    const modalCapacity = document.getElementById('modal-capacity');
    const modalIsActive = document.getElementById('modal-is-active');
    const modalPicContainer = document.getElementById('modal-pic-select');
    const closeModalBtn = document.getElementById('edit-room-modal-close');
    const cancelModalBtn = document.getElementById('edit-room-modal-cancel');
    const backdrop = document.getElementById('edit-room-modal-backdrop');

    function openEditModal(btn) {
        const roomId = btn.dataset.roomId;
        const roomName = btn.dataset.roomName;
        const capacity = btn.dataset.capacity;
        const isActive = btn.dataset.isActive === '1';
        const updateUrl = btn.dataset.updateUrl;
        let picId = btn.dataset.picId;
        if (!picId) {
            try {
                const arr = JSON.parse(btn.dataset.picIds || '[]');
                if (arr.length > 0) picId = arr[0];
            } catch (e) {}
        }

        editForm.action = updateUrl;
        editForm.dataset.confirmItem = 'Ruang: ' + roomName;
        modalRoomName.value = roomName;
        modalCapacity.value = capacity;
        modalIsActive.checked = isActive;

        if (modalPicContainer && typeof modalPicContainer._setSelectedPic === 'function') {
            modalPicContainer._setSelectedPic(picId);
        }

        editModal.classList.remove('hidden');
        editModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => modalRoomName.focus(), 50);
    }

    function closeEditModal() {
        editModal.classList.add('hidden');
        editModal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        modalPicContainer?.querySelector('[data-pic-dropdown-menu]')?.classList.add('hidden');
        modalPicContainer?.querySelector('[data-pic-arrow]')?.classList.remove('rotate-180');
    }

    document.querySelectorAll('[data-open-edit-room]').forEach(btn => {
        btn.addEventListener('click', () => openEditModal(btn));
    });

    closeModalBtn?.addEventListener('click', closeEditModal);
    cancelModalBtn?.addEventListener('click', closeEditModal);
    backdrop?.addEventListener('click', closeEditModal);

    // Create Room Modal logic
    const createModal = document.getElementById('create-room-modal');
    const openCreateBtn = document.getElementById('open-create-room-btn');
    const closeCreateBtn = document.getElementById('create-room-modal-close');
    const cancelCreateBtn = document.getElementById('create-room-modal-cancel');
    const createBackdrop = document.getElementById('create-room-modal-backdrop');
    const createRoomNameInput = document.getElementById('create_room_name');

    function openCreateModal() {
        createModal.classList.remove('hidden');
        createModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => createRoomNameInput?.focus(), 50);
    }

    function closeCreateModal() {
        createModal.classList.add('hidden');
        createModal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    openCreateBtn?.addEventListener('click', openCreateModal);
    closeCreateBtn?.addEventListener('click', closeCreateModal);
    cancelCreateBtn?.addEventListener('click', closeCreateModal);
    createBackdrop?.addEventListener('click', closeCreateModal);

    // Auto-open create modal if query param create=1 or validation error on store
    @if ($errors->any() && old('room_name') && !old('_method'))
        openCreateModal();
    @endif
    if (new URLSearchParams(window.location.search).get('create') === '1') {
        openCreateModal();
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const confirmationDialog = document.getElementById('action-confirmation');
            if (confirmationDialog && confirmationDialog.open) return;

            if (createModal && !createModal.classList.contains('hidden')) {
                closeCreateModal();
            } else if (editModal && !editModal.classList.contains('hidden')) {
                closeEditModal();
            }
        }
    });
</script>
