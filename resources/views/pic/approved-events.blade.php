<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Panel PIC Ruang</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Manajemen Agenda PIC</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pantau agenda yang sedang dan sudah terlaksana, serta agenda yang ditolak di ruang tanggung jawab Anda.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-2xs">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-2xs">{{ session('error') }}</div>
            @endif
            @if (session('warning'))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-2xs">{{ session('warning') }}</div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Daftar Agenda</h3>
                    <p class="text-xs text-slate-500">
                        @if ($events->total() > 0)
                            Menampilkan {{ $events->firstItem() }}–{{ $events->lastItem() }} dari {{ $events->total() }} agenda di bawah tanggung jawab Anda.
                        @else
                            Daftar agenda di ruang penugasan Anda.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Form Pencarian & Filter Status (Serupa dengan Admin) -->
            <form method="GET" action="{{ route('pic.events.approved') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
                <input 
                    type="search" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari agenda, ruang, pengaju" 
                    aria-label="Cari agenda, ruang, atau pengaju" 
                    class="rounded-lg border-gray-300 text-sm lg:col-span-2 focus:border-slate-800 focus:ring-1 focus:ring-slate-800"
                >
                <select name="status" aria-label="Filter status agenda" class="rounded-lg border-gray-300 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    <option value="">Semua status</option>
                    <option value="in_progress" @selected(request('status') === 'in_progress')>Sedang Terlaksana</option>
                    <option value="upcoming" @selected(request('status') === 'upcoming')>Belum Terlaksana</option>
                    <option value="completed" @selected(request('status') === 'completed')>Sudah Terlaksana</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Tanggal mulai" class="rounded-lg border-gray-300 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Tanggal akhir" class="rounded-lg border-gray-300 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-slate-700 transition">Terapkan</button>
                    <a href="{{ route('pic.events.approved') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50 transition">Reset</a>
                </div>
            </form>

            <!-- Tabel Terpadu Agenda (Format dan Kerapian Sama Persis dengan Admin) -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto min-h-[320px]">
                    <table class="w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="w-[26%] px-5 py-3.5 font-bold">Agenda</th>
                                <th scope="col" class="w-[16%] px-5 py-3.5 font-bold">Ruang</th>
                                <th scope="col" class="w-[18%] px-5 py-3.5 font-bold">Pengaju</th>
                                <th scope="col" class="w-[24%] px-5 py-3.5 font-bold">Jadwal</th>
                                <th scope="col" class="w-[16%] px-5 py-3.5 font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($events as $index => $event)
                                <tr class="align-middle hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 py-3.5 align-middle">
                                        <p class="font-semibold text-slate-900 text-sm sm:text-base leading-snug">{{ $event->event_name }}</p>
                                        @if ($event->event_type)
                                            <span class="mt-1 inline-block text-[11px] font-medium text-slate-500 uppercase tracking-wide">
                                                {{ config("event_types.{$event->event_type}.label") ?? ucfirst($event->event_type) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 align-middle text-slate-700 font-medium">
                                        <div class="flex items-center gap-1.5">
                                            <svg width="16" height="16" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            <span class="text-sm font-semibold text-slate-800">{{ $event->room_names ?: '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 align-middle text-slate-700">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                                                {{ strtoupper(substr($event->pic?->fullname ?? 'U', 0, 1)) }}
                                            </div>
                                            <div class="truncate max-w-[140px]">
                                                <p class="truncate text-sm font-medium text-slate-900">{{ $event->pic?->fullname ?? '-' }}</p>
                                                @if ($event->pic?->email)
                                                    <p class="truncate text-[11px] text-slate-400">{{ $event->pic->email }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 align-middle">
                                        <div class="space-y-1.5 whitespace-nowrap text-left">
                                            <div class="flex items-center gap-1.5 font-bold text-slate-900 text-sm sm:text-base">
                                                <svg width="17" height="17" style="width:17px;height:17px;min-width:17px;" class="text-amber-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></span>
                                            </div>
                                            @php
                                                $startFormatted = date('H:i', strtotime($event->start_time));
                                                $endFormatted = date('H:i', strtotime($event->end_time));
                                                $sessionList = collect(config('schedule.sessions'));
                                                $startSessionIndex = $sessionList->search(fn ($t) => $t['start_time'] === $startFormatted);
                                                $endSessionIndex = $sessionList->search(fn ($t) => $t['end_time'] === $endFormatted);
                                            @endphp
                                            @if ($startSessionIndex !== false && $endSessionIndex !== false)
                                                <div>
                                                    <span class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-900 shadow-2xs">
                                                        Sesi {{ $startSessionIndex }}{{ $startSessionIndex !== $endSessionIndex ? '–' . $endSessionIndex : '' }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="flex items-center gap-1.5 font-mono text-sm sm:text-base font-bold text-slate-900">
                                                <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;" class="text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>{{ $startFormatted }}–{{ $endFormatted }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 align-middle whitespace-nowrap">
                                        @if ($event->status === 'approved')
                                            @if ($event->lifecycle_status === 'completed')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800 shadow-2xs">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                                    <span>{{ $event->lifecycle_status_label }}</span>
                                                </span>
                                            @elseif ($event->lifecycle_status === 'in_progress')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 shadow-2xs">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span>{{ $event->lifecycle_status_label }}</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 shadow-2xs">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                    <span>{{ $event->lifecycle_status_label }}</span>
                                                </span>
                                            @endif
                                            @if ($event->completed_at)
                                                <p class="mt-1 text-[11px] text-slate-400">Selesai {{ $event->completed_at->format('d/m/Y H:i') }}</p>
                                            @endif
                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ $event->approver && (str_contains(strtolower($event->approver->role?->role_name ?? ''), 'admin') || str_contains(strtolower($event->approver->role?->role_name ?? ''), 'waka')) ? ($event->approved_by === $event->pic_id ? 'Disetujui otomatis (Admin)' : 'Disetujui Admin') : 'Disetujui PIC' }}
                                            </p>
                                        @elseif ($event->status === 'rejected')
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 shadow-2xs">
                                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                <span>Ditolak</span>
                                            </span>
                                            @if ($event->rejection_reason)
                                                <p class="mt-1 max-w-[190px] text-xs text-red-600 break-words font-medium">
                                                    Alasan: {{ $event->rejection_reason }}
                                                </p>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">
                                        Tidak ada data agenda yang sesuai dengan pencarian atau filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($events->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4 bg-white">
                        {{ $events->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
