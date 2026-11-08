<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuruDashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        if (! str_contains(strtolower($user->role?->role_name ?? ''), 'guru')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Guru/Organisasi.');
        }

        $events = Event::with(['room', 'rooms'])
            ->where('pic_id', $user->id)
            ->latest('date')
            ->latest('start_time')
            ->get();
        $inProgressEvents = $events->where('lifecycle_status', 'in_progress')->count();
        $pendingEvents = $events->where('status', 'pending')->count();
        $approvedEventsCount = $events->where('status', 'approved')->count();

        $openComplaints = Complaint::where('reporter_id', $user->id)
            ->where('status_repair', '!=', 'Sudah Diperbaiki')
            ->count();

        $recentComplaints = Complaint::with(['room'])
            ->where('reporter_id', $user->id)
            ->latest('reported_at')
            ->take(5)
            ->get();

        return view('guru.dashboard', [
            'events' => $events,
            'approvedEvents' => $inProgressEvents,
            'pendingEvents' => $pendingEvents,
            'approvedEventsCount' => $approvedEventsCount,
            'openComplaints' => $openComplaints,
            'recentComplaints' => $recentComplaints,
        ]);
    }

    public function repairStatus(Request $request): View
    {
        $user = Auth::user();

        if (! str_contains(strtolower($user->role?->role_name ?? ''), 'guru')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Guru/Organisasi.');
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:Belum Diperbaiki,Menunggu Spare Part,Sudah Diperbaiki'],
        ]);
        $search = trim($filters['search'] ?? '');

        $userComplaints = Complaint::query()->where('reporter_id', $user->id);
        $statusCounts = (clone $userComplaints)
            ->selectRaw('status_repair, COUNT(*) as total')
            ->groupBy('status_repair')
            ->pluck('total', 'status_repair');
        $statistics = [
            'total' => $statusCounts->sum(),
            'not_repaired' => (int) $statusCounts->get('Belum Diperbaiki', 0),
            'waiting_parts' => (int) $statusCounts->get('Menunggu Spare Part', 0),
            'repaired' => (int) $statusCounts->get('Sudah Diperbaiki', 0),
        ];

        $complaints = Complaint::with(['room', 'handler', 'reporter.role'])
            ->where('reporter_id', $user->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhereHas('room', fn ($query) => $query->where('room_name', 'like', "%{$search}%"))
                        ->orWhereHas('handler', fn ($query) => $query->where('fullname', 'like', "%{$search}%"));
                });
            })
            ->when(isset($filters['status']), fn ($query) => $query->where('status_repair', $filters['status']))
            ->latest('reported_at')
            ->paginate(10)
            ->withQueryString();

        return view('guru.complaints.status', [
            'complaints' => $complaints,
            'statistics' => $statistics,
        ]);
    }

    public function eventHistory(Request $request): View
    {
        $user = Auth::user();

        if (! str_contains(strtolower($user->role?->role_name ?? ''), 'guru')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Guru/Organisasi.');
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,approved,upcoming,in_progress,completed,rejected'],
        ]);
        $search = trim($filters['search'] ?? '');

        $events = Event::with(['room', 'rooms'])
            ->where('pic_id', $user->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('event_name', 'like', "%{$search}%")
                        ->orWhereHas('room', fn ($query) => $query->where('room_name', 'like', "%{$search}%"));
                });
            })
            ->when(isset($filters['status']), function ($query) use ($filters) {
                match ($filters['status']) {
                    'upcoming' => $query->approvedUpcoming(),
                    'in_progress' => $query->approvedInProgress(),
                    'completed' => $query->approvedCompleted(),
                    'approved' => $query->where('status', 'approved'),
                    default => $query->where('status', $filters['status']),
                };
            })
            ->get();

        $events = Event::sortByLifecycle($events);

        return view('guru.events.history', [
            'events' => $events,
        ]);
    }
}
