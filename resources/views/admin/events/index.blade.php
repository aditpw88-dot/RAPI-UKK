<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-slate-500">Administrasi</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Manajemen Agenda</h2>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ session('error') }}</div>
            @endif
            @if (session('warning'))
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">{{ session('warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('admin.events.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">+ Tambah Agenda</a>
            </div>

            <form method="GET" action="{{ route('admin.events') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari agenda, ruang, pengaju" aria-label="Cari agenda, ruang, atau pengaju" class="rounded-md border-gray-300 text-sm lg:col-span-2">
                <select name="status" aria-label="Filter status agenda" class="rounded-md border-gray-300 text-sm">
                    <option value="">Semua status</option>
                    <option value="pending" @selected(request('status') === 'pending')>Menunggu</option>
                    <option value="in_progress" @selected(in_array(request('status'), ['in_progress', 'approved'], true))>Sedang Terlaksana</option>
                    <option value="completed" @selected(request('status') === 'completed')>Sudah Terlaksana</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Tanggal mulai" class="rounded-md border-gray-300 text-sm">
                <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Tanggal akhir" class="rounded-md border-gray-300 text-sm">
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Terapkan</button>
                    <a href="{{ route('admin.events') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Agenda</th>
                                <th class="px-5 py-3">Ruang</th>
                                <th class="px-5 py-3">Pengaju</th>
                                <th class="px-5 py-3">Jadwal</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($events as $event)
                                <tr>
                                    <td class="px-5 py-4 font-medium">{{ $event->event_name }}</td>
                                    <td class="px-5 py-4">{{ $event->room_names ?: '-' }}</td>
                                    <td class="px-5 py-4">{{ $event->pic?->fullname ?? '-' }}</td>
                                    <td class="min-w-40 px-5 py-4">
                                        <p class="mb-1 whitespace-nowrap font-medium text-slate-900"><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></p>
                                        <x-event-time :start-time="$event->start_time" :end-time="$event->end_time" compact />
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($event->status === 'pending')
                                            <span class="text-amber-700">Menunggu persetujuan Admin/PIC</span>
                                            <form method="POST" action="{{ route('admin.events.approve', $event) }}" class="mt-2" data-confirm-action="menyetujui agenda" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                @csrf
                                                <button type="submit" class="event-action-primary rounded-md px-3 py-1.5 text-xs font-semibold">ACC Agenda</button>
                                            </form>
                                        @elseif ($event->status === 'approved' && $event->completed_at)
                                            <span class="font-semibold text-blue-800">Sudah Terlaksana</span>
                                            <p class="mt-1 text-xs text-slate-500">Diselesaikan {{ $event->completed_at->format('d/m/Y H:i') }}</p>
                                        @elseif ($event->status === 'approved')
                                            <span class="font-semibold text-amber-800">Sedang Terlaksana</span>
                                            <p class="mt-1 text-xs text-slate-500">{{ $event->approver && (str_contains(strtolower($event->approver->role?->role_name ?? ''), 'admin') || str_contains(strtolower($event->approver->role?->role_name ?? ''), 'waka')) ? ($event->approved_by === $event->pic_id ? 'Disetujui otomatis (Admin)' : 'Disetujui Admin') : 'Disetujui PIC' }}</p>
                                        @else
                                            <span class="text-red-700">Ditolak PIC</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($event->status !== 'rejected')
                                            <a href="{{ route('admin.complaints.create', ['room_id' => $event->room_id]) }}" class="event-action-secondary mb-2 inline-flex items-center rounded-md px-3 py-1.5 text-xs font-semibold">
                                                Buat Pengaduan Kerusakan
                                            </a>
                                            <button type="button" data-open-move-dialog="move-event-{{ $event->id }}" class="event-action-outline mt-2 rounded-md px-3 py-1.5 text-xs font-semibold">
                                                Pindahkan Ruangan
                                            </button>
                                            @if ($event->move_reason)
                                                <p class="mt-2 max-w-48 text-xs text-slate-500">Alasan pemindahan: {{ $event->move_reason }}</p>
                                            @endif
                                            <dialog id="move-event-{{ $event->id }}" class="w-[min(92vw,32rem)] rounded-lg border border-slate-300 bg-transparent p-0 shadow-2xl backdrop:bg-slate-950/50">
                                                <form method="POST" action="{{ route('admin.events.move', $event) }}" class="overflow-hidden rounded-lg bg-white text-slate-900" data-move-form data-move-event-id="{{ $event->id }}" data-confirm-action="memindahkan ruangan untuk agenda" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <header class="border-b border-slate-300 bg-slate-50 px-6 py-4">
                                                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-600">Surat Pemindahan Ruangan</p>
                                                        <h3 class="mt-1 text-lg font-semibold">Pemindahan Agenda</h3>
                                                    </header>
                                                    <div class="space-y-4 px-6 py-5">
                                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                                            <p><span class="block text-xs text-slate-500">Agenda</span>{{ $event->event_name }}</p>
                                                            <p><span class="block text-xs text-slate-500">Tanggal</span><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></p>
                                                            <p><span class="block text-xs text-slate-500">Dari ruangan</span>{{ $event->room_names ?: '-' }}</p>
                                                            <p><span class="block text-xs text-slate-500">Waktu</span>{{ date('H:i', strtotime($event->start_time)) }} - {{ date('H:i', strtotime($event->end_time)) }}</p>
                                                        </div>
                                                        @php($destinationRooms = $rooms->whereNotIn('id', $event->reservedRoomIds()))
                                                        <div>
                                                            <label for="destination-room-{{ $event->id }}" class="block text-sm font-semibold">Ruangan tujuan</label>
                                                            <select id="destination-room-{{ $event->id }}" name="room_id" required @disabled($destinationRooms->isEmpty()) class="mt-1 w-full rounded-md border-slate-300 bg-white text-sm focus:border-slate-700 focus:ring-slate-700">
                                                                <option value="">Pilih ruangan aktif</option>
                                                                @foreach ($destinationRooms as $room)
                                                                    <option value="{{ $room->id }}">{{ $room->room_name }} (Kapasitas {{ $room->capacity }})</option>
                                                                @endforeach
                                                            </select>
                                                            @if ($destinationRooms->isEmpty())
                                                                <p class="mt-1 text-xs text-slate-600">Tidak ada ruangan aktif lain yang dapat dipilih.</p>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <div class="flex items-center justify-between gap-3">
                                                                <span class="block text-sm font-semibold">Catatan alasan pemindahan</span>
                                                                <button type="button" data-open-note-dialog="move-note-{{ $event->id }}" class="event-action-outline rounded-md px-3 py-1.5 text-xs font-semibold">
                                                                    Buka Catatan
                                                                </button>
                                                            </div>
                                                            <input type="hidden" id="move-reason-{{ $event->id }}" name="move_reason" value="{{ $event->move_reason }}">
                                                            <p id="move-reason-help-{{ $event->id }}" class="mt-2 text-xs text-slate-500" aria-live="polite">{{ $event->move_reason ? 'Catatan alasan pemindahan sudah tersimpan.' : 'Belum ada catatan. Tekan “Buka Catatan” untuk menulis alasan pemindahan.' }}</p>
                                                        </div>
                                                    </div>
                                                    <footer class="flex justify-end gap-2 border-t border-slate-300 bg-slate-50 px-6 py-4">
                                                        <button type="button" data-close-move-dialog class="event-action-outline rounded-md px-4 py-2 text-sm font-semibold">Batal</button>
                                                        <button type="submit" @disabled($destinationRooms->isEmpty()) class="event-action-primary rounded-md px-4 py-2 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50">Simpan &amp; Beri Tahu via WhatsApp</button>
                                                    </footer>
                                                </form>
                                            </dialog>
                                            <div id="move-note-{{ $event->id }}" data-note-panel hidden class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="move-note-title-{{ $event->id }}">
                                                <form method="POST" action="{{ route('admin.events.move-reason', $event) }}" class="mx-auto w-full max-w-xl overflow-hidden rounded-xl border border-slate-300 bg-white text-slate-900 shadow-2xl" data-confirm-action="menyimpan catatan alasan pemindahan" data-confirm-item="Agenda: {{ $event->event_name }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="flex items-center justify-between border-b border-slate-300 bg-slate-50 px-5 py-4">
                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-600">Catatan Pemindahan</p>
                                                            <h3 id="move-note-title-{{ $event->id }}" class="mt-1 text-lg font-semibold">Alasan untuk {{ $event->event_name }}</h3>
                                                        </div>
                                                        <button type="button" data-close-note-dialog class="rounded-md p-2 text-xl leading-none text-slate-500 hover:bg-slate-200" aria-label="Tutup catatan">&times;</button>
                                                    </div>
                                                    <div class="space-y-3 p-5">
                                                        <label for="move-note-input-{{ $event->id }}" class="block text-sm font-semibold">Catatan alasan</label>
                                                        <textarea id="move-note-input-{{ $event->id }}" name="move_reason" rows="10" maxlength="2000" required data-note-input-for="move-reason-{{ $event->id }}" placeholder="Tuliskan alasan pemindahan agenda..." class="w-full resize-y rounded-md border-slate-300 bg-white text-sm leading-6 focus:border-slate-700 focus:ring-slate-700">{{ $event->move_reason }}</textarea>
                                                        <p class="text-xs text-slate-500">Catatan akan tersimpan pada agenda ini dan ikut dikirim saat pemindahan dilakukan.</p>
                                                    </div>
                                                    <div class="flex justify-end gap-2 border-t border-slate-300 bg-slate-50 px-5 py-4">
                                                        <button type="button" data-close-note-dialog class="event-action-outline rounded-md px-4 py-2 text-sm font-semibold">Batal</button>
                                                        <button type="submit" class="event-action-primary rounded-md px-4 py-2 text-sm font-semibold">Simpan Catatan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="mt-2" data-confirm-action="menghapus agenda" data-confirm-item="Agenda: {{ $event->event_name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="event-action-danger rounded-md px-3 py-1.5 text-xs font-semibold">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada agenda.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    document.querySelectorAll('[data-open-move-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById(button.dataset.openMoveDialog)?.showModal();
        });
    });

    document.querySelectorAll('[data-close-move-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('dialog')?.close();
        });
    });

    document.querySelectorAll('[data-open-note-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            const noteDialog = document.getElementById(button.dataset.openNoteDialog);
            const noteInput = noteDialog?.querySelector('[data-note-input-for]');
            const reasonField = document.getElementById(noteInput?.dataset.noteInputFor);

            if (noteInput instanceof HTMLTextAreaElement && reasonField instanceof HTMLInputElement) {
                noteInput.value = reasonField.value;
                button.closest('dialog')?.close();
                noteDialog.hidden = false;
                noteDialog.classList.add('flex');
                noteInput.focus();
            }
        });
    });

    document.querySelectorAll('[data-close-note-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            const notePanel = button.closest('[data-note-panel]');

            if (notePanel instanceof HTMLElement) {
                notePanel.hidden = true;
                notePanel.classList.remove('flex');
                document.getElementById(`move-event-${notePanel.id.replace('move-note-', '')}`)?.showModal();
            }
        });
    });

    document.querySelectorAll('[data-move-form]').forEach((moveForm) => {
        moveForm.addEventListener('submit', (event) => {
            const reasonField = moveForm.querySelector('input[name="move_reason"]');

            if (!(reasonField instanceof HTMLInputElement) || !reasonField.value.trim()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                const notePanel = document.getElementById(`move-note-${moveForm.dataset.moveEventId}`);

                if (notePanel instanceof HTMLElement) {
                    moveForm.closest('dialog')?.close();
                    notePanel.hidden = false;
                    notePanel.classList.add('flex');
                    notePanel.querySelector('textarea')?.focus();
                }
            }
        });
    });
</script>
