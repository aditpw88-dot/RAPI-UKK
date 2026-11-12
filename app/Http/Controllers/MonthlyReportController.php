<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Room;
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
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $roomId = (string) $request->query('room_id', '');

        $events = $this->periodEvents($period['start'], $period['end'], $search, $status, $roomId);
        $rooms = Room::orderBy('room_name')->get();

        return view('reports.monthly', [
            'period' => $period,
            'events' => $events,
            'rooms' => $rooms,
            'search' => $search,
            'status' => $status,
            'roomId' => $roomId,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $period = ReportPeriod::resolve($request, true);
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $roomId = (string) $request->query('room_id', '');

        $events = $this->periodEvents($period['start'], $period['end'], $search, $status, $roomId);
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
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $roomId = (string) $request->query('room_id', '');

        $events = $this->periodEvents($period['start'], $period['end'], $search, $status, $roomId);

        return Pdf::loadView('reports.monthly-pdf', [
            'period' => $period,
            'events' => $events,
        ])
            ->setPaper('a4', 'landscape')
            ->download('laporan-'.$period['slug'].'.pdf');
    }

    private function periodEvents(
        Carbon $start,
        Carbon $end,
        ?string $search = null,
        ?string $status = null,
        ?string $roomId = null
    ): Collection {
        return Event::with(['room', 'rooms', 'pic.role', 'approver.role'])
            ->whereDate('date', '<=', $end->toDateString())
            ->whereRaw('COALESCE(end_date, date) >= ?', [$start->toDateString()])
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('event_name', 'like', "%{$search}%")
                        ->orWhereHas('rooms', fn ($query) => $query->where('room_name', 'like', "%{$search}%"))
                        ->orWhereHas('room', fn ($query) => $query->where('room_name', 'like', "%{$search}%"))
                        ->orWhereHas('pic', fn ($query) => $query->where('fullname', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
                });
            })
            ->when($roomId !== null && $roomId !== '', function ($query) use ($roomId) {
                $query->where(function ($query) use ($roomId) {
                    $query->where('room_id', $roomId)
                        ->orWhereHas('rooms', fn ($query) => $query->where('rooms.id', $roomId));
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                match ($status) {
                    'upcoming' => $query->approvedUpcoming(),
                    'in_progress' => $query->approvedInProgress(),
                    'completed' => $query->approvedCompleted(),
                    'cancelled' => $query->where('status', 'cancelled'),
                    'rejected' => $query->where('status', 'rejected'),
                    default => null,
                };
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    private function safeCsvValue(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
