@php
    $prefix = $prefix ?? 'wa';
    $groupedTeachers = $teachersByMapel ?? collect();
    $oldSelectedIds = collect(old('notify_teacher_ids', []))->map(fn ($id) => (int) $id)->all();
    $totalTeachersCount = $groupedTeachers->flatten(1)->count();
@endphp

<div data-wa-selector-root="{{ $prefix }}" class="rounded-xl border border-emerald-200/80 bg-emerald-50/30 p-4 transition-all">
    <!-- Top Header & Toggle -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-xs">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824zm-3.423-14.416c-6.627 0-12 5.373-12 12 0 2.625.846 5.059 2.284 7.033l-1.492 5.448 5.581-1.464c1.907 1.252 4.186 1.983 6.627 1.983 6.627 0 12-5.373 12-12s-5.373-12-12-12z"/>
                </svg>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900">Pengingat WhatsApp ke Guru (Per Mapel)</h4>
                    <span data-wa-selected-badge class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 border border-emerald-300">
                        0 Guru Dipilih
                    </span>
                </div>
                <p class="mt-0.5 text-[11px] sm:text-xs text-slate-600">
                    Pesan pengingat otomatis akan dikirimkan ke guru bersangkutan <strong>1 jam sebelum agenda dimulai</strong>.
                </p>
            </div>
        </div>

        <button type="button"
                data-wa-collapse-toggle
                class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-50 transition focus:outline-none">
            <span data-wa-collapse-label>Pilih Guru / Mapel</span>
            <svg data-wa-collapse-arrow class="h-4 w-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </div>

    <!-- Collapsible Body -->
    <div data-wa-collapsible-body class="{{ count($oldSelectedIds) > 0 ? '' : 'hidden' }} mt-4 space-y-3 border-t border-emerald-200/70 pt-3.5">
        @if ($groupedTeachers->isEmpty())
            <p class="text-xs text-slate-500 italic py-2">Belum ada data user dengan role Guru.</p>
        @else
            <!-- Toolbar: Search & Global Select All / Reset -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </span>
                    <input type="text"
                           data-wa-search-input
                           placeholder="Cari mata pelajaran atau nama guru..."
                           class="w-full rounded-lg border-gray-300 bg-white pl-8 pr-3 py-1.5 text-xs focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button"
                            data-wa-select-all
                            class="rounded-lg border border-emerald-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-emerald-800 hover:bg-emerald-100 transition">
                        Centang Semua Guru ({{ $totalTeachersCount }})
                    </button>
                    <button type="button"
                            data-wa-clear-all
                            class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Reset
                    </button>
                </div>
            </div>

            <!-- Grouped List by Mapel -->
            <div data-wa-mapel-list class="max-h-64 overflow-y-auto space-y-2 pr-1">
                @foreach ($groupedTeachers as $mapelName => $teachers)
                    @php
                        $groupIndex = $loop->index;
                        $validTeachersInGroup = $teachers->filter(fn ($t) => trim((string) $t->phone_number) !== '');
                    @endphp
                    <div data-wa-mapel-card
                         data-mapel-name="{{ strtolower($mapelName) }}"
                         class="rounded-lg border border-slate-200 bg-white overflow-hidden shadow-2xs transition">
                        <!-- Mapel Group Header -->
                        <div class="flex items-center justify-between gap-2 bg-slate-50/90 px-3 py-2 hover:bg-slate-100/80 transition">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none min-w-0 flex-1">
                                <input type="checkbox"
                                       data-wa-mapel-checkbox="{{ $groupIndex }}"
                                       @disabled($validTeachersInGroup->isEmpty())
                                       class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 disabled:opacity-40">
                                <span class="text-xs font-bold text-slate-800 truncate" title="{{ $mapelName }}">
                                    {{ $mapelName }}
                                </span>
                                <span data-wa-mapel-count="{{ $groupIndex }}" class="shrink-0 rounded-full bg-slate-200/80 px-2 py-0.5 text-[10px] font-semibold text-slate-700">
                                    0/{{ $teachers->count() }} Guru
                                </span>
                            </label>

                            <button type="button"
                                    data-wa-mapel-toggle="{{ $groupIndex }}"
                                    class="inline-flex items-center gap-1 rounded px-2 py-1 text-[11px] font-semibold text-slate-500 hover:bg-slate-200/60 hover:text-slate-800 focus:outline-none">
                                <span data-wa-mapel-toggle-text="{{ $groupIndex }}">Lihat Guru</span>
                                <svg data-wa-mapel-arrow="{{ $groupIndex }}" class="h-3.5 w-3.5 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Teachers List inside Mapel -->
                        <div data-wa-mapel-items="{{ $groupIndex }}" class="hidden divide-y divide-slate-100 border-t border-slate-100 bg-white px-3 py-1">
                            @foreach ($teachers as $teacher)
                                @php
                                    $hasPhone = trim((string) $teacher->phone_number) !== '';
                                    $isChecked = $hasPhone && in_array((int) $teacher->id, $oldSelectedIds, true);
                                @endphp
                                <label data-wa-teacher-row
                                       data-teacher-name="{{ strtolower($teacher->fullname . ' ' . $teacher->username) }}"
                                       class="flex items-center justify-between gap-2 py-2 {{ $hasPhone ? 'cursor-pointer hover:bg-emerald-50/40' : 'opacity-50 cursor-not-allowed' }} px-1.5 rounded transition select-none">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <input type="checkbox"
                                               name="notify_teacher_ids[]"
                                               value="{{ $teacher->id }}"
                                               data-wa-teacher-checkbox="{{ $groupIndex }}"
                                               @checked($isChecked)
                                               @disabled(! $hasPhone)
                                               class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 disabled:opacity-40">
                                        <span class="text-xs font-medium text-slate-800 truncate">
                                            {{ $teacher->fullname }}
                                        </span>
                                    </div>

                                    @if ($hasPhone)
                                        <span class="shrink-0 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-mono font-medium text-emerald-700 border border-emerald-200/60">
                                            {{ $teacher->phone_number }}
                                        </span>
                                    @else
                                        <span class="shrink-0 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 border border-amber-200/60">
                                            Tanpa No. WA
                                        </span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div data-wa-empty-search class="hidden py-4 text-center text-xs text-slate-500">
                    Tidak ada mata pelajaran atau nama guru yang cocok dengan pencarian.
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    (function () {
        const root = document.querySelector('[data-wa-selector-root="{{ $prefix }}"]');
        if (!root || root.dataset.initialized === 'true') return;
        root.dataset.initialized = 'true';

        const collapseToggle = root.querySelector('[data-wa-collapse-toggle]');
        const collapseBody = root.querySelector('[data-wa-collapsible-body]');
        const collapseArrow = root.querySelector('[data-wa-collapse-arrow]');
        const collapseLabel = root.querySelector('[data-wa-collapse-label]');
        const selectedBadge = root.querySelector('[data-wa-selected-badge]');
        const searchInput = root.querySelector('[data-wa-search-input]');
        const selectAllBtn = root.querySelector('[data-wa-select-all]');
        const clearAllBtn = root.querySelector('[data-wa-clear-all]');
        const emptySearchEl = root.querySelector('[data-wa-empty-search]');

        const mapelCards = Array.from(root.querySelectorAll('[data-wa-mapel-card]'));
        const allTeacherCheckboxes = Array.from(root.querySelectorAll('[data-wa-teacher-checkbox]'));

        function updateCountsAndStates() {
            let totalChecked = 0;

            mapelCards.forEach((card) => {
                const groupCheckbox = card.querySelector('[data-wa-mapel-checkbox]');
                if (!groupCheckbox) return;
                const groupIdx = groupCheckbox.dataset.waMapelCheckbox;
                const countBadge = card.querySelector(`[data-wa-mapel-count="${groupIdx}"]`);
                const teacherBoxes = Array.from(card.querySelectorAll(`[data-wa-teacher-checkbox="${groupIdx}"]`));
                const enabledBoxes = teacherBoxes.filter((cb) => !cb.disabled);
                const checkedBoxes = enabledBoxes.filter((cb) => cb.checked);

                totalChecked += checkedBoxes.length;

                if (enabledBoxes.length === 0) {
                    groupCheckbox.checked = false;
                    groupCheckbox.indeterminate = false;
                } else if (checkedBoxes.length === 0) {
                    groupCheckbox.checked = false;
                    groupCheckbox.indeterminate = false;
                } else if (checkedBoxes.length === enabledBoxes.length) {
                    groupCheckbox.checked = true;
                    groupCheckbox.indeterminate = false;
                } else {
                    groupCheckbox.checked = false;
                    groupCheckbox.indeterminate = true;
                }

                if (countBadge) {
                    countBadge.textContent = `${checkedBoxes.length}/${teacherBoxes.length} Guru`;
                    if (checkedBoxes.length > 0) {
                        countBadge.className = 'shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 border border-emerald-300';
                    } else {
                        countBadge.className = 'shrink-0 rounded-full bg-slate-200/80 px-2 py-0.5 text-[10px] font-semibold text-slate-700';
                    }
                }
            });

            if (selectedBadge) {
                selectedBadge.textContent = `${totalChecked} Guru Dipilih`;
                if (totalChecked > 0) {
                    selectedBadge.className = 'inline-flex items-center rounded-full bg-emerald-600 px-2.5 py-0.5 text-[11px] font-bold text-white shadow-2xs';
                } else {
                    selectedBadge.className = 'inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 border border-emerald-300';
                }
            }
        }

        // Toggle main collapsible body
        collapseToggle?.addEventListener('click', () => {
            if (!collapseBody) return;
            const isHidden = collapseBody.classList.toggle('hidden');
            collapseArrow?.classList.toggle('rotate-180', !isHidden);
            if (collapseLabel) {
                collapseLabel.textContent = isHidden ? 'Pilih Guru / Mapel' : 'Tutup Pilihan';
            }
        });

        // Mapel group checkbox (Select/Deselect all teachers in that Mapel)
        mapelCards.forEach((card) => {
            const groupCheckbox = card.querySelector('[data-wa-mapel-checkbox]');
            const groupIdx = groupCheckbox?.dataset.waMapelCheckbox;
            const toggleBtn = card.querySelector(`[data-wa-mapel-toggle="${groupIdx}"]`);
            const toggleText = card.querySelector(`[data-wa-mapel-toggle-text="${groupIdx}"]`);
            const arrow = card.querySelector(`[data-wa-mapel-arrow="${groupIdx}"]`);
            const itemsContainer = card.querySelector(`[data-wa-mapel-items="${groupIdx}"]`);
            const teacherBoxes = Array.from(card.querySelectorAll(`[data-wa-teacher-checkbox="${groupIdx}"]`));

            groupCheckbox?.addEventListener('change', () => {
                const shouldCheck = groupCheckbox.checked;
                teacherBoxes.forEach((cb) => {
                    if (!cb.disabled) {
                        cb.checked = shouldCheck;
                    }
                });
                updateCountsAndStates();
            });

            teacherBoxes.forEach((cb) => {
                cb.addEventListener('change', updateCountsAndStates);
            });

            toggleBtn?.addEventListener('click', () => {
                if (!itemsContainer) return;
                const isHidden = itemsContainer.classList.toggle('hidden');
                arrow?.classList.toggle('rotate-180', !isHidden);
                if (toggleText) {
                    toggleText.textContent = isHidden ? 'Lihat Guru' : 'Sembunyikan';
                }
            });
        });

        // Select All & Clear All
        selectAllBtn?.addEventListener('click', () => {
            allTeacherCheckboxes.forEach((cb) => {
                if (!cb.disabled) cb.checked = true;
            });
            updateCountsAndStates();
        });

        clearAllBtn?.addEventListener('click', () => {
            allTeacherCheckboxes.forEach((cb) => {
                cb.checked = false;
            });
            updateCountsAndStates();
        });

        // Search by Mapel or Teacher Name
        searchInput?.addEventListener('input', () => {
            const q = (searchInput.value || '').toLowerCase().trim();
            let visibleCards = 0;

            mapelCards.forEach((card) => {
                const mapelName = card.dataset.mapelName || '';
                const groupCheckbox = card.querySelector('[data-wa-mapel-checkbox]');
                const groupIdx = groupCheckbox?.dataset.waMapelCheckbox;
                const itemsContainer = card.querySelector(`[data-wa-mapel-items="${groupIdx}"]`);
                const arrow = card.querySelector(`[data-wa-mapel-arrow="${groupIdx}"]`);
                const toggleText = card.querySelector(`[data-wa-mapel-toggle-text="${groupIdx}"]`);
                const rows = Array.from(card.querySelectorAll('[data-wa-teacher-row]'));

                if (q === '') {
                    card.classList.remove('hidden');
                    rows.forEach((r) => r.classList.remove('hidden'));
                    visibleCards++;
                    return;
                }

                const mapelMatches = mapelName.includes(q);
                let matchingRowsCount = 0;

                rows.forEach((row) => {
                    const tName = row.dataset.teacherName || '';
                    const rowMatches = mapelMatches || tName.includes(q);
                    row.classList.toggle('hidden', !rowMatches);
                    if (rowMatches) matchingRowsCount++;
                });

                if (mapelMatches || matchingRowsCount > 0) {
                    card.classList.remove('hidden');
                    visibleCards++;
                    // Auto-expand if searching specific teacher name
                    if (!mapelMatches && matchingRowsCount > 0 && itemsContainer) {
                        itemsContainer.classList.remove('hidden');
                        arrow?.classList.add('rotate-180');
                        if (toggleText) toggleText.textContent = 'Sembunyikan';
                    }
                } else {
                    card.classList.add('hidden');
                }
            });

            emptySearchEl?.classList.toggle('hidden', visibleCards > 0);
        });

        updateCountsAndStates();
    })();
</script>

