<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">Panel PIC Ruang</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Persetujuan Peminjaman Ruangan</h2>
            <p class="mt-1 text-sm text-slate-500">Periksa dan proses pengajuan peminjaman yang masuk untuk ruang tanggung jawab Anda.</p>
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

            <!-- Section: Permintaan Pembatalan Agenda Guru -->
            <section class="overflow-hidden rounded-xl border {{ $cancellations->isNotEmpty() ? 'border-purple-200 ring-1 ring-purple-200' : 'border-gray-200' }} bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-purple-50/50 px-6 py-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-2.5 w-2.5 rounded-full {{ $cancellations->isNotEmpty() ? 'bg-purple-600 animate-pulse' : 'bg-slate-300' }}"></span>
                            <h3 class="font-semibold text-slate-900">Permintaan Pembatalan Agenda dari Guru</h3>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Guru mengajukan pembatalan agenda dan memerlukan konfirmasi dari Anda atau Admin.</p>
                    </div>
                    <span class="rounded-full bg-purple-100 px-3 py-1 text-sm font-semibold text-purple-800">{{ $cancellations->count() }} permintaan</span>
                </div>

                @if ($cancellations->isEmpty())
                    <div class="px-6 py-8 text-center">
                        <p class="text-sm text-slate-500">Tidak ada permintaan pembatalan agenda yang sedang menunggu.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($cancellations as $event)
                            <article class="grid gap-6 px-6 py-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-lg font-semibold text-slate-900">{{ $event->event_name }}</h4>
                                        <span class="rounded-full bg-purple-100 border border-purple-200 px-2.5 py-0.5 text-xs font-semibold text-purple-800">
                                            Pengajuan Batal
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-600">Diajukan oleh: <span class="font-semibold text-slate-800">{{ $event->pic?->fullname ?? 'Guru' }}</span></p>

                                    <!-- Alasan Pembatalan dari Guru -->
                                    <div class="rounded-xl border border-purple-200 bg-purple-50/60 p-3.5 text-sm">
                                        <p class="text-xs font-bold uppercase tracking-wider text-purple-900 flex items-center gap-1.5">
                                            <svg class="h-4 w-4 text-purple-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Alasan Pembatalan Guru:</span>
                                        </p>
                                        <p class="mt-1 font-medium text-purple-950 leading-relaxed italic">"{{ $event->cancellation_reason }}"</p>
                                        <p class="mt-1 text-[11px] text-purple-700/80">Waktu pengajuan: {{ $event->cancellation_requested_at?->format('d/m/Y H:i') }}</p>
                                    </div>

                                    <dl class="grid gap-3 text-sm sm:grid-cols-3">
                                        <div>
                                            <dt class="text-xs font-medium uppercase text-slate-500">Ruangan</dt>
                                            <dd class="mt-1 font-medium text-slate-800">{{ $event->room_names ?: '-' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium uppercase text-slate-500">Tanggal</dt>
                                            <dd class="mt-1 text-slate-800"><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium uppercase text-slate-500">Waktu</dt>
                                            <dd class="mt-1 text-slate-800"><x-event-time :start-time="$event->start_time" :end-time="$event->end_time" /></dd>
                                        </div>
                                    </dl>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                                    <form method="POST" action="{{ route('pic.events.approve-cancellation', $event) }}" data-confirm-action="menyetujui pembatalan agenda dan membebaskan jadwal ruangan" data-confirm-item="Agenda: {{ $event->event_name }}">
                                        @csrf
                                        <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 shadow-sm transition">
                                            Setujui Batal
                                        </button>
                                    </form>
                                    <button
                                        type="button"
                                        data-open-reject-cancel="reject-cancel-modal-{{ $event->id }}"
                                        class="rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 transition"
                                    >
                                        Tolak Batal
                                    </button>
                                </div>
                            </article>

                            <!-- Modal Penolakan Pembatalan -->
                            <div
                                id="reject-cancel-modal-{{ $event->id }}"
                                data-reject-cancel-panel
                                hidden
                                class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4"
                                role="dialog"
                                aria-modal="true"
                            >
                                <form method="POST" action="{{ route('pic.events.reject-cancellation', $event) }}" class="mx-auto w-full max-w-lg overflow-hidden rounded-xl border border-slate-300 bg-white text-slate-900 shadow-2xl">
                                    @csrf
                                    <div class="flex items-center justify-between border-b border-slate-300 bg-slate-50 px-5 py-4">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-600">Konfirmasi Penolakan Batal</p>
                                            <h3 class="mt-1 text-base font-semibold">Tolak Pembatalan: {{ $event->event_name }}</h3>
                                        </div>
                                        <button type="button" data-close-reject-cancel class="rounded-md p-2 text-xl leading-none text-slate-500 hover:bg-slate-200">&times;</button>
                                    </div>
                                    <div class="space-y-3 p-5">
                                        <label for="reject-cancel-reason-{{ $event->id }}" class="block text-sm font-semibold">Alasan Penolakan Pembatalan <span class="text-red-600">*</span></label>
                                        <textarea id="reject-cancel-reason-{{ $event->id }}" name="cancellation_rejected_reason" rows="4" maxlength="1000" required class="w-full resize-y rounded-md border-slate-300 bg-white text-sm leading-6 focus:border-slate-700 focus:ring-slate-700" placeholder="Tuliskan alasan mengapa permintaan pembatalan ini ditolak..."></textarea>
                                        <p class="text-xs text-slate-500">Alasan ini akan disampaikan kepada guru pengaju.</p>
                                    </div>
                                    <div class="flex justify-end gap-2 border-t border-slate-300 bg-slate-50 px-5 py-4">
                                        <button type="button" data-close-reject-cancel class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Batal</button>
                                        <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Tolak Permintaan Batal</button>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 class="font-semibold text-slate-900">Pengajuan Menunggu Persetujuan</h3>
                        <p class="mt-1 text-sm text-slate-500">Hanya pengajuan untuk ruang yang ditugaskan kepada Anda yang ditampilkan.</p>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">{{ $events->count() }} menunggu</span>
                </div>

                @if ($events->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="font-medium text-slate-900">Tidak ada pengajuan yang menunggu.</p>
                        <p class="mt-1 text-sm text-slate-500">Pengajuan baru dari Guru akan muncul di halaman ini.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($events as $event)
                            <article class="grid gap-6 px-6 py-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                <div class="space-y-3">
                                    <div>
                                        <h4 class="text-lg font-semibold text-slate-900">{{ $event->event_name }}</h4>
                                        <p class="mt-1 text-sm text-slate-600">Diajukan oleh {{ $event->pic?->fullname ?? 'Guru' }}</p>
                                    </div>
                                    <dl class="grid gap-3 text-sm sm:grid-cols-3">
                                        <div>
                                            <dt class="text-xs font-medium uppercase text-slate-500">Ruangan</dt>
                                            <dd class="mt-1 font-medium text-slate-800">{{ $event->room_names ?: '-' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium uppercase text-slate-500">Tanggal</dt>
                                            <dd class="mt-1 text-slate-800"><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium uppercase text-slate-500">Waktu</dt>
                                            <dd class="mt-1 text-slate-800"><x-event-time :start-time="$event->start_time" :end-time="$event->end_time" /></dd>
                                        </div>
                                    </dl>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                                    <form method="POST" action="{{ route('pic.events.approve', $event) }}" data-confirm-action="menyetujui agenda sehingga statusnya menjadi sedang terlaksana" data-confirm-item="Agenda: {{ $event->event_name }}">
                                        @csrf
                                        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                                            Setujui
                                        </button>
                                    </form>
                                    <button
                                        type="button"
                                        data-open-rejection-note="rejection-note-{{ $event->id }}"
                                        class="rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"
                                    >
                                        Tolak
                                    </button>
                                </div>
                            </article>
                            <div
                                id="rejection-note-{{ $event->id }}"
                                data-rejection-note-panel
                                hidden
                                class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4"
                                role="dialog"
                                aria-modal="true"
                                aria-labelledby="rejection-note-title-{{ $event->id }}"
                            >
                                <form method="POST" action="{{ route('pic.events.reject', $event) }}" class="mx-auto w-full max-w-xl overflow-hidden rounded-xl border border-slate-300 bg-white text-slate-900 shadow-2xl" data-confirm-action="menolak agenda" data-confirm-item="Agenda: {{ $event->event_name }}">
                                    @csrf
                                    <div class="flex items-center justify-between border-b border-slate-300 bg-slate-50 px-5 py-4">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-600">Notepad Penolakan</p>
                                            <h3 id="rejection-note-title-{{ $event->id }}" class="mt-1 text-lg font-semibold">Alasan penolakan: {{ $event->event_name }}</h3>
                                        </div>
                                        <button type="button" data-close-rejection-note class="rounded-md p-2 text-xl leading-none text-slate-500 hover:bg-slate-200" aria-label="Tutup notepad">&times;</button>
                                    </div>
                                    <div class="space-y-3 p-5">
                                        <label for="rejection-reason-{{ $event->id }}" class="block text-sm font-semibold">Alasan penolakan</label>
                                        <textarea id="rejection-reason-{{ $event->id }}" name="rejection_reason" rows="8" maxlength="1000" required class="w-full resize-y rounded-md border-slate-300 bg-white text-sm leading-6 focus:border-slate-700 focus:ring-slate-700" placeholder="Tuliskan alasan penolakan agenda..."></textarea>
                                        <p class="text-xs text-slate-500">Alasan ini akan ditampilkan kepada pengaju agenda.</p>
                                    </div>
                                    <div class="flex justify-end gap-2 border-t border-slate-300 bg-slate-50 px-5 py-4">
                                        <button type="button" data-close-rejection-note class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Batal</button>
                                        <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Simpan Penolakan</button>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>

<script>
    document.querySelectorAll('[data-open-rejection-note]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = document.getElementById(button.dataset.openRejectionNote);
            const textarea = panel?.querySelector('textarea');

            if (panel instanceof HTMLElement && textarea instanceof HTMLTextAreaElement) {
                panel.hidden = false;
                panel.classList.add('flex');
                textarea.focus();
            }
        });
    });

    document.querySelectorAll('[data-close-rejection-note]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = button.closest('[data-rejection-note-panel]');

            if (panel instanceof HTMLElement) {
                panel.hidden = true;
                panel.classList.remove('flex');
            }
        });
    });
    document.querySelectorAll('[data-open-reject-cancel]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = document.getElementById(button.dataset.openRejectCancel);
            const textarea = panel?.querySelector('textarea');

            if (panel instanceof HTMLElement) {
                panel.hidden = false;
                panel.classList.add('flex');
                textarea?.focus();
            }
        });
    });

    document.querySelectorAll('[data-close-reject-cancel]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = button.closest('[data-reject-cancel-panel]');

            if (panel instanceof HTMLElement) {
                panel.hidden = true;
                panel.classList.remove('flex');
            }
        });
    });
</script>
