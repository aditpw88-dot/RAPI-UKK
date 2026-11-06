<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Kerusakan {{ $period['label'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #29211b; font-size: 10px; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        h2 { margin: 18px 0 8px; border-bottom: 2px solid #c96a1b; padding-bottom: 5px; color: #493524; font-size: 14px; }
        p { margin: 4px 0; color: #78634c; }
        .summary { width: 100%; margin: 14px 0; }
        .summary td { width: 25%; border: 1px solid #e6d3bc; padding: 9px; background: #fbf2e6; color: #493524; }
        .summary strong { display: block; margin-top: 5px; color: #7c451e; font-size: 16px; }
        table.data { width: 100%; border-collapse: collapse; }
        .data th, .data td { border: 1px solid #e6d3bc; padding: 6px; text-align: left; vertical-align: top; }
        .data th { background: #f4e9d8; color: #493524; }
    </style>
</head>
<body>
    <p><strong>SISTEM RAPI · LAPORAN PIC</strong></p>
    <h1>Laporan Kerusakan {{ match ($period['type']) { 'daily' => 'Harian', 'weekly' => 'Mingguan', default => 'Bulanan' } }}</h1>
    <p>Periode: {{ $period['label'] }}</p>
    <p>PIC: {{ $pic->fullname }} · Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
    <table class="summary">
        <tr>
            <td>Total Laporan<strong>{{ $complaints->count() }}</strong></td>
            <td>Belum Diperbaiki<strong>{{ $complaints->where('status_repair', 'Belum Diperbaiki')->count() }}</strong></td>
            <td>Menunggu Spare Part<strong>{{ $complaints->where('status_repair', 'Menunggu Spare Part')->count() }}</strong></td>
            <td>Sudah Diperbaiki<strong>{{ $complaints->where('status_repair', 'Sudah Diperbaiki')->count() }}</strong></td>
        </tr>
    </table>
    <h2>Daftar Laporan Kerusakan</h2>
    <table class="data">
        <thead><tr><th>Tanggal Laporan</th><th>Ruang</th><th>Deskripsi</th><th>Pelapor</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($complaints as $complaint)
                <tr>
                    <td>{{ $complaint->reported_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $complaint->room?->room_name ?? '-' }}</td>
                    <td>{{ $complaint->description }}</td>
                    <td>
                        @if ($complaint->reporter)
                            <strong>{{ $complaint->reporter->fullname }}</strong><br>
                            Username: {{ $complaint->reporter->username }}<br>
                            Peran: {{ $complaint->reporter->role?->role_name ?? '-' }}<br>
                            Email: {{ $complaint->reporter->email }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $complaint->status_repair }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada laporan kerusakan pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
