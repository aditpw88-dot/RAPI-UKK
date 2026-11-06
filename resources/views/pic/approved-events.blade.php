<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">Panel PIC Ruang</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Manajemen Agenda PIC</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau agenda yang sedang dan sudah terlaksana, serta agenda yang tidak disetujui.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            @foreach ([
                ['title' => 'Sedang Terlaksana', 'events' => $inProgressEvents, 'statusClass' => 'bg-amber-100 text-amber-800', 'empty' => 'Belum ada agenda yang sedang terlaksana.', 'completed' => false, 'hasReason' => false],
                ['title' => 'Sudah Terlaksana', 'events' => $completedEvents, 'statusClass' => 'bg-blue-100 text-blue-800', 'empty' => 'Belum ada agenda yang sudah terlaksana.', 'completed' => true, 'hasReason' => false],
                ['title' => 'Tidak Disetujui', 'events' => $rejectedEvents, 'statusClass' => 'bg-red-100 text-red-700', 'empty' => 'Belum ada agenda yang tidak disetujui.', 'completed' => false, 'hasReason' => true],
            ] as $section)
                <section class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="font-semibold text-slate-900">{{ $section['title'] }}</h3>
                    </div>
                    @if ($section['events']->isEmpty())
                        <div class="px-6 py-8 text-center text-sm text-slate-500">{{ $section['empty'] }}</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-6 py-3">Nama Agenda</th>
                                        <th class="px-6 py-3">Ruangan</th>
                                        <th class="px-6 py-3">Pengaju</th>
                                        <th class="px-6 py-3">Jadwal</th>
                                        <th class="px-6 py-3">Status</th>
                                        @if ($section['hasReason'])
                                            <th class="px-6 py-3">Alasan</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($section['events'] as $event)
                                        <tr>
                                            <td class="px-6 py-4 font-medium text-slate-900">{{ $event->event_name }}</td>
                                            <td class="px-6 py-4 text-slate-600">{{ $event->room_names ?: '-' }}</td>
                                            <td class="px-6 py-4 text-slate-600">{{ $event->pic?->fullname ?? '-' }}</td>
                                            <td class="px-6 py-4 text-slate-600">
                                                <x-event-date-range :start-date="$event->date" :end-date="$event->end_date" />
                                                <x-event-time :start-time="$event->start_time" :end-time="$event->end_time" />
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $section['statusClass'] }}">{{ $section['title'] }}</span>
                                            </td>
                                            @if ($section['hasReason'])
                                                <td class="px-6 py-4 text-slate-600">{{ $event->rejection_reason ?: 'Alasan tidak dicantumkan.' }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
