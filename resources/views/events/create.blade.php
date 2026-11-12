<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">Administrasi</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Peminjaman Ruangan</h2>
            <p class="mt-1 text-sm text-slate-500">Ajukan penggunaan ruang untuk agenda sekolah.</p>
        </div>
    </x-slot>

    <div class="event-form-page px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            @if (session('success'))
                <div class="event-alert event-alert-success" role="alert">
                    <strong>Pengajuan berhasil.</strong>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="event-alert event-alert-error" role="alert">
                    <strong>Pengajuan belum dapat disimpan.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="event-form-card">
                <div class="event-form-card-header">
                    <div>
                        <h3>Pengajuan Peminjaman Ruangan</h3>
                        <p>Isi agenda yang akan dilakukan, lalu tentukan ruang dan jadwal pemakaiannya.</p>
                    </div>
                    <span class="event-form-badge">Status: Menunggu</span>
                </div>

                @if ($rooms->isEmpty())
                    <div class="event-empty-state">
                        <div class="event-empty-icon" aria-hidden="true">!</div>
                        <h3>Belum ada ruang aktif</h3>
                        <p>Tambahkan ruang terlebih dahulu sebelum membuat pengajuan peminjaman.</p>
                    </div>
                @else
                    @php($selectedRoomIds = is_array(old('room_ids')) ? old('room_ids') : (old('room_id') ? [old('room_id')] : []))
                    <form id="guru-event-form" action="{{ route('events.store') }}" method="POST" class="event-form" data-confirm-action="mengirim pengajuan agenda" data-confirm-item="Pengajuan agenda baru">
                        @csrf

                        <section class="event-form-field-full rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-4 border-b border-slate-200 pb-3">
                                <h4 class="font-semibold text-slate-900">Informasi Agenda</h4>
                                <p class="mt-1 text-sm text-slate-500">Tentukan kegiatan yang akan diselenggarakan.</p>
                            </div>
                            <div class="event-form-field">
                                <label for="event_name">Nama Agenda</label>
                                <input type="text" id="event_name" name="event_name" value="{{ old('event_name') }}" placeholder="Contoh: Rapat Koordinasi Kesiswaan" required>
                            </div>
                            <div class="event-form-field mt-4">
                                <label for="event_type">Jenis Agenda</label>
                                <select id="event_type" name="event_type" required>
                                    @foreach (config('event_types') as $value => $type)
                                        <option value="{{ $value }}" @selected(old('event_type', 'lainnya') === $value)>{{ $type['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </section>

                        <section class="event-form-field-full rounded-lg border border-slate-200 bg-white p-4">
                            <div class="mb-4 border-b border-slate-200 pb-3">
                                <h4 class="font-semibold text-slate-900">Informasi Peminjaman Ruangan</h4>
                                <p class="mt-1 text-sm text-slate-500">Pilih ruang dan tentukan waktu penggunaan.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div class="event-form-field sm:col-span-2">
                                    <fieldset>
                                        <legend class="font-semibold">Pilih Ruangan</legend>
                                        @php($selectedRoomIds = is_array(old('room_ids')) ? array_values(old('room_ids')) : (old('room_id') ? [old('room_id')] : ['']))
                                        <div id="room-selection-list" class="mt-3 space-y-3">
                                            @foreach ($selectedRoomIds as $index => $selectedRoomId)
                                                @php($currentRoom = $rooms->firstWhere('id', $selectedRoomId))
                                                <div data-room-row class="flex items-end gap-2">
                                                    <div class="min-w-0 flex-1">
                                                        <label for="room-combobox-input-{{ $index }}" class="mb-1 block text-sm font-medium text-slate-700">Ruangan {{ $index + 1 }}</label>
                                                        <div data-room-combobox class="relative">
                                                            <div class="relative">
                                                                <input type="text"
                                                                       id="room-combobox-input-{{ $index }}"
                                                                       data-combobox-input
                                                                       autocomplete="off"
                                                                       placeholder="Pilih atau ketik nama ruangan..."
                                                                       value="{{ $currentRoom ? $currentRoom->room_name . ' (Kapasitas ' . $currentRoom->capacity . ')' : '' }}"
                                                                       class="w-full rounded-md border-gray-300 pr-10 text-sm focus:border-slate-800 focus:ring-slate-800">
                                                                <button type="button"
                                                                        data-combobox-toggle
                                                                        tabindex="-1"
                                                                        aria-label="Buka pilihan ruangan"
                                                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 focus:outline-none">
                                                                    <svg class="h-4 w-4 transition-transform duration-200" data-combobox-arrow fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                            <select id="room-select-{{ $index }}" name="room_ids[]" data-room-select required class="sr-only" tabindex="-1" style="position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px;">
                                                                <option value="">Pilih ruangan</option>
                                                                @foreach ($rooms as $room)
                                                                    <option
                                                                        value="{{ $room->id }}"
                                                                        data-name="{{ $room->room_name }}"
                                                                        data-capacity="{{ $room->capacity }}"
                                                                        data-is-active="{{ $room->is_active ? '1' : '0' }}"
                                                                        data-maintenance-note="{{ $room->maintenance_note ?? '' }}"
                                                                        data-pic-name="{{ $room->pic_names }}"
                                                                        data-pic-phone="{{ $room->pic_phone ?? '' }}"
                                                                        data-pic-wa="{{ $room->pic_whatsapp_url ?? '' }}"
                                                                        @selected((string) $selectedRoomId === (string) $room->id)
                                                                    >
                                                                        {{ $room->room_name }} (Kapasitas {{ $room->capacity }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div data-combobox-dropdown class="hidden absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg py-1 text-sm">
                                                                <ul data-combobox-list role="listbox" class="divide-y divide-gray-50"></ul>
                                                                <div data-combobox-empty class="hidden px-4 py-3 text-center text-xs text-slate-500">
                                                                    Tidak ada ruangan yang cocok dengan pencarian.
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <p data-room-occupied-warning class="hidden mt-1.5 text-xs font-semibold text-red-600 flex items-center gap-1">
                                                            <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                                            <span data-warning-text>Ruangan ini sedang dipakai pada jadwal tersebut.</span>
                                                        </p>
                                                    </div>
                                                    <button type="button" data-remove-room class="hidden rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <button type="button" id="add-room-button" class="mt-3 rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">+ Tambah Ruang</button>
                                        <template id="room-select-template">
                                            <div data-room-row class="flex items-end gap-2">
                                                <div class="min-w-0 flex-1">
                                                    <label data-room-label class="mb-1 block text-sm font-medium text-slate-700">Ruangan</label>
                                                    <div data-room-combobox class="relative">
                                                        <div class="relative">
                                                            <input type="text"
                                                                   data-combobox-input
                                                                   autocomplete="off"
                                                                   placeholder="Pilih atau ketik nama ruangan..."
                                                                   class="w-full rounded-md border-gray-300 pr-10 text-sm focus:border-slate-800 focus:ring-slate-800">
                                                            <button type="button"
                                                                    data-combobox-toggle
                                                                    tabindex="-1"
                                                                    aria-label="Buka pilihan ruangan"
                                                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 focus:outline-none">
                                                                <svg class="h-4 w-4 transition-transform duration-200" data-combobox-arrow fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <select name="room_ids[]" data-room-select required class="sr-only" tabindex="-1" style="position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px;">
                                                            <option value="">Pilih ruangan</option>
                                                            @foreach ($rooms as $room)
                                                                <option
                                                                    value="{{ $room->id }}"
                                                                    data-name="{{ $room->room_name }}"
                                                                    data-capacity="{{ $room->capacity }}"
                                                                    data-is-active="{{ $room->is_active ? '1' : '0' }}"
                                                                    data-maintenance-note="{{ $room->maintenance_note ?? '' }}"
                                                                    data-pic-name="{{ $room->pic_names }}"
                                                                    data-pic-phone="{{ $room->pic_phone ?? '' }}"
                                                                    data-pic-wa="{{ $room->pic_whatsapp_url ?? '' }}"
                                                                >
                                                                    {{ $room->room_name }} (Kapasitas {{ $room->capacity }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div data-combobox-dropdown class="hidden absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg py-1 text-sm">
                                                            <ul data-combobox-list role="listbox" class="divide-y divide-gray-50"></ul>
                                                            <div data-combobox-empty class="hidden px-4 py-3 text-center text-xs text-slate-500">
                                                                Tidak ada ruangan yang cocok dengan pencarian.
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <p data-room-occupied-warning class="hidden mt-1.5 text-xs font-semibold text-red-600 flex items-center gap-1">
                                                        <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                                        <span data-warning-text>Ruangan ini sedang dipakai pada jadwal tersebut.</span>
                                                    </p>
                                                </div>
                                                <button type="button" data-remove-room class="rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                                            </div>
                                        </template>
                                    </fieldset>
                                </div>

                                <div class="event-form-field">
                                    <label for="date">Tanggal Mulai</label>
                                    <div class="relative mt-1">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </span>
                                        <input type="date" id="date" name="date" value="{{ old('date') }}" min="{{ now()->addDay()->toDateString() }}" required style="padding-left: 2.25rem;">
                                    </div>
                                    <small class="event-form-help">Pengajuan wajib dilakukan paling lambat H-1.</small>
                                </div>

                                <div class="event-form-field">
                                    <label for="end_date">Tanggal Selesai</label>
                                    <div class="relative mt-1">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </span>
                                        <input type="date" id="end_date" name="end_date" value="{{ old('end_date', old('date')) }}" min="{{ old('date', now()->addDay()->toDateString()) }}" required style="padding-left: 2.25rem;">
                                    </div>
                                </div>

                                <div class="event-form-field">
                                    <label for="start_session">Sesi Mulai</label>
                                    <select id="start_session" name="start_session" required>
                                        <option value="">Pilih sesi mulai</option>
                                        @foreach (config('schedule.sessions') as $number => $times)
                                            <option value="{{ $number }}" data-session-start="{{ $times['start_time'] }}" data-session-end="{{ $times['end_time'] }}" @selected(old('start_session') == $number)>Sesi {{ $number }} ({{ $times['start_time'] }} - {{ $times['end_time'] }})</option>
                                        @endforeach
                                    </select>
                                    <small id="start_session_time" class="event-form-help">Pilih sesi mulai untuk melihat jam mulai dan selesai sesi.</small>
                                </div>

                                <div class="event-form-field">
                                    <label for="end_session">Sesi Selesai</label>
                                    <select id="end_session" name="end_session" required>
                                        <option value="">Pilih sesi selesai</option>
                                        @foreach (config('schedule.sessions') as $number => $times)
                                            @continue($number === 1)
                                            <option value="{{ $number }}" data-session-start="{{ $times['start_time'] }}" data-session-end="{{ $times['end_time'] }}" @selected(old('end_session') == $number)>Sesi {{ $number }} ({{ $times['start_time'] }} - {{ $times['end_time'] }})</option>
                                        @endforeach
                                    </select>
                                    <small id="end_session_time" class="event-form-help">Pilih sesi selesai untuk melihat jam mulai dan selesai sesi.</small>
                                </div>
                            </div>
                        </section>

                        <div class="event-form-actions">
                            <a href="{{ route('guru.events') }}" class="event-button event-button-secondary">Batal</a>
                            <button type="submit" class="event-button event-button-primary">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                                </svg>
                                Ajukan Peminjaman
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @include('partials.room-maintenance-modal')
</x-app-layout>

<script>
    const bookedEvents = @json($bookedEvents ?? []);
    const sessionTimes = @json(config('schedule.sessions'));

    const roomSelectionList = document.getElementById('room-selection-list');
    const roomSelectTemplate = document.getElementById('room-select-template');
    const addRoomButton = document.getElementById('add-room-button');

    function getEventLifecycle(ev) {
        if (ev.lifecycle_status === 'rejected') {
            return 'rejected';
        }

        const now = new Date();
        const startDate = ev.date;
        const endDate = ev.end_date || ev.date;
        const startTime = ev.start_time || '00:00';
        const endTime = ev.end_time || '23:59';

        const startDt = new Date(`${startDate}T${startTime}:00`);
        const endDt = new Date(`${endDate}T${endTime}:00`);

        // 1. Sudah terlaksana jika waktu saat ini sudah melewati waktu selesai agenda
        if (now >= endDt) {
            return 'completed'; // Sudah Terlaksana
        }

        // 2. Belum terlaksana / Akan berlangsung jika waktu saat ini belum mencapai waktu mulai agenda
        if (now < startDt) {
            return 'upcoming'; // Akan Berlangsung
        }

        // 3. Sedang terlaksana / Sedang berlangsung jika waktu saat ini berada dalam rentang agenda
        return 'in_progress'; // Sedang Berlangsung
    }

    function getOccupiedRoomIds() {
        const startDateInput = document.getElementById('date');
        const endDateInput = document.getElementById('end_date');
        const startSessionInput = document.getElementById('start_session');
        const endSessionInput = document.getElementById('end_session');

        const startDate = startDateInput?.value?.trim() || '';
        const endDate = endDateInput?.value?.trim() || startDate;
        const startSession = startSessionInput?.value || '';
        const endSession = endSessionInput?.value || '';

        const occupiedMap = new Map();

        if (startDate) {
            let sessionStartTime = '00:00';
            let sessionEndTime = '23:59';
            const hasSession = Boolean(startSession && endSession && sessionTimes[startSession] && sessionTimes[endSession]);

            if (hasSession) {
                sessionStartTime = sessionTimes[startSession].start_time;
                sessionEndTime = sessionTimes[endSession].end_time;
            }

            bookedEvents.forEach((ev) => {
                const lifecycle = getEventLifecycle(ev);

                // ATURAN: Jika agenda tersebut sudah terlaksana maka status ruangannya adalah "tersedia"
                if (lifecycle === 'completed' || lifecycle === 'rejected') {
                    return;
                }

                // ATURAN: Jika ada agenda yang akan berlangsung atau sedang berlangsung maka ruangan itu statusnya adalah "dipakai"
                const evStartDate = ev.date;
                const evEndDate = ev.end_date || ev.date;
                const evStartTime = ev.start_time;
                const evEndTime = ev.end_time;

                const dateOverlap = evStartDate <= endDate && evEndDate >= startDate;
                const timeOverlap = hasSession
                    ? (evStartTime < sessionEndTime && evEndTime > sessionStartTime)
                    : true;

                if (dateOverlap && timeOverlap) {
                    const statusLabel = lifecycle === 'in_progress' ? 'Sedang Berlangsung' : 'Akan Berlangsung';
                    (ev.room_ids || []).forEach((roomId) => {
                        const idStr = String(roomId);
                        if (!occupiedMap.has(idStr)) {
                            occupiedMap.set(idStr, {
                                eventName: ev.event_name,
                                time: `${evStartTime} - ${evEndTime}`,
                                statusLabel: statusLabel,
                                date: evStartDate === evEndDate ? evStartDate : `${evStartDate} s/d ${evEndDate}`
                            });
                        }
                    });
                }
            });
        } else {
            // Ketika belum memilih tanggal: periksa seluruh agenda
            bookedEvents.forEach((ev) => {
                const lifecycle = getEventLifecycle(ev);

                // ATURAN: Jika agenda tersebut sudah terlaksana maka status ruangannya adalah "tersedia"
                if (lifecycle === 'completed' || lifecycle === 'rejected') {
                    return;
                }

                // ATURAN: Jika ada agenda yang akan berlangsung atau sedang berlangsung maka ruangan itu statusnya adalah "dipakai"
                if (lifecycle === 'in_progress' || lifecycle === 'upcoming' || lifecycle === 'pending') {
                    const statusLabel = lifecycle === 'in_progress' ? 'Sedang Berlangsung' : 'Akan Berlangsung';
                    (ev.room_ids || []).forEach((roomId) => {
                        const idStr = String(roomId);
                        if (!occupiedMap.has(idStr)) {
                            occupiedMap.set(idStr, {
                                eventName: ev.event_name,
                                time: `${ev.start_time} - ${ev.end_time}`,
                                statusLabel: statusLabel,
                                date: ev.date === ev.end_date || !ev.end_date ? ev.date : `${ev.date} s/d ${ev.end_date}`
                            });
                        }
                    });
                }
            });
        }

        return occupiedMap;
    }

    function updateRoomAvailabilityBadges() {
        const occupiedMap = getOccupiedRoomIds();

        document.querySelectorAll('[data-room-status-badge]').forEach((badge) => {
            const roomId = badge.dataset.roomStatusBadge;
            const isActive = badge.dataset.roomActive !== '0';
            const isOccupied = occupiedMap.has(String(roomId));

            if (!isActive) {
                badge.textContent = 'Dalam Perbaikan';
                badge.className = 'text-xs px-2 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-800 border border-amber-200';
                const li = badge.closest('li');
                if (li) {
                    li.classList.add('bg-amber-50/40');
                    li.classList.remove('bg-red-50/40');
                }
            } else if (isOccupied) {
                badge.textContent = 'Dipakai';
                badge.className = 'text-xs px-2 py-0.5 rounded-full font-semibold bg-red-100 text-red-700';
                const li = badge.closest('li');
                if (li) {
                    li.classList.add('bg-red-50/40');
                    li.classList.remove('bg-amber-50/40');
                }
            } else {
                badge.textContent = 'Tersedia';
                badge.className = 'text-xs px-2 py-0.5 rounded-full font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60';
                const li = badge.closest('li');
                if (li) {
                    li.classList.remove('bg-red-50/40', 'bg-amber-50/40');
                }
            }
        });

        document.querySelectorAll('[data-room-row]').forEach((row) => {
            const select = row.querySelector('[data-room-select]');
            const input = row.querySelector('[data-combobox-input]');
            const warningEl = row.querySelector('[data-room-occupied-warning]');
            const warningText = row.querySelector('[data-warning-text]');

            if (!select || !input || !warningEl) return;

            const selectedVal = select.value;
            if (selectedVal && occupiedMap.has(String(selectedVal))) {
                const info = occupiedMap.get(String(selectedVal));
                if (warningText) {
                    warningText.textContent = info?.eventName
                        ? `Ruangan ini berstatus Dipakai (${info.statusLabel}: "${info.eventName}" pada ${info.date} pukul ${info.time}).`
                        : 'Ruangan ini berstatus Dipakai (ada agenda yang sedang atau akan berlangsung).';
                }
                warningEl.classList.remove('hidden');
                input.classList.add('border-red-500', 'ring-1', 'ring-red-500');
            } else {
                warningEl.classList.add('hidden');
                input.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
            }
        });
    }

    function initRoomCombobox(comboboxEl) {
        if (!comboboxEl || comboboxEl.dataset.initialized === 'true') {
            return;
        }
        comboboxEl.dataset.initialized = 'true';

        const input = comboboxEl.querySelector('[data-combobox-input]');
        const toggleBtn = comboboxEl.querySelector('[data-combobox-toggle]');
        const arrow = comboboxEl.querySelector('[data-combobox-arrow]');
        const dropdown = comboboxEl.querySelector('[data-combobox-dropdown]');
        const list = comboboxEl.querySelector('[data-combobox-list]');
        const emptyEl = comboboxEl.querySelector('[data-combobox-empty]');
        const select = comboboxEl.querySelector('[data-room-select]');

        if (!input || !dropdown || !list || !select) {
            return;
        }

        function buildList() {
            list.innerHTML = '';
            Array.from(select.options).forEach((opt) => {
                if (opt.value === '') return;

                const li = document.createElement('li');
                li.dataset.value = opt.value;
                li.dataset.text = opt.text.trim();
                li.className = 'flex items-center justify-between px-3 py-2.5 cursor-pointer hover:bg-slate-100 transition-colors select-none text-slate-800';

                const nameWrap = document.createElement('div');
                nameWrap.className = 'flex items-center gap-1.5';
                const nameSpan = document.createElement('span');
                nameSpan.className = 'font-medium';
                nameSpan.textContent = opt.dataset.name || opt.text.split(' (')[0];
                nameWrap.append(nameSpan);

                const rightWrap = document.createElement('div');
                rightWrap.className = 'flex items-center gap-2';

                const statusSpan = document.createElement('span');
                statusSpan.dataset.roomStatusBadge = opt.value;
                statusSpan.dataset.roomActive = opt.dataset.isActive || '1';
                statusSpan.className = 'text-xs px-2 py-0.5 rounded-full font-medium';

                const capSpan = document.createElement('span');
                capSpan.className = 'text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full';
                capSpan.textContent = opt.dataset.capacity
                    ? `Kapasitas ${opt.dataset.capacity}`
                    : (opt.text.includes('Kapasitas') ? opt.text.split('(')[1]?.replace(')', '') : '');

                rightWrap.append(statusSpan, capSpan);
                li.append(nameWrap, rightWrap);

                li.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (li.dataset.disabled === 'true') return;
                    selectOption(opt.value, opt.text.trim());
                });

                list.append(li);
            });
        }

        function selectOption(val, text) {
            const targetOpt = Array.from(select.options).find((o) => String(o.value) === String(val));
            if (targetOpt && targetOpt.dataset.isActive === '0') {
                closeDropdown();
                if (typeof window.showRoomMaintenanceModal === 'function') {
                    window.showRoomMaintenanceModal({
                        roomName: targetOpt.dataset.name || targetOpt.text.split(' (')[0],
                        note: targetOpt.dataset.maintenanceNote || '',
                        picName: targetOpt.dataset.picName || '',
                        picPhone: targetOpt.dataset.picPhone || '',
                        picWa: targetOpt.dataset.picWa || '',
                    });
                }
                return;
            }

            select.value = val;
            input.value = text;
            input.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
            closeDropdown();
            select.dispatchEvent(new Event('change', { bubbles: true }));
            updateRoomAvailabilityBadges();
        }

        function openDropdown() {
            document.querySelectorAll('[data-combobox-dropdown]').forEach((d) => {
                if (d !== dropdown) {
                    d.classList.add('hidden');
                    d.closest('[data-room-combobox]')?.querySelector('[data-combobox-arrow]')?.classList.remove('rotate-180');
                }
            });

            dropdown.classList.remove('hidden');
            arrow?.classList.add('rotate-180');
            updateRoomAvailabilityBadges();
            filterList(input.value);
        }

        function closeDropdown() {
            dropdown.classList.add('hidden');
            arrow?.classList.remove('rotate-180');

            const selectedOpt = select.selectedOptions[0];
            if (selectedOpt && selectedOpt.value !== '') {
                input.value = selectedOpt.text.trim();
            } else {
                input.value = '';
                select.value = '';
            }
            updateRoomAvailabilityBadges();
        }

        function isOpen() {
            return !dropdown.classList.contains('hidden');
        }

        function filterList(query) {
            const q = (query || '').toLowerCase().trim();
            const selectedOpt = select.selectedOptions[0];
            const isFullMatch = selectedOpt && selectedOpt.text.trim().toLowerCase() === q;
            let matchCount = 0;

            Array.from(list.children).forEach((item) => {
                const itemFullText = (item.textContent || '').toLowerCase();
                const matches = isFullMatch || item.dataset.text.toLowerCase().includes(q) || itemFullText.includes(q);
                if (matches) {
                    item.classList.remove('hidden');
                    matchCount++;
                } else {
                    item.classList.add('hidden');
                }

                if (item.dataset.value === select.value) {
                    item.classList.add('bg-amber-50', 'text-amber-900', 'font-semibold');
                } else {
                    item.classList.remove('bg-amber-50', 'text-amber-900', 'font-semibold');
                }
            });

            if (emptyEl) {
                emptyEl.classList.toggle('hidden', matchCount > 0);
            }
        }

        buildList();
        const initialOpt = select.selectedOptions[0];
        if (initialOpt && initialOpt.value !== '') {
            input.value = initialOpt.text.trim();
        }

        input.addEventListener('focus', () => {
            openDropdown();
            input.select();
        });

        input.addEventListener('input', () => {
            if (!isOpen()) openDropdown();
            filterList(input.value);
            if (input.value.trim() === '') {
                select.value = '';
                select.dispatchEvent(new Event('change', { bubbles: true }));
                updateRoomAvailabilityBadges();
            }
        });

        toggleBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            if (isOpen()) {
                closeDropdown();
            } else {
                openDropdown();
                input.focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!isOpen()) {
                    openDropdown();
                } else {
                    const visibleItems = Array.from(list.children).filter(el => !el.classList.contains('hidden') && el.dataset.disabled !== 'true');
                    if (visibleItems.length > 0) {
                        visibleItems[0].scrollIntoView({ block: 'nearest' });
                    }
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            } else if (e.key === 'Enter') {
                if (isOpen()) {
                    e.preventDefault();
                    const visibleItems = Array.from(list.children).filter(el => !el.classList.contains('hidden') && el.dataset.disabled !== 'true');
                    if (visibleItems.length > 0) {
                        selectOption(visibleItems[0].dataset.value, visibleItems[0].dataset.text);
                    }
                }
            }
        });
    }

    function updateRoomSelections() {
        if (!roomSelectionList || !addRoomButton) {
            return;
        }

        const roomSelects = Array.from(roomSelectionList.querySelectorAll('[data-room-select]'));

        roomSelects.forEach((select) => {
            const selectedInOtherRows = new Set(
                roomSelects.filter((otherSelect) => otherSelect !== select).map((otherSelect) => otherSelect.value)
            );

            Array.from(select.options).forEach((option) => {
                option.disabled = option.value !== '' && selectedInOtherRows.has(option.value);
            });

            const combobox = select.closest('[data-room-combobox]');
            if (combobox) {
                const listItems = combobox.querySelectorAll('[data-combobox-list] li');
                listItems.forEach((li) => {
                    const isDisabled = selectedInOtherRows.has(li.dataset.value);
                    li.dataset.disabled = isDisabled ? 'true' : 'false';
                    li.classList.toggle('opacity-40', isDisabled);
                    li.classList.toggle('cursor-not-allowed', isDisabled);
                    li.classList.toggle('bg-slate-50', isDisabled);

                    let badge = li.querySelector('[data-selected-badge]');
                    if (isDisabled) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.dataset.selectedBadge = 'true';
                            badge.className = 'text-xs text-red-500 font-medium ml-2';
                            badge.textContent = '(Sudah dipilih)';
                            li.firstElementChild?.append(badge);
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                });
            }
        });

        roomSelectionList.querySelectorAll('[data-remove-room]').forEach((button) => {
            button.classList.toggle('hidden', roomSelects.length <= 1);
        });

        addRoomButton.disabled = roomSelects.length >= {{ $rooms->count() }};
        addRoomButton.classList.toggle('cursor-not-allowed', addRoomButton.disabled);
        addRoomButton.classList.toggle('opacity-50', addRoomButton.disabled);
    }

    function updateRowNumbers() {
        roomSelectionList.querySelectorAll('[data-room-row]').forEach((row, idx) => {
            const label = row.querySelector('label');
            if (label) {
                label.textContent = `Ruangan ${idx + 1}`;
            }
        });
    }

    // Initialize existing comboboxes
    document.querySelectorAll('[data-room-combobox]').forEach(initRoomCombobox);

    roomSelectionList?.addEventListener('change', () => {
        updateRoomSelections();
        updateRoomAvailabilityBadges();
    });

    roomSelectionList?.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-room]');
        if (removeButton && roomSelectionList.querySelectorAll('[data-room-row]').length > 1) {
            removeButton.closest('[data-room-row]').remove();
            updateRowNumbers();
            updateRoomSelections();
            updateRoomAvailabilityBadges();
        }
    });

    addRoomButton?.addEventListener('click', () => {
        const roomCount = roomSelectionList.querySelectorAll('[data-room-row]').length;
        if (roomCount >= {{ $rooms->count() }}) {
            return;
        }

        const row = roomSelectTemplate.content.firstElementChild.cloneNode(true);
        const combobox = row.querySelector('[data-room-combobox]');
        const select = row.querySelector('[data-room-select]');
        const label = row.querySelector('[data-room-label]');
        const input = row.querySelector('[data-combobox-input]');
        const uniqueId = `room-select-${Date.now()}`;

        select.id = uniqueId;
        input.id = `combobox-input-${Date.now()}`;
        label.htmlFor = input.id;
        label.textContent = `Ruangan ${roomCount + 1}`;

        roomSelectionList.append(row);
        initRoomCombobox(combobox);
        updateRoomSelections();
        updateRoomAvailabilityBadges();
        input.focus();
    });

    document.addEventListener('click', (e) => {
        document.querySelectorAll('[data-room-combobox]').forEach((combobox) => {
            if (!combobox.contains(e.target)) {
                const dropdown = combobox.querySelector('[data-combobox-dropdown]');
                const arrow = combobox.querySelector('[data-combobox-arrow]');
                const select = combobox.querySelector('[data-room-select]');
                const input = combobox.querySelector('[data-combobox-input]');

                dropdown?.classList.add('hidden');
                arrow?.classList.remove('rotate-180');

                if (select && input) {
                    const selectedOpt = select.selectedOptions[0];
                    if (selectedOpt && selectedOpt.value !== '') {
                        input.value = selectedOpt.text.trim();
                    } else if (!select.value) {
                        input.value = '';
                    }
                }
            }
        });
    });

    document.getElementById('guru-event-form')?.addEventListener('submit', (e) => {
        const occupiedMap = getOccupiedRoomIds();
        const roomSelects = document.querySelectorAll('[data-room-select]');
        let firstInvalid = null;
        let hasOccupiedError = false;

        roomSelects.forEach((select) => {
            const input = select.closest('[data-room-combobox]')?.querySelector('[data-combobox-input]');
            if (!select.value) {
                if (input) {
                    input.classList.add('border-red-500', 'ring-1', 'ring-red-500');
                    if (!firstInvalid) firstInvalid = input;
                }
            } else if (occupiedMap.has(String(select.value))) {
                hasOccupiedError = true;
                if (input) {
                    input.classList.add('border-red-500', 'ring-1', 'ring-red-500');
                    if (!firstInvalid) firstInvalid = input;
                }
            }
        });

        if (firstInvalid) {
            e.preventDefault();
            firstInvalid.focus();
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (hasOccupiedError) {
                alert('Ruangan yang dipilih sedang dipakai pada jadwal tersebut. Silakan pilih ruangan lain.');
            }
        }
    });

    updateRoomSelections();

    const bookingStartDate = document.getElementById('date');
    const bookingEndDate = document.getElementById('end_date');

    bookingStartDate?.addEventListener('change', () => {
        bookingEndDate.min = bookingStartDate.value;
        if (bookingEndDate.value && bookingEndDate.value < bookingStartDate.value) {
            bookingEndDate.value = bookingStartDate.value;
        }
        updateRoomAvailabilityBadges();
    });
    bookingStartDate?.addEventListener('input', updateRoomAvailabilityBadges);

    bookingEndDate?.addEventListener('change', updateRoomAvailabilityBadges);
    bookingEndDate?.addEventListener('input', updateRoomAvailabilityBadges);

    const startSession = document.getElementById('start_session');
    const endSession = document.getElementById('end_session');
    const startSessionTime = document.getElementById('start_session_time');
    const endSessionTime = document.getElementById('end_session_time');

    function updateSessionTime(select, detail, label) {
        const selectedOption = select.selectedOptions[0];
        const sessionStart = selectedOption?.dataset.sessionStart;
        const sessionEnd = selectedOption?.dataset.sessionEnd;
        detail.textContent = sessionStart && sessionEnd
            ? `Waktu sesi ${selectedOption.value}: ${sessionStart.replace(':', '.')} - ${sessionEnd.replace(':', '.')}`
            : `Pilih sesi ${label.toLowerCase()} untuk melihat jam mulai dan selesai sesi.`;
    }

    startSession?.addEventListener('change', () => {
        updateSessionTime(startSession, startSessionTime, 'mulai');
        updateRoomAvailabilityBadges();
    });
    endSession?.addEventListener('change', () => {
        updateSessionTime(endSession, endSessionTime, 'selesai');
        updateRoomAvailabilityBadges();
    });
    updateSessionTime(startSession, startSessionTime, 'mulai');
    updateSessionTime(endSession, endSessionTime, 'selesai');

    updateRoomAvailabilityBadges();
    setInterval(updateRoomAvailabilityBadges, 30000);
</script>
