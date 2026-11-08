<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-slate-500">Administrasi</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Manajemen Kerusakan</h2>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($escalationNotifications->isNotEmpty())
                <section class="rounded-xl border border-amber-200 bg-amber-50 p-4" role="alert">
                    <h3 class="font-semibold text-amber-900">Notifikasi eskalasi ({{ $escalationNotifications->count() }})</h3>
                    <ul class="mt-2 space-y-1 text-sm text-amber-800">
                        @foreach ($escalationNotifications as $notification)
                            <li>
                                Laporan #{{ $notification->data['complaint_id'] }} di {{ $notification->data['room_name'] }} belum diperbaiki melewati batas waktu.
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-sm text-amber-800">Pilih PIC pengganti pada laporan terkait di bawah.</p>
                </section>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Daftar Pengaduan Kerusakan</h3>
                    <p class="text-xs text-slate-500">Kelola dan pantau laporan kerusakan dari seluruh ruang sekolah.</p>
                </div>
                <button type="button" id="open-create-complaint-btn" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
                    <svg width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Buat Pengaduan Kerusakan</span>
                </button>
            </div>

            <form method="GET" action="{{ route('admin.complaints') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-[1fr_16rem_12rem_auto_auto]">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari laporan, ruang, pelapor, penangan" aria-label="Cari laporan, ruang, pelapor, atau penangan" class="rounded-md border-gray-300 text-sm">
                <select name="status" aria-label="Filter status perbaikan" class="rounded-md border-gray-300 text-sm">
                    <option value="">Semua status</option>
                    <option value="Belum Diperbaiki" @selected(request('status') === 'Belum Diperbaiki')>Belum Diperbaiki</option>
                    <option value="Sedang Diperbaiki" @selected(request('status') === 'Sedang Diperbaiki')>Sedang Diperbaiki</option>
                    <option value="Menunggu Spare Part" @selected(request('status') === 'Menunggu Spare Part')>Menunggu Spare Part</option>
                    <option value="Sudah Diperbaiki" @selected(request('status') === 'Sudah Diperbaiki')>Sudah Diperbaiki</option>
                </select>
                <select name="room_type" aria-label="Filter tipe ruangan" class="rounded-md border-gray-300 text-sm">
                    <option value="">Semua tipe ruangan</option>
                    <option value="lab" @selected(request('room_type') === 'lab')>Lab</option>
                    <option value="ruang" @selected(request('room_type') === 'ruang')>Ruang</option>
                </select>
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cari / Filter</button>
                <a href="{{ route('admin.complaints') }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-gray-50">Reset</a>
            </form>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto min-h-[300px]">
                    <table class="w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="w-[14%] px-5 py-3.5 font-bold">Ruang</th>
                                <th scope="col" class="w-[31%] px-5 py-3.5 font-bold">Laporan</th>
                                <th scope="col" class="w-[20%] px-5 py-3.5 font-bold">Pelapor</th>
                                <th scope="col" class="w-[16%] px-5 py-3.5 font-bold">Status</th>
                                <th scope="col" class="w-[19%] px-5 py-3.5 font-bold">PIC Penanggung Jawab</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($complaints as $index => $complaint)
                                <tr class="align-middle hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 py-4 align-middle">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 ring-1 ring-slate-200/60">
                                                <svg width="15" height="15" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-base font-semibold text-slate-900 leading-tight">{{ $complaint->room?->room_name ?? '-' }}</p>
                                                @if ($complaint->room)
                                                    <p class="mt-0.5 text-xs text-slate-400">Kapasitas {{ $complaint->room->capacity }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 align-middle text-slate-700">
                                        <p class="text-sm font-medium leading-relaxed text-slate-800 break-words">{{ $complaint->description }}</p>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            @if ($complaint->is_escalated)
                                                <span class="inline-flex items-center gap-1 rounded-full border border-amber-300 bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-900 shadow-2xs">
                                                    <svg width="11" height="11" style="width:11px;height:11px;" class="shrink-0 text-amber-700" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span>Eskalasi Otomatis</span>
                                                </span>
                                            @endif
                                            @if ($complaint->photo_path)
                                                <a href="{{ asset('storage/' . ltrim($complaint->photo_path, '/')) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-100 hover:text-slate-900 hover:border-slate-300">
                                                    <svg width="13" height="13" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>Lihat foto</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 align-middle text-slate-600">
                                        @if ($complaint->reporter)
                                            <div 
                                                x-data="{ openPopover: false }" 
                                                class="relative inline-block text-left"
                                                @mouseenter="openPopover = true" 
                                                @mouseleave="openPopover = false"
                                            >
                                                <div 
                                                    @click="openPopover = !openPopover"
                                                    class="flex items-center gap-3 cursor-pointer group"
                                                >
                                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700 ring-1 ring-slate-200 transition group-hover:bg-amber-50 group-hover:ring-amber-300">
                                                        {{ strtoupper(substr($complaint->reporter->fullname ?? 'U', 0, 1)) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-1.5">
                                                            <p class="text-sm font-semibold text-slate-900 leading-snug truncate group-hover:text-amber-800 transition">
                                                                {{ $complaint->reporter->fullname }}
                                                            </p>
                                                            <svg width="12" height="12" style="width:12px;height:12px;" class="text-slate-400 group-hover:text-amber-600 transition shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                        </div>
                                                        <div class="mt-1">
                                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                                                {{ $complaint->reporter->role?->role_name ?? '-' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Popover Teks Melayang: Detail Lengkap Pelapor -->
                                                <div 
                                                    x-show="openPopover" 
                                                    x-cloak
                                                    x-transition:enter="transition ease-out duration-150" 
                                                    x-transition:enter-start="opacity-0 translate-y-1 scale-95" 
                                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
                                                    x-transition:leave="transition ease-in duration-100" 
                                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
                                                    x-transition:leave-end="opacity-0 translate-y-1 scale-95" 
                                                    :class="({{ $complaints->count() }} <= 2 || {{ $index }} < 3) ? 'top-full mt-2' : 'bottom-full mb-2'"
                                                    class="absolute left-0 z-40 w-72 rounded-xl border border-slate-200 bg-white p-3.5 shadow-xl ring-1 ring-black/5"
                                                    style="display: none;"
                                                >
                                                    <div class="flex items-center gap-2.5 pb-2.5 border-b border-slate-100">
                                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-50 text-xs font-bold text-amber-800 ring-1 ring-amber-200">
                                                            {{ strtoupper(substr($complaint->reporter->fullname ?? 'U', 0, 1)) }}
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="text-xs font-bold text-slate-900 truncate">{{ $complaint->reporter->fullname }}</p>
                                                            <p class="text-[11px] text-slate-500">Detail Lengkap Pelapor</p>
                                                        </div>
                                                    </div>

                                                    <div class="pt-2.5 space-y-1.5 text-xs text-slate-600">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-slate-400 font-medium">Username:</span>
                                                            <span class="font-mono text-slate-800 truncate font-medium">{{ $complaint->reporter->username }}</span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-slate-400 font-medium">Peran:</span>
                                                            <span class="font-medium text-slate-800 truncate">{{ $complaint->reporter->role?->role_name ?? '-' }}</span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-slate-400 font-medium">Email:</span>
                                                            <span class="font-mono text-slate-800 truncate">{{ $complaint->reporter->email }}</span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-slate-400 font-medium">Telepon:</span>
                                                            <span class="font-medium text-slate-800">{{ $complaint->reporter->phone_number ?: '-' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-xs italic text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 align-middle whitespace-nowrap">
                                        @if ($complaint->status_repair === 'Sudah Diperbaiki')
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 shadow-2xs">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                <span>{{ $complaint->status_repair }}</span>
                                            </span>
                                        @elseif ($complaint->status_repair === 'Sedang Diperbaiki')
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800 shadow-2xs">
                                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                                <span>{{ $complaint->status_repair }}</span>
                                            </span>
                                        @elseif ($complaint->status_repair === 'Menunggu Spare Part')
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-900 shadow-2xs">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                <span>{{ $complaint->status_repair }}</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 shadow-2xs">
                                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                <span>{{ $complaint->status_repair }}</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 align-middle text-slate-600">
                                        @php($complaintHandler = $complaint->handler ?? $complaint->room?->pic)
                                        <div class="space-y-2">
                                            <div>
                                                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">PIC saat ini:</p>
                                                <p class="text-sm font-semibold text-slate-900 leading-snug">
                                                    {{ $complaintHandler?->fullname ?? 'Belum ditentukan' }}
                                                </p>
                                            </div>
                                            @if ($complaint->status_repair !== 'Sudah Diperbaiki' && $complaintHandler)
                                                <button
                                                    type="button"
                                                    data-open-complaint-reminder
                                                    data-reminder-action="{{ route('admin.complaints.remind', $complaint) }}"
                                                    data-reminder-item="Laporan #{{ $complaint->id }} - {{ $complaint->room?->room_name ?? 'Ruang tidak diketahui' }}"
                                                    data-reminder-recipient="{{ $complaintHandler->fullname }}"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-900 shadow-2xs transition hover:bg-amber-100 hover:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                                                >
                                                    <svg width="13" height="13" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <span>Tegur PIC</span>
                                                </button>
                                            @elseif ($complaint->status_repair !== 'Sudah Diperbaiki')
                                                <span class="block text-xs font-medium text-amber-700">Tentukan PIC penanggung jawab sebelum mengirim teguran.</span>
                                            @endif
                                            @if ($complaint->is_escalated && $complaint->status_repair === 'Belum Diperbaiki')
                                                <form method="POST" action="{{ route('admin.complaints.reassign', $complaint) }}" class="mt-2 space-y-1.5" data-confirm-action="mengalihkan penanganan laporan kerusakan" data-confirm-item="Laporan #{{ $complaint->id }} · {{ $complaint->room?->room_name ?? 'Ruang tidak diketahui' }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="handler_id" class="w-full rounded-md border-gray-300 text-xs py-1.5" required>
                                                        <option value="">Pilih PIC pengganti</option>
                                                        @foreach ($handlers as $handler)
                                                            @if ($handler->id !== ($complaint->handler_id ?? $complaint->room?->pic_id))
                                                                <option value="{{ $handler->id }}">{{ $handler->fullname }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="w-full rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-slate-700 transition">Alihkan ke PIC</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada laporan kerusakan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $complaints->links() }}
                </div>
            </section>

            <dialog id="complaint-reminder-dialog" class="w-[min(92vw,36rem)] rounded-xl border border-slate-300 bg-transparent p-0 shadow-2xl backdrop:bg-slate-950/50">
                <form id="complaint-reminder-form" method="POST" class="overflow-hidden rounded-xl bg-white text-slate-900" data-confirm-action="menyiapkan teguran melalui WhatsApp" data-confirm-item="Laporan yang dipilih">
                    @csrf
                    <header class="flex items-center justify-between border-b border-slate-300 bg-amber-50 px-5 py-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-amber-800">Teguran Admin</p>
                            <h3 class="mt-1 text-lg font-semibold">Minta PIC segera memperbaiki</h3>
                            <p id="complaint-reminder-item" class="mt-1 text-sm text-slate-600"></p>
                        </div>
                        <button type="button" data-close-complaint-reminder class="rounded-md p-2 text-xl leading-none text-slate-500 hover:bg-amber-100" aria-label="Tutup notepad">&times;</button>
                    </header>
                    <div class="space-y-3 p-5">
                        <p class="text-sm text-slate-600">Pesan akan disiapkan di WhatsApp untuk <span id="complaint-reminder-recipient" class="font-semibold"></span>. Tekan tombol kirim di WhatsApp untuk mengirim teguran.</p>
                        <label for="complaint-reminder-message" class="block text-sm font-semibold">Pesan teguran</label>
                        <textarea id="complaint-reminder-message" name="message" rows="7" maxlength="2000" required class="w-full resize-y rounded-md border-slate-300 text-sm leading-6 focus:border-slate-700 focus:ring-slate-700" placeholder="Contoh: Mohon segera periksa dan perbaiki kerusakan ini. Harap perbarui status setelah ditangani."></textarea>
                    </div>
                    <footer class="flex justify-end gap-2 border-t border-slate-300 bg-slate-50 px-5 py-4">
                        <button type="button" data-close-complaint-reminder class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Lanjutkan ke WhatsApp</button>
                    </footer>
                </form>
            </dialog>
        </div>

        <!-- Modal Window Buat Pengaduan Kerusakan (Backdrop Blur) -->
        <div id="create-complaint-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="create-complaint-modal-title">
            <div id="create-complaint-modal-backdrop" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity duration-200" style="backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"></div>

            <div class="relative z-10 w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all my-auto max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                    <div>
                        <span class="inline-block rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 uppercase tracking-wider">Laporan Baru</span>
                        <h3 id="create-complaint-modal-title" class="mt-1 text-lg font-bold text-slate-900">Buat Pengaduan Kerusakan</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Laporkan kerusakan fasilitas atau sarana prasarana ruangan sekolah.</p>
                    </div>
                    <button type="button" id="create-complaint-modal-close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition focus:outline-none" aria-label="Tutup modal">
                        <svg width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="create-complaint-form" method="POST" action="{{ route('admin.complaints.store') }}" enctype="multipart/form-data" class="space-y-4 pt-4" data-confirm-action="mengirim laporan kerusakan" data-confirm-item="Laporan kerusakan baru" data-confirm-title="Konfirmasi Pengaduan Kerusakan" data-confirm-message="Apakah Anda yakin ingin mengirim laporan pengaduan kerusakan ini? Pastikan detail ruangan dan kerusakan sudah sesuai.">
                    @csrf

                    <div>
                        <label for="complaint_room_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Pilih Ruangan <span class="text-red-500">*</span></label>
                        <select id="complaint_room_id" name="room_id" required class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                            <option value="">Pilih ruangan yang bermasalah</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id', request('room_id')) == $room->id)>
                                    {{ $room->room_name }} (Kapasitas {{ $room->capacity }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="complaint_description" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Deskripsi Kerusakan <span class="text-red-500">*</span></label>
                        <textarea id="complaint_description" name="description" rows="4" required placeholder="Jelaskan secara detail kerusakan yang ditemukan (contoh: AC tidak dingin, proyektor mati, kabel putus)..." class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label for="complaint_photo" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Foto Bukti Kerusakan <span class="text-red-500">*</span></label>
                        <input id="complaint_photo" name="photo" type="file" accept="image/*" required class="w-full rounded-lg border border-gray-300 text-xs file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                        <p class="mt-1 text-[11px] text-slate-500">Pilih file gambar (JPG, PNG, JPEG, WEBP), maksimal 5MB.</p>
                        <div id="complaint-photo-preview-container" class="mt-2 hidden">
                            <img id="complaint-photo-preview" src="#" alt="Pratinjau Foto Kerusakan" class="h-40 w-full rounded-lg border border-slate-200 object-cover">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-2 border-t border-gray-100">
                        <button type="button" id="create-complaint-cancel-btn" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 transition">
                            <svg width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <span>Kirim Pengaduan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    const complaintReminderDialog = document.getElementById('complaint-reminder-dialog');
    const complaintReminderForm = document.getElementById('complaint-reminder-form');

    document.querySelectorAll('[data-open-complaint-reminder]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!(complaintReminderDialog instanceof HTMLDialogElement) || !(complaintReminderForm instanceof HTMLFormElement)) {
                return;
            }

            complaintReminderForm.action = button.dataset.reminderAction;
            complaintReminderForm.dataset.confirmItem = button.dataset.reminderItem;
            document.getElementById('complaint-reminder-item').textContent = button.dataset.reminderItem;
            document.getElementById('complaint-reminder-recipient').textContent = button.dataset.reminderRecipient;
            complaintReminderDialog.showModal();
            document.getElementById('complaint-reminder-message').focus();
        });
    });

    document.querySelectorAll('[data-close-complaint-reminder]').forEach((button) => {
        button.addEventListener('click', () => complaintReminderDialog?.close());
    });

    // Modal Buat Pengaduan Kerusakan
    const createComplaintModal = document.getElementById('create-complaint-modal');
    const openCreateComplaintBtn = document.getElementById('open-create-complaint-btn');
    const closeCreateComplaintBtn = document.getElementById('create-complaint-modal-close');
    const cancelCreateComplaintBtn = document.getElementById('create-complaint-cancel-btn');
    const createComplaintBackdrop = document.getElementById('create-complaint-modal-backdrop');
    const complaintPhotoInput = document.getElementById('complaint_photo');
    const complaintPhotoPreviewContainer = document.getElementById('complaint-photo-preview-container');
    const complaintPhotoPreview = document.getElementById('complaint-photo-preview');

    function openCreateComplaintModal() {
        if (!createComplaintModal) return;
        createComplaintModal.classList.remove('hidden');
        createComplaintModal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeCreateComplaintModal() {
        if (!createComplaintModal) return;
        createComplaintModal.classList.add('hidden');
        createComplaintModal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    openCreateComplaintBtn?.addEventListener('click', openCreateComplaintModal);
    closeCreateComplaintBtn?.addEventListener('click', closeCreateComplaintModal);
    cancelCreateComplaintBtn?.addEventListener('click', closeCreateComplaintModal);
    createComplaintBackdrop?.addEventListener('click', closeCreateComplaintModal);

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && createComplaintModal && !createComplaintModal.classList.contains('hidden')) {
            closeCreateComplaintModal();
        }
    });

    complaintPhotoInput?.addEventListener('change', function() {
        const file = this.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (complaintPhotoPreview && complaintPhotoPreviewContainer) {
                    complaintPhotoPreview.src = e.target.result;
                    complaintPhotoPreviewContainer.classList.remove('hidden');
                }
            };
            reader.readAsDataURL(file);
        } else {
            if (complaintPhotoPreviewContainer) {
                complaintPhotoPreviewContainer.classList.add('hidden');
            }
        }
    });

    @if ($errors->any() && (old('room_id') || old('description')))
        openCreateComplaintModal();
    @endif

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('create') === '1') {
        openCreateComplaintModal();
    }
</script>
