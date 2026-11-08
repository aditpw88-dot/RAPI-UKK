<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ match ($period['type']) { 'daily' => 'Harian', 'weekly' => 'Mingguan', default => 'Bulanan' } }} {{ $period['label'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #292524; font-size: 10px; }
        h1 { font-size: 20px; margin: 0 0 6px; color: #171513; }
        h2 { font-size: 14px; margin: 18px 0 8px; }
        p { margin: 4px 0; color: #57534e; }
        body > p:first-child { color: #b45309; }
        .summary { width: 100%; margin: 14px 0; }
        .summary td { width: 33.333%; padding: 9px; background: #faf6ef; border: 1px solid #e7ddd0; }
        .summary strong { display: block; margin-top: 5px; font-size: 16px; }
        .summary td:last-child strong { color: #b45309; }
        table.data { width: 100%; border-collapse: collapse; }
        .data th, .data td { padding: 6px; border: 1px solid #e7ddd0; text-align: left; vertical-align: top; }
        .data th { background: #eee7dc; }
        .applicant-details { font-size: 11px; line-height: 1.45; }
        .footer { margin-top: 18px; border-top: 1px solid #e7ddd0; padding-top: 8px; font-size: 9px; }
    </style>
</head>
<body>
    <p><strong>SISTEM RAPI</strong></p>
    <h1>Laporan {{ match ($period['type']) { 'daily' => 'Harian', 'weekly' => 'Mingguan', default => 'Bulanan' } }} Peminjaman Ruang</h1>
    <p>Periode: {{ $period['label'] }}</p>
    <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>

    <table class="summary">
        <tr>
            <td>Total Peminjaman<strong>{{ $events->count() }}</strong></td>
            <td>Belum Terlaksana<strong>{{ $events->where('lifecycle_status', 'upcoming')->count() }}</strong></td>
            <td>Sedang Terlaksana<strong>{{ $events->where('lifecycle_status', 'in_progress')->count() }}</strong></td>
            <td>Sudah Terlaksana<strong>{{ $events->where('lifecycle_status', 'completed')->count() }}</strong></td>
        </tr>
    </table>

    <h2>Daftar Peminjaman Ruang</h2>
    <table class="data">
        <thead><tr><th>Tanggal</th><th>Agenda</th><th>Ruang</th><th>Pengaju</th><th>Waktu</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($events as $event)
                <tr>
                    <td><x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /></td>
                    <td>{{ $event->event_name }}</td>
                    <td>{{ $event->room_names ?: '-' }}</td>
                    <td>
                        @if ($event->pic)
                            <strong>{{ $event->pic->fullname }}</strong><br>
                            <span class="applicant-details">
                                Username: {{ $event->pic->username }}<br>
                                Peran: {{ $event->pic->role?->role_name ?? '-' }}<br>
                                Email: {{ $event->pic->email }}<br>
                                Telepon: {{ $event->pic->phone_number ?: '-' }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }}</td>
                    <td>{{ $event->lifecycle_status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Tidak ada peminjaman pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Dokumen laporan periode Sistem RAPI.</p>
</body>
</html>
