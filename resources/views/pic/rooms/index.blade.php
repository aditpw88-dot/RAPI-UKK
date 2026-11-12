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
            @if (session('ongoing_conflict'))
                @php
                    $conflict = session('ongoing_conflict');
                @endphp
                <div class="rounded-xl border border-red-300 bg-red-50 p-4 shadow-sm text-red-950">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-red-200 text-red-800 text-xs font-bold">!</span>
                                <p class="font-bold text-sm text-red-900">Perubahan Status Ruangan Ditolak</p>
                            </div>
                            <p class="text-xs text-red-800 leading-relaxed">
                                Ruangan <strong>{{ $conflict['room_name'] }}</strong> saat ini sedang digunakan untuk agenda <strong>"{{ $conflict['event_name'] }}"</strong> oleh <strong>{{ $conflict['borrower_name'] }}</strong> ({{ $conflict['event_time'] }} WIB). Ruangan tidak dapat dinonaktifkan saat agenda sedang berlangsung.
                            </p>
                        </div>
                        @if (!empty($conflict['whatsapp_url']))
                            <a 
                                href="{{ $conflict['whatsapp_url'] }}" 
                                target="_blank" 
                                rel="noopener noreferrer" 
                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md hover:bg-emerald-700 transition"
                            >
                                <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.772.82 2.79.82 3.181 0 5.768-2.587 5.768-5.766.001-3.187-2.575-5.806-5.767-6.006zm3.328 8.163c-.144.405-.837.774-1.17.822-.312.043-.687.078-2.285-.561-1.921-.767-3.136-2.73-3.232-2.858-.095-.128-.775-1.031-.775-1.966 0-.935.489-1.394.664-1.583.174-.189.38-.236.507-.236.126 0 .253.002.364.007.119.006.277-.045.433.329.16.38.547 1.332.595 1.428.048.095.08.207.016.333-.064.127-.096.206-.19.317-.095.111-.2.248-.286.333-.095.095-.195.198-.084.388.111.19.493.813 1.059 1.317.728.647 1.341.848 1.531.943.19.095.302.08.413-.048.111-.127.476-.554.603-.744.127-.19.254-.158.428-.095.174.063 1.111.523 1.301.618.19.095.317.143.364.222.048.08.048.46-.096.865z"/>
                                </svg>
                                <span>Hubungi {{ $conflict['borrower_name'] }} via WhatsApp</span>
                            </a>
                        @endif
                    </div>
                </div>
            @elseif (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-2xs">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-2xs">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
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
            <form method="GET" action="{{ route('pic.rooms') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_11rem_11rem_auto_auto]">
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
                <select name="condition" aria-label="Filter kondisi ruang" class="rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    <option value="">Semua kondisi</option>
                    <option value="active" @selected(request('condition', request('active')) === 'active')>Kondisi: Aktif</option>
                    <option value="inactive" @selected(request('condition', request('active')) === 'inactive')>Kondisi: Tidak aktif</option>
                </select>
                <select name="status" aria-label="Filter status ruang" class="rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    <option value="">Semua status</option>
                    <option value="available" @selected(request('status') === 'available')>Status: Tersedia</option>
                    <option value="unavailable" @selected(request('status') === 'unavailable')>Status: Tidak tersedia</option>
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
                                <th scope="col" class="px-6 py-4 font-bold">Kondisi &amp; Status</th>
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
                                        <div class="flex flex-col gap-1.5">
                                            <div>
                                                @if ($room->is_active)
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                        <span>Kondisi: Aktif</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                        <span>Kondisi: Tidak aktif (Dalam Perbaikan)</span>
                                                    </span>
                                                @endif
                                            </div>
                                            @if (!$room->is_active && $room->maintenance_note)
                                                <div class="mt-0.5 max-w-xs rounded-lg border border-amber-200/80 bg-amber-50/70 px-2.5 py-1.5 text-xs text-amber-900">
                                                    <span class="font-semibold">Catatan PIC:</span> {{ $room->maintenance_note }}
                                                </div>
                                            @endif
                                            <div>
                                                @if ($room->is_available)
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-800">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>
                                                        <span>Status: Tersedia</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-800">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                                        <span>Status: Tidak tersedia</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            @php
                                                $currEv = $room->current_event;
                                                $currEvBorrower = $currEv?->pic?->fullname ?? $currEv?->pic?->name ?? 'Peminjam Ruangan';
                                                $currEvTime = $currEv ? substr($currEv->start_time, 0, 5) . ' - ' . substr($currEv->end_time, 0, 5) : '';
                                                $currEvWa = ($currEv && $currEv->pic?->phone_number) ? \App\Support\WhatsAppLink::make(
                                                    $currEv->pic->phone_number,
                                                    "Halo Bapak/Ibu {$currEvBorrower}, saya " . (auth()->user()->fullname ?? 'PIC Ruang') . " (PIC {$room->room_name}). Mohon maaf, ruangan {$room->room_name} rencananya akan dinonaktifkan (perbaikan/renovasi), namun saat ini sedang berlangsung agenda \"{$currEv->event_name}\" ({$currEvTime} WIB). Saya ingin berkoordinasi terlebih dahulu dengan Anda mengenai penggunaan ruangan tersebut. Terima kasih."
                                                ) : '';
                                            @endphp
                                            <button
                                                type="button"
                                                data-open-room-status
                                                data-room-id="{{ $room->id }}"
                                                data-room-name="{{ $room->room_name }}"
                                                data-room-active="{{ $room->is_active ? 'Aktif' : 'Tidak aktif' }}"
                                                data-room-note="{{ $room->maintenance_note ?? '' }}"
                                                data-has-ongoing-event="{{ $currEv ? '1' : '0' }}"
                                                data-ongoing-event-name="{{ $currEv?->event_name ?? '' }}"
                                                data-ongoing-event-borrower="{{ $currEvBorrower }}"
                                                data-ongoing-event-time="{{ $currEvTime }}"
                                                data-ongoing-event-wa="{{ $currEvWa ?? '' }}"
                                                data-action-url="{{ route('pic.rooms.update-status', $room) }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-900 shadow-2xs transition hover:border-amber-400 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-400"
                                            >
                                                <svg class="h-3.5 w-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span>Ubah Status</span>
                                            </button>

                                            <details class="group inline-block text-left">
                                                <summary class="flex w-fit cursor-pointer list-none items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-500 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 [&::-webkit-details-marker]:hidden">
                                                    <span class="text-slate-400 transition group-open:rotate-90" aria-hidden="true">▸</span>
                                                    <span>Lihat Identitas Ruangan</span>
                                                </summary>
                                                <div class="mt-2.5 w-80 rounded-xl border border-slate-200 bg-slate-50 p-4 text-left shadow-lg ring-1 ring-black/5">
                                                    <p class="font-bold text-slate-900 text-sm border-b border-slate-200 pb-2 mb-2">
                                                        {{ $room->room_name }}
                                                    </p>
                                                    <div class="space-y-2.5 text-xs text-slate-700">
                                                        <p class="font-medium text-slate-800">Kuantitas: {{ $room->capacity }} orang</p>
                                                        @if ($room->all_pics->isNotEmpty())
                                                            @foreach ($room->all_pics as $pic)
                                                                <div class="space-y-1 {{ !$loop->first ? 'pt-2 border-t border-slate-200' : '' }}">
                                                                    <p class="font-medium text-slate-800">Penanggung jawab : {{ $pic->fullname }}</p>
                                                                    <div class="space-y-0.5 pl-2 border-l-2 border-slate-300">
                                                                        <p class="font-semibold text-slate-900 text-xs">Data Diri</p>
                                                                        <p class="text-xs text-slate-600">Nomor whatsapp: {{ $pic->phone_number ?: 'Belum diisi' }}</p>
                                                                        <p class="text-xs text-slate-600">gmail: {{ $pic->email }}</p>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <p class="font-medium text-slate-800">Penanggung jawab : Belum ditentukan</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </details>
                                        </div>
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

    <!-- Modal Ubah Status Kondisi Ruangan -->
    <div id="room-status-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="room-status-modal-title">
        <div id="room-status-backdrop" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Manajemen Ruang PIC</p>
                    <h3 id="room-status-modal-title" class="mt-1 text-lg font-bold text-slate-900">Ubah Status Kondisi Ruangan</h3>
                    <p id="room-status-modal-subtitle" class="mt-0.5 text-sm font-semibold text-slate-600"></p>
                </div>
                <button type="button" id="room-status-close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="room-status-form" method="POST" action="" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="room-status-select" class="block text-sm font-semibold text-slate-800">Kondisi Ruangan</label>
                    <select id="room-status-select" name="is_active" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm font-medium focus:border-slate-900 focus:ring-slate-900">
                        <option value="Aktif">Aktif (Siap Digunakan / Bisa Dipinjam)</option>
                        <option value="Tidak aktif">Tidak aktif (Dalam Perbaikan / Renovasi)</option>
                    </select>
                </div>

                <!-- Peringatan Jika Ruangan Sedang Berlangsung Agenda -->
                <div id="room-ongoing-warning" class="hidden rounded-xl border border-red-300 bg-red-50 p-3.5 text-xs text-red-950 space-y-2.5">
                    <div class="flex items-start gap-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-200 text-red-800 font-bold">!</span>
                        <div>
                            <p class="font-bold text-red-900 text-xs">Agenda Sedang Berlangsung</p>
                            <p id="room-ongoing-warning-text" class="text-red-800 mt-0.5 leading-relaxed"></p>
                        </div>
                    </div>
                    <div id="room-ongoing-wa-wrap" class="pt-1">
                        <a id="room-ongoing-wa-link" href="#" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700 transition">
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.772.82 2.79.82 3.181 0 5.768-2.587 5.768-5.766.001-3.187-2.575-5.806-5.767-6.006zm3.328 8.163c-.144.405-.837.774-1.17.822-.312.043-.687.078-2.285-.561-1.921-.767-3.136-2.73-3.232-2.858-.095-.128-.775-1.031-.775-1.966 0-.935.489-1.394.664-1.583.174-.189.38-.236.507-.236.126 0 .253.002.364.007.119.006.277-.045.433.329.16.38.547 1.332.595 1.428.048.095.08.207.016.333-.064.127-.096.206-.19.317-.095.111-.2.248-.286.333-.095.095-.195.198-.084.388.111.19.493.813 1.059 1.317.728.647 1.341.848 1.531.943.19.095.302.08.413-.048.111-.127.476-.554.603-.744.127-.19.254-.158.428-.095.174.063 1.111.523 1.301.618.19.095.317.143.364.222.048.08.048.46-.096.865z"/>
                            </svg>
                            <span id="room-ongoing-wa-btn-text">Hubungi Peminjam via WhatsApp</span>
                        </a>
                    </div>
                </div>

                <div id="room-note-wrapper" class="space-y-1.5">
                    <label for="room-maintenance-note" class="block text-sm font-semibold text-slate-800">
                        Catatan Perbaikan / Kondisi Ruangan <span id="note-required-badge" class="text-red-600">*</span>
                    </label>
                    <textarea
                        id="room-maintenance-note"
                        name="maintenance_note"
                        rows="3"
                        placeholder="Contoh: Ruangan sedang dalam renovasi AC & plafon sampai tanggal 20 November."
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900"
                    ></textarea>
                    <p class="text-xs text-slate-500">
                        Catatan ini akan muncul otomatis beserta link WhatsApp Anda saat Guru atau Admin mengklik ruangan ini pada form peminjaman.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4">
                    <button type="button" id="room-status-cancel" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                        Simpan Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('room-status-modal');
            const backdrop = document.getElementById('room-status-backdrop');
            const closeBtn = document.getElementById('room-status-close');
            const cancelBtn = document.getElementById('room-status-cancel');
            const form = document.getElementById('room-status-form');
            const subtitle = document.getElementById('room-status-modal-subtitle');
            const statusSelect = document.getElementById('room-status-select');
            const noteInput = document.getElementById('room-maintenance-note');
            const noteWrapper = document.getElementById('room-note-wrapper');
            const noteRequiredBadge = document.getElementById('note-required-badge');
            const ongoingWarning = document.getElementById('room-ongoing-warning');
            const ongoingWarningText = document.getElementById('room-ongoing-warning-text');
            const ongoingWaLink = document.getElementById('room-ongoing-wa-link');
            const ongoingWaBtnText = document.getElementById('room-ongoing-wa-btn-text');

            let currentRoomHasOngoing = false;
            let currentRoomOngoingName = '';
            let currentRoomOngoingBorrower = '';
            let currentRoomOngoingTime = '';
            let currentRoomOngoingWa = '';

            function syncNoteVisibility() {
                const isInactive = statusSelect.value === 'Tidak aktif';
                if (isInactive) {
                    noteWrapper.classList.remove('opacity-60');
                    noteInput.required = true;
                    noteRequiredBadge?.classList.remove('hidden');

                    if (currentRoomHasOngoing) {
                        ongoingWarning?.classList.remove('hidden');
                        if (ongoingWarningText) {
                            ongoingWarningText.textContent = `Ruangan sedang digunakan untuk agenda "${currentRoomOngoingName}" oleh ${currentRoomOngoingBorrower} (${currentRoomOngoingTime} WIB). Ruangan tidak dapat dinonaktifkan saat agenda sedang berlangsung.`;
                        }
                        if (currentRoomOngoingWa && ongoingWaLink) {
                            ongoingWaLink.href = currentRoomOngoingWa;
                            ongoingWaLink.parentElement?.classList.remove('hidden');
                            if (ongoingWaBtnText) {
                                ongoingWaBtnText.textContent = `Hubungi ${currentRoomOngoingBorrower} via WhatsApp`;
                            }
                        } else if (ongoingWaLink) {
                            ongoingWaLink.parentElement?.classList.add('hidden');
                        }
                    } else {
                        ongoingWarning?.classList.add('hidden');
                    }
                } else {
                    noteWrapper.classList.add('opacity-60');
                    noteInput.required = false;
                    noteRequiredBadge?.classList.add('hidden');
                    ongoingWarning?.classList.add('hidden');
                }
            }

            statusSelect?.addEventListener('change', syncNoteVisibility);

            function openModal(btn) {
                if (!modal || !form) return;
                form.action = btn.dataset.actionUrl || '';
                subtitle.textContent = btn.dataset.roomName || '';
                statusSelect.value = btn.dataset.roomActive === 'Tidak aktif' ? 'Tidak aktif' : 'Aktif';
                noteInput.value = btn.dataset.roomNote || '';

                currentRoomHasOngoing = btn.dataset.hasOngoingEvent === '1';
                currentRoomOngoingName = btn.dataset.ongoingEventName || '';
                currentRoomOngoingBorrower = btn.dataset.ongoingEventBorrower || 'Peminjam Ruangan';
                currentRoomOngoingTime = btn.dataset.ongoingEventTime || '';
                currentRoomOngoingWa = btn.dataset.ongoingEventWa || '';

                syncNoteVisibility();

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            document.querySelectorAll('[data-open-room-status]').forEach((btn) => {
                btn.addEventListener('click', () => openModal(btn));
            });

            closeBtn?.addEventListener('click', closeModal);
            cancelBtn?.addEventListener('click', closeModal);
            backdrop?.addEventListener('click', closeModal);
        });
    </script>
</x-app-layout>

