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
                    <form action="{{ route('events.store') }}" method="POST" class="event-form" data-confirm-action="mengirim pengajuan agenda" data-confirm-item="Pengajuan agenda baru">
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
                                        <p class="event-form-help">Pilih satu ruangan. Tekan “Tambah Ruang” jika perlu meminjam ruangan lain juga. Pengajuan ditolak jika ada ruangan yang sudah dipakai pada jadwal tersebut.</p>
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
                                                                    <option value="{{ $room->id }}" data-name="{{ $room->room_name }}" data-capacity="{{ $room->capacity }}" @selected((string) $selectedRoomId === (string) $room->id)>
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
                                                                <option value="{{ $room->id }}" data-name="{{ $room->room_name }}" data-capacity="{{ $room->capacity }}">
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
                                                </div>
                                                <button type="button" data-remove-room class="rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                                            </div>
                                        </template>
                                    </fieldset>
                                </div>

                                <div class="event-form-field">
                                    <label for="date">Tanggal Mulai</label>
                                    <input type="date" id="date" name="date" value="{{ old('date') }}" min="{{ now()->addDay()->toDateString() }}" required>
                                    <small class="event-form-help">Pengajuan wajib dilakukan paling lambat H-1.</small>
                                </div>

                                <div class="event-form-field">
                                    <label for="end_date">Tanggal Selesai</label>
                                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date', old('date')) }}" min="{{ old('date', now()->addDay()->toDateString()) }}" required>
                                    <small class="event-form-help">Jam sesi yang dipilih berlaku setiap hari selama rentang ini.</small>
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
                            <a href="{{ route('dashboard') }}" class="event-button event-button-secondary">Batal</a>
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
</x-app-layout>

<script>
    const roomSelectionList = document.getElementById('room-selection-list');
    const roomSelectTemplate = document.getElementById('room-select-template');
    const addRoomButton = document.getElementById('add-room-button');

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

                const capSpan = document.createElement('span');
                capSpan.className = 'text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full';
                capSpan.textContent = opt.dataset.capacity
                    ? `Kapasitas ${opt.dataset.capacity}`
                    : (opt.text.includes('Kapasitas') ? opt.text.split('(')[1]?.replace(')', '') : '');

                li.append(nameWrap, capSpan);

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
                const matches = isFullMatch || item.dataset.text.toLowerCase().includes(q);
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

    roomSelectionList?.addEventListener('change', updateRoomSelections);
    roomSelectionList?.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-room]');
        if (removeButton && roomSelectionList.querySelectorAll('[data-room-row]').length > 1) {
            removeButton.closest('[data-room-row]').remove();
            updateRowNumbers();
            updateRoomSelections();
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

    document.querySelector('form')?.addEventListener('submit', (e) => {
        const roomSelects = document.querySelectorAll('[data-room-select]');
        let firstInvalid = null;
        roomSelects.forEach((select) => {
            if (!select.value) {
                const input = select.closest('[data-room-combobox]')?.querySelector('[data-combobox-input]');
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
    });

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

    startSession?.addEventListener('change', () => updateSessionTime(startSession, startSessionTime, 'mulai'));
    endSession?.addEventListener('change', () => updateSessionTime(endSession, endSessionTime, 'selesai'));
    updateSessionTime(startSession, startSessionTime, 'mulai');
    updateSessionTime(endSession, endSessionTime, 'selesai');
</script>
