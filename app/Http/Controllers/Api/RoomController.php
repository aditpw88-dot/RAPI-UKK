<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Room::query()
                ->where('is_active', 'Aktif')
                ->with('pic:id,fullname')
                ->orderBy('room_name')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'pic_id' => ['required', 'integer', 'exists:users,id'],
        ]);
        $this->ensurePic($validated['pic_id']);

        $room = Room::create($validated + ['is_active' => 'Aktif']);

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil ditambahkan.',
            'data' => $room->load('pic:id,fullname'),
        ], 201);
    }

    public function show(Room $room): JsonResponse
    {
        abort_unless($room->is_active === true, 404);

        return response()->json([
            'success' => true,
            'data' => $room->load('pic:id,fullname'),
        ]);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'room_name' => ['sometimes', 'required', 'string', 'max:255'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'pic_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);

        if (array_key_exists('pic_id', $validated)) {
            $this->ensurePic($validated['pic_id']);
        }

        if (array_key_exists('is_active', $validated)) {
            $validated['is_active'] = $validated['is_active'] ? 'Aktif' : 'Tidak aktif';
        }

        $room->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil diperbarui.',
            'data' => $room->fresh()->load('pic:id,fullname'),
        ]);
    }

    public function destroy(Request $request, Room $room): JsonResponse
    {
        $this->ensureAdmin($request);
        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil dihapus.',
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        $roleName = strtolower($request->user()->role?->role_name ?? '');

        abort_unless(str_contains($roleName, 'admin') || str_contains($roleName, 'waka'), 403);
    }

    private function ensurePic(int $userId): void
    {
        $roleName = strtolower(User::findOrFail($userId)->role?->role_name ?? '');

        if (! str_contains($roleName, 'pic')) {
            throw ValidationException::withMessages([
                'pic_id' => ['Pengguna yang dipilih harus memiliki peran PIC.'],
            ]);
        }
    }
}
