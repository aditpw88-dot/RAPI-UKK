<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">Panel PIC Ruang</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Dashboard PIC</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola ruang dan pantau pengajuan agenda di ruang tanggung jawab Anda.</p>
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

            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm text-slate-500">Ruang Tanggung Jawab</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $rooms->count() }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm text-slate-500">Menunggu Diproses</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $pendingEvents }}</p>
                </div>
            </div>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">Daftar Ruang</h3>
                </div>
                @if ($rooms->isEmpty())
                    <p class="px-6 py-8 text-sm text-slate-500">Belum ada ruang yang ditugaskan kepada Anda.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-6 py-3">Nama Ruang</th>
                                    <th class="px-6 py-3">Kapasitas</th>
                                    <th class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($rooms as $room)
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $room->room_name }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $room->capacity }} orang</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $room->is_active ? 'Aktif' : 'Tidak aktif' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">Pengajuan Agenda</h3>
                </div>
                @if ($events->isEmpty())
                    <p class="px-6 py-8 text-sm text-slate-500">Belum ada pengajuan untuk ruang Anda.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-6 py-3">Agenda</th>
                                    <th class="px-6 py-3">Ruang</th>
                                    <th class="px-6 py-3">Tanggal</th>
                                    <th class="px-6 py-3">Waktu</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($events as $event)
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $event->event_name }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $event->room_names ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600"><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></td>
                                        <td class="px-6 py-4 text-slate-600"><x-event-time :start-time="$event->start_time" :end-time="$event->end_time" /></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $event->status === 'approved' ? ($event->completed_at ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') : ($event->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                                {{ $event->status === 'approved' ? ($event->completed_at ? 'Sudah Terlaksana' : 'Sedang Terlaksana') : ($event->status === 'rejected' ? 'Ditolak' : 'Menunggu') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($event->status === 'pending')
                                                <a href="{{ route('pic.approvals') }}" class="text-xs font-semibold text-slate-900 underline">Tinjau pengajuan</a>
                                            @elseif ($event->status === 'rejected')
                                                <span class="text-xs text-red-600">Ditolak</span>
                                            @else
                                                <span class="text-xs text-slate-500">-</span>
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