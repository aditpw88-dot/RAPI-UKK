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
        gap: 20px;
        margin-top: 0;
        width: 100%;
    }

    @media (min-width: 1024px) {
        .calendar-layout-grid {
            /* Responsif pada layar 1366px ke atas: Kalender proporsional (~70%), Sidebar lega (340px-360px) */
            grid-template-columns: minmax(0, 1fr) 340px;
            align-items: stretch;
            gap: 24px;
        }
    }

    @media (min-width: 1280px) {
        .calendar-layout-grid {
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 28px;
        }
    }

    @media (min-width: 1536px) {
        .calendar-layout-grid {
            grid-template-columns: minmax(0, 1fr) 380px;
            gap: 32px;
        }
    }

    .calendar-main-col {
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    /* Reset container kalender agar membungkus rapi di dalam card */
    #calendar {
        width: 100%;
        max-width: 100%;
        margin: 0;
        background-color: transparent;
        padding: 0;
        border: none;
        box-shadow: none;
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
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .agenda-side-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 10px 25px -5px rgb(54 38 24 / 8%), 0 8px 10px -6px rgb(54 38 24 / 4%);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .agenda-side-header {
        background: linear-gradient(135deg, #2b2118, #423022);
        padding: 16px 20px;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
    }

    .agenda-side-header h3 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #fffaf3;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .agenda-badge-count {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #fef3c7;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .agenda-side-subtitle {
        margin: 0;
        padding: 10px 18px;
        background: #faf6f0;
        border-bottom: 1px solid #ede4d8;
        font-size: 0.78rem;
        color: #785a3e;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 6px;
    }

    /* Filter & Pencarian Agenda Sekolah di Samping / Bawah Kalender */
    .agenda-side-filter-box {
        padding: 10px 14px 12px;
        background: #fdfaf7;
        border-bottom: 1px solid #f1e5d6;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .agenda-side-search-wrap {
        position: relative;
        width: 100%;
    }

    .agenda-side-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }

    .agenda-side-search-input {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 6px 10px 6px 30px;
        font-size: 0.8rem;
        color: #1e293b;
        background: #ffffff;
        outline: none;
        transition: all 0.15s ease;
        box-sizing: border-box;
    }

    .agenda-side-search-input:focus {
        border-color: #c96a1b;
        box-shadow: 0 0 0 2px rgba(201, 106, 27, 0.12);
    }

    .agenda-side-filter-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        width: 100%;
    }

    .agenda-side-select {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 5px 8px;
        font-size: 0.76rem;
        font-weight: 500;
        color: #334155;
        background: #ffffff;
        outline: none;
        transition: all 0.15s ease;
        cursor: pointer;
    }

    .agenda-side-select:focus {
        border-color: #c96a1b;
        box-shadow: 0 0 0 2px rgba(201, 106, 27, 0.12);
    }

    .agenda-side-body {
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        flex: 1 1 auto;
    }

    .agenda-card-item {
        border: 1px solid #e2e8f0;
        border-left: 4.5px solid #c96a1b;
        border-radius: 12px;
        padding: 14px 15px;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        gap: 9px;
        text-align: left;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .agenda-card-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -4px rgba(15, 23, 42, 0.08);
        border-color: #cbd5e1;
        background: #fdfcfb;
    }

    .agenda-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .agenda-card-title {
        font-size: 0.92rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.35;
        margin: 0;
        word-break: break-word;
        flex: 1;
    }

    /* Badge Status Pelaksanaan (Soft/Pastel Background, High Contrast Text) */
    .agenda-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
        line-height: 1.25;
    }

    .agenda-status-ongoing {
        background-color: #ecfdf5; /* emerald-50 */
        color: #047857; /* emerald-700 */
        border: 1px solid #a7f3d0; /* emerald-200 */
    }

    .agenda-status-upcoming {
        background-color: #fffbeb; /* amber-50 */
        color: #b45309; /* amber-700 */
        border: 1px solid #fde68a; /* amber-200 */
    }

    .agenda-status-completed {
        background-color: #f0f9ff; /* sky-50 */
        color: #0369a1; /* sky-700 */
        border: 1px solid #bae6fd; /* sky-200 */
    }

    .agenda-pulse-dot {
        position: relative;
        width: 6.5px;
        height: 6.5px;
        border-radius: 50%;
        background-color: #10b981;
        display: inline-block;
        flex-shrink: 0;
    }

    .agenda-pulse-dot::before {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 50%;
        background-color: #34d399;
        opacity: 0.75;
        animation: pulseRing 1.8s cubic-bezier(0.24, 0, 0.38, 1) infinite;
    }

    @keyframes pulseRing {
        0% { transform: scale(0.9); opacity: 0.8; }
        50% { transform: scale(1.6); opacity: 0; }
        100% { transform: scale(0.9); opacity: 0; }
    }

    .agenda-card-meta {
        display: flex;
        flex-direction: column;
        gap: 5.5px;
        margin-top: 2px;
    }

    .agenda-card-meta-row {
        display: flex;
        align-items: baseline;
        gap: 6px;
        line-height: 1.4;
        font-size: 0.79rem;
    }

    .agenda-card-meta-icon {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
        min-width: 72px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #64748b;
    }

    .agenda-card-meta-val {
        color: #334155;
        font-size: 0.84rem;
        font-weight: 500;
        word-break: break-word;
        flex: 1;
    }

    .agenda-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 9px;
        margin-top: 2px;
        border-top: 1px solid #f1f5f9;
        font-size: 0.75rem;
        gap: 8px;
    }

    .agenda-card-creator {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        min-width: 0;
        color: #64748b;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .agenda-card-action {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        color: #c96a1b;
        font-weight: 700;
        font-size: 0.76rem;
        flex-shrink: 0;
        transition: transform 0.15s ease, color 0.15s ease;
    }

    .agenda-card-item:hover .agenda-card-action {
        color: #9a4c0a;
        transform: translateX(2px);
    }

    /* Paginasi Agenda Sidebar */
    .agenda-pagination {
        margin-top: auto;
        padding: 12px 14px;
        border-top: 1px solid #ede4d8;
        background: #faf6f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
        font-size: 0.78rem;
        color: #64748b;
    }

    .agenda-pagination-info {
        font-weight: 600;
        color: #785a3e;
        font-size: 0.78rem;
    }

    .agenda-pagination-controls {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .agenda-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;
        padding: 0 8px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #334155;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        line-height: 1;
        user-select: none;
    }

    .agenda-page-btn:hover:not(:disabled) {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    .agenda-page-btn.active {
        background: #c96a1b;
        border-color: #c96a1b;
        color: #ffffff;
    }

    .agenda-page-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        background: #f1f5f9;
        border-color: #e2e8f0;
        color: #94a3b8;
    }

    .agenda-empty-state {
        border-radius: 10px;
        padding: 24px 16px;
        background: #fbf7f0;
        border: 1px dashed #eddcc9;
        color: #64748b;
        text-align: center;
        font-size: 0.9rem;
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
        font-size: 1.35rem;
    }

    /* Filter Toolbar Kalender (Bagian Kanan) */
    #calendar .fc-header-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 16px !important;
    }

    #calendar .fc-header-toolbar .fc-toolbar-chunk:last-child {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    .calendar-header-filter {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .calendar-view-toggle {
        display: inline-flex;
        border-radius: 8px;
        background: #f1e4d3;
        border: 1px solid #d8c4ad;
        padding: 3px;
        gap: 2px;
    }

    .calendar-view-btn {
        border: none;
        background: transparent;
        color: #574634;
        font-size: 0.8rem;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
        line-height: 1.2;
    }

    .calendar-view-btn:hover {
        color: #211c18;
    }

    .calendar-view-btn.active {
        background: #3b2b1f;
        color: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.16);
    }

    .calendar-filter-divider {
        display: inline-block;
        width: 1px;
        height: 22px;
        background: #d8c4ad;
        margin: 0 2px;
    }

    .calendar-select-group {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .calendar-filter-select {
        border: 1px solid #d8c4ad;
        border-radius: 7px;
        padding: 5px 10px;
        background: #ffffff;
        color: #3b2b1f;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        outline: none;
        height: 34px;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .calendar-filter-select:hover {
        border-color: #c96a1b;
    }

    .calendar-filter-select:focus {
        border-color: #c96a1b;
        box-shadow: 0 0 0 2px rgba(201, 106, 27, 0.2);
    }

    .calendar-filter-year-input {
        width: 82px;
        border: 1px solid #d8c4ad;
        border-radius: 7px;
        padding: 5px 8px;
        background: #ffffff;
        color: #3b2b1f;
        font-size: 0.84rem;
        font-weight: 700;
        text-align: center;
        outline: none;
        height: 34px;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .calendar-filter-year-input:hover {
        border-color: #c96a1b;
    }

    .calendar-filter-year-input:focus {
        border-color: #c96a1b;
        box-shadow: 0 0 0 2px rgba(201, 106, 27, 0.2);
        background: #fffdfa;
    }

    @media (max-width: 900px) {
        #calendar .fc-header-toolbar {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        #calendar .fc-header-toolbar .fc-toolbar-chunk {
            display: flex;
            justify-content: center !important;
            flex-wrap: wrap;
        }

        .calendar-header-filter {
            justify-content: center !important;
        }
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

    /* Hilangkan scrollbar vertikal yang tidak perlu pada container kalender */
    #calendar .fc-scroller {
        overflow: visible !important;
        height: auto !important;
    }

    #calendar .fc-scroller-liquid-absolute {
        position: static !important;
    }

    #calendar .fc .fc-scrollgrid,
    #calendar .fc .fc-scrollgrid td,
    #calendar .fc .fc-scrollgrid th {
        border-color: #eedecf !important;
    }

    #calendar .fc-daygrid-day-top {
        padding: 4px 6px 2px !important;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }

    #calendar .fc-daygrid-day-number {
        color: #475569;
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        padding: 2px 6px;
        border-radius: 6px;
        line-height: 1.2;
        transition: all 0.15s ease;
    }

    #calendar .fc-day-today {
        background-color: #fff9f2 !important;
    }

    #calendar .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number,
    #calendar .fc .fc-daygrid-day.calendar-weekend-day.fc-day-today .fc-daygrid-day-number,
    #calendar .fc-day-today .fc-daygrid-day-number {
        background: #c96a1b !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        border-radius: 6px;
        padding: 2px 7px;
        box-shadow: 0 2px 5px rgba(201, 106, 27, 0.35);
    }

    #calendar .fc-event-time {
        display: none !important;
    }

    #calendar .fc-daygrid-event-dot {
        display: none !important;
    }

    #calendar .fc-daygrid-day-frame {
        min-height: 98px;
        padding: 4px 4px 6px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        transition: background-color 0.15s ease;
    }

    #calendar .fc-daygrid-day:hover .fc-daygrid-day-frame {
        background-color: rgba(251, 247, 240, 0.75);
    }

    #calendar .fc-daygrid-event {
        overflow: hidden;
        margin: 2px 3px !important;
        padding: 3px 7px !important;
        border-radius: 6px !important;
        font-size: 0.74rem !important;
        font-weight: 600 !important;
        line-height: 1.25 !important;
        cursor: pointer !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        transition: transform 0.12s ease, box-shadow 0.12s ease;
    }

    #calendar .fc-daygrid-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.12);
    }

    #calendar .fc-daygrid-event .fc-event-title {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
        width: 100%;
    }

    #calendar .fc-daygrid-more-link,
    #calendar .fc-more-link {
        display: none !important;
    }

    /* Batasi tampilan agenda di kotak tanggal kalender maksimal 3 agenda (Desktop badge & Mobile dot) */
    #calendar .fc-daygrid-day-events .fc-daygrid-event-harness:nth-child(n+4) {
        display: none !important;
    }

    .agenda-dialog-filter-wrap {
        margin-bottom: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .agenda-dialog-search-wrap {
        position: relative;
        width: 100%;
    }

    .agenda-dialog-search-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        padding: 7px 12px 7px 34px;
        font-size: 0.82rem;
        color: #1e293b;
        background: #f8fafc;
        outline: none;
        transition: border-color 0.15s ease, background 0.15s ease;
        box-sizing: border-box;
    }

    .agenda-dialog-search-input:focus {
        border-color: #c96a1b;
        background: #ffffff;
        box-shadow: 0 0 0 2px rgba(201, 106, 27, 0.15);
    }

    .agenda-dialog-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
        display: flex;
        align-items: center;
    }

    .agenda-dialog-filter-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        width: 100%;
    }

    .agenda-dialog-select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 0.78rem;
        font-weight: 500;
        color: #334155;
        background: #f8fafc;
        outline: none;
        transition: border-color 0.15s ease, background 0.15s ease;
        box-sizing: border-box;
        cursor: pointer;
    }

    .agenda-dialog-select:focus {
        border-color: #c96a1b;
        background: #ffffff;
        box-shadow: 0 0 0 2px rgba(201, 106, 27, 0.15);
    }

    .agenda-dialog-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
        font-size: 0.78rem;
        color: #64748b;
        flex-wrap: wrap;
    }

    .agenda-dialog-pagination-btns {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .agenda-dialog-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;
        padding: 0 8px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #334155;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .agenda-dialog-page-btn:hover:not(:disabled) {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    .agenda-dialog-page-btn.active {
        background: #c96a1b;
        border-color: #c96a1b;
        color: #ffffff;
    }

    .agenda-dialog-page-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        background: #f8fafc;
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
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .agenda-dialog-item-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.35;
        word-break: break-word;
        flex: 1 1 auto;
        min-width: 0;
    }

    .agenda-dialog-item-badges {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }

    .agenda-dialog-item-badge {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 2.5px 8px;
        border-radius: 9999px;
        background: #f1f5f9;
        color: #475569;
        white-space: nowrap;
        flex-shrink: 0;
    }

    @media (max-width: 520px) {
        .agenda-dialog-item-top {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }

        .agenda-dialog-item-badges {
            width: 100%;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }
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
        .calendar-page {
            padding: 0.75rem 0.5rem 1.5rem !important;
        }

        .calendar-page-heading {
            padding: 14px 12px !important;
            margin-bottom: 12px !important;
            border-radius: 10px !important;
        }

        .calendar-page-heading h2 {
            font-size: 1.15rem !important;
        }

        .calendar-page-heading p {
            font-size: 0.8rem !important;
        }

        #calendar {
            padding: 8px 6px !important;
            border-radius: 10px !important;
        }

        #calendar .fc .fc-toolbar {
            flex-direction: column !important;
            gap: 8px !important;
        }

        #calendar .fc-header-toolbar .fc-toolbar-chunk {
            display: flex !important;
            justify-content: center !important;
            flex-wrap: wrap !important;
            gap: 4px !important;
        }

        #calendar .fc .fc-toolbar-title {
            font-size: 1.1rem !important;
            text-align: center !important;
        }

        #calendar .fc .fc-toolbar .fc-button {
            padding: 4px 8px !important;
            font-size: 0.8rem !important;
            height: auto !important;
        }

        .calendar-header-filter {
            flex-wrap: wrap !important;
            justify-content: center !important;
            gap: 4px !important;
            width: 100% !important;
        }

        .calendar-view-toggle .calendar-view-btn {
            padding: 4px 8px !important;
            font-size: 0.75rem !important;
        }

        .calendar-select-group {
            gap: 4px !important;
        }

        .calendar-filter-select,
        .calendar-filter-year-input {
            height: 30px !important;
            font-size: 0.78rem !important;
            padding: 2px 6px !important;
        }

        .calendar-filter-year-input {
            width: 65px !important;
        }

        #calendar .fc .fc-col-header-cell-cushion {
            padding: 6px 1px !important;
            font-size: 0.72rem !important;
        }

        /* Tampilan Mobile: Ubah badge teks panjang yang berdesakan menjadi indikator titik warna (Dot Indicators) */
        #calendar .fc-daygrid-day-frame {
            min-height: 48px !important;
            height: 52px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: flex-start !important;
            padding: 2px 1px !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }

        #calendar .fc-daygrid-day-top {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            width: 100% !important;
            flex-direction: row !important;
            margin-bottom: 2px !important;
        }

        #calendar .fc-daygrid-day-number {
            font-size: 0.75rem !important;
            padding: 1px 4px !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
        }

        #calendar .fc-daygrid-day-events {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 2.5px !important;
            padding: 0 !important;
            margin: 0 auto !important;
            width: 100% !important;
            max-width: 44px !important;
            min-height: 8px !important;
            overflow: hidden !important;
        }

        #calendar .fc-daygrid-event-harness {
            margin: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex: 0 0 auto !important;
            line-height: 1 !important;
            visibility: visible !important;
        }

        #calendar .fc-daygrid-event {
            display: inline-block !important;
            width: 7px !important;
            height: 7px !important;
            min-width: 7px !important;
            max-width: 7px !important;
            border-radius: 50% !important;
            padding: 0 !important;
            margin: 0.5px !important;
            border: none !important;
            background-color: currentColor !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.25) !important;
            cursor: pointer !important;
            flex-shrink: 0 !important;
        }

        #calendar .fc-daygrid-event .fc-event-main,
        #calendar .fc-daygrid-event .fc-event-title,
        #calendar .fc-daygrid-event .fc-event-time {
            display: none !important;
        }

        #calendar .fc-daygrid-day-bottom {
            display: none !important;
        }

        #calendar .fc-daygrid-more-link,
        #calendar .fc-more-link {
            display: none !important;
        }

        .calendar-legend {
            padding: 8px 10px !important;
            gap: 6px 12px !important;
            font-size: 0.75rem !important;
            margin-top: 10px !important;
        }

        .calendar-legend-item {
            gap: 5px !important;
        }

        .calendar-legend-swatch {
            width: 8px !important;
            height: 8px !important;
            border-radius: 50% !important;
            border-width: 1.5px !important;
        }

        .agenda-card-item {
            width: 100% !important;
            padding: 11px 12px !important;
        }

        .calendar-actions {
            flex-direction: column;
        }

        .calendar-action {
            text-align: center;
        }

        .agenda-dialog {
            width: 95vw !important;
            max-width: 95vw !important;
            margin: auto !important;
            border-radius: 12px !important;
        }

        .agenda-dialog-panel {
            padding: 14px !important;
        }

        .agenda-dialog-header h3 {
            font-size: 1.05rem !important;
        }

        .agenda-detail-grid {
            grid-template-columns: 90px 1fr !important;
            gap: 6px !important;
            font-size: 0.82rem !important;
        }

        .agenda-section {
            overflow-x: auto;
        }

        .agenda-table {
            min-width: 680px;
        }
    }
</style>

<div class="w-full min-h-screen bg-stone-50/50 py-3 sm:py-6 px-3 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">

        <!-- Banner Judul Halaman Responsif -->
        <div class="mb-4 sm:mb-6 rounded-2xl border border-stone-800/40 bg-gradient-to-br from-[#291f16] to-[#402e1f] p-4 sm:p-6 text-white shadow-md">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg sm:text-2xl font-extrabold tracking-tight text-amber-100">Kalender & Agenda Ruang PIC</h2>
                    <p class="mt-0.5 text-xs sm:text-sm text-amber-200/80">Pantau ketersediaan ruang dan jadwal kegiatan sekolah secara interaktif.</p>
                </div>
                <div class="mt-2 sm:mt-0 flex items-center gap-2 text-xs font-semibold text-amber-200/90">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Panel PIC Terintegrasi</span>
                </div>
            </div>
        </div>

        <!-- 1. Grid Layout Responsif: 1 Kolom di Layar Kecil (mobile/tablet), 3 Kolom di Layar Lebar (lg) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 w-full items-start">
            
            <!-- Kolom 1 (Kalender Sekolah - Mengambil 2 Kolom di Desktop): lg:col-span-2 -->
            <div class="lg:col-span-2 min-w-0 w-full flex flex-col gap-4">
                
                <!-- 2. Perbaikan Kalender: Dibungkus container overflow-x-auto untuk swipe horizontal mulus di HP -->
                <div class="calendar-card w-full overflow-hidden rounded-2xl border border-stone-200/90 bg-white p-3 sm:p-5 shadow-xs">
                    <div class="w-full overflow-x-auto scrollbar-thin -webkit-overflow-scrolling-touch">
                        <div id="calendar" class="min-w-[340px] sm:min-w-0 w-full"></div>
                    </div>
                    <!-- Petunjuk Tampilan Ponsel -->
                    <div class="sm:hidden mt-3 pt-2.5 border-t border-stone-100 flex items-center justify-center gap-1.5 text-[11.5px] text-stone-500 font-medium text-center">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                        </svg>
                        <span>Ketuk tanggal bertitik untuk membuka rincian agenda</span>
                    </div>
                </div>

                <!-- Legend Keterangan Warna Agenda -->
                <x-calendar-legend />

            <!-- Filter Toolbar Kalender (Akan dipasang otomatis di kanan header kalender) -->
            <div id="calendar-header-filter-template" style="display: none;">
                <div class="calendar-header-filter" id="calendar-header-filter">
                    <!-- Switcher Mode: Bulan & Minggu -->
                    <div class="calendar-view-toggle" title="Ubah tampilan kalender">
                        <button type="button" class="calendar-view-btn active" id="btn-view-month">Bulan</button>
                        <button type="button" class="calendar-view-btn" id="btn-view-week">Minggu</button>
                    </div>

                    <span class="calendar-filter-divider"></span>

                    <!-- Pilihan Bulan & Tahun (Dapat diketik) -->
                    <div class="calendar-select-group">
                        <select id="cal-filter-month" class="calendar-filter-select" aria-label="Pilih Bulan" title="Pilih Bulan">
                            <option value="0">Januari</option>
                            <option value="1">Februari</option>
                            <option value="2">Maret</option>
                            <option value="3">April</option>
                            <option value="4">Mei</option>
                            <option value="5">Juni</option>
                            <option value="6">Juli</option>
                            <option value="7">Agustus</option>
                            <option value="8">September</option>
                            <option value="9">Oktober</option>
                            <option value="10">November</option>
                            <option value="11">Desember</option>
                        </select>
                        <input type="number" id="cal-filter-year" class="calendar-filter-year-input" min="1970" max="2100" step="1" placeholder="Tahun" aria-label="Ketik Tahun" title="Ketik tahun untuk langsung mencari (misal: 2026)">
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Daftar Agenda Sedang Berlangsung & Akan Datang -->
        <div class="lg:col-span-1 min-w-0 w-full flex flex-col">
            @php
                $now = now();
                $today = $now->toDateString();
                $threshold = $now->copy()->subHours(24);
                $activeAndUpcomingEvents = collect($events ?? [])
                    ->map(function ($ev) use ($now, $today, $threshold) {
                        $lifecycle = $ev['lifecycle_status'] ?? null;
                        $startDate = $ev['date'] ?? $today;
                        $endDate = $ev['end_date'] ?? $startDate;
                        $startTime = $ev['start_time'] ?? (!empty($ev['start']) && str_contains($ev['start'], 'T') ? explode('T', $ev['start'])[1] : '00:00:00');
                        $endTime = $ev['end_time'] ?? (!empty($ev['end']) && str_contains($ev['end'], 'T') ? explode('T', $ev['end'])[1] : '23:59:59');
                        $startDt = \Illuminate\Support\Carbon::parse("{$startDate} {$startTime}");
                        $endDt = \Illuminate\Support\Carbon::parse("{$endDate} {$endTime}");

                        if (!$lifecycle) {
                            if ($now->greaterThanOrEqualTo($endDt)) {
                                $lifecycle = 'completed';
                            } elseif ($now->lessThan($startDt)) {
                                $lifecycle = 'upcoming';
                            } else {
                                $lifecycle = 'in_progress';
                            }
                        }
                        $ev['display_lifecycle'] = $lifecycle;

                        if (array_key_exists('is_completed_over_24h', $ev)) {
                            $ev['hide_from_sidebar'] = (bool) $ev['is_completed_over_24h'];
                        } elseif ($lifecycle === 'completed') {
                            $finishedAt = !empty($ev['completed_at'])
                                ? \Illuminate\Support\Carbon::parse($ev['completed_at'])
                                : $endDt;
                            $ev['hide_from_sidebar'] = $finishedAt->lessThanOrEqualTo($threshold);
                        } else {
                            $ev['hide_from_sidebar'] = false;
                        }

                        return $ev;
                    })
                    ->reject(fn ($ev) => !empty($ev['hide_from_sidebar']) || ($ev['display_lifecycle'] ?? '') === 'completed')
                    ->sortBy(function ($ev) {
                        $order = match($ev['display_lifecycle']) {
                            'in_progress' => 0,
                            'upcoming' => 1,
                            default => 2,
                        };
                        return $order . '_' . ($ev['date'] ?? '') . '_' . ($ev['start'] ?? '');
                    })
                    ->values();
            @endphp

            <section id="agenda-list" class="agenda-side-card">
                <div class="agenda-side-header">
                    <h3>
                        <svg width="18" height="18" style="width:18px;height:18px;" class="text-amber-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Agenda Sekolah</span>
                    </h3>
                    <span id="side-agenda-count-badge" class="agenda-badge-count">{{ $activeAndUpcomingEvents->count() }} Agenda</span>
                </div>
                <div class="agenda-side-subtitle">
                    <span class="font-medium text-slate-700">Status Pelaksanaan</span>
                    <span class="text-[11px] text-slate-500">Klik untuk melihat detail</span>
                </div>

                <!-- Form Filter & Pencarian Agenda Sekolah Bawah/Samping Kalender -->
                <div class="agenda-side-filter-box">
                    <div class="agenda-side-search-wrap">
                        <svg class="agenda-side-search-icon" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="search" id="side-agenda-search" class="agenda-side-search-input" placeholder="Cari nama agenda, ruangan, PIC..." aria-label="Cari agenda sekolah">
                    </div>
                    <div class="agenda-side-filter-row">
                        <select id="side-agenda-filter-status" class="agenda-side-select" aria-label="Filter Status Agenda">
                            <option value="">Semua Status</option>
                            <option value="in_progress">Sedang Terlaksana</option>
                            <option value="upcoming">Akan Terlaksana</option>
                            <option value="completed">Sudah Terlaksana</option>
                        </select>
                        <select id="side-agenda-filter-type" class="agenda-side-select" aria-label="Filter Kategori Agenda">
                            <option value="">Semua Kategori</option>
                            @foreach (config('event_types') as $typeKey => $typeCfg)
                                <option value="{{ $typeKey }}">{{ $typeCfg['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="agenda-cards-container" class="agenda-side-body">
                    @forelse ($activeAndUpcomingEvents as $item)
                        @php
                            $lifecycle = $item['display_lifecycle'] ?? 'upcoming';

                            $startTimeRaw = !empty($item['start_time']) ? $item['start_time'] : (!empty($item['start']) && str_contains($item['start'], 'T') ? explode('T', $item['start'])[1] : ($item['start'] ?? ''));
                            $endTimeRaw = !empty($item['end_time']) ? $item['end_time'] : (!empty($item['end']) && str_contains($item['end'], 'T') ? explode('T', $item['end'])[1] : ($item['end'] ?? ''));

                            $startH = !empty($startTimeRaw) ? date('H:i', strtotime($startTimeRaw)) : '';
                            $endH = !empty($endTimeRaw) ? date('H:i', strtotime($endTimeRaw)) : '';

                            $sessions = collect(config('schedule.sessions', []));
                            $startSession = $sessions->search(fn ($times) => $times['start_time'] === $startH);
                            $endSession = $sessions->search(fn ($times) => $times['end_time'] === $endH);

                            $sessionLabel = '';
                            if ($startSession !== false && $endSession !== false) {
                                $sessionLabel = $startSession === $endSession ? " (Sesi {$startSession})" : " (Sesi {$startSession} - Sesi {$endSession})";
                            }

                            $timeFormatted = $item['allDay'] ?? false
                                ? 'Sepanjang hari'
                                : ($startH && $endH ? "{$startH} - {$endH} WIB{$sessionLabel}" : 'Waktu menyusul');

                            $startDateFmt = !empty($item['date']) ? date('d/m/Y', strtotime($item['date'])) : '';
                            $endDateFmt = !empty($item['end_date']) && $item['end_date'] !== $item['date'] ? date('d/m/Y', strtotime($item['end_date'])) : '';
                            $dateFormatted = $endDateFmt ? "{$startDateFmt} - {$endDateFmt}" : $startDateFmt;
                        @endphp
                        <div class="agenda-card-item group"
                             data-title="{{ strtolower($item['title'] ?? '') }}"
                             data-room="{{ strtolower($item['roomName'] ?? '') }}"
                             data-pic="{{ strtolower($item['picName'] ?? '') }}"
                             data-type="{{ strtolower($item['eventType'] ?? '') }}"
                             data-status="{{ strtolower($lifecycle) }}"
                             style="border-left-color: {{ $item['borderColor'] ?? '#c96a1b' }}; {{ $loop->iteration > 5 ? 'display: none;' : '' }}"
                             onclick="openEventById('{{ $item['id'] }}', '{{ $item['date'] }}')">
                            <div class="agenda-card-top">
                                <h4 class="agenda-card-title">{{ $item['title'] }}</h4>
                                @if ($lifecycle === 'in_progress')
                                    <span class="agenda-status-pill agenda-status-ongoing">
                                        <span class="agenda-pulse-dot"></span>
                                        <span>Sedang Terlaksana</span>
                                    </span>
                                @elseif ($lifecycle === 'completed')
                                    <span class="agenda-status-pill agenda-status-completed">
                                        <span>Sudah Terlaksana</span>
                                    </span>
                                @else
                                    <span class="agenda-status-pill agenda-status-upcoming">
                                        <span>Belum Terlaksana</span>
                                    </span>
                                @endif
                            </div>

                            <div class="agenda-card-meta">
                                <div class="agenda-card-meta-row">
                                    <span class="agenda-card-meta-icon">
                                        <svg width="14" height="14" style="width:14px;height:14px;" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>Tanggal:</span>
                                    </span>
                                    <span class="agenda-card-meta-val">{{ $dateFormatted }}</span>
                                </div>
                                <div class="agenda-card-meta-row">
                                    <span class="agenda-card-meta-icon">
                                        <svg width="14" height="14" style="width:14px;height:14px;" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Waktu:</span>
                                    </span>
                                    <span class="agenda-card-meta-val">{{ $timeFormatted }}</span>
                                </div>
                                <div class="agenda-card-meta-row">
                                    <span class="agenda-card-meta-icon">
                                        <svg width="14" height="14" style="width:14px;height:14px;" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span>Ruangan:</span>
                                    </span>
                                    <span class="agenda-card-meta-val font-semibold text-slate-800">{{ $item['roomName'] ?? 'Ruangan tidak tersedia' }}</span>
                                </div>
                            </div>

                            <div class="agenda-card-footer">
                                <div class="agenda-card-creator">
                                    <svg width="14" height="14" style="width:14px;height:14px;" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="truncate">{{ $item['picName'] ?? 'Pihak Sekolah' }}</span>
                                </div>
                                <span class="agenda-card-action">
                                    <span>Lihat detail</span>
                                    <span aria-hidden="true">&rarr;</span>
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="agenda-empty-state">
                            <p class="font-medium text-slate-700">Tidak ada agenda saat ini</p>
                            <p class="mt-1 text-xs text-slate-500">Agenda yang sedang berlangsung atau yang akan datang akan tampil di sini.</p>
                        </div>
                    @endforelse

                    <div id="side-agenda-empty-search" class="agenda-empty-state" style="display: none;">
                        <p class="font-medium text-slate-700">Tidak ada agenda yang cocok</p>
                        <p class="mt-1 text-xs text-slate-500">Coba ubah kata kunci pencarian atau ganti filter status & kategori.</p>
                    </div>
                </div>

                <!-- Paginasi 5 Data Per Halaman -->
                <div id="agenda-pagination-bar" class="agenda-pagination" @if($activeAndUpcomingEvents->count() === 0) style="display: none;" @endif>
                    <span id="agenda-pagination-info" class="agenda-pagination-info">Menampilkan 1–5</span>
                    <div class="agenda-pagination-controls">
                        <button type="button" id="agenda-pagination-prev" class="agenda-page-btn" aria-label="Halaman Sebelumnya">
                            &larr; Prev
                        </button>
                        <span id="agenda-pagination-pages" class="inline-flex items-center gap-1"></span>
                        <button type="button" id="agenda-pagination-next" class="agenda-page-btn" aria-label="Halaman Selanjutnya">
                            Next &rarr;
                        </button>
                    </div>
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
                ['Status', event.extendedProps.lifecycleStatusLabel || event.extendedProps.lifecycle_status_label || 'Disetujui']
            ].forEach(([label, value]) => {
                const term = document.createElement('dt');
                term.textContent = label;
                const description = document.createElement('dd');
                if (label === 'Status') {
                    const statusSpan = document.createElement('span');
                    const lc = event.extendedProps.lifecycleStatus || event.extendedProps.lifecycle_status || '';
                    let badgeBg = '#ecfdf5';
                    let badgeColor = '#047857';
                    let badgeBorder = '#a7f3d0';
                    if (lc === 'completed') {
                        badgeBg = '#f0f9ff';
                        badgeColor = '#0369a1';
                        badgeBorder = '#bae6fd';
                    } else if (lc === 'upcoming') {
                        badgeBg = '#fffbeb';
                        badgeColor = '#b45309';
                        badgeBorder = '#fde68a';
                    }
                    statusSpan.style.cssText = `display: inline-flex; align-items: center; padding: 2.5px 9px; border-radius: 9999px; background: ${badgeBg}; color: ${badgeColor}; border: 1px solid ${badgeBorder}; font-size: 0.75rem; font-weight: 700;`;
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

        function getEventLifecycleOrder(event) {
            const status = (event.extendedProps?.lifecycleStatus || event.extendedProps?.lifecycle_status || '').toLowerCase();
            if (status === 'in_progress') return 0; // 1: Sedang terlaksana
            if (status === 'upcoming') return 1;    // 2: Akan / Belum terlaksana
            if (status === 'completed') return 2;   // 3: Sudah terlaksana
            return 3;
        }

        function compareEventsByLifecycle(a, b) {
            const orderA = getEventLifecycleOrder(a);
            const orderB = getEventLifecycleOrder(b);
            if (orderA !== orderB) {
                return orderA - orderB;
            }
            if (a.allDay && !b.allDay) return -1;
            if (!a.allDay && b.allDay) return 1;
            return (a.startStr || '').localeCompare(b.startStr || '') || (a.title || '').localeCompare(b.title || '');
        }

        function showDateAgendas(dateString) {
            const allSelectedEvents = calendar.getEvents()
                .filter((event) => {
                    if (event.extendedProps.isHoliday) return false;
                    return eventOccursOnDate(event, dateString);
                })
                .sort(compareEventsByLifecycle);

            const holidays = calendar.getEvents()
                .filter((event) => event.extendedProps.isHoliday && eventOccursOnDate(event, dateString));

            dialogContent.replaceChildren();

            const header = document.createElement('div');
            header.className = 'agenda-dialog-header';

            const heading = document.createElement('div');
            const title = document.createElement('h3');
            title.id = 'agenda-dialog-title';
            title.textContent = `Agenda ${formatDate(dateString)}`;
            const subtitle = document.createElement('p');
            subtitle.textContent = `${allSelectedEvents.length} agenda tersimpan pada tanggal ini`;
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

            const bodyContainer = document.createElement('div');

            if (holidays.length > 0) {
                const holidayBanner = document.createElement('div');
                holidayBanner.style.cssText = 'padding: 9px 12px; border-radius: 8px; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; font-size: 0.82rem; font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;';
                holidayBanner.innerHTML = `<span>🚩</span> <span>Libur Nasional: ${holidays.map(h => h.title).join(', ')}</span>`;
                bodyContainer.append(holidayBanner);
            }

            if (allSelectedEvents.length === 0 && holidays.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'agenda-dialog-empty';
                empty.textContent = 'Tidak ada agenda pada tanggal ini.';
                bodyContainer.append(empty);
                dialogContent.append(header, bodyContainer);
                appendWakaForm(dialogContent, dateString);
                return;
            } else if (allSelectedEvents.length === 0 && holidays.length > 0) {
                const empty = document.createElement('p');
                empty.className = 'agenda-dialog-empty';
                empty.textContent = 'Tidak ada kegiatan atau rapat sekolah pada tanggal merah ini.';
                bodyContainer.append(empty);
                dialogContent.append(header, bodyContainer);
                appendWakaForm(dialogContent, dateString);
                return;
            }

            // Filter & Pencarian di dalam Dialog
            const filterWrap = document.createElement('div');
            filterWrap.className = 'agenda-dialog-filter-wrap';
            filterWrap.innerHTML = `
                <div class="agenda-dialog-search-wrap">
                    <span class="agenda-dialog-search-icon">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="search" class="agenda-dialog-search-input" placeholder="Cari nama agenda, ruangan, PIC..." aria-label="Cari agenda pada tanggal ini">
                </div>
                <div class="agenda-dialog-filter-row">
                    <select class="agenda-dialog-select filter-status" aria-label="Filter Status">
                        <option value="">Semua Status</option>
                        <option value="in_progress">Sedang Terlaksana</option>
                        <option value="upcoming">Akan Terlaksana</option>
                        <option value="completed">Sudah Terlaksana</option>
                    </select>
                    <select class="agenda-dialog-select filter-type" aria-label="Filter Kategori">
                        <option value="">Semua Kategori</option>
                    </select>
                </div>
            `;
            bodyContainer.append(filterWrap);

            const searchInput = filterWrap.querySelector('.agenda-dialog-search-input');
            const statusSelect = filterWrap.querySelector('.filter-status');
            const typeSelect = filterWrap.querySelector('.filter-type');

            eventTypeOptions.forEach((opt) => {
                const optEl = document.createElement('option');
                optEl.value = opt.value;
                optEl.textContent = opt.label;
                typeSelect.append(optEl);
            });

            const list = document.createElement('div');
            list.className = 'agenda-dialog-list';
            bodyContainer.append(list);

            const paginationContainer = document.createElement('div');
            paginationContainer.className = 'agenda-dialog-pagination';
            bodyContainer.append(paginationContainer);

            let searchQuery = '';
            let statusFilter = '';
            let typeFilter = '';
            let currentPage = 1;
            const pageSize = 5;

            function renderModalEvents() {
                list.replaceChildren();
                paginationContainer.replaceChildren();

                const q = (searchQuery || '').toLowerCase().trim();
                const st = (statusFilter || '').toLowerCase().trim();
                const tp = (typeFilter || '').toLowerCase().trim();

                const filtered = allSelectedEvents.filter(ev => {
                    const name = (ev.extendedProps.eventName || ev.title || '').toLowerCase();
                    const room = (ev.extendedProps.roomName || '').toLowerCase();
                    const type = (eventTypeLabels[ev.extendedProps.eventType] || '').toLowerCase();
                    const pic = (ev.extendedProps.picName || '').toLowerCase();
                    const lcLabel = (ev.extendedProps.lifecycleStatusLabel || ev.extendedProps.lifecycle_status_label || '').toLowerCase();
                    const lc = (ev.extendedProps.lifecycleStatus || ev.extendedProps.lifecycle_status || '').toLowerCase();
                    const evType = (ev.extendedProps.eventType || '').toLowerCase();

                    const matchQ = !q || name.includes(q) || room.includes(q) || type.includes(q) || pic.includes(q) || lcLabel.includes(q);
                    const matchSt = !st || lc === st;
                    const matchTp = !tp || evType === tp;

                    return matchQ && matchSt && matchTp;
                });

                const isFiltered = Boolean(q || st || tp);
                subtitle.textContent = isFiltered
                    ? `${filtered.length} dari ${allSelectedEvents.length} agenda cocok dengan filter`
                    : `${allSelectedEvents.length} agenda tersimpan pada tanggal ini`;

                if (filtered.length === 0) {
                    const empty = document.createElement('p');
                    empty.className = 'agenda-dialog-empty';
                    empty.textContent = 'Tidak ada agenda yang cocok dengan pencarian atau filter.';
                    list.append(empty);
                    paginationContainer.style.display = 'none';
                    return;
                }

                paginationContainer.style.display = 'flex';
                const totalPages = Math.ceil(filtered.length / pageSize) || 1;
                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;

                const startIdx = (currentPage - 1) * pageSize;
                const endIdx = Math.min(startIdx + pageSize, filtered.length);
                const pagedItems = filtered.slice(startIdx, endIdx);

                pagedItems.forEach((event) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'agenda-dialog-item';
                    item.style.borderLeftColor = event.borderColor || '#c96a1b';

                    const topRow = document.createElement('div');
                    topRow.className = 'agenda-dialog-item-top';
                    const name = document.createElement('span');
                    name.className = 'agenda-dialog-item-title';
                    name.textContent = event.extendedProps.eventName || event.title;

                    const badgesDiv = document.createElement('div');
                    badgesDiv.className = 'agenda-dialog-item-badges';

                    const typeBadge = document.createElement('span');
                    typeBadge.className = 'agenda-dialog-item-badge';
                    typeBadge.textContent = eventTypeLabels[event.extendedProps.eventType] || 'Agenda';
                    badgesDiv.append(typeBadge);

                    const lc = (event.extendedProps.lifecycleStatus || event.extendedProps.lifecycle_status || '').toLowerCase();
                    const lcLabel = event.extendedProps.lifecycleStatusLabel || event.extendedProps.lifecycle_status_label || '';
                    if (lcLabel) {
                        const statusBadge = document.createElement('span');
                        let badgeBg = '#ecfdf5';
                        let badgeColor = '#047857';
                        let badgeBorder = '#a7f3d0';
                        if (lc === 'in_progress') {
                            badgeBg = '#ecfdf5';
                            badgeColor = '#047857';
                            badgeBorder = '#6ee7b7';
                        } else if (lc === 'completed') {
                            badgeBg = '#f0f9ff';
                            badgeColor = '#0369a1';
                            badgeBorder = '#bae6fd';
                        } else {
                            badgeBg = '#fffbeb';
                            badgeColor = '#b45309';
                            badgeBorder = '#fde68a';
                        }
                        statusBadge.style.cssText = `padding: 2.5px 8px; border-radius: 9999px; background: ${badgeBg}; color: ${badgeColor}; border: 1px solid ${badgeBorder}; font-size: 0.72rem; font-weight: 700; white-space: nowrap; flex-shrink: 0;`;
                        statusBadge.textContent = lcLabel;
                        badgesDiv.append(statusBadge);
                    }

                    topRow.append(name, badgesDiv);

                    const meta = document.createElement('div');
                    meta.className = 'agenda-dialog-item-meta';
                    const timeText = event.allDay ? 'Sepanjang hari' : `${formatTime(event.start)} – ${formatTime(event.end)} WIB`;
                    const roomText = event.extendedProps.roomName || 'Ruangan tidak tersedia';
                    meta.textContent = `${timeText} · ${roomText}`;

                    const actionHint = document.createElement('div');
                    actionHint.className = 'agenda-dialog-item-action';
                    actionHint.textContent = 'Klik untuk melihat detail agenda →';

                    item.append(topRow, meta, actionHint);
                    item.addEventListener('click', (e) => {
                        e.stopPropagation();
                        showEventDetails(event, dateString);
                    });
                    list.append(item);
                });

                // Teks Info Paginasi
                const infoSpan = document.createElement('span');
                infoSpan.textContent = `Menampilkan ${startIdx + 1}–${endIdx} dari ${filtered.length} agenda`;
                paginationContainer.append(infoSpan);

                if (totalPages > 1) {
                    const btnGroup = document.createElement('div');
                    btnGroup.className = 'agenda-dialog-pagination-btns';

                    const prevBtn = document.createElement('button');
                    prevBtn.type = 'button';
                    prevBtn.className = 'agenda-dialog-page-btn';
                    prevBtn.textContent = '← Prev';
                    prevBtn.disabled = currentPage === 1;
                    prevBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (currentPage > 1) {
                            currentPage--;
                            renderModalEvents();
                        }
                    });
                    btnGroup.append(prevBtn);

                    for (let p = 1; p <= totalPages; p++) {
                        const pageBtn = document.createElement('button');
                        pageBtn.type = 'button';
                        pageBtn.className = `agenda-dialog-page-btn ${p === currentPage ? 'active' : ''}`;
                        pageBtn.textContent = String(p);
                        pageBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            currentPage = p;
                            renderModalEvents();
                        });
                        btnGroup.append(pageBtn);
                    }

                    const nextBtn = document.createElement('button');
                    nextBtn.type = 'button';
                    nextBtn.className = 'agenda-dialog-page-btn';
                    nextBtn.textContent = 'Next →';
                    nextBtn.disabled = currentPage === totalPages;
                    nextBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (currentPage < totalPages) {
                            currentPage++;
                            renderModalEvents();
                        }
                    });
                    btnGroup.append(nextBtn);

                    paginationContainer.append(btnGroup);
                }
            }

            searchInput.addEventListener('input', (e) => {
                searchQuery = e.target.value;
                currentPage = 1;
                renderModalEvents();
            });

            statusSelect.addEventListener('change', (e) => {
                statusFilter = e.target.value;
                currentPage = 1;
                renderModalEvents();
            });

            typeSelect.addEventListener('change', (e) => {
                typeFilter = e.target.value;
                currentPage = 1;
                renderModalEvents();
            });

            renderModalEvents();
            dialogContent.append(header, bodyContainer);

            appendWakaForm(dialogContent, dateString);
        }

        function appendWakaForm(container, dateString) {
            if (!wakaAgendaEnabled) return;
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
            container.append(form);
        }

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'id',
            firstDay: 1,
            height: 'auto',
            contentHeight: 'auto',
            expandRows: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: '' // Bagian kanan (Bulan, Minggu, Daftar) dihilangkan sesuai permintaan user
            },
            buttonText: {
                today: 'Hari Ini'
            },
            dayMaxEvents: false,
            dayMaxEventRows: false,
            eventDisplay: 'block',
            displayEventTime: false, // Hanya menampilkan nama agenda saja di badge kalender
            eventOrder: compareEventsByLifecycle,
            eventOrderStrict: true,
            eventDidMount: function(info) {
                if (info.event.extendedProps && info.event.extendedProps.isHoliday) {
                    const titleEl = info.el.querySelector('.fc-event-title');
                    if (titleEl && !titleEl.textContent.trim().startsWith('🚩')) {
                        titleEl.prepend('🚩 ');
                    }
                    info.el.style.fontWeight = '600';
                    info.el.style.fontSize = '0.78rem';
                }
            },
            dayCellClassNames: (info) => [0, 6].includes(info.date.getDay()) ? ['calendar-weekend-day'] : [],
            dayHeaderClassNames: (info) => [0, 6].includes(info.date.getDay()) ? ['calendar-weekend-header'] : [],
            dateClick: (info) => {
                showDateAgendas(info.dateStr.slice(0, 10));
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
                const isToday = info.el.classList.contains('fc-day-today');
                if (isWeekend) {
                    info.el.classList.add('calendar-weekend-day');
                    if (!isToday) {
                        info.el.querySelector('.fc-daygrid-day-number')
                            ?.style.setProperty('color', '#b91c1c', 'important');
                    }
                }
                if (isToday) {
                    const numEl = info.el.querySelector('.fc-daygrid-day-number');
                    if (numEl) {
                        numEl.style.setProperty('background', '#c96a1b', 'important');
                        numEl.style.setProperty('color', '#ffffff', 'important');
                    }
                }
                applyDayColors();
            },
            datesSet: function(info) {
                applyDayColors();

                const curDate = calendar.getDate();
                const curMonth = curDate.getMonth();
                const curYear = curDate.getFullYear();

                const monthSelect = document.getElementById('cal-filter-month');
                const yearInput = document.getElementById('cal-filter-year');
                const btnViewMonth = document.getElementById('btn-view-month');
                const btnViewWeek = document.getElementById('btn-view-week');

                if (monthSelect && monthSelect.value !== String(curMonth)) {
                    monthSelect.value = curMonth;
                }
                if (yearInput && document.activeElement !== yearInput) {
                    yearInput.value = curYear;
                }

                const isWeek = info.view.type.includes('Week');
                if (btnViewMonth) btnViewMonth.classList.toggle('active', !isWeek);
                if (btnViewWeek) btnViewWeek.classList.toggle('active', isWeek);
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
                const clickedCell = info.el.closest('.fc-daygrid-day') || info.el.closest('.fc-timegrid-col');
                const clickedDate = (clickedCell && clickedCell.dataset.date)
                    ? clickedCell.dataset.date
                    : (info.event.startStr ? info.event.startStr.slice(0, 10) : '');
                if (clickedDate) {
                    showDateAgendas(clickedDate);
                    dialog.showModal();
                }
            }
        });

        calendar.render();

        // Pasang widget filter pencarian (Bulan, Tahun, Minggu) ke toolbar kanan kalender
        const rightChunk = calendarEl.querySelector('.fc-header-toolbar .fc-toolbar-chunk:last-child');
        const filterWidget = document.getElementById('calendar-header-filter');
        if (rightChunk && filterWidget) {
            rightChunk.appendChild(filterWidget);
            filterWidget.style.display = 'inline-flex';
        }

        const monthSelect = document.getElementById('cal-filter-month');
        const yearInput = document.getElementById('cal-filter-year');
        const btnViewMonth = document.getElementById('btn-view-month');
        const btnViewWeek = document.getElementById('btn-view-week');

        // Sinkronisasi nilai awal filter
        const initialDate = calendar.getDate();
        if (monthSelect) monthSelect.value = initialDate.getMonth();
        if (yearInput) yearInput.value = initialDate.getFullYear();

        // Fungsi navigasi saat bulan atau tahun diubah
        function navigateCalendar() {
            if (!monthSelect || !yearInput) return;
            const m = parseInt(monthSelect.value, 10);
            const y = parseInt(yearInput.value, 10);
            if (isNaN(y) || y < 1970 || y > 2100) return;
            const curDate = calendar.getDate();
            const daysInMonth = new Date(y, m + 1, 0).getDate();
            const d = Math.min(curDate.getDate(), daysInMonth);
            calendar.gotoDate(new Date(y, m, d));
        }

        if (monthSelect) monthSelect.addEventListener('change', navigateCalendar);
        if (yearInput) {
            // Bisa diketik langsung (otomatis melompat saat 4 digit terketik atau saat enter / blur)
            yearInput.addEventListener('input', function() {
                if (this.value.length === 4) {
                    navigateCalendar();
                }
            });
            yearInput.addEventListener('change', navigateCalendar);
            yearInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    navigateCalendar();
                    this.blur();
                }
            });
        }

        // Toggle tampilan: Bulan vs Minggu
        if (btnViewMonth) {
            btnViewMonth.addEventListener('click', function() {
                calendar.changeView('dayGridMonth');
            });
        }
        if (btnViewWeek) {
            btnViewWeek.addEventListener('click', function() {
                calendar.changeView('timeGridWeek');
            });
        }

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
            // FIX: Hanya tangani klik yang langsung mengenai elemen <dialog> (backdrop luar)
            if (e.target !== dialog) return;

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

        // Cegah klik di dalam panel dialog merambat ke dialog backdrop
        dialogContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Paginasi & Filter Agenda Sekolah di Kolom Kanan / Bawah Kalender (5 data per halaman)
        function initAgendaPagination() {
            const container = document.getElementById('agenda-cards-container');
            if (!container) return;

            const allCards = Array.from(container.querySelectorAll('.agenda-card-item'));
            const paginationBar = document.getElementById('agenda-pagination-bar');
            const infoText = document.getElementById('agenda-pagination-info');
            const prevBtn = document.getElementById('agenda-pagination-prev');
            const nextBtn = document.getElementById('agenda-pagination-next');
            const pagesContainer = document.getElementById('agenda-pagination-pages');
            const badgeCount = document.getElementById('side-agenda-count-badge');
            const emptySearch = document.getElementById('side-agenda-empty-search');

            const searchInput = document.getElementById('side-agenda-search');
            const statusSelect = document.getElementById('side-agenda-filter-status');
            const typeSelect = document.getElementById('side-agenda-filter-type');

            const itemsPerPage = 5;
            let currentPage = 1;
            let matchedCards = allCards;

            function applyFiltersAndRender(page = 1) {
                const q = (searchInput?.value || '').toLowerCase().trim();
                const st = (statusSelect?.value || '').toLowerCase().trim();
                const tp = (typeSelect?.value || '').toLowerCase().trim();

                matchedCards = allCards.filter(card => {
                    const title = card.dataset.title || '';
                    const room = card.dataset.room || '';
                    const pic = card.dataset.pic || '';
                    const cardType = card.dataset.type || '';
                    const cardStatus = card.dataset.status || '';

                    const matchQ = !q || title.includes(q) || room.includes(q) || pic.includes(q) || cardType.includes(q);
                    const matchSt = !st || cardStatus === st;
                    const matchTp = !tp || cardType === tp;

                    return matchQ && matchSt && matchTp;
                });

                if (badgeCount) {
                    badgeCount.textContent = (q || st || tp)
                        ? `${matchedCards.length} dari ${allCards.length} Agenda`
                        : `${allCards.length} Agenda`;
                }

                allCards.forEach(c => c.style.display = 'none');

                if (matchedCards.length === 0) {
                    if (emptySearch) emptySearch.style.display = 'block';
                    if (paginationBar) paginationBar.style.display = 'none';
                    return;
                }

                if (emptySearch) emptySearch.style.display = 'none';
                if (paginationBar) paginationBar.style.display = 'flex';

                const totalPages = Math.ceil(matchedCards.length / itemsPerPage) || 1;
                currentPage = Math.max(1, Math.min(page, totalPages));
                const start = (currentPage - 1) * itemsPerPage;
                const end = Math.min(start + itemsPerPage, matchedCards.length);

                matchedCards.forEach((card, index) => {
                    card.style.display = (index >= start && index < end) ? 'flex' : 'none';
                });

                if (infoText) {
                    infoText.textContent = `Menampilkan ${start + 1}–${end} dari ${matchedCards.length}`;
                }

                if (prevBtn) {
                    prevBtn.disabled = (currentPage === 1);
                }
                if (nextBtn) {
                    nextBtn.disabled = (currentPage === totalPages);
                }

                if (pagesContainer) {
                    pagesContainer.replaceChildren();
                    if (totalPages > 1) {
                        for (let i = 1; i <= totalPages; i++) {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'agenda-page-btn' + (i === currentPage ? ' active' : '');
                            btn.textContent = i;
                            btn.setAttribute('aria-label', `Halaman ${i}`);
                            btn.addEventListener('click', (e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                applyFiltersAndRender(i);
                            });
                            pagesContainer.appendChild(btn);
                        }
                    }
                }
            }

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    currentPage = 1;
                    applyFiltersAndRender(1);
                });
            }

            if (statusSelect) {
                statusSelect.addEventListener('change', () => {
                    currentPage = 1;
                    applyFiltersAndRender(1);
                });
            }

            if (typeSelect) {
                typeSelect.addEventListener('change', () => {
                    currentPage = 1;
                    applyFiltersAndRender(1);
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (currentPage > 1) applyFiltersAndRender(currentPage - 1);
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    applyFiltersAndRender(currentPage + 1);
                });
            }

            applyFiltersAndRender(1);
        }

        initAgendaPagination();

        function applyDayColors() {
            calendarEl.querySelectorAll('.fc-daygrid-day[data-date]').forEach((cell) => {
                const dateString = cell.dataset.date;
                const cellEvents = calendar.getEvents().filter((event) => eventOccursOnDate(event, dateString));
                const holidayEvent = cellEvents.find((event) => event.extendedProps && event.extendedProps.isHoliday);

                // Tanggal Merah untuk Hari Libur Nasional
                if (holidayEvent) {
                    cell.classList.add('calendar-holiday-day');
                    const numEl = cell.querySelector('.fc-daygrid-day-number');
                    if (numEl && !cell.classList.contains('fc-day-today')) {
                        numEl.style.setProperty('color', '#dc2626', 'important');
                        numEl.style.setProperty('font-weight', '700', 'important');
                    }
                }

                const colors = [...new Set(cellEvents
                    .filter((event) => !(event.extendedProps && event.extendedProps.isHoliday))
                    .map((event) => event.backgroundColor)
                    .filter(Boolean))];
                const frame = cell.querySelector('.fc-daygrid-day-frame');
                if (!frame) return;

                if (colors.length === 0 && holidayEvent) {
                    frame.style.background = 'rgba(254, 226, 226, 0.35)';
                } else if (colors.length === 1) {
                    frame.style.background = `color-mix(in srgb, ${colors[0]} 16%, white)`;
                } else if (colors.length > 1) {
                    frame.style.background = `linear-gradient(135deg, ${colors.map((color, index) => `color-mix(in srgb, ${color} 16%, white) ${index * 100 / colors.length}% ${(index + 1) * 100 / colors.length}%`).join(', ')})`;
                } else {
                    frame.style.background = '';
                }
            });
        }
    });
</script>
@endsection