@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>

<style>
    .calendar-page {
        box-sizing: border-box;
        width: 100%;
        max-width: 1600px;
        margin-right: auto;
        margin-left: auto;
        padding: 1.5rem 1.5rem 2.5rem;
    }

    .calendar-content {
        box-sizing: border-box;
        width: 100%;
        padding: 0;
    }

    .calendar-page-heading {
        max-width: 100%;
        margin-right: auto;
        margin-left: auto;
        border: 1px solid #6b4c32;
        border-radius: 14px;
        padding: 22px 18px;
        background: linear-gradient(135deg, #302319, #4a3424);
        box-shadow: 0 10px 24px rgb(48 35 25 / 16%);
        margin-bottom: 20px;
    }

    .calendar-page-heading h2 {
        color: #fffaf3 !important;
        letter-spacing: 0.06em;
    }

    .calendar-page-heading p {
        color: #e7d2ba !important;
    }

    /* Grid Layout 2 Kolom (Kalender di Kiri, Agenda di Kanan) */
    .calendar-layout-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 22px;
        margin-top: 0;
        width: 100%;
    }

    @media (min-width: 1024px) {
        .calendar-layout-grid {
            grid-template-columns: 2.2fr 1fr;
            align-items: start;
        }
    }

    .calendar-main-col {
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    #calendar {
        width: 100%;
        max-width: 100%;
        margin: 0;
        background-color: #ffffff;
        padding: 18px;
        border: 1px solid #e6d3bc;
        border-radius: 14px;
        box-shadow: 0 12px 30px rgb(54 38 24 / 10%);
    }

    .calendar-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 20px;
    }

    .calendar-action {
        border-radius: 6px;
        padding: 10px 16px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
    }

    .calendar-action-primary {
        background: #3b2b1f;
        color: #ffffff;
        box-shadow: 0 4px 10px rgb(59 43 31 / 18%);
    }

    .calendar-action-primary:hover {
        background: #c96a1b;
        color: #ffffff;
    }

    .calendar-action-secondary {
        border: 1px solid #e4c9a8;
        background: #ffffff;
        color: #6b3f1f;
    }

    .calendar-action-secondary:hover {
        background: #fbf2e6;
        color: #3b2b1f;
    }

    /* Panel Agenda Kolom Kanan */
    .agenda-side-col {
        min-width: 0;
    }

    .agenda-side-card {
        background: #ffffff;
        border: 1px solid #e6d3bc;
        border-radius: 14px;
        box-shadow: 0 12px 30px rgb(54 38 24 / 10%);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .agenda-side-header {
        background: linear-gradient(135deg, #302319, #4a3424);
        padding: 12px 16px;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .agenda-side-header h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #fffaf3;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .agenda-badge-count {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #fce7cf;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .agenda-side-subtitle {
        margin: 0;
        padding: 6px 16px;
        background: #fbf7f0;
        border-bottom: 1px solid #eddcc9;
        font-size: 0.75rem;
        color: #785a3e;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .agenda-side-body {
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 640px;
        overflow-y: auto;
    }

    .agenda-card-item {
        border: 1px solid #e2e8f0;
        border-left: 4px solid #c96a1b;
        border-radius: 8px;
        padding: 10px 12px;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.18s ease;
        display: flex;
        flex-direction: column;
        gap: 6px;
        text-align: left;
    }

    .agenda-card-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        background: #fdfaf6;
        border-color: #cbd5e1;
    }

    .agenda-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
    }

    .agenda-card-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.35;
        margin: 0;
    }

    .agenda-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 7px;
        border-radius: 9999px;
        font-size: 0.68rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .agenda-status-ongoing {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .agenda-status-upcoming {
        background: #fef3c7;
        color: #b45309;
        border: 1px solid #fde68a;
    }

    .agenda-pulse-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #16a34a;
        display: inline-block;
        animation: pulseDot 1.8s infinite;
    }

    @keyframes pulseDot {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.35); opacity: 1; }
        100% { transform: scale(0.95); opacity: 0.8; }
    }

    .agenda-card-meta {
        display: grid;
        gap: 3px;
        font-size: 0.775rem;
        color: #64748b;
    }

    .agenda-card-meta-row {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .agenda-card-meta-icon {
        flex-shrink: 0;
        font-size: 0.7rem;
        font-weight: 600;
        color: #94a3b8;
        min-width: 44px;
    }

    .agenda-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 6px;
        border-top: 1px solid #f1f5f9;
        font-size: 0.7rem;
        color: #94a3b8;
    }

    .agenda-card-action {
        color: #c96a1b;
        font-weight: 600;
    }

    .agenda-empty-state {
        border-radius: 10px;
        padding: 20px 14px;
        background: #fbf7f0;
        border: 1px dashed #eddcc9;
        color: #64748b;
        text-align: center;
    }

    .agenda-empty-state p {
        margin: 0;
    }

    .calendar-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px 18px;
        width: 100%;
        max-width: 100%;
        margin: 14px auto 0;
        border: 1px solid #e6d3bc;
        border-radius: 12px;
        padding: 12px 16px;
        background: #ffffff;
        box-shadow: 0 5px 16px rgb(54 38 24 / 5%);
        color: #493524;
        font-size: 0.875rem;
    }

    .calendar-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .calendar-legend-swatch {
        width: 14px;
        height: 14px;
        border: 2px solid;
        border-radius: 4px;
    }

    #calendar,
    #calendar .fc {
        --fc-button-text-color: #ffffff;
        --fc-button-bg-color: #3b2b1f;
        --fc-button-border-color: #3b2b1f;
        --fc-button-hover-bg-color: #5a402c;
        --fc-button-hover-border-color: #5a402c;
        --fc-button-active-bg-color: #3b2b1f;
        --fc-button-active-border-color: #3b2b1f;
    }

    html body #calendar .fc .fc-toolbar .fc-button,
    html body #calendar .fc .fc-toolbar .fc-button-primary,
    html body #calendar .fc .fc-toolbar .fc-today-button {
        border-color: #3b2b1f !important;
        background: #3b2b1f !important;
        color: #ffffff !important;
        box-shadow: none !important;
    }

    html body #calendar .fc .fc-toolbar .fc-button:hover,
    html body #calendar .fc .fc-toolbar .fc-button:focus,
    html body #calendar .fc .fc-toolbar .fc-button-primary:hover {
        border-color: #5a402c !important;
        background: #5a402c !important;
        color: #ffffff !important;
    }

    html body #calendar .fc .fc-toolbar .fc-button:disabled,
    html body #calendar .fc .fc-toolbar .fc-button-primary:disabled,
    html body #calendar .fc .fc-toolbar .fc-button:not(:disabled).fc-button-active {
        border-color: #3b2b1f !important;
        background: #3b2b1f !important;
        color: #ffffff !important;
        opacity: 1 !important;
    }

    #calendar .fc .fc-toolbar-title {
        color: #302319;
        font-weight: 700;
    }

    #calendar .fc .fc-col-header-cell {
        border-color: #e6d3bc;
        background: #f7ecdc;
    }

    #calendar .fc .fc-col-header-cell-cushion {
        color: #493524;
        padding: 10px 4px;
        text-decoration: none;
    }

    #calendar .fc .fc-daygrid-day.calendar-weekend-day .fc-daygrid-day-number {
        color: #b91c1c !important;
        font-weight: 700;
    }

    #calendar .fc .fc-col-header-cell.calendar-weekend-header .fc-col-header-cell-cushion {
        color: #b91c1c !important;
        font-weight: 700;
    }

    #calendar .fc .fc-scrollgrid,
    #calendar .fc .fc-scrollgrid td,
    #calendar .fc .fc-scrollgrid th {
        border-color: #eadcc9;
    }

    #calendar .fc .fc-daygrid-day-number {
        color: #574634;
        text-decoration: none;
    }

    #calendar .fc .fc-day-today {
        background-color: #fbf1e4 !important;
    }

    #calendar .fc-event-time {
        display: none !important;
    }

    #calendar .fc-daygrid-event-dot {
        display: none !important;
    }

    #calendar .fc-daygrid-day-frame {
        min-height: 105px;
    }

    #calendar .fc-daygrid-event {
        overflow: hidden;
        margin: 2px 2px !important;
        padding: 3px 6px !important;
        border-radius: 5px !important;
        font-size: 0.78rem !important;
        font-weight: 600 !important;
        line-height: 1.25 !important;
        cursor: pointer !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        transition: transform 0.12s ease, box-shadow 0.12s ease;
    }

    #calendar .fc-daygrid-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.16);
    }

    #calendar .fc-daygrid-event .fc-event-title {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
        width: 100%;
    }

    #calendar .fc-daygrid-more-link {
        display: inline-block;
        margin: 2px 2px !important;
        padding: 1px 6px !important;
        color: #8a4b16 !important;
        background: #fbf2e6;
        border-radius: 4px;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-decoration: none;
        cursor: pointer;
    }

    #calendar .fc-daygrid-more-link:hover {
        background: #f3dfc8;
        color: #4a280e !important;
    }

    .agenda-dialog {
        width: min(580px, calc(100vw - 2rem));
        max-height: min(85vh, 720px);
        padding: 0;
        border: 0;
        border-radius: 16px;
        color: #0f172a;
        box-shadow: 0 24px 70px rgb(15 23 42 / 25%);
        overflow: hidden;
    }

    .agenda-dialog::backdrop {
        background: rgb(15 23 42 / 50%);
        backdrop-filter: blur(3px);
    }

    .agenda-dialog-panel {
        padding: 22px;
        max-height: min(85vh, 720px);
        overflow-y: auto;
    }

    .agenda-dialog-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid #e2e8f0;
    }

    .agenda-dialog-header h3 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 700;
        color: #1e293b;
    }

    .agenda-dialog-header p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 0.875rem;
    }

    .agenda-dialog-header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .agenda-dialog-close,
    .agenda-dialog-back {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #475569;
        cursor: pointer;
        padding: 6px 12px;
        font: inherit;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.15s ease;
    }

    .agenda-dialog-close:hover,
    .agenda-dialog-back:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
    }

    .agenda-dialog-list {
        display: grid;
        gap: 10px;
        max-height: 50vh;
        overflow-y: auto;
    }

    .agenda-dialog-item {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-left: 5px solid #c96a1b;
        border-radius: 10px;
        padding: 12px 14px;
        background: #fff;
        color: #0f172a;
        cursor: pointer;
        text-align: left;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        transition: all 0.15s ease;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .agenda-dialog-item:hover {
        background: #faf6f0;
        border-color: #cbd5e1;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }

    .agenda-dialog-item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .agenda-dialog-item-title {
        font-size: 0.98rem;
        font-weight: 700;
        color: #1e293b;
    }

    .agenda-dialog-item-badge {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 9999px;
        background: #f1f5f9;
        color: #475569;
        white-space: nowrap;
    }

    .agenda-dialog-item-meta {
        font-size: 0.82rem;
        color: #64748b;
    }

    .agenda-dialog-item-action {
        font-size: 0.78rem;
        font-weight: 600;
        color: #c96a1b;
        margin-top: 2px;
    }

    .agenda-detail-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
    }

    .agenda-detail-grid {
        display: grid;
        grid-template-columns: 130px 1fr;
        gap: 12px;
        margin: 0;
    }

    .agenda-detail-grid dt {
        color: #64748b;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .agenda-detail-grid dd {
        margin: 0;
        color: #0f172a;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .agenda-dialog-empty {
        border-radius: 9px;
        padding: 18px;
        background: #faf6f0;
        color: #64748b;
        text-align: center;
    }

    .school-event-form {
        display: grid;
        gap: 12px;
        margin-top: 18px;
        border-top: 1px solid #e2e8f0;
        padding-top: 16px;
    }

    .school-event-form label {
        display: grid;
        gap: 5px;
        color: #334155;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .school-event-form input,
    .school-event-form select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        padding: 9px 10px;
        color: #0f172a;
        font: inherit;
        font-weight: 400;
    }

    .school-event-form button {
        border: 0;
        border-radius: 7px;
        padding: 10px 12px;
        background: #c96a1b;
        color: white;
        cursor: pointer;
        font: inherit;
        font-weight: 700;
    }

    .school-event-form button:disabled {
        cursor: wait;
        opacity: 0.65;
    }

    .school-event-form-error {
        margin: 0;
        color: #b91c1c;
        font-size: 0.875rem;
    }

    @media (max-width: 640px) {
        .calendar-actions {
            flex-direction: column;
        }

        .calendar-action {
            text-align: center;
        }

        .agenda-section {
            overflow-x: auto;
        }

        .agenda-table {
            min-width: 680px;
        }
    }
</style>

<div class="calendar-content">
    <div class="calendar-page">


    <!-- Layout 2 Kolom: Kalender di Kiri, Agenda di Kanan -->
    <div class="calendar-layout-grid">
        <!-- Kolom Kiri: Kalender Interaktif -->
        <div class="calendar-main-col">
            <div id="calendar"></div>
            <x-calendar-legend />
        </div>

        <!-- Kolom Kanan: Daftar Agenda Sedang Berlangsung & Akan Datang -->
        <div class="agenda-side-col">
            @php
                $today = now()->toDateString();
                $activeAndUpcomingEvents = collect($events ?? [])
                    ->filter(function ($ev) use ($today) {
                        $endDate = $ev['end_date'] ?? $ev['date'] ?? '';
                        return $endDate >= $today;
                    })
                    ->sortBy(function ($ev) use ($today) {
                        $isOngoing = ($ev['date'] <= $today && ($ev['end_date'] ?? $ev['date']) >= $today);
                        $group = $isOngoing ? '0' : '1';
                        return $group . '_' . ($ev['date'] ?? '') . '_' . ($ev['start'] ?? '');
                    })
                    ->values();
            @endphp

            <section id="agenda-list" class="agenda-side-card">
                <div class="agenda-side-header">
                    <h3>
                        <span>📅</span> Agenda Sekolah
                    </h3>
                    <span class="agenda-badge-count">{{ $activeAndUpcomingEvents->count() }} Agenda</span>
                </div>
                <div class="agenda-side-subtitle">
                    <span>Sedang Berlangsung & Akan Datang</span>
                    <span class="text-xs">Klik untuk melihat detail</span>
                </div>

                <div class="agenda-side-body">
                    @forelse ($activeAndUpcomingEvents as $item)
                        @php
                            $isOngoing = ($item['date'] <= $today && ($item['end_date'] ?? $item['date']) >= $today);
                        @endphp
                        <div class="agenda-card-item" style="border-left-color: {{ $item['borderColor'] ?? '#c96a1b' }};" onclick="openEventById('{{ $item['id'] }}', '{{ $item['date'] }}')">
                            <div class="agenda-card-top">
                                <h4 class="agenda-card-title">{{ $item['title'] }}</h4>
                                @if ($isOngoing)
                                    <span class="agenda-status-pill agenda-status-ongoing">
                                        <span class="agenda-pulse-dot"></span> Berlangsung
                                    </span>
                                @else
                                    <span class="agenda-status-pill agenda-status-upcoming">
                                        Akan Datang
                                    </span>
                                @endif
                            </div>
                            <div class="agenda-card-meta">
                                <div class="agenda-card-meta-row">
                                    <span class="agenda-card-meta-icon">📅</span>
                                    <span><x-event-date-range :start-date="$item['date']" :end-date="$item['end_date']" /></span>
                                </div>
                                <div class="agenda-card-meta-row">
                                    <span class="agenda-card-meta-icon">⏰</span>
                                    <span>
                                        @if ($item['allDay'] ?? false)
                                            Sepanjang hari
                                        @else
                                            <x-event-time :start-time="$item['start']" :end-time="$item['end']" /> WIB
                                        @endif
                                    </span>
                                </div>
                                <div class="agenda-card-meta-row">
                                    <span class="agenda-card-meta-icon">📍</span>
                                    <span>{{ $item['roomName'] ?? 'Ruangan tidak tersedia' }}</span>
                                </div>
                            </div>
                            <div class="agenda-card-footer">
                                <span>{{ $item['picName'] ?? 'Pihak Sekolah' }}</span>
                                <span class="agenda-card-action">Lihat detail →</span>
                            </div>
                        </div>
                    @empty
                        <div class="agenda-empty-state">
                            <p class="font-medium text-slate-700">Tidak ada agenda saat ini</p>
                            <p class="mt-1 text-xs text-slate-500">Agenda yang sedang berlangsung atau yang akan datang akan tampil di sini.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <dialog id="agenda-dialog" class="agenda-dialog" aria-labelledby="agenda-dialog-title">
        <div id="agenda-dialog-content" class="agenda-dialog-panel"></div>
    </dialog>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var calendar;
        const dialog = document.getElementById('agenda-dialog');
        const dialogContent = document.getElementById('agenda-dialog-content');
        const eventTypeLabels = @json(collect(config('event_types'))->mapWithKeys(fn ($type, $key) => [$key => $type['label']])->all());
        const wakaAgendaEnabled = @json(auth()->check() && (str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'waka') || str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'admin')));
        const schoolEventStoreUrl = @json(route('admin.school-events.store'));
        const eventTypeOptions = @json(collect(config('event_types'))->map(fn ($type, $key) => ['value' => $key, 'label' => $type['label']])->values()->all());

        function formatDate(dateString) {
            if (!dateString) return '';
            const cleanDate = dateString.slice(0, 10);
            const [year, month, day] = cleanDate.split('-').map(Number);
            if (!year || !month || !day) return dateString;
            return new Date(year, month - 1, day).toLocaleDateString('id-ID', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });
        }

        function formatTime(val) {
            if (!val) return 'Tidak tersedia';
            const d = val instanceof Date ? val : new Date(val);
            if (isNaN(d.getTime())) return 'Tidak tersedia';
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            return `${hours}:${minutes}`;
        }

        function eventOccursOnDate(event, dateString) {
            const startDate = event.startStr.slice(0, 10);
            const endDate = (event.endStr || event.startStr).slice(0, 10);
            return startDate <= dateString && (event.allDay
                ? (event.endStr ? endDate > dateString : startDate === dateString)
                : endDate >= dateString);
        }

        function showEventDetails(event, dateString) {
            dialogContent.replaceChildren();

            const header = document.createElement('div');
            header.className = 'agenda-dialog-header';

            const heading = document.createElement('div');
            const title = document.createElement('h3');
            title.id = 'agenda-dialog-title';
            title.textContent = event.extendedProps.eventName || event.title;
            const date = document.createElement('p');
            date.textContent = `Detail pelaksanaan • ${formatDate(dateString)}`;
            heading.append(title, date);

            const actions = document.createElement('div');
            actions.className = 'agenda-dialog-header-actions';

            const back = document.createElement('button');
            back.type = 'button';
            back.className = 'agenda-dialog-back';
            back.textContent = '← Kembali ke Daftar';
            back.addEventListener('click', () => showDateAgendas(dateString));

            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'agenda-dialog-close';
            close.textContent = '✕ Tutup';
            close.addEventListener('click', () => dialog.close());

            actions.append(back, close);
            header.append(heading, actions);

            const card = document.createElement('div');
            card.className = 'agenda-detail-card';

            const details = document.createElement('dl');
            details.className = 'agenda-detail-grid';

            const startDate = event.startStr.slice(0, 10);
            const endDate = (event.endStr || event.startStr).slice(0, 10);
            const dateText = event.allDay
                ? formatDate(startDate)
                : (startDate === endDate ? formatDate(startDate) : `${formatDate(startDate)} – ${formatDate(endDate)}`);
            const timeText = event.allDay
                ? 'Sepanjang hari'
                : `${formatTime(event.start)} – ${formatTime(event.end)} WIB`;

            [
                ['Nama Agenda', event.extendedProps.eventName || event.title],
                ['Ruangan', event.extendedProps.roomName || 'Seluruh sekolah'],
                ['Jenis Agenda', eventTypeLabels[event.extendedProps.eventType] || 'Lainnya'],
                ['Tanggal', dateText],
                ['Waktu', timeText],
                ['Penyelenggara', event.extendedProps.picName || 'Pihak Sekolah'],
                ['Status', 'Disetujui']
            ].forEach(([label, value]) => {
                const term = document.createElement('dt');
                term.textContent = label;
                const description = document.createElement('dd');
                if (label === 'Status') {
                    const statusSpan = document.createElement('span');
                    statusSpan.style.cssText = 'display: inline-block; padding: 2px 8px; border-radius: 9999px; background: #dcfce7; color: #166534; font-size: 0.78rem; font-weight: 700;';
                    statusSpan.textContent = value;
                    description.append(statusSpan);
                } else {
                    description.textContent = value;
                }
                details.append(term, description);
            });

            card.append(details);
            dialogContent.append(header, card);
        }

        function showDateAgendas(dateString) {
            const selectedEvents = calendar.getEvents()
                .filter((event) => {
                    if (event.extendedProps.isHoliday) return false;
                    return eventOccursOnDate(event, dateString);
                })
                .sort((a, b) => {
                    if (a.allDay && !b.allDay) return -1;
                    if (!a.allDay && b.allDay) return 1;
                    return (a.startStr || '').localeCompare(b.startStr || '');
                });

            dialogContent.replaceChildren();

            const header = document.createElement('div');
            header.className = 'agenda-dialog-header';

            const heading = document.createElement('div');
            const title = document.createElement('h3');
            title.id = 'agenda-dialog-title';
            title.textContent = `Agenda ${formatDate(dateString)}`;
            const subtitle = document.createElement('p');
            subtitle.textContent = `${selectedEvents.length} agenda berlangsung pada tanggal ini`;
            heading.append(title, subtitle);

            const actions = document.createElement('div');
            actions.className = 'agenda-dialog-header-actions';
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'agenda-dialog-close';
            close.textContent = '✕ Tutup';
            close.addEventListener('click', () => dialog.close());
            actions.append(close);

            header.append(heading, actions);

            const list = document.createElement('div');
            list.className = 'agenda-dialog-list';

            if (selectedEvents.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'agenda-dialog-empty';
                empty.textContent = 'Tidak ada agenda pada tanggal ini.';
                list.append(empty);
            } else {
                selectedEvents.forEach((event) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'agenda-dialog-item';
                    item.style.borderLeftColor = event.borderColor || '#c96a1b';

                    const topRow = document.createElement('div');
                    topRow.className = 'agenda-dialog-item-top';
                    const name = document.createElement('span');
                    name.className = 'agenda-dialog-item-title';
                    name.textContent = event.extendedProps.eventName || event.title;

                    const typeBadge = document.createElement('span');
                    typeBadge.className = 'agenda-dialog-item-badge';
                    typeBadge.textContent = eventTypeLabels[event.extendedProps.eventType] || 'Agenda';
                    topRow.append(name, typeBadge);

                    const meta = document.createElement('div');
                    meta.className = 'agenda-dialog-item-meta';
                    const timeText = event.allDay ? 'Sepanjang hari' : `${formatTime(event.start)} – ${formatTime(event.end)} WIB`;
                    const roomText = event.extendedProps.roomName || 'Ruangan tidak tersedia';
                    meta.textContent = `${timeText} · ${roomText}`;

                    const actionHint = document.createElement('div');
                    actionHint.className = 'agenda-dialog-item-action';
                    actionHint.textContent = 'Klik untuk melihat detail agenda →';

                    item.append(topRow, meta, actionHint);
                    item.addEventListener('click', () => showEventDetails(event, dateString));
                    list.append(item);
                });
            }

            dialogContent.append(header, list);

            if (wakaAgendaEnabled) {
                const form = document.createElement('form');
                form.className = 'school-event-form';
                const nameLabel = document.createElement('label');
                nameLabel.textContent = 'Input agenda sekolah';
                const nameInput = document.createElement('input');
                nameInput.name = 'event_name';
                nameInput.placeholder = 'Nama agenda';
                nameInput.maxLength = 255;
                nameInput.required = true;
                nameLabel.append(nameInput);

                const typeLabel = document.createElement('label');
                typeLabel.textContent = 'Jenis agenda';
                const typeSelect = document.createElement('select');
                typeSelect.name = 'event_type';
                typeSelect.required = true;
                eventTypeOptions.forEach((type) => {
                    const option = document.createElement('option');
                    option.value = type.value;
                    option.textContent = type.label;
                    typeSelect.append(option);
                });
                typeLabel.append(typeSelect);

                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'date';
                dateInput.value = dateString;
                const error = document.createElement('p');
                error.className = 'school-event-form-error';
                error.hidden = true;
                const submit = document.createElement('button');
                submit.type = 'submit';
                submit.textContent = 'Simpan agenda sekolah';
                form.append(nameLabel, typeLabel, dateInput, error, submit);
                form.addEventListener('submit', async (submitEvent) => {
                    submitEvent.preventDefault();
                    error.hidden = true;
                    submit.disabled = true;
                    try {
                        const response = await fetch(schoolEventStoreUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: new FormData(form)
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Agenda tidak dapat disimpan.');
                        }
                        calendar.addEvent(result);
                        showDateAgendas(dateString);
                    } catch (requestError) {
                        error.textContent = requestError.message || 'Terjadi kesalahan saat menyimpan agenda.';
                        error.hidden = false;
                    } finally {
                        submit.disabled = false;
                    }
                });
                dialogContent.append(form);
            }
        }

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'id',
            firstDay: 1,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: '' // Bagian kanan (Bulan, Minggu, Daftar) dihilangkan sesuai permintaan user
            },
            buttonText: {
                today: 'Hari Ini'
            },
            dayMaxEvents: 3,
            moreLinkText: function(num) {
                return '+' + num + ' lainnya';
            },
            eventDisplay: 'block',
            displayEventTime: false, // Hanya menampilkan nama agenda saja di badge kalender
            eventOrder: (a, b) => (b.extendedProps.createdAt || '').localeCompare(a.extendedProps.createdAt || ''),
            eventOrderStrict: true,
            dayCellClassNames: (info) => [0, 6].includes(info.date.getDay()) ? ['calendar-weekend-day'] : [],
            dayHeaderClassNames: (info) => [0, 6].includes(info.date.getDay()) ? ['calendar-weekend-header'] : [],
            dateClick: (info) => {
                showDateAgendas(info.dateStr);
                dialog.showModal();
            },
            dayHeaderDidMount: (info) => {
                if (![0, 6].includes(info.date.getDay())) return;
                info.el.querySelector('.fc-col-header-cell-cushion')
                    ?.style.setProperty('color', '#b91c1c', 'important');
            },
            dayCellDidMount: (info) => {
                const date = info.el.dataset.date;
                const isWeekend = date && [0, 6].includes(new Date(`${date}T00:00:00Z`).getUTCDay());
                if (isWeekend) {
                    info.el.classList.add('calendar-weekend-day');
                    info.el.querySelector('.fc-daygrid-day-number')
                        ?.style.setProperty('color', '#b91c1c', 'important');
                }
                applyDayColors();
            },
            eventsSet: applyDayColors,
            moreLinkClick: (info) => {
                showDateAgendas(info.dateStr);
                dialog.showModal();
                return false;
            },
            eventSources: [
                { events: {!! json_encode($events ?? []) !!} },
                {
                    url: '{{ route('calendar.holidays') }}',
                    failure: (error) => console.error('Hari libur nasional gagal dimuat.', error)
                }
            ],
            
            // Pop-up saat agenda di kalender diklik: menampilkan semua agenda pada tanggal tersebut
            eventClick: function(info) {
                if (info.event.extendedProps.isHoliday) {
                    return;
                }
                const clickedCell = info.el.closest('.fc-daygrid-day');
                const clickedDate = (clickedCell && clickedCell.dataset.date)
                    ? clickedCell.dataset.date
                    : info.event.startStr.slice(0, 10);
                showDateAgendas(clickedDate);
                dialog.showModal();
            }
        });

        calendar.render();

        // Fungsi membuka detail agenda dari kartu di kolom kanan
        window.openEventById = function(id, dateString) {
            const ev = calendar.getEventById(id) || calendar.getEvents().find(e => String(e.id) === String(id));
            if (ev) {
                showEventDetails(ev, dateString);
                dialog.showModal();
            } else {
                showDateAgendas(dateString);
                dialog.showModal();
            }
        };

        // Klik di luar dialog (backdrop) untuk menutup modal
        dialog.addEventListener('click', function(e) {
            const rect = dialog.getBoundingClientRect();
            const isInDialog = (
                rect.top <= e.clientY &&
                e.clientY <= rect.top + rect.height &&
                rect.left <= e.clientX &&
                e.clientX <= rect.left + rect.width
            );
            if (!isInDialog) {
                dialog.close();
            }
        });

        function applyDayColors() {
            calendarEl.querySelectorAll('.fc-daygrid-day[data-date]').forEach((cell) => {
                const dateString = cell.dataset.date;
                const colors = [...new Set(calendar.getEvents()
                    .filter((event) => eventOccursOnDate(event, dateString))
                    .map((event) => event.backgroundColor)
                    .filter(Boolean))];
                const frame = cell.querySelector('.fc-daygrid-day-frame');
                if (!frame) return;
                frame.style.background = colors.length === 0
                    ? ''
                    : colors.length === 1
                        ? `color-mix(in srgb, ${colors[0]} 16%, white)`
                        : `linear-gradient(135deg, ${colors.map((color, index) => `color-mix(in srgb, ${color} 16%, white) ${index * 100 / colors.length}% ${(index + 1) * 100 / colors.length}%`).join(', ')})`;
            });
        }
    });
</script>
@endsection