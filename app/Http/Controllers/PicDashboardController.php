<?php

namespace App\Http\Controllers;

use App\Models\Event;
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

        $rooms = $user->rooms()->orderBy('room_name')->get();
        $roomIds = $rooms->pluck('id');

        $events = Event::with(['room', 'rooms'])
            ->forRooms($roomIds->all())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $pendingEvents = $events->where('status', 'pending')->count();

        return view('pic.dashboard', compact('rooms', 'events', 'pendingEvents'));
    }

    public function approvals(): View
    {
        $this->ensurePic();

        $roomIds = Auth::user()->rooms()->pluck('rooms.id');
        $events = Event::with(['room', 'rooms', 'pic'])
            ->forRooms($roomIds->all())
            ->where('status', 'pending')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('pic.approvals', compact('events'));
    }

    public function approvedEvents(): View
    {
        $this->ensurePic();

        $roomIds = Auth::user()->rooms()->pluck('rooms.id');
        $approvedEvents = Event::with(['room', 'rooms', 'pic'])
            ->where('approved_by', Auth::id())
            ->where('status', 'approved')
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->get();
        $inProgressEvents = $approvedEvents->whereNull('completed_at');
        $completedEvents = $approvedEvents->whereNotNull('completed_at');
        $rejectedEvents = Event::with(['room', 'rooms', 'pic'])
            ->forRooms($roomIds->all())
            ->where('status', 'rejected')
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->get();

        return view('pic.approved-events', compact('inProgressEvents', 'completedEvents', 'rejectedEvents'));
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

    private function ensureCanManage(Event $event): void
    {
        $user = Auth::user();

        $roleName = strtolower($user->role?->role_name ?? '');
        $isAdmin = str_contains($roleName, 'admin') || str_contains($roleName, 'waka');
        $isAssignedPic = str_contains($roleName, 'pic')
            && $user->rooms()->whereIn('rooms.id', $event->reservedRoomIds())->exists();

        if (! $isAdmin && ! $isAssignedPic) {
            abort(403, 'Anda tidak memiliki akses untuk memproses pengajuan ini.');
        }
    }

    private function ensureAssignedPicCanManage(Event $event): void
    {
        $user = Auth::user();

        if (! str_contains(strtolower($user->role?->role_name ?? ''), 'pic')
            || ! $user->rooms()->whereIn('rooms.id', $event->reservedRoomIds())->exists()) {
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
