<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Kerusakan {{ $period['label'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #29211b; font-size: 10px; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #1e293b; }
        h2 { margin: 14px 0 6px; border-bottom: 2px solid #c96a1b; padding-bottom: 4px; color: #493524; font-size: 13px; }
        p { margin: 3px 0; color: #64748b; }
        .summary { width: 100%; margin: 12px 0; border-collapse: collapse; }
        .summary td { width: 20%; border: 1px solid #e2e8f0; padding: 7px; background: #f8fafc; color: #334155; text-align: center; }
        .summary strong { display: block; margin-top: 3px; color: #0f172a; font-size: 14px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .data th, .data td { border: 1px solid #e2e8f0; padding: 5px 6px; text-align: left; vertical-align: top; }
        .data th { background: #f1f5f9; color: #334155; font-size: 9px; text-transform: uppercase; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 8.5px; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .badge-emerald { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <p style="font-weight: bold; color: #b45309; text-transform: uppercase; font-size: 9px; margin-bottom: 2px;">SISTEM RAPI · LAPORAN PIC RUANG</p>
    <h1>Laporan Rekapitulasi Kerusakan {{ match ($period['type']) { 'daily' => 'Harian', 'weekly' => 'Mingguan', default => 'Bulanan' } }}</h1>
    <p>Periode: <strong>{{ $period['label'] }}</strong> · PIC: <strong>{{ $pic->fullname }}</strong> · Dicetak pada: {{ now()->format('d/m/Y H:i') }} WIB</p>
    @if (!empty($search) || !empty($status) || !empty($roomId))
        <p style="font-size: 9px; color: #92400e;">
            <strong>Filter Terpasang:</strong>
            @if(!empty($roomId)) Ruangan ID: {{ $roomId }}; @endif
            @if(!empty($status)) Status: {{ $status }}; @endif
            @if(!empty($search)) Kata Kunci: "{{ $search }}"; @endif
        </p>
    @endif

    <table class="summary">
        <tr>
            <td>Total Laporan<strong>{{ $complaints->count() }}</strong></td>
            <td style="background: #fff5f5; color: #991b1b;">Belum Diperbaiki<strong>{{ $complaints->where('status_repair', 'Belum Diperbaiki')->count() }}</strong></td>
            <td style="background: #eff6ff; color: #1e40af;">Sedang Diperbaiki<strong>{{ $complaints->where('status_repair', 'Sedang Diperbaiki')->count() }}</strong></td>
            <td style="background: #fffbeb; color: #92400e;">Menunggu Spare Part<strong>{{ $complaints->where('status_repair', 'Menunggu Spare Part')->count() }}</strong></td>
            <td style="background: #ecfdf5; color: #065f46;">Sudah Diperbaiki<strong>{{ $complaints->where('status_repair', 'Sudah Diperbaiki')->count() }}</strong></td>
        </tr>
    </table>

    <h2>Daftar Rincian Kerusakan</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 15%;">Ruang</th>
                <th style="width: 28%;">Deskripsi Kerusakan</th>
                <th style="width: 22%;">Pelapor</th>
                <th style="width: 13%;">Status</th>
                <th style="width: 10%;">Selesai</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($complaints as $complaint)
                @php
                    $badgeClass = match ($complaint->status_repair) {
                        'Sudah Diperbaiki' => 'badge-emerald',
                        'Sedang Diperbaiki' => 'badge-blue',
                        'Menunggu Spare Part' => 'badge-amber',
                        default => 'badge-red',
                    };
                @endphp
                <tr>
                    <td>{{ $complaint->reported_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td><strong>{{ $complaint->room?->room_name ?? '-' }}</strong></td>
                    <td>{{ $complaint->description }}</td>
                    <td>
                        @if ($complaint->reporter)
                            <strong>{{ $complaint->reporter->fullname }}</strong><br>
                            Username: {{ $complaint->reporter->username }} · {{ $complaint->reporter->role?->role_name ?? '-' }}<br>
                            Email: {{ $complaint->reporter->email }}<br>
                            Telp: {{ $complaint->reporter->phone_number ?: '-' }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $badgeClass }}">{{ $complaint->status_repair }}</span>
                    </td>
                    <td>
                        {{ $complaint->repaired_at?->format('d/m/Y H:i') ?? ($complaint->status_repair === 'Sudah Diperbaiki' ? 'Selesai' : '-') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align: center; padding: 12px; color: #94a3b8;">Tidak ada laporan kerusakan pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
