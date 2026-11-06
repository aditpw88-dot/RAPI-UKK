<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm text-slate-500">Administrasi</p>
                <h2 class="mt-1 text-2xl font-semibold text-slate-900">Manajemen Kerusakan</h2>
                <p class="mt-1 text-sm text-slate-500">Kelola laporan kerusakan dari seluruh ruang sekolah.</p>
            </div>
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

            <form method="GET" action="{{ route('admin.complaints') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-[1fr_16rem_12rem_auto_auto]">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari laporan, ruang, pelapor, penangan" aria-label="Cari laporan, ruang, pelapor, atau penangan" class="rounded-md border-gray-300 text-sm">
                <select name="status" aria-label="Filter status perbaikan" class="rounded-md border-gray-300 text-sm">
                    <option value="">Semua status</option>
                    <option value="Belum Diperbaiki" @selected(request('status') === 'Belum Diperbaiki')>Belum Diperbaiki</option>
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
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Ruang</th>
                                <th class="px-5 py-3">Laporan</th>
                                <th class="px-5 py-3">Pelapor</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">PIC Penanggung Jawab</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($complaints as $complaint)
                                <tr class="align-top">
                                    <td class="min-w-56 px-5 py-4">
                                        <p class="text-base font-semibold text-slate-900">{{ $complaint->room?->room_name ?? '-' }}</p>
                                    </td>
                                    <td class="max-w-sm px-5 py-4 text-base text-slate-600">
                                        <p>{{ $complaint->description }}</p>
                                        @if ($complaint->is_escalated)
                                            <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Eskalasi Otomatis</span>
                                        @endif
                                        @if ($complaint->photo_path)
                                            <a href="{{ asset('storage/' . ltrim($complaint->photo_path, '/')) }}" target="_blank" class="mt-2 inline-block text-xs font-semibold text-slate-700 hover:underline">Lihat foto</a>
                                        @endif
                                    </td>
                                    <td class="min-w-56 px-5 py-4 text-base text-slate-600">
                                        @if ($complaint->reporter)
                                            <p class="font-semibold text-slate-900">{{ $complaint->reporter->fullname }}</p>
                                            <p class="mt-1 text-sm">Username: {{ $complaint->reporter->username }}</p>
                                            <p class="text-sm">Peran: {{ $complaint->reporter->role?->role_name ?? '-' }}</p>
                                            <p class="text-sm">Email: {{ $complaint->reporter->email }}</p>
                                            <p class="text-sm">Telepon: {{ $complaint->reporter->phone_number ?: '-' }}</p>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-base text-slate-600">
                                        {{ $complaint->status_repair }}
                                    </td>
                                    <td class="min-w-64 px-5 py-4 text-base text-slate-600">
                                        @php($complaintHandler = $complaint->handler ?? $complaint->room?->pic)
                                        <p class="mb-2 text-sm">PIC saat ini: {{ $complaintHandler?->fullname ?? 'Belum ditentukan' }}</p>
                                        @if ($complaint->status_repair !== 'Sudah Diperbaiki' && $complaintHandler)
                                            <button
                                                type="button"
                                                data-open-complaint-reminder
                                                data-reminder-action="{{ route('admin.complaints.remind', $complaint) }}"
                                                data-reminder-item="Laporan #{{ $complaint->id }} - {{ $complaint->room?->room_name ?? 'Ruang tidak diketahui' }}"
                                                data-reminder-recipient="{{ $complaintHandler->fullname }}"
                                                class="mb-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100"
                                            >
                                                Tegur PIC
                                            </button>
                                        @elseif ($complaint->status_repair !== 'Sudah Diperbaiki')
                                            <span class="mb-2 block text-sm text-amber-700">Tentukan PIC penanggung jawab sebelum mengirim teguran.</span>
                                        @endif
                                        @if ($complaint->is_escalated && $complaint->status_repair === 'Belum Diperbaiki')
                                            <form method="POST" action="{{ route('admin.complaints.reassign', $complaint) }}" class="grid gap-2" data-confirm-action="mengalihkan penanganan laporan kerusakan" data-confirm-item="Laporan #{{ $complaint->id }} · {{ $complaint->room?->room_name ?? 'Ruang tidak diketahui' }}">
                                                @csrf
                                                @method('PATCH')
                                                <select name="handler_id" class="rounded-md border-gray-300 text-xs" required>
                                                    <option value="">Pilih PIC pengganti</option>
                                                    @foreach ($handlers as $handler)
                                                        @if ($handler->id !== ($complaint->handler_id ?? $complaint->room?->pic_id))
                                                            <option value="{{ $handler->id }}">{{ $handler->fullname }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700">Alihkan ke PIC</button>
                                            </form>
                                        @endif
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
</script>
