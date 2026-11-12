<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Room;
use App\Support\WhatsAppLink;
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

        $condition = $request->query('condition') ?? $request->query('active');
        if ($condition === 'active') {
            $query->where(function ($q) {
                $q->where('is_active', 'Aktif')->orWhere('is_active', '1');
            });
        } elseif ($condition === 'inactive') {
            $query->where(function ($q) {
                $q->where('is_active', 'Tidak aktif')->orWhere('is_active', '0');
            });
        }

        $rooms = $query->get();

        // Preload ongoing events right now to avoid N+1 queries
        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();

        $ongoingEvents = Event::where('status', 'approved')
            ->whereNull('completed_at')
            ->where(function ($q) use ($today) {
                $q->where('date', '<=', $today)
                    ->where(function ($sub) use ($today) {
                        $sub->whereNull('end_date')
                            ->orWhere('end_date', '>=', $today);
                    });
            })
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->with(['rooms', 'pic'])
            ->get();

        $occupiedRoomMap = [];
        foreach ($ongoingEvents as $event) {
            foreach ($event->reservedRoomIds() as $rId) {
                if (! isset($occupiedRoomMap[$rId])) {
                    $occupiedRoomMap[$rId] = $event;
                }
            }
        }

        foreach ($rooms as $room) {
            $room->setAttribute('current_event', $occupiedRoomMap[$room->id] ?? null);
        }

        $status = $request->query('status');
        if ($status !== null && $status !== '') {
            $rooms = $rooms->filter(function (Room $room) use ($status) {
                return $status === 'available' ? $room->is_available : ! $room->is_available;
            })->values();
        }

        $rooms = Room::paginateRooms($rooms, 10, $request);

        return view('pic.rooms.index', compact('rooms'));
    }

    public function updateRoomStatus(Request $request, Room $room): RedirectResponse
    {
        $this->ensurePic();

        $user = Auth::user();
        $hasSpecificRooms = $user->rooms()->exists();
        $canManageRoom = ! $hasSpecificRooms
            || (int) $room->pic_id === (int) $user->id
            || $user->rooms()->where('rooms.id', $room->id)->exists();

        if (! $canManageRoom) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah status ruangan ini.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'string', 'in:Aktif,Tidak aktif,1,0,true,false'],
            'maintenance_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'is_active.required' => 'Status kondisi ruangan wajib dipilih.',
            'maintenance_note.max' => 'Catatan perbaikan maksimal 1000 karakter.',
        ]);

        $rawActive = $validated['is_active'];
        $isActive = in_array($rawActive, ['Aktif', '1', 'true'], true);
        $note = trim((string) ($validated['maintenance_note'] ?? ''));

        if (! $isActive) {
            if ($note === '') {
                return back()->withErrors([
                    'maintenance_note' => 'Mohon isi catatan perbaikan/alasan saat menonaktifkan ruangan.',
                ])->withInput();
            }

            $ongoingEvent = $room->getOngoingEvent();
            if ($ongoingEvent) {
                $borrower = $ongoingEvent->pic;
                $borrowerName = $borrower?->fullname ?? $borrower?->name ?? 'Peminjam Ruangan';
                $borrowerPhone = $borrower?->phone_number;
                $eventTime = substr($ongoingEvent->start_time, 0, 5) . ' - ' . substr($ongoingEvent->end_time, 0, 5);
                $picName = $user->fullname ?? 'PIC Ruang';

                $waMessage = "Halo Bapak/Ibu {$borrowerName}, saya {$picName} (PIC {$room->room_name}). "
                    . "Mohon maaf, ruangan {$room->room_name} rencananya akan dinonaktifkan (perbaikan/renovasi), "
                    . "namun saat ini sedang berlangsung agenda \"{$ongoingEvent->event_name}\" ({$eventTime} WIB). "
                    . "Saya ingin berkoordinasi terlebih dahulu dengan Anda mengenai penggunaan ruangan tersebut. Terima kasih.";

                $whatsAppUrl = WhatsAppLink::make($borrowerPhone, $waMessage);

                return back()
                    ->withInput()
                    ->with('error', "Ruangan {$room->room_name} tidak dapat dinonaktifkan karena sedang berlangsung agenda \"{$ongoingEvent->event_name}\" oleh {$borrowerName} ({$eventTime} WIB). Silakan berkoordinasi terlebih dahulu.")
                    ->with('ongoing_conflict', [
                        'room_name' => $room->room_name,
                        'event_name' => $ongoingEvent->event_name,
                        'borrower_name' => $borrowerName,
                        'borrower_phone' => $borrowerPhone,
                        'event_time' => $eventTime,
                        'whatsapp_url' => $whatsAppUrl,
                    ]);
            }
        }

        $room->update([
            'is_active' => $isActive ? 'Aktif' : 'Tidak aktif',
            'maintenance_note' => $isActive ? null : $note,
        ]);

        $statusText = $isActive ? 'Aktif (Siap Digunakan)' : 'Tidak Aktif (Dalam Perbaikan)';

        return back()->with('success', "Status ruangan {$room->room_name} berhasil diubah menjadi {$statusText}.");
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

        $cancellations = Event::with(['room', 'rooms', 'pic'])
            ->forRooms($roomIds)
            ->where('cancellation_status', 'pending')
            ->orderBy('cancellation_requested_at', 'desc')
            ->get();

        return view('pic.approvals', compact('events', 'cancellations'));
    }

    public function approvedEvents(Request $request): View
    {
        $this->ensurePic();

        $roomIds = $this->getPicRoomIds();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:upcoming,in_progress,completed,rejected,cancelled,cancellation_pending'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
        $search = trim($filters['search'] ?? '');

        $threshold = now()->subHours(24);
        $thresholdDate = $threshold->toDateString();
        $thresholdTime = $threshold->format('H:i:s');

        $query = Event::with(['room', 'rooms', 'pic.role', 'approver.role', 'cancellationApprover.role'])
            ->forRooms($roomIds)
            ->whereIn('status', ['approved', 'rejected', 'cancelled'])
            ->where(function ($query) use ($threshold, $thresholdDate, $thresholdTime) {
                $query->where('status', '!=', 'approved')
                    ->orWhere(function ($q) use ($threshold, $thresholdDate, $thresholdTime) {
                        $q->where('status', 'approved')
                            ->where(function ($sub) use ($thresholdDate, $thresholdTime) {
                                $sub->whereRaw('COALESCE(end_date, date) > ?', [$thresholdDate])
                                    ->orWhere(function ($timeQ) use ($thresholdDate, $thresholdTime) {
                                        $timeQ->whereRaw('COALESCE(end_date, date) = ?', [$thresholdDate])
                                            ->where('end_time', '>', $thresholdTime);
                                    });
                            })
                            ->where(function ($sub) use ($threshold) {
                                $sub->whereNull('completed_at')
                                    ->orWhere('completed_at', '>=', $threshold);
                            });
                    });
            })
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
                    'cancelled' => $query->where('status', 'cancelled'),
                    'cancellation_pending' => $query->where('cancellation_status', 'pending'),
                    default => null,
                };
            })
            ->when(isset($filters['date_from']), fn ($query) => $query->whereRaw('COALESCE(end_date, date) >= ?', [$filters['date_from']]))
            ->when(isset($filters['date_to']), fn ($query) => $query->whereDate('date', '<=', $filters['date_to']))
            ->get();

        $query = $query->filter(function ($event) use ($threshold) {
            if ($event->lifecycle_status === 'completed') {
                $endDate = $event->end_date ?: $event->date;
                $endDateStr = is_string($endDate) ? $endDate : $endDate->format('Y-m-d');
                $endTime = $event->end_time ?: '23:59:59';
                $endDateTime = \Illuminate\Support\Carbon::parse("{$endDateStr} {$endTime}");
                $finishedAt = $event->completed_at ? \Illuminate\Support\Carbon::parse($event->completed_at) : $endDateTime;

                if ($finishedAt->lt($threshold) && $endDateTime->lt($threshold)) {
                    return false;
                }
            }

            return true;
        });

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
        $cancelledEvents = $sortedEvents->where('status', 'cancelled');
        $cancellationPendingEvents = $sortedEvents->where('cancellation_status', 'pending');

        return view('pic.approved-events', compact(
            'events',
            'upcomingEvents',
            'inProgressEvents',
            'completedEvents',
            'rejectedEvents',
            'cancelledEvents',
            'cancellationPendingEvents'
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

    public function approveCancellation(Event $event): RedirectResponse
    {
        $this->ensureCanManage($event);

        if ($event->cancellation_status !== 'pending') {
            return back()->with('error', 'Tidak ada pengajuan pembatalan aktif untuk agenda ini.');
        }

        $event->update([
            'status' => 'cancelled',
            'cancellation_status' => 'approved',
            'cancellation_approved_by' => Auth::id(),
            'cancellation_approved_at' => now(),
        ]);

        return back()->with('success', 'Pembatalan agenda berhasil disetujui. Jadwal ruangan telah dibebaskan.');
    }

    public function rejectCancellation(Request $request, Event $event): RedirectResponse
    {
        $this->ensureCanManage($event);

        if ($event->cancellation_status !== 'pending') {
            return back()->with('error', 'Tidak ada pengajuan pembatalan aktif untuk agenda ini.');
        }

        $validated = $request->validate([
            'cancellation_rejected_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'cancellation_rejected_reason.required' => 'Mohon isi alasan penolakan pembatalan.',
            'cancellation_rejected_reason.min' => 'Alasan penolakan pembatalan minimal 3 karakter.',
            'cancellation_rejected_reason.max' => 'Alasan penolakan pembatalan maksimal 1000 karakter.',
        ]);

        $event->update([
            'cancellation_status' => 'rejected',
            'cancellation_rejected_reason' => trim($validated['cancellation_rejected_reason']),
        ]);

        return back()->with('success', 'Pengajuan pembatalan ditolak. Agenda tetap berjalan sesuai jadwal.');
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
