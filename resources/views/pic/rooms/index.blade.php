<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Panel PIC Ruang</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Manajemen Ruang</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pantau ketersediaan, kapasitas, dan identitas ruangan di bawah tanggung jawab Anda.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-2xs">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-2xs">{{ session('error') }}</div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Daftar Ruangan</h3>
                    <p class="text-xs text-slate-500">
                        @if ($rooms->total() > 0)
                            Menampilkan {{ $rooms->firstItem() }}–{{ $rooms->lastItem() }} dari {{ $rooms->total() }} ruangan (Urut: Ruang Wijaya, DAK, Lab 1–21, Ruang 1–seterusnya).
                        @else
                            Tidak ada data ruangan.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Form Pencarian & Filter Status -->
            <form method="GET" action="{{ route('pic.rooms') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_12rem_auto_auto]">
                <div class="relative">
                    <input 
                        type="search" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Cari nama ruang..." 
                        aria-label="Cari nama ruang" 
                        class="w-full rounded-lg border-gray-300 py-2 pl-9 pr-3 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800"
                    >
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <select name="active" aria-label="Filter status ruang" class="rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    <option value="">Semua status</option>
                    <option value="active" @selected(request('active') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('active') === 'inactive')>Tidak aktif</option>
                </select>
                <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow-2xs transition hover:bg-slate-700">Cari / Filter</button>
                <a href="{{ route('pic.rooms') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 transition hover:bg-gray-50">Reset</a>
            </form>

            <!-- Tabel Daftar Ruang -->
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-bold">Ruangan</th>
                                <th scope="col" class="px-6 py-4 font-bold">Kapasitas</th>
                                <th scope="col" class="px-6 py-4 font-bold">Status</th>
                                <th scope="col" class="px-6 py-4 text-right font-bold">Identitas &amp; Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($rooms as $room)
                                <tr class="align-top hover:bg-slate-50/70 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="text-base font-bold text-slate-900">{{ $room->room_name }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-slate-700">
                                        <span class="inline-flex items-center gap-1.5 font-medium text-sm">
                                            <svg width="16" height="16" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <span>{{ $room->capacity }} orang</span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($room->is_active)
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                <span>Aktif</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                <span>Tidak aktif</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <details class="group inline-block text-left">
                                            <summary class="flex w-fit ml-auto cursor-pointer list-none items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-500 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 [&::-webkit-details-marker]:hidden">
                                                <span class="text-slate-400 transition group-open:rotate-90" aria-hidden="true">▸</span>
                                                <span>Lihat Identitas Ruangan</span>
                                            </summary>
                                            <div class="mt-2.5 w-80 rounded-xl border border-slate-200 bg-slate-50 p-4 text-left shadow-lg ring-1 ring-black/5">
                                                <p class="font-bold text-slate-900 text-sm border-b border-slate-200 pb-2 mb-2">
                                                    {{ $room->room_name }}
                                                </p>
                                                <div class="space-y-2 text-xs text-slate-700">
                                                    <p><span class="font-semibold text-slate-900">Kapasitas:</span> {{ $room->capacity }} orang</p>
                                                    <p><span class="font-semibold text-slate-900">Status Operasional:</span> {{ $room->is_active ? 'Aktif' : 'Tidak aktif' }}</p>
                                                    <div>
                                                        <p class="font-semibold text-slate-900 mb-1">Penanggung Jawab (PIC):</p>
                                                        @if ($room->all_pics->isNotEmpty())
                                                            <ul class="space-y-1.5 pl-2.5 border-l-2 border-slate-300">
                                                                @foreach ($room->all_pics as $pic)
                                                                    <li>
                                                                        <span class="font-semibold text-slate-900">• {{ $pic->fullname }}</span>
                                                                        <span class="text-[11px] text-slate-500 block ml-2">WA: {{ $pic->phone_number ?: 'Belum diisi' }} &bull; {{ $pic->email }}</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <p class="text-slate-400 italic">Belum ada PIC ditugaskan</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">
                                        Tidak ada data ruangan yang cocok dengan filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($rooms->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4 bg-white">
                        {{ $rooms->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>

