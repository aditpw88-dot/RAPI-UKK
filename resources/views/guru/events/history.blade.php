<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">Portal Guru/Organisasi</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Riwayat &amp; Status</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau pengajuan agenda dan status persetujuannya.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
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
                        <option value="pending" @selected(request('status') === 'pending')>Menunggu</option>
                        <option value="upcoming" @selected(request('status') === 'upcoming')>Belum Terlaksana</option>
                        <option value="in_progress" @selected(request('status') === 'in_progress')>Sedang Terlaksana</option>
                        <option value="completed" @selected(request('status') === 'completed')>Sudah Terlaksana</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
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
                                @foreach ($events as $event)
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
                                            @if ($event->move_reason)
                                                <p><span class="font-semibold text-slate-700">Alasan pemindahan:</span> {{ $event->move_reason }}</p>
                                            @endif
                                            @if ($event->rejection_reason)
                                                <p class="mt-0.5"><span class="font-semibold text-slate-700">Catatan:</span> {{ $event->rejection_reason }}</p>
                                            @elseif (! $event->move_reason)
                                                {{ $event->status === 'pending' ? 'Menunggu persetujuan Admin atau PIC.' : ($event->status === 'approved' ? ($event->lifecycle_status === 'completed' ? 'Agenda telah selesai terlaksana.' : ($event->lifecycle_status === 'in_progress' ? 'Agenda sedang terlaksana.' : 'Agenda belum terlaksana.')) : 'Agenda ditolak.') }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-3.5 align-middle text-right whitespace-nowrap">
                                            @if ($event->room_id)
                                                <a 
                                                    href="{{ route('guru.complaints.create', ['room_id' => $event->room_id]) }}" 
                                                    title="Buat Pengajuan Kerusakan"
                                                    aria-label="Buat Pengajuan Kerusakan"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50/70 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-2xs transition hover:border-red-300 hover:bg-red-100 hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                                                >
                                                    <svg width="14" height="14" class="text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <span>Lapor Kerusakan</span>
                                                </a>
                                            @else
                                                <span class="text-slate-400 text-xs">-</span>
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
</x-app-layout>
