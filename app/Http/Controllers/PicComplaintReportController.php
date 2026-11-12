<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Room;
use App\Support\ReportPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PicComplaintReportController extends Controller
{
    public function index(Request $request)
    {
        $this->ensurePic();
        $period = ReportPeriod::resolve($request);
        $search = trim((string) $request->input('search', ''));
        $status = $request->filled('status') ? (string) $request->input('status') : null;
        $roomId = $request->filled('room_id') ? (int) $request->input('room_id') : null;

        $complaints = $this->periodComplaints($period['start'], $period['end'], $search, $status, $roomId);

        $user = auth()->user();
        $assignedRooms = $user ? $user->rooms()->orderBy('room_name')->get() : collect();
        $rooms = $assignedRooms->isNotEmpty() ? $assignedRooms : Room::where('is_active', 'Aktif')->orderBy('room_name')->get();

        $statistics = [
            'total' => $complaints->count(),
            'not_repaired' => $complaints->where('status_repair', 'Belum Diperbaiki')->count(),
            'in_repair' => $complaints->where('status_repair', 'Sedang Diperbaiki')->count(),
            'waiting_parts' => $complaints->where('status_repair', 'Menunggu Spare Part')->count(),
            'repaired' => $complaints->where('status_repair', 'Sudah Diperbaiki')->count(),
        ];

        return view('reports.pic-complaints', compact('period', 'complaints', 'rooms', 'statistics', 'search', 'status', 'roomId'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->ensurePic();
        $period = ReportPeriod::resolve($request, true);
        $search = trim((string) $request->input('search', ''));
        $status = $request->filled('status') ? (string) $request->input('status') : null;
        $roomId = $request->filled('room_id') ? (int) $request->input('room_id') : null;

        $complaints = $this->periodComplaints($period['start'], $period['end'], $search, $status, $roomId);

        return response()->streamDownload(function () use ($complaints): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Tidak dapat membuka aliran ekspor laporan.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal Laporan', 'Tanggal Selesai', 'Ruang', 'Deskripsi Kerusakan', 'Pelapor', 'Username', 'Peran', 'Email', 'Nomor Telepon', 'Status Perbaikan']);

            foreach ($complaints as $complaint) {
                fputcsv($output, [
                    $complaint->reported_at?->format('Y-m-d H:i') ?? '-',
                    $complaint->repaired_at?->format('Y-m-d H:i') ?? '-',
                    $this->safeCsvValue($complaint->room?->room_name ?? '-'),
                    $this->safeCsvValue($complaint->description),
                    $this->safeCsvValue($complaint->reporter?->fullname ?? '-'),
                    $this->safeCsvValue($complaint->reporter?->username ?? '-'),
                    $this->safeCsvValue($complaint->reporter?->role?->role_name ?? '-'),
                    $this->safeCsvValue($complaint->reporter?->email ?? '-'),
                    $this->safeCsvValue($complaint->reporter?->phone_number ?? '-'),
                    $this->safeCsvValue($complaint->status_repair),
                ]);
            }

            fclose($output);
        }, 'laporan-kerusakan-'.$period['slug'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request)
    {
        $this->ensurePic();
        $period = ReportPeriod::resolve($request, true);
        $search = trim((string) $request->input('search', ''));
        $status = $request->filled('status') ? (string) $request->input('status') : null;
        $roomId = $request->filled('room_id') ? (int) $request->input('room_id') : null;

        $complaints = $this->periodComplaints($period['start'], $period['end'], $search, $status, $roomId);
        $pic = $request->user();

        return Pdf::loadView('reports.pic-complaints-pdf', compact('period', 'complaints', 'pic', 'search', 'status', 'roomId'))
            ->setPaper('a4', 'landscape')
            ->download('laporan-kerusakan-'.$period['slug'].'.pdf');
    }

    public function exportApiPdf(Request $request)
    {
        $pic = $request->user();
        abort_unless(str_contains(strtolower($pic->role?->role_name ?? ''), 'pic'), 403);

        $period = ReportPeriod::resolve($request, true);
        $search = trim((string) $request->input('search', ''));
        $status = $request->filled('status') ? (string) $request->input('status') : null;
        $roomId = $request->filled('room_id') ? (int) $request->input('room_id') : null;

        $roomIds = $pic->rooms()->pluck('rooms.id');
        if ($roomIds->isEmpty()) {
            $roomIds = Room::pluck('id');
        }
        $complaints = Complaint::with(['room', 'reporter.role'])
            ->where(function ($query) use ($roomIds, $pic) {
                $query->whereIn('room_id', $roomIds)
                    ->orWhere('handler_id', $pic->id);
            })
            ->whereBetween('reported_at', [$period['start'], $period['end']])
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->when($status, fn ($q) => $q->where('status_repair', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('room', fn ($roomQ) => $roomQ->where('room_name', 'like', "%{$search}%"))
                        ->orWhereHas('reporter', fn ($userQ) => $userQ
                            ->where('fullname', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%"));
                });
            })
            ->orderBy('reported_at')
            ->get();

        $pdf = Pdf::loadView('reports.pic-complaints-pdf', compact('period', 'complaints', 'pic', 'search', 'status', 'roomId'))
            ->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="laporan-kerusakan-'.$period['slug'].'.pdf"',
        ]);
    }

    private function periodComplaints(Carbon $start, Carbon $end, ?string $search = null, ?string $status = null, ?int $roomId = null): Collection
    {
        $picId = auth()->id();
        $user = auth()->user();
        $roomIds = $user ? $user->rooms()->pluck('rooms.id') : collect();
        if ($roomIds->isEmpty()) {
            $roomIds = Room::pluck('id');
        }

        return Complaint::with(['room', 'reporter.role'])
            ->where(function ($query) use ($picId, $roomIds) {
                $query->whereIn('room_id', $roomIds)
                    ->orWhere('handler_id', $picId);
            })
            ->whereBetween('reported_at', [$start, $end])
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->when($status, fn ($q) => $q->where('status_repair', $status))
            ->when(! empty($search), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('room', fn ($roomQ) => $roomQ->where('room_name', 'like', "%{$search}%"))
                        ->orWhereHas('reporter', fn ($userQ) => $userQ
                            ->where('fullname', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%"));
                });
            })
            ->orderBy('reported_at')
            ->get();
    }

    private function safeCsvValue(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    private function ensurePic(): void
    {
        if (! str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'pic')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh PIC Ruang.');
        }
    }
}
