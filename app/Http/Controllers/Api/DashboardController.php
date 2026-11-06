<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $roleName = strtolower($user->role?->role_name ?? '');
        $isGuru = str_contains($roleName, 'guru');
        $isPic = str_contains($roleName, 'pic');
        $isAdmin = str_contains($roleName, 'admin') || str_contains($roleName, 'waka');
        $roomIds = $isPic ? $user->rooms()->pluck('rooms.id') : collect();

        $complaints = Complaint::query();
        if ($isPic) {
            $complaints->where(fn ($query) => $query
                ->whereIn('room_id', $roomIds)
                ->orWhere('handler_id', $user->id));
        } elseif (! $isAdmin) {
            $complaints->where('reporter_id', $user->id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_approved_events' => Event::where('status', 'approved')->count(),
                'total_rooms' => Room::where('is_active', 'Aktif')->count(),
                'my_events_count' => $isGuru
                    ? Event::where('pic_id', $user->id)->count()
                    : 0,
                'pending_events_count' => $isPic
                    ? Event::forRooms($roomIds->all())->where('status', 'pending')->count()
                    : 0,
                'complaints_count' => $isGuru || $isPic ? $complaints->count() : 0,
                'upcoming_events' => Event::query()
                    ->select([
                        'id',
                        'room_id',
                        'event_name',
                        'date',
                        'end_date',
                        'start_time',
                        'end_time',
                        'status',
                    ])
                    ->with(['room:id,room_name', 'rooms:id,room_name'])
                    ->where('status', 'approved')
                    ->orderBy('date')
                    ->orderBy('start_time')
                    ->limit(5)
                    ->get(),
            ],
        ]);
    }
}
