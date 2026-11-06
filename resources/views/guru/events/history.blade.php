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
                        <option value="in_progress" @selected(in_array(request('status'), ['in_progress', 'approved'], true))>Sedang Terlaksana</option>
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
                            <thead class="bg-gray-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-6 py-3">Nama Agenda</th>
                                    <th class="px-6 py-3">Nama Ruangan</th>
                                    <th class="px-6 py-3">Jadwal</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Catatan</th>
                                    <th class="px-6 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($events as $event)
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $event->event_name }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $event->room_names ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">
                                            <x-event-date-range :start-date="$event->date" :end-date="$event->end_date" />
                                            <x-event-time :start-time="$event->start_time" :end-time="$event->end_time" />
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $event->status === 'approved' ? ($event->completed_at ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') : ($event->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $event->status === 'approved' ? ($event->completed_at ? 'Sudah Terlaksana' : 'Sedang Terlaksana') : ($event->status === 'rejected' ? 'Ditolak' : 'Menunggu Persetujuan') }}</span>
                                        </td>
                                        <td class="max-w-xs px-6 py-4 text-xs text-slate-500">
                                            @if ($event->move_reason)
                                                <p><span class="font-semibold text-slate-700">Alasan pemindahan:</span> {{ $event->move_reason }}</p>
                                            @endif
                                            @if ($event->rejection_reason)
                                                <p class="mt-1"><span class="font-semibold text-slate-700">Catatan:</span> {{ $event->rejection_reason }}</p>
                                            @elseif (! $event->move_reason)
                                                {{ $event->status === 'pending' ? 'Menunggu persetujuan Admin atau PIC.' : ($event->status === 'approved' ? ($event->completed_at ? 'Agenda telah ditandai sudah terlaksana.' : 'Agenda sedang terlaksana.') : 'Agenda ditolak.') }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="{{ route('guru.complaints.create', ['room_id' => $event->room_id]) }}" class="mt-2 inline-flex items-center justify-center rounded-md bg-red-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-800">Buat Pengajuan Kerusakan</a>
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
