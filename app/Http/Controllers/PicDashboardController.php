<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PicDashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $this->ensurePic();

        $rawRooms = $user->rooms()->with(['pics', 'pic'])->get();
        if ($rawRooms->isEmpty()) {
            $rawRooms = Room::with(['pics', 'pic'])->get();
        }
        $rooms = Room::sortRooms($rawRooms);
        $roomIds = $rooms->pluck('id');

        $pendingEvents = Event::forRooms($roomIds->all())->where('status', 'pending')->count();

        $events = Event::with(['room', 'rooms'])
            ->forRooms($roomIds->all())
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate(5);

        return view('pic.dashboard', compact('rooms', 'events', 'pendingEvents'));
    }

    public function rooms(Request $request): View
    {
        $this->ensurePic();

        $user = Auth::user();
        $query = $user->rooms()->with(['pics', 'pic']);
        if ($user->rooms()->count() === 0) {
            $query = Room::with(['pics', 'pic']);
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where('room_name', 'like', "%{$search}%");
        }

        if ($request->filled('active')) {
            $active = $request->query('active');
            if ($active === 'active') {
                $query->where(function ($q) {
                    $q->where('is_active', 'Aktif')->orWhere('is_active', '1');
                });
            } elseif ($active === 'inactive') {
                $query->where(function ($q) {
                    $q->where('is_active', 'Tidak aktif')->orWhere('is_active', '0');
                });
            }
        }

        $rooms = Room::paginateRooms($query->get(), 10, $request);

        return view('pic.rooms.index', compact('rooms'));
    }

    public function approvals(): View
    {
        $this->ensurePic();

        $roomIds = $this->getPicRoomIds();
        $events = Event::with(['room', 'rooms', 'pic'])
            ->forRooms($roomIds)
            ->where('status', 'pending')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('pic.approvals', compact('events'));
    }

    public function approvedEvents(Request $request): View
    {
        $this->ensurePic();

        $roomIds = $this->getPicRoomIds();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:upcoming,in_progress,completed,rejected'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
        $search = trim($filters['search'] ?? '');

        $query = Event::with(['room', 'rooms', 'pic', 'approver.role'])
            ->forRooms($roomIds)
            ->whereIn('status', ['approved', 'rejected'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('event_name', 'like', "%{$search}%")
                        ->orWhereHas('rooms', fn ($query) => $query->where('room_name', 'like', "%{$search}%"))
                        ->orWhereHas('room', fn ($query) => $query->where('room_name', 'like', "%{$search}%"))
                        ->orWhereHas('pic', fn ($query) => $query->where('fullname', 'like', "%{$search}%"));
                });
            })
            ->when(isset($filters['status']), function ($query) use ($filters) {
                match ($filters['status']) {
                    'upcoming' => $query->approvedUpcoming(),
                    'in_progress' => $query->approvedInProgress(),
                    'completed' => $query->approvedCompleted(),
                    'rejected' => $query->where('status', 'rejected'),
                    default => null,
                };
            })
            ->when(isset($filters['date_from']), fn ($query) => $query->whereRaw('COALESCE(end_date, date) >= ?', [$filters['date_from']]))
            ->when(isset($filters['date_to']), fn ($query) => $query->whereDate('date', '<=', $filters['date_to']))
            ->get();

        $sortedEvents = Event::sortByLifecycle($query);

        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $perPage = 5;
        $events = new \Illuminate\Pagination\LengthAwarePaginator(
            $sortedEvents->forPage($page, $perPage)->values(),
            $sortedEvents->count(),
            $perPage,
            $page,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
        $events->appends($request->query());

        $upcomingEvents = $sortedEvents->where('lifecycle_status', 'upcoming');
        $inProgressEvents = $sortedEvents->where('lifecycle_status', 'in_progress');
        $completedEvents = $sortedEvents->where('lifecycle_status', 'completed');
        $rejectedEvents = $sortedEvents->where('status', 'rejected');

        return view('pic.approved-events', compact(
            'events',
            'upcomingEvents',
            'inProgressEvents',
            'completedEvents',
            'rejectedEvents'
        ));
    }

    public function approve(Event $event): RedirectResponse
    {
        $this->ensureCanManage($event);

        if ($event->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $hasConflict = Event::where('id', '!=', $event->id)
            ->forRooms($event->reservedRoomIds())
            ->where('status', 'approved')
            ->whereNull('completed_at')
            ->overlappingSchedule(
                $event->date,
                $event->end_date ?? $event->date,
                $event->start_time,
                $event->end_time
            )
            ->exists();

        if ($hasConflict) {
            return back()->with('error', 'Pengajuan tidak dapat disetujui karena jadwalnya bertabrakan.');
        }

        $event->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Pengajuan disetujui dan statusnya menjadi sedang terlaksana.');
    }

    public function reject(Request $request, Event $event): RedirectResponse
    {
        $this->ensureAssignedPicCanManage($event);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($event->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $event->update([
            'status' => 'rejected',
            'approved_by' => null,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', 'Pengajuan berhasil ditolak.');
    }

    private function getPicRoomIds(): array
    {
        $user = Auth::user();
        $roomIds = $user->rooms()->pluck('rooms.id')->all();
        if (empty($roomIds)) {
            $roomIds = Room::pluck('id')->all();
        }

        return $roomIds;
    }

    private function ensureCanManage(Event $event): void
    {
        $user = Auth::user();

        $roleName = strtolower($user->role?->role_name ?? '');
        $isPic = str_contains($roleName, 'pic');
        $hasSpecificRooms = $user->rooms()->exists();
        $isAssignedPic = $isPic && (
            ! $hasSpecificRooms
            || $user->rooms()->whereIn('rooms.id', $event->reservedRoomIds())->exists()
        );

        if (! $isAssignedPic) {
            abort(403, 'Anda tidak memiliki akses untuk memproses pengajuan ini.');
        }
    }

    private function ensureAssignedPicCanManage(Event $event): void
    {
        $user = Auth::user();

        $isPic = str_contains(strtolower($user->role?->role_name ?? ''), 'pic');
        $hasSpecificRooms = $user->rooms()->exists();
        $hasAccess = $isPic && (
            ! $hasSpecificRooms
            || $user->rooms()->whereIn('rooms.id', $event->reservedRoomIds())->exists()
        );

        if (! $hasAccess) {
            abort(403, 'Hanya PIC ruang yang dapat menolak pengajuan ini.');
        }
    }

    private function ensurePic(): void
    {
        if (! str_contains(strtolower(Auth::user()->role?->role_name ?? ''), 'pic')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh PIC Ruang.');
        }
    }
}
