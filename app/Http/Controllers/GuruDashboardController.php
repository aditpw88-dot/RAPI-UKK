<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GuruDashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        if (! str_contains(strtolower($user->role?->role_name ?? ''), 'guru')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Guru/Organisasi.');
        }

        $events = Event::with(['room', 'rooms', 'cancellationApprover'])
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
            'status' => ['nullable', 'in:Belum Diperbaiki,Sedang Diperbaiki,Menunggu Spare Part,Sudah Diperbaiki'],
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
            'in_repair' => (int) $statusCounts->get('Sedang Diperbaiki', 0),
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
            'status' => ['nullable', 'in:pending,approved,upcoming,in_progress,completed,rejected,cancelled,cancellation_pending'],
        ]);
        $search = trim($filters['search'] ?? '');

        $events = Event::with(['room', 'rooms', 'cancellationApprover'])
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
                    'cancellation_pending' => $query->where('cancellation_status', 'pending'),
                    'cancelled' => $query->where('status', 'cancelled'),
                    default => $query->where('status', $filters['status']),
                };
            })
            ->get();

        $events = Event::sortByLifecycle($events);

        return view('guru.events.history', [
            'events' => $events,
        ]);
    }

    public function requestCancellation(Request $request, Event $event): RedirectResponse
    {
        $user = Auth::user();

        if (! str_contains(strtolower($user->role?->role_name ?? ''), 'guru')) {
            abort(403, 'Aksi ini hanya dapat dilakukan oleh Guru/Organisasi.');
        }

        if ((int) $event->pic_id !== (int) $user->id) {
            abort(403, 'Anda hanya dapat mengajukan pembatalan untuk agenda milik Anda sendiri.');
        }

        if (! $event->canBeCancelledByGuru()) {
            return back()->with('error', 'Agenda ini tidak dapat dibatalkan (sudah selesai, ditolak, atau sudah dalam proses pembatalan).');
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'cancellation_reason.required' => 'Mohon isi alasan pembatalan agenda.',
            'cancellation_reason.min' => 'Alasan pembatalan minimal 5 karakter.',
            'cancellation_reason.max' => 'Alasan pembatalan maksimal 1000 karakter.',
        ]);

        $event->update([
            'cancellation_status' => 'pending',
            'cancellation_reason' => trim($validated['cancellation_reason']),
            'cancellation_requested_at' => now(),
            'cancellation_rejected_reason' => null,
        ]);

        return back()->with('success', 'Pengajuan pembatalan agenda berhasil dikirim. Menunggu persetujuan dari Admin atau PIC Ruang.');
    }

    /**
     * Manajemen Agenda untuk Guru: melihat semua agenda di sekolah
     * dilengkapi shortcut untuk membuat agenda sendiri.
     */
    public function eventsIndex(Request $request): View
    {
        $user = Auth::user();

        if (! str_contains(strtolower($user->role?->role_name ?? ''), 'guru')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Guru/Organisasi.');
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,approved,upcoming,in_progress,completed,rejected,cancelled,cancellation_pending'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
        $search = trim($filters['search'] ?? '');

        $query = Event::with(['room', 'rooms', 'pic.role', 'approver.role', 'cancellationApprover.role']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('event_name', 'like', "%{$search}%")
                    ->orWhereHas('rooms', fn ($sub) => $sub->where('room_name', 'like', "%{$search}%"))
                    ->orWhereHas('room', fn ($sub) => $sub->where('room_name', 'like', "%{$search}%"))
                    ->orWhereHas('pic', fn ($sub) => $sub->where('fullname', 'like', "%{$search}%"));
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            match ($filters['status']) {
                'upcoming' => $query->approvedUpcoming(),
                'in_progress' => $query->approvedInProgress(),
                'completed' => $query->approvedCompleted(),
                'approved' => $query->where('status', 'approved'),
                'cancellation_pending' => $query->where('cancellation_status', 'pending'),
                'cancelled' => $query->where('status', 'cancelled'),
                default => $query->where('status', $filters['status']),
            };
        }

        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->whereRaw('COALESCE(end_date, date) >= ?', [$filters['date_from']]);
        }

        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        $eventsList = $query->latest('date')->latest('start_time')->get();
        $sortedEvents = Event::sortByLifecycle($eventsList);

        $perPage = 15;
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $sortedEvents->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $events = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $sortedEvents->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('guru.events.index', [
            'events' => $events,
        ]);
    }

    /**
     * Manajemen Kerusakan untuk Guru: melihat semua kerusakan yang diadukan person lain
     * dilengkapi shortcut untuk membuat laporan kerusakan sendiri.
     */
    public function complaintsIndex(Request $request): View
    {
        $user = Auth::user();

        if (! str_contains(strtolower($user->role?->role_name ?? ''), 'guru')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Guru/Organisasi.');
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:Belum Diperbaiki,Sedang Diperbaiki,Menunggu Spare Part,Sudah Diperbaiki'],
        ]);
        $search = trim($filters['search'] ?? '');

        $allComplaints = Complaint::query();
        $statusCounts = (clone $allComplaints)
            ->selectRaw('status_repair, COUNT(*) as total')
            ->groupBy('status_repair')
            ->pluck('total', 'status_repair');

        $statistics = [
            'total' => $statusCounts->sum(),
            'not_repaired' => (int) $statusCounts->get('Belum Diperbaiki', 0),
            'in_repair' => (int) $statusCounts->get('Sedang Diperbaiki', 0),
            'waiting_parts' => (int) $statusCounts->get('Menunggu Spare Part', 0),
            'repaired' => (int) $statusCounts->get('Sudah Diperbaiki', 0),
        ];

        $complaints = Complaint::with(['room', 'handler', 'reporter.role'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('room', fn ($sub) => $sub->where('room_name', 'like', "%{$search}%"))
                        ->orWhereHas('reporter', fn ($sub) => $sub->where('fullname', 'like', "%{$search}%"))
                        ->orWhereHas('handler', fn ($sub) => $sub->where('fullname', 'like', "%{$search}%"));
                });
            })
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($query) => $query->where('status_repair', $filters['status']))
            ->latest('reported_at')
            ->paginate(12)
            ->withQueryString();

        return view('guru.complaints.index', [
            'complaints' => $complaints,
            'statistics' => $statistics,
        ]);
    }
}
