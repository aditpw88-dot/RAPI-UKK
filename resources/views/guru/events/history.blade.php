<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">Portal Guru/Organisasi</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Riwayat &amp; Status</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau pengajuan agenda dan status persetujuannya.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-4">
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

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800 shadow-2xs" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-900">RIWAYAT PENGAJUAN</h3>
                        <p class="mt-1 text-sm text-slate-500">Daftar pengajuan agenda dan status persetujuan Anda.</p>
                    </div>
                    <a href="{{ route('events.create') }}" class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Ajukan Peminjaman</a>
                </div>
                <form method="GET" action="{{ route('guru.events.history') }}" class="grid gap-3 border-b border-gray-200 bg-gray-50 px-6 py-4 sm:grid-cols-[1fr_14rem_auto_auto]">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari agenda atau ruang" aria-label="Cari agenda atau ruang" class="rounded-md border-gray-300 text-sm">
                    <select name="status" aria-label="Filter status agenda" class="rounded-md border-gray-300 text-sm">
                        <option value="">Semua status</option>
                        <option value="pending" @selected(request('status') === 'pending')>Menunggu Persetujuan</option>
                        <option value="cancellation_pending" @selected(request('status') === 'cancellation_pending')>Menunggu Pembatalan</option>
                        <option value="upcoming" @selected(request('status') === 'upcoming')>Belum Terlaksana</option>
                        <option value="in_progress" @selected(request('status') === 'in_progress')>Sedang Terlaksana</option>
                        <option value="completed" @selected(request('status') === 'completed')>Sudah Terlaksana</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                    </select>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cari / Filter</button>
                    <a href="{{ route('guru.events.history') }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-white">Reset</a>
                </form>
                @if ($events->isEmpty())
                    <p class="px-6 py-10 text-sm text-slate-500">Belum ada pengajuan agenda.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 font-bold">Nama Agenda</th>
                                    <th scope="col" class="px-6 py-3.5 font-bold">Nama Ruangan</th>
                                    <th scope="col" class="px-6 py-3.5 font-bold">Jadwal</th>
                                    <th scope="col" class="px-6 py-3.5 font-bold">Status</th>
                                    <th scope="col" class="px-6 py-3.5 font-bold">Catatan</th>
                                    <th scope="col" class="px-6 py-3.5 font-bold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($events as $index => $event)
                                    <tr class="align-middle hover:bg-slate-50/70 transition-colors">
                                        <td class="px-6 py-3.5 align-middle">
                                            <p class="font-semibold text-slate-900 text-sm sm:text-base leading-snug">{{ $event->event_name }}</p>
                                            @if ($event->event_type)
                                                <span class="mt-0.5 inline-block text-[11px] font-medium text-slate-500 uppercase tracking-wide">
                                                    {{ config("event_types.{$event->event_type}.label") ?? ucfirst($event->event_type) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3.5 align-middle font-medium text-slate-700 text-sm whitespace-nowrap">
                                            {{ $event->room_names ?: '-' }}
                                        </td>
                                        <td class="px-6 py-3.5 align-middle whitespace-nowrap">
                                            @php
                                                $startFormatted = date('H:i', strtotime($event->start_time));
                                                $endFormatted = date('H:i', strtotime($event->end_time));
                                                $sessionList = collect(config('schedule.sessions'));
                                                $startSessionIndex = $sessionList->search(fn ($t) => $t['start_time'] === $startFormatted);
                                                $endSessionIndex = $sessionList->search(fn ($t) => $t['end_time'] === $endFormatted);
                                            @endphp
                                            <div class="inline-flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1.5 font-semibold text-slate-900 text-sm">
                                                    <svg class="h-4 w-4 text-amber-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></span>
                                                </span>

                                                <span class="text-slate-300 font-semibold select-none">|</span>

                                                <div class="inline-flex items-center gap-1.5">
                                                    @if ($startSessionIndex !== false && $endSessionIndex !== false)
                                                        <span class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-900 shadow-2xs">
                                                            Sesi {{ $startSessionIndex }}{{ $startSessionIndex !== $endSessionIndex ? '–' . $endSessionIndex : '' }}
                                                        </span>
                                                    @endif
                                                    <span class="font-mono text-xs font-semibold text-slate-600">
                                                        ({{ $startFormatted }} - {{ $endFormatted }})
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3.5 align-middle whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $event->lifecycle_badge_class }} border {{ str_contains($event->lifecycle_badge_class, 'amber') ? 'border-amber-200' : (str_contains($event->lifecycle_badge_class, 'emerald') ? 'border-emerald-200' : (str_contains($event->lifecycle_badge_class, 'blue') ? 'border-blue-200' : 'border-red-200')) }} shadow-2xs">
                                                <span class="h-1.5 w-1.5 rounded-full {{ str_contains($event->lifecycle_badge_class, 'amber') ? 'bg-amber-500' : (str_contains($event->lifecycle_badge_class, 'emerald') ? 'bg-emerald-500 animate-pulse' : (str_contains($event->lifecycle_badge_class, 'blue') ? 'bg-blue-500' : 'bg-red-500')) }}"></span>
                                                <span>{{ $event->lifecycle_status_label }}</span>
                                            </span>
                                        </td>
                                        <td class="max-w-xs px-6 py-3.5 align-middle text-xs text-slate-500">
                                            @if ($event->cancellation_status === 'pending')
                                                <p class="text-purple-700 font-semibold"><span class="font-bold">Pengajuan Batal:</span> {{ $event->cancellation_reason }}</p>
                                                <p class="text-[11px] text-purple-600/80 mt-0.5">Diajukan: {{ $event->cancellation_requested_at?->format('d/m/Y H:i') }} (Menunggu persetujuan Admin / PIC)</p>
                                            @elseif ($event->status === 'cancelled')
                                                <p class="text-rose-700 font-semibold"><span class="font-bold">Dibatalkan:</span> {{ $event->cancellation_reason ?: 'Agenda dibatalkan.' }}</p>
                                                <p class="text-[11px] text-slate-500 mt-0.5">Disetujui oleh: {{ $event->cancellationApprover?->fullname ?? 'Admin / PIC' }} {{ $event->cancellation_approved_at ? 'pada ' . $event->cancellation_approved_at->format('d/m/Y H:i') : '' }}</p>
                                            @else
                                                @if ($event->cancellation_status === 'rejected')
                                                    <div class="mb-1 rounded-md border border-red-200 bg-red-50 p-1.5 text-red-700">
                                                        <span class="font-bold">Pembatalan Ditolak:</span> {{ $event->cancellation_rejected_reason }}
                                                    </div>
                                                @endif
                                                @if ($event->move_reason)
                                                    <p><span class="font-semibold text-slate-700">Alasan pemindahan:</span> {{ $event->move_reason }}</p>
                                                @endif
                                                @if ($event->rejection_reason)
                                                    <p class="mt-0.5"><span class="font-semibold text-slate-700">Catatan:</span> {{ $event->rejection_reason }}</p>
                                                @elseif (! $event->move_reason && ! $event->cancellation_status)
                                                    {{ $event->status === 'pending' ? 'Menunggu persetujuan Admin atau PIC.' : ($event->status === 'approved' ? ($event->lifecycle_status === 'completed' ? 'Agenda telah selesai terlaksana.' : ($event->lifecycle_status === 'in_progress' ? 'Agenda sedang terlaksana.' : 'Agenda belum terlaksana.')) : 'Agenda ditolak.') }}
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-6 py-3.5 align-middle text-right whitespace-nowrap">
                                            @php
                                                $canCancel = $event->canBeCancelledByGuru();
                                                $canComplain = $event->room_id && $event->status !== 'cancelled';
                                                $hasAction = $canCancel || $canComplain || $event->cancellation_status === 'pending';
                                            @endphp

                                            @if ($hasAction)
                                                <div 
                                                    x-data="{ open: false }" 
                                                    @click.outside="open = false" 
                                                    @keydown.escape.window="open = false"
                                                    class="relative inline-block text-right"
                                                >
                                                    <button 
                                                        type="button" 
                                                        @click.stop="open = !open" 
                                                        :class="open ? 'bg-amber-50 text-amber-900 border-amber-300 ring-2 ring-amber-500/20' : 'text-slate-600 bg-white border-slate-200 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 shadow-2xs'"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition-all focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                                                        aria-haspopup="true"
                                                        :aria-expanded="open"
                                                        title="Pilihan tindakan agenda"
                                                    >
                                                        <span class="sr-only">Menu aksi agenda</span>
                                                        <svg width="17" height="17" style="width:17px;height:17px;min-width:17px;max-width:17px;" class="transition-transform duration-200" :class="open ? 'rotate-90 text-amber-800' : 'text-slate-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                    </button>

                                                    <!-- Dropdown Menu Popover -->
                                                    <div 
                                                        x-show="open" 
                                                        x-cloak
                                                        x-transition:enter="transition ease-out duration-100" 
                                                        x-transition:enter-start="transform opacity-0 scale-95" 
                                                        x-transition:enter-end="transform opacity-100 scale-100" 
                                                        x-transition:leave="transition ease-in duration-75" 
                                                        x-transition:leave-start="transform opacity-100 scale-100" 
                                                        x-transition:leave-end="transform opacity-0 scale-95" 
                                                        class="absolute right-0 top-full mt-1.5 z-40 min-w-[210px] w-max max-w-xs rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl ring-1 ring-black/5 text-left focus:outline-none"
                                                        style="display: none;"
                                                    >
                                                        @if ($canComplain)
                                                            <a 
                                                                href="{{ route('guru.complaints.create', ['room_id' => $event->room_id]) }}" 
                                                                title="Buat Pengajuan Kerusakan"
                                                                aria-label="Buat Pengajuan Kerusakan"
                                                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-700 whitespace-nowrap transition hover:bg-slate-100 hover:text-slate-900"
                                                            >
                                                                <svg width="15" height="15" class="text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                                <span>Laporkan Kerusakan Ruangan</span>
                                                            </a>
                                                        @endif

                                                        @if ($canComplain && ($canCancel || $event->cancellation_status === 'pending'))
                                                            <div class="my-1 border-t border-slate-100"></div>
                                                        @endif

                                                        @if ($canCancel)
                                                            <button 
                                                                type="button" 
                                                                @click="open = false; document.getElementById('cancel-modal-{{ $event->id }}')?.classList.add('flex'); document.getElementById('cancel-modal-{{ $event->id }}')?.removeAttribute('hidden'); document.getElementById('cancel-modal-{{ $event->id }}')?.querySelector('textarea')?.focus();"
                                                                data-open-cancel-modal="cancel-modal-{{ $event->id }}"
                                                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-semibold text-rose-600 whitespace-nowrap transition hover:bg-rose-50 hover:text-rose-700"
                                                            >
                                                                <svg width="15" height="15" class="text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                                <span>Batalkan Agenda</span>
                                                            </button>
                                                        @elseif ($event->cancellation_status === 'pending')
                                                            <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-purple-700 bg-purple-50">
                                                                <svg class="h-3.5 w-3.5 text-purple-600 animate-spin shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                </svg>
                                                                <span>Menunggu Pembatalan</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-400 font-medium">-</span>
                                            @endif

                                            @if ($canCancel)
                                                <div
                                                    id="cancel-modal-{{ $event->id }}"
                                                    data-cancel-modal-panel
                                                    hidden
                                                    class="fixed inset-0 z-[100] items-center justify-center bg-black/60 p-4 text-left whitespace-normal"
                                                    role="dialog"
                                                    aria-modal="true"
                                                >
                                                    <form 
                                                        method="POST" 
                                                        action="{{ route('guru.events.cancel-request', $event) }}" 
                                                        class="mx-auto w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-2xl text-left whitespace-normal"
                                                        data-confirm-action="mengajukan pembatalan agenda ini" 
                                                        data-confirm-item="Agenda: {{ $event->event_name }}"
                                                    >
                                                        @csrf
                                                        <div class="flex items-center justify-between border-b border-slate-200 bg-rose-50/70 px-5 py-4 text-left whitespace-normal">
                                                            <div class="flex items-center gap-2.5 text-left whitespace-normal">
                                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </div>
                                                                <div class="text-left whitespace-normal">
                                                                    <h4 class="text-base font-bold text-slate-900 text-left">Pengajuan Pembatalan Agenda</h4>
                                                                    <p class="text-xs text-slate-500 text-left">{{ $event->event_name }}</p>
                                                                </div>
                                                            </div>
                                                            <button type="button" data-close-cancel-modal class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" aria-label="Tutup dialog">&times;</button>
                                                        </div>
                                                        <div class="space-y-4 p-5 text-left whitespace-normal">
                                                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-900 text-left whitespace-normal break-words">
                                                                <p class="font-bold flex items-center gap-1.5 text-left whitespace-normal">
                                                                    <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                    </svg>
                                                                    <span>Perhatian: Membutuhkan Persetujuan</span>
                                                                </p>
                                                                <p class="mt-1 text-slate-600 leading-relaxed text-left whitespace-normal break-words">
                                                                    Pembatalan agenda membutuhkan persetujuan dari <strong>Admin atau PIC Ruang</strong> sebelum jadwal ruangan resmi dibebaskan untuk pihak lain.
                                                                </p>
                                                            </div>
                                                            <div class="text-left whitespace-normal">
                                                                <label for="cancellation-reason-{{ $event->id }}" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5 text-left">
                                                                    Alasan Pembatalan <span class="text-rose-600">*</span>
                                                                </label>
                                                                <textarea 
                                                                    id="cancellation-reason-{{ $event->id }}" 
                                                                    name="cancellation_reason" 
                                                                    rows="4" 
                                                                    maxlength="1000" 
                                                                    required 
                                                                    class="w-full rounded-xl border border-slate-300 p-3 text-sm focus:border-rose-500 focus:ring-1 focus:ring-rose-500 leading-relaxed text-left whitespace-normal" 
                                                                    placeholder="Tuliskan alasan mengapa agenda ini dibatalkan..."
                                                                ></textarea>
                                                                <p class="mt-1 text-[11px] text-slate-400 text-left whitespace-normal">Minimal 5 karakter. Jelaskan alasan pembatalan secara jelas.</p>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50 px-5 py-3.5 text-left whitespace-normal">
                                                            <button type="button" data-close-cancel-modal class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">Kembali</button>
                                                            <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700 shadow-sm transition">Kirim Pengajuan Batal</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-open-cancel-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-open-cancel-modal');
                const modal = document.getElementById(modalId);
                const textarea = modal?.querySelector('textarea');

                if (modal instanceof HTMLElement) {
                    modal.hidden = false;
                    modal.classList.add('flex');
                    textarea?.focus();
                }
            });
        });

        document.querySelectorAll('[data-close-cancel-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('[data-cancel-modal-panel]');
                if (modal instanceof HTMLElement) {
                    modal.hidden = true;
                    modal.classList.remove('flex');
                }
            });
        });
    </script>
</x-app-layout>
