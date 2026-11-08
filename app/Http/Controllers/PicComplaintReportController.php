<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
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
        $complaints = $this->periodComplaints($period['start'], $period['end']);

        return view('reports.pic-complaints', compact('period', 'complaints'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->ensurePic();
        $period = ReportPeriod::resolve($request, true);
        $complaints = $this->periodComplaints($period['start'], $period['end']);

        return response()->streamDownload(function () use ($complaints): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Tidak dapat membuka aliran ekspor laporan.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal Laporan', 'Ruang', 'Deskripsi Kerusakan', 'Pelapor', 'Username', 'Peran', 'Email', 'Nomor Telepon', 'Status Perbaikan']);

            foreach ($complaints as $complaint) {
                fputcsv($output, [
                    $complaint->reported_at?->format('Y-m-d H:i') ?? '-',
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
        $complaints = $this->periodComplaints($period['start'], $period['end']);
        $pic = $request->user();

        return Pdf::loadView('reports.pic-complaints-pdf', compact('period', 'complaints', 'pic'))
            ->setPaper('a4', 'landscape')
            ->download('laporan-kerusakan-'.$period['slug'].'.pdf');
    }

    public function exportApiPdf(Request $request)
    {
        $pic = $request->user();
        abort_unless(str_contains(strtolower($pic->role?->role_name ?? ''), 'pic'), 403);

        $period = ReportPeriod::resolve($request, true);
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
            ->orderBy('reported_at')
            ->get();

        $pdf = Pdf::loadView('reports.pic-complaints-pdf', compact('period', 'complaints', 'pic'))
            ->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="laporan-kerusakan-'.$period['slug'].'.pdf"',
        ]);
    }

    private function periodComplaints(Carbon $start, Carbon $end): Collection
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
