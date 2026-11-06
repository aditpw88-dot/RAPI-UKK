<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\ReportPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyReportController extends Controller
{
    public function index(Request $request)
    {
        $period = ReportPeriod::resolve($request);
        $events = $this->periodEvents($period['start'], $period['end']);

        return view('reports.monthly', [
            'period' => $period,
            'events' => $events,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $period = ReportPeriod::resolve($request, true);
        $events = $this->periodEvents($period['start'], $period['end']);
        $filename = 'laporan-'.$period['slug'].'.csv';

        return response()->streamDownload(function () use ($events): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Tidak dapat membuka aliran ekspor laporan.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'Agenda Peminjaman', 'Ruang', 'Nama Pengaju', 'Username', 'Peran', 'Email', 'Nomor Telepon', 'Status', 'Waktu']);

            foreach ($events as $event) {
                fputcsv($output, [
                    $event->date . (($event->end_date && $event->end_date !== $event->date) ? ' - ' . $event->end_date : ''),
                    $this->safeCsvValue($event->event_name),
                    $this->safeCsvValue($event->room_names ?: '-'),
                    $this->safeCsvValue($event->pic?->fullname ?? '-'),
                    $this->safeCsvValue($event->pic?->username ?? '-'),
                    $this->safeCsvValue($event->pic?->role?->role_name ?? '-'),
                    $this->safeCsvValue($event->pic?->email ?? '-'),
                    $this->safeCsvValue($event->pic?->phone_number ?? '-'),
                    $event->lifecycle_status_label,
                    substr($event->start_time, 0, 5).' - '.substr($event->end_time, 0, 5),
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request)
    {
        $period = ReportPeriod::resolve($request, true);
        $events = $this->periodEvents($period['start'], $period['end']);

        return Pdf::loadView('reports.monthly-pdf', [
            'period' => $period,
            'events' => $events,
        ])
            ->setPaper('a4', 'landscape')
            ->download('laporan-'.$period['slug'].'.pdf');
    }

    private function periodEvents(Carbon $start, Carbon $end): Collection
    {
        return Event::with(['room', 'rooms', 'pic.role', 'approver.role'])
            ->whereDate('date', '<=', $end->toDateString())
            ->whereRaw('COALESCE(end_date, date) >= ?', [$start->toDateString()])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    private function safeCsvValue(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
