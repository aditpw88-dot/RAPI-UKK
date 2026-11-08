<!-- Modal Window Tambah Agenda (Backdrop Blur) -->
<div id="create-event-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="create-event-modal-title">
    <!-- Backdrop Blur Layer -->
    <div id="create-event-modal-backdrop" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity duration-200" style="backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"></div>

    <!-- Modal Card Content -->
    <div class="relative z-10 w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all my-auto max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex items-start justify-between border-b border-gray-100 pb-4">
            <div>
                <span class="inline-block rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 uppercase tracking-wider">Agenda Baru</span>
                <h3 id="create-event-modal-title" class="mt-1 text-lg font-bold text-slate-900">Tambah Agenda</h3>
                <p class="mt-0.5 text-xs text-slate-500">Agenda yang dibuat Waka/Admin akan otomatis disetujui.</p>
            </div>
            <button type="button" id="create-event-modal-close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition focus:outline-none" aria-label="Tutup modal">
                <svg width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        @php($selectedRoomIds = is_array(old('room_ids')) ? array_values(old('room_ids')) : (old('room_id', $selectedRoom?->id ?? null) ? [old('room_id', $selectedRoom?->id ?? null)] : ['']))
        <form id="create-event-form" method="POST" action="{{ route('admin.events.store') }}" class="space-y-4 pt-4" data-confirm-action="menambahkan agenda" data-confirm-item="Agenda baru" data-confirm-title="Konfirmasi Penambahan Agenda" data-confirm-message="Apakah Anda yakin ingin menambahkan agenda ini?">
            @csrf

            <div>
                <label for="event_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Nama Agenda</label>
                <input id="event_name" name="event_name" value="{{ old('event_name') }}" required maxlength="255" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800" placeholder="Contoh: Rapat Koordinasi Kesiswaan">
            </div>

            <div>
                <label for="event_type" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Jenis Agenda</label>
                <select id="event_type" name="event_type" required class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    @foreach (config('event_types') as $value => $type)
                        <option value="{{ $value }}" @selected(old('event_type', 'lainnya') === $value)>{{ $type['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <fieldset>
                <legend class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">Ruangan</legend>
                <div id="room-selection-list" class="space-y-3">
                    @foreach ($selectedRoomIds as $index => $selectedRoomId)
                        @php($currentRoom = $rooms->firstWhere('id', $selectedRoomId))
                        <div data-room-row class="flex items-end gap-2">
                            <div class="min-w-0 flex-1">
                                <label for="room-combobox-input-{{ $index }}" class="mb-1 block text-xs font-medium text-slate-600">Ruangan {{ $index + 1 }}</label>
                                <div data-room-combobox class="relative">
                                    <div class="relative">
                                        <input type="text"
                                               id="room-combobox-input-{{ $index }}"
                                               data-combobox-input
                                               autocomplete="off"
                                               placeholder="Pilih atau ketik nama ruangan..."
                                               value="{{ $currentRoom ? $currentRoom->room_name . ' (Kapasitas ' . $currentRoom->capacity . ')' : '' }}"
                                               class="w-full rounded-lg border-gray-300 pr-10 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                                        <button type="button"
                                                data-combobox-toggle
                                                tabindex="-1"
                                                aria-label="Buka pilihan ruangan"
                                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 focus:outline-none">
                                            <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;min-height:16px;" class="transition-transform duration-200" data-combobox-arrow fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>
                                    <select id="room-select-{{ $index }}" name="room_ids[]" data-room-select required class="sr-only" tabindex="-1" style="position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px;">
                                        <option value="">Pilih ruangan</option>
                                        @foreach ($rooms as $room)
                                            <option value="{{ $room->id }}" data-name="{{ $room->room_name }}" data-capacity="{{ $room->capacity }}" @selected((string) $selectedRoomId === (string) $room->id)>
                                                {{ $room->room_name }} (Kapasitas {{ $room->capacity }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div data-combobox-dropdown class="hidden absolute z-50 left-0 right-0 mt-1 max-h-52 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl py-1 text-sm">
                                        <ul data-combobox-list role="listbox" class="divide-y divide-gray-50"></ul>
                                        <div data-combobox-empty class="hidden px-4 py-3 text-center text-xs text-slate-500">
                                            Tidak ada ruangan yang cocok dengan pencarian.
                                        </div>
                                    </div>
                                </div>
                                <p data-room-occupied-warning class="hidden mt-1 text-xs font-semibold text-red-600 flex items-center gap-1">
                                    <svg width="14" height="14" style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    <span data-warning-text>Ruangan ini sedang dipakai pada jadwal tersebut.</span>
                                </p>
                            </div>
                            <button type="button" data-remove-room class="hidden rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="add-room-button" @disabled($rooms->isEmpty()) class="mt-2.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">+ Tambah Ruang Lain</button>
                <template id="room-select-template">
                    <div data-room-row class="flex items-end gap-2">
                        <div class="min-w-0 flex-1">
                            <label data-room-label class="mb-1 block text-xs font-medium text-slate-600">Ruangan</label>
                            <div data-room-combobox class="relative">
                                <div class="relative">
                                    <input type="text"
                                           data-combobox-input
                                           autocomplete="off"
                                           placeholder="Pilih atau ketik nama ruangan..."
                                           class="w-full rounded-lg border-gray-300 pr-10 text-sm focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                                    <button type="button"
                                            data-combobox-toggle
                                            tabindex="-1"
                                            aria-label="Buka pilihan ruangan"
                                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 focus:outline-none">
                                        <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;min-height:16px;" class="transition-transform duration-200" data-combobox-arrow fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                                <select name="room_ids[]" data-room-select required class="sr-only" tabindex="-1" style="position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px;">
                                    <option value="">Pilih ruangan</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}" data-name="{{ $room->room_name }}" data-capacity="{{ $room->capacity }}">
                                            {{ $room->room_name }} (Kapasitas {{ $room->capacity }})
                                        </option>
                                    @endforeach
                                </select>
                                <div data-combobox-dropdown class="hidden absolute z-50 left-0 right-0 mt-1 max-h-52 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl py-1 text-sm">
                                    <ul data-combobox-list role="listbox" class="divide-y divide-gray-50"></ul>
                                    <div data-combobox-empty class="hidden px-4 py-3 text-center text-xs text-slate-500">
                                        Tidak ada ruangan yang cocok dengan pencarian.
                                    </div>
                                </div>
                            </div>
                            <p data-room-occupied-warning class="hidden mt-1 text-xs font-semibold text-red-600 flex items-center gap-1">
                                <svg width="14" height="14" style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                <span data-warning-text>Ruangan ini sedang dipakai pada jadwal tersebut.</span>
                            </p>
                        </div>
                        <button type="button" data-remove-room class="rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                    </div>
                </template>
                @if ($rooms->isEmpty())
                    <p class="mt-2 text-xs text-amber-700">Belum ada ruangan aktif. Aktifkan atau tambahkan ruang terlebih dahulu.</p>
                @endif
            </fieldset>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Tanggal Mulai</label>
                    <input id="date" name="date" type="date" value="{{ old('date', request('date')) }}" min="{{ now()->toDateString() }}" required class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    <p class="mt-1 text-[11px] text-slate-500">Admin/Waka boleh meminjam mulai hari ini.</p>
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Tanggal Selesai</label>
                    <input id="end_date" name="end_date" type="date" value="{{ old('end_date', old('date', request('date'))) }}" min="{{ old('date', request('date', now()->toDateString())) }}" required class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="start_session" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Sesi Mulai</label>
                    <select id="start_session" name="start_session" required class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                        <option value="">Pilih sesi mulai</option>
                        @foreach (config('schedule.sessions') as $number => $times)
                            <option value="{{ $number }}" data-session-start="{{ $times['start_time'] }}" data-session-end="{{ $times['end_time'] }}" @selected(old('start_session') == $number)>Sesi {{ $number }} ({{ $times['start_time'] }} - {{ $times['end_time'] }})</option>
                        @endforeach
                    </select>
                    <p id="start_session_time" class="mt-1 text-[11px] text-slate-500">Pilih sesi mulai untuk melihat jam.</p>
                </div>
                <div>
                    <label for="end_session" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Sesi Selesai</label>
                    <select id="end_session" name="end_session" required class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                        <option value="">Pilih sesi selesai</option>
                        @foreach (config('schedule.sessions') as $number => $times)
                            @continue($number === 1)
                            <option value="{{ $number }}" data-session-start="{{ $times['start_time'] }}" data-session-end="{{ $times['end_time'] }}" @selected(old('end_session') == $number)>Sesi {{ $number }} ({{ $times['start_time'] }} - {{ $times['end_time'] }})</option>
                        @endforeach
                    </select>
                    <p id="end_session_time" class="mt-1 text-[11px] text-slate-500">Pilih sesi selesai untuk melihat jam.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 mt-6">
                <button type="button" id="create-event-modal-cancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50 transition">
                    Batal
                </button>
                <button type="submit" @disabled($rooms->isEmpty()) class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700 shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Simpan Agenda
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        const createEventModal = document.getElementById('create-event-modal');
        const openCreateEventBtn = document.getElementById('open-create-event-btn');
        const closeCreateEventBtn = document.getElementById('create-event-modal-close');
        const cancelCreateEventBtn = document.getElementById('create-event-modal-cancel');
        const createEventBackdrop = document.getElementById('create-event-modal-backdrop');
        const eventNameInput = document.getElementById('event_name');

        function openCreateEventModal() {
            if (!createEventModal) return;
            createEventModal.classList.remove('hidden');
            createEventModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => eventNameInput?.focus(), 50);
            updateRoomAvailabilityBadges();
        }

        function closeCreateEventModal() {
            if (!createEventModal) return;
            createEventModal.classList.add('hidden');
            createEventModal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        openCreateEventBtn?.addEventListener('click', openCreateEventModal);
        closeCreateEventBtn?.addEventListener('click', closeCreateEventModal);
        cancelCreateEventBtn?.addEventListener('click', closeCreateEventModal);
        createEventBackdrop?.addEventListener('click', closeCreateEventModal);

        @if ($errors->any() && old('event_name'))
            openCreateEventModal();
        @endif
        if (new URLSearchParams(window.location.search).get('create') === '1') {
            openCreateEventModal();
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && createEventModal && !createEventModal.classList.contains('hidden')) {
                const confirmationDialog = document.getElementById('action-confirmation');
                if (!confirmationDialog || !confirmationDialog.open) {
                    closeCreateEventModal();
                }
            }
        });

        const bookedEvents = @json($bookedEvents ?? []);
        const sessionTimes = @json(config('schedule.sessions'));

        const roomSelectionList = document.getElementById('room-selection-list');
        const roomSelectTemplate = document.getElementById('room-select-template');
        const addRoomButton = document.getElementById('add-room-button');

        function getEventLifecycle(ev) {
            if (ev.lifecycle_status === 'rejected') return 'rejected';

            const now = new Date();
            const startDate = ev.date;
            const endDate = ev.end_date || ev.date;
            const startTime = ev.start_time || '00:00';
            const endTime = ev.end_time || '23:59';

            const startDt = new Date(`${startDate}T${startTime}:00`);
            const endDt = new Date(`${endDate}T${endTime}:00`);

            if (now >= endDt) return 'completed';
            if (now < startDt) return 'upcoming';
            return 'in_progress';
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
                    if (lifecycle === 'completed' || lifecycle === 'rejected') return;

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
                bookedEvents.forEach((ev) => {
                    const lifecycle = getEventLifecycle(ev);
                    if (lifecycle === 'completed' || lifecycle === 'rejected') return;

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
                const isOccupied = occupiedMap.has(String(roomId));

                if (isOccupied) {
                    badge.textContent = 'Dipakai';
                    badge.className = 'text-xs px-2 py-0.5 rounded-full font-semibold bg-red-100 text-red-700';
                    const li = badge.closest('li');
                    if (li) li.classList.add('bg-red-50/40');
                } else {
                    badge.textContent = 'Tersedia';
                    badge.className = 'text-xs px-2 py-0.5 rounded-full font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60';
                    const li = badge.closest('li');
                    if (li) li.classList.remove('bg-red-50/40');
                }
            });

            document.querySelectorAll('#create-event-modal [data-room-row]').forEach((row) => {
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
            if (!comboboxEl || comboboxEl.dataset.initialized === 'true') return;
            comboboxEl.dataset.initialized = 'true';

            const input = comboboxEl.querySelector('[data-combobox-input]');
            const toggleBtn = comboboxEl.querySelector('[data-combobox-toggle]');
            const arrow = comboboxEl.querySelector('[data-combobox-arrow]');
            const dropdown = comboboxEl.querySelector('[data-combobox-dropdown]');
            const list = comboboxEl.querySelector('[data-combobox-list]');
            const emptyEl = comboboxEl.querySelector('[data-combobox-empty]');
            const select = comboboxEl.querySelector('[data-room-select]');

            if (!input || !dropdown || !list || !select) return;

            function buildList() {
                list.innerHTML = '';
                Array.from(select.options).forEach((opt) => {
                    if (opt.value === '') return;

                    const li = document.createElement('li');
                    li.dataset.value = opt.value;
                    li.dataset.text = opt.text.trim();
                    li.className = 'flex items-center justify-between px-3 py-2.5 cursor-pointer hover:bg-slate-100 transition-colors select-none text-slate-800 text-xs sm:text-sm';

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
                select.value = val;
                input.value = text;
                input.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
                closeDropdown();
                select.dispatchEvent(new Event('change', { bubbles: true }));
                updateRoomAvailabilityBadges();
            }

            function openDropdown() {
                document.querySelectorAll('#create-event-modal [data-combobox-dropdown]').forEach((d) => {
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

                if (emptyEl) emptyEl.classList.toggle('hidden', matchCount > 0);
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
                if (isOpen()) closeDropdown();
                else { openDropdown(); input.focus(); }
            });
        }

        function updateRoomSelections() {
            if (!roomSelectionList || !addRoomButton) return;
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
                    });
                }
            });

            roomSelectionList.querySelectorAll('[data-remove-room]').forEach((button) => {
                button.classList.toggle('hidden', roomSelects.length <= 1);
            });

            addRoomButton.disabled = roomSelects.length >= {{ $rooms->count() }};
        }

        function updateRowNumbers() {
            roomSelectionList?.querySelectorAll('[data-room-row]').forEach((row, idx) => {
                const label = row.querySelector('label');
                if (label) label.textContent = `Ruangan ${idx + 1}`;
            });
        }

        document.querySelectorAll('#create-event-modal [data-room-combobox]').forEach(initRoomCombobox);

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
            if (roomCount >= {{ $rooms->count() }}) return;

            const row = roomSelectTemplate.content.firstElementChild.cloneNode(true);
            const combobox = row.querySelector('[data-room-combobox]');
            const select = row.querySelector('[data-room-select]');
            const label = row.querySelector('[data-room-label]');
            const input = row.querySelector('[data-combobox-input]');
            const uniqueId = `modal-room-select-${Date.now()}`;

            select.id = uniqueId;
            input.id = `modal-combobox-input-${Date.now()}`;
            label.htmlFor = input.id;
            label.textContent = `Ruangan ${roomCount + 1}`;

            roomSelectionList.append(row);
            initRoomCombobox(combobox);
            updateRoomSelections();
            updateRoomAvailabilityBadges();
            input.focus();
        });

        document.addEventListener('click', (e) => {
            document.querySelectorAll('#create-event-modal [data-room-combobox]').forEach((combobox) => {
                if (!combobox.contains(e.target)) {
                    combobox.querySelector('[data-combobox-dropdown]')?.classList.add('hidden');
                    combobox.querySelector('[data-combobox-arrow]')?.classList.remove('rotate-180');
                }
            });
        });

        document.getElementById('create-event-form')?.addEventListener('submit', (e) => {
            const occupiedMap = getOccupiedRoomIds();
            const roomSelects = document.querySelectorAll('#create-event-modal [data-room-select]');
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
                e.stopImmediatePropagation();
                e.stopPropagation();
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (hasOccupiedError) {
                    alert('Ruangan yang dipilih sedang dipakai pada jadwal tersebut. Silakan pilih ruangan lain.');
                }
            }
        });

        updateRoomSelections();

        const adminStartDate = document.getElementById('date');
        const adminEndDate = document.getElementById('end_date');

        adminStartDate?.addEventListener('change', () => {
            if (adminEndDate) {
                adminEndDate.min = adminStartDate.value;
                if (adminEndDate.value && adminEndDate.value < adminStartDate.value) {
                    adminEndDate.value = adminStartDate.value;
                }
            }
            updateRoomAvailabilityBadges();
        });
        adminStartDate?.addEventListener('input', updateRoomAvailabilityBadges);
        adminEndDate?.addEventListener('change', updateRoomAvailabilityBadges);
        adminEndDate?.addEventListener('input', updateRoomAvailabilityBadges);

        const startSession = document.getElementById('start_session');
        const endSession = document.getElementById('end_session');
        const startSessionTime = document.getElementById('start_session_time');
        const endSessionTime = document.getElementById('end_session_time');

        function updateSessionTime(select, detail, label) {
            const selectedOption = select?.selectedOptions[0];
            const sessionStart = selectedOption?.dataset.sessionStart;
            const sessionEnd = selectedOption?.dataset.sessionEnd;
            if (detail) {
                detail.textContent = sessionStart && sessionEnd
                    ? `Waktu sesi ${selectedOption.value}: ${sessionStart.replace(':', '.')} - ${sessionEnd.replace(':', '.')}`
                    : `Pilih sesi ${label.toLowerCase()} untuk melihat jam mulai dan selesai sesi.`;
            }
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
    })();
</script>

