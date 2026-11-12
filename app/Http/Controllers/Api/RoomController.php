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
        $rooms = Room::query()
            ->with(['pic:id,fullname,phone_number', 'pics:id,fullname,phone_number'])
            ->get()
            ->sort(fn ($a, $b) => Room::compareRoomNames($a->room_name, $b->room_name))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $rooms,
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
            'data' => $room->load('pic:id,fullname,phone_number'),
        ], 201);
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $room->load('pic:id,fullname,phone_number'),
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
            'maintenance_note' => ['nullable', 'string', 'max:1000'],
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
            'data' => $room->fresh()->load('pic:id,fullname,phone_number'),
        ]);
    }

    public function updateStatus(Request $request, Room $room): JsonResponse
    {
        $user = $request->user();
        $roleName = strtolower($user->role?->role_name ?? '');
        $isAdmin = str_contains($roleName, 'admin') || str_contains($roleName, 'waka');
        $isPic = str_contains($roleName, 'pic');

        $hasSpecificRooms = $user->rooms()->exists();
        $canManage = $isAdmin || ($isPic && (
            ! $hasSpecificRooms
            || (int) $room->pic_id === (int) $user->id
            || $user->rooms()->where('rooms.id', $room->id)->exists()
        ));

        abort_unless($canManage, 403, 'Anda tidak memiliki akses untuk mengubah status ruangan ini.');

        $validated = $request->validate([
            'is_active' => ['required'],
            'maintenance_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $rawActive = $validated['is_active'];
        $isActive = in_array($rawActive, ['Aktif', '1', 1, true, 'true'], true);
        $note = trim((string) ($validated['maintenance_note'] ?? ''));

        if (! $isActive) {
            if ($note === '') {
                throw ValidationException::withMessages([
                    'maintenance_note' => ['Mohon isi catatan perbaikan/alasan saat menonaktifkan ruangan.'],
                ]);
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

                $whatsAppUrl = \App\Support\WhatsAppLink::make($borrowerPhone, $waMessage);

                return response()->json([
                    'success' => false,
                    'message' => "Ruangan {$room->room_name} tidak dapat dinonaktifkan karena sedang berlangsung agenda \"{$ongoingEvent->event_name}\" oleh {$borrowerName} ({$eventTime} WIB). Silakan berkoordinasi terlebih dahulu.",
                    'ongoing_conflict' => [
                        'room_name' => $room->room_name,
                        'event_name' => $ongoingEvent->event_name,
                        'borrower_name' => $borrowerName,
                        'borrower_phone' => $borrowerPhone,
                        'event_time' => $eventTime,
                        'whatsapp_url' => $whatsAppUrl,
                    ],
                    'whatsapp_url' => $whatsAppUrl,
                ], 422);
            }
        }

        $room->update([
            'is_active' => $isActive ? 'Aktif' : 'Tidak aktif',
            'maintenance_note' => $isActive ? null : $note,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status ruangan berhasil diperbarui.',
            'data' => $room->fresh()->load('pic:id,fullname,phone_number'),
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
