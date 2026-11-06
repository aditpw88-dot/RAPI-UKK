<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComplaintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $roleName = strtolower($user->role?->role_name ?? '');
        $query = Complaint::with(['room:id,room_name', 'reporter:id,fullname', 'handler:id,fullname']);

        if (str_contains($roleName, 'pic')) {
            $roomIds = $user->rooms()->pluck('rooms.id');
            $query->where(fn ($complaints) => $complaints
                ->whereIn('room_id', $roomIds)
                ->orWhere('handler_id', $user->id));
        } elseif (! str_contains($roleName, 'admin') && ! str_contains($roleName, 'waka')) {
            $query->where('reporter_id', $user->id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest('reported_at')->get()->map(
                fn (Complaint $complaint) => $this->complaintData($complaint)
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureGuruOrAdmin($request->user());
        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $room = Room::whereKey($validated['room_id'])
            ->where('is_active', 'Aktif')
            ->first();

        if (! $room) {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan tidak ditemukan atau sedang tidak aktif.',
            ], 422);
        }

        $photoPath = $request->file('photo')->store('complaints', 'public');
        $complaint = Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $request->user()->id,
            'description' => $validated['description'],
            'photo_path' => $photoPath,
            'status_repair' => 'Belum Diperbaiki',
        ])->load(['room:id,room_name', 'reporter:id,fullname']);

        return response()->json([
            'success' => true,
            'message' => 'Pengaduan berhasil dikirim.',
            'data' => $this->complaintData($complaint),
        ], 201);
    }

    public function updateStatus(Request $request, Complaint $complaint): JsonResponse
    {
        $user = $request->user();
        abort_unless(str_contains(strtolower($user->role?->role_name ?? ''), 'pic'), 403);

        if (! $user->rooms()->whereKey($complaint->room_id)->exists()
            && (int) $complaint->handler_id !== (int) $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status_repair' => [
                'required',
                'in:Belum Diperbaiki,Menunggu Spare Part,Sudah Diperbaiki',
            ],
        ]);

        $complaint->update([
            'status_repair' => $validated['status_repair'],
            'handler_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status pengaduan berhasil diperbarui.',
            'data' => $this->complaintData($complaint->fresh()->load([
                'room:id,room_name',
                'reporter:id,fullname',
                'handler:id,fullname',
            ])),
        ]);
    }

    private function complaintData(Complaint $complaint): array
    {
        return [
            'id' => $complaint->id,
            'room' => $complaint->room,
            'reporter' => $complaint->reporter,
            'handler' => $complaint->handler,
            'description' => $complaint->description,
            'photo_url' => Storage::disk('public')->url($complaint->photo_path),
            'status_repair' => $complaint->status_repair,
            'reported_at' => $complaint->reported_at,
        ];
    }

    private function ensureGuruOrAdmin(User $user): void
    {
        $roleName = strtolower($user->role?->role_name ?? '');

        abort_unless(
            str_contains($roleName, 'guru')
                || str_contains($roleName, 'admin')
                || str_contains($roleName, 'waka'),
            403
        );
    }
}
