<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $includeCompleted = filter_var($request->query('include_completed', false), FILTER_VALIDATE_BOOLEAN);

        $events = Event::query()
            ->select([
                'id',
                'room_id',
                'pic_id',
                'event_name',
                'event_type',
                'date',
                'end_date',
                'start_time',
                'end_time',
                'status',
                'rejection_reason',
                'completed_at',
                'cancellation_status',
            ])
            ->with(['room:id,room_name', 'rooms:id,room_name', 'pic:id,fullname'])
            ->where('status', 'approved')
            ->get()
            ->filter(function ($event) use ($includeCompleted) {
                if ($includeCompleted) {
                    return true;
                }
                // Sembunyikan agenda yang sudah selesai di jam-jam sebelumnya dari kalender publik
                return $event->lifecycle_status !== 'completed';
            })
            ->sort(function ($a, $b) {
                $roomA = $a->room?->room_name ?? ($a->rooms->first()?->room_name ?? '');
                $roomB = $b->room?->room_name ?? ($b->rooms->first()?->room_name ?? '');
                $roomCmp = Room::compareRoomNames($roomA, $roomB);
                if ($roomCmp !== 0) {
                    return $roomCmp;
                }
                $dateCmp = strcmp($b->date, $a->date);
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }
                return strcmp($a->start_time, $b->start_time);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $events = Event::with(['room:id,room_name', 'rooms:id,room_name'])
            ->where('pic_id', $request->user()->id)
            ->get()
            ->sort(function ($a, $b) {
                $roomA = $a->room?->room_name ?? ($a->rooms->first()?->room_name ?? '');
                $roomB = $b->room?->room_name ?? ($b->rooms->first()?->room_name ?? '');
                $roomCmp = Room::compareRoomNames($roomA, $roomB);
                if ($roomCmp !== 0) {
                    return $roomCmp;
                }
                $dateCmp = strcmp($b->date, $a->date);
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }
                return strcmp($a->start_time, $b->start_time);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    public function approvedMine(Request $request): JsonResponse
    {
        $events = Event::with(['room:id,room_name', 'rooms:id,room_name'])
            ->where('pic_id', $request->user()->id)
            ->where('status', 'approved')
            ->get()
            ->sort(function ($a, $b) {
                $roomA = $a->room?->room_name ?? ($a->rooms->first()?->room_name ?? '');
                $roomB = $b->room?->room_name ?? ($b->rooms->first()?->room_name ?? '');
                $roomCmp = Room::compareRoomNames($roomA, $roomB);
                if ($roomCmp !== 0) {
                    return $roomCmp;
                }
                $dateCmp = strcmp($b->date, $a->date);
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }
                return strcmp($a->start_time, $b->start_time);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $this->ensurePic($request);
        $roomIds = $request->user()->rooms()->pluck('rooms.id');

        $events = Event::with(['room:id,room_name', 'rooms:id,room_name', 'pic:id,fullname'])
            ->forRooms($roomIds->all())
            ->where('status', 'pending')
            ->get()
            ->sort(function ($a, $b) {
                $roomA = $a->room?->room_name ?? ($a->rooms->first()?->room_name ?? '');
                $roomB = $b->room?->room_name ?? ($b->rooms->first()?->room_name ?? '');
                $roomCmp = Room::compareRoomNames($roomA, $roomB);
                if ($roomCmp !== 0) {
                    return $roomCmp;
                }
                $dateCmp = strcmp($b->date, $a->date);
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }
                return strcmp($a->start_time, $b->start_time);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    public function managed(Request $request): JsonResponse
    {
        $this->ensurePic($request);
        $roomIds = $request->user()->rooms()->pluck('rooms.id');

        $events = Event::with(['room:id,room_name', 'rooms:id,room_name', 'pic:id,fullname'])
            ->forRooms($roomIds->all())
            ->get()
            ->sort(function ($a, $b) {
                $roomA = $a->room?->room_name ?? ($a->rooms->first()?->room_name ?? '');
                $roomB = $b->room?->room_name ?? ($b->rooms->first()?->room_name ?? '');
                $roomCmp = Room::compareRoomNames($roomA, $roomB);
                if ($roomCmp !== 0) {
                    return $roomCmp;
                }
                $dateCmp = strcmp($b->date, $a->date);
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }
                return strcmp($a->start_time, $b->start_time);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureGuruOrAdmin($request);
        $roleName = strtolower($request->user()->role?->role_name ?? '');
        $isAdmin = str_contains($roleName, 'admin') || str_contains($roleName, 'waka');
        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_type' => ['sometimes', 'required', 'string', Rule::in(array_keys(config('event_types')))],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'date' => ['required', 'date', $isAdmin ? 'after_or_equal:today' : 'after:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'start_session' => ['required', 'integer', 'between:1,13'],
            'end_session' => ['required', 'integer', 'between:2,13', 'gte:start_session'],
        ]);

        $sessions = config('schedule.sessions');
        $startTime = $sessions[$validated['start_session']]['start_time'];
        $endTime = $sessions[$validated['end_session']]['end_time'];
        $endDate = $validated['end_date'] ?? $validated['date'];

        $bookingResult = DB::transaction(function () use ($validated, $request, $isAdmin, $startTime, $endTime, $endDate): Event|array|string {
            $room = Room::whereKey($validated['room_id'])->lockForUpdate()->first();
            if (! $room || $room->getRawOriginal('is_active') !== 'Aktif') {
                return 'inactive';
            }

            $conflictingEvents = Event::forRooms([$room->id])
                ->where(function ($query) {
                    $query->where('status', 'pending')
                        ->orWhere(fn ($query) => $query->where('status', 'approved')->whereNull('completed_at'));
                })
                ->overlappingSchedule($validated['date'], $endDate, $startTime, $endTime)
                ->with(['pic.role', 'rooms', 'room'])
                ->lockForUpdate()
                ->get();

            if ($conflictingEvents->isNotEmpty()) {
                if (! $isAdmin) {
                    return 'conflict';
                }

                $hasAdminConflict = $conflictingEvents->contains(function (Event $ev) {
                    $roleName = strtolower($ev->pic?->role?->role_name ?? '');
                    return str_contains($roleName, 'admin') || str_contains($roleName, 'waka');
                });

                if ($hasAdminConflict) {
                    return 'conflict';
                }

                // Relokasi agenda guru ke ruangan terdekat
                $relocations = [];
                $excludedRoomIds = [(int) $room->id];

                foreach ($conflictingEvents as $guruEvent) {
                    $sourceRoom = $guruEvent->room ?? Room::find($guruEvent->room_id);
                    $gStartDate = is_string($guruEvent->date) ? $guruEvent->date : $guruEvent->date->format('Y-m-d');
                    $gEndDate = $guruEvent->end_date ? (is_string($guruEvent->end_date) ? $guruEvent->end_date : $guruEvent->end_date->format('Y-m-d')) : $gStartDate;

                    $nearestRoom = Room::findNearestAvailableRoom(
                        $sourceRoom ?: $room,
                        $gStartDate,
                        $gEndDate,
                        $guruEvent->start_time,
                        $guruEvent->end_time,
                        $excludedRoomIds
                    );

                    if (! $nearestRoom) {
                        return 'no_room_for_guru';
                    }

                    $origName = $sourceRoom?->room_name ?: 'ruangan sebelumnya';
                    $guruEvent->room_id = $nearestRoom->id;
                    $guruEvent->rooms()->sync([$nearestRoom->id]);
                    $guruEvent->move_reason = "Dialokasikan otomatis dari {$origName} ke {$nearestRoom->room_name} karena ruangan digunakan oleh Admin untuk agenda: {$validated['event_name']}.";
                    $guruEvent->save();

                    $excludedRoomIds[] = $nearestRoom->id;
                    $relocations[] = "agenda guru \"{$guruEvent->event_name}\" dialihkan dari {$origName} ke {$nearestRoom->room_name}";
                }

                $event = Event::create([
                    'room_id' => $room->id,
                    'pic_id' => $request->user()->id,
                    'event_name' => $validated['event_name'],
                    'event_type' => $validated['event_type'] ?? 'lainnya',
                    'date' => $validated['date'],
                    'end_date' => $endDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => 'approved',
                    'approved_by' => $request->user()->id,
                ]);
                $event->rooms()->sync([$room->id]);

                return ['event' => $event, 'relocations' => $relocations];
            }

            $event = Event::create([
                'room_id' => $room->id,
                'pic_id' => $request->user()->id,
                'event_name' => $validated['event_name'],
                'event_type' => $validated['event_type'] ?? 'lainnya',
                'date' => $validated['date'],
                'end_date' => $endDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $isAdmin ? 'approved' : 'pending',
                'approved_by' => $isAdmin ? $request->user()->id : null,
            ]);
            $event->rooms()->sync([$room->id]);

            return $event;
        });

        if ($bookingResult === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan tidak ditemukan atau sedang tidak aktif.',
            ], 422);
        }

        if ($bookingResult === 'conflict') {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan dan waktu tersebut sudah digunakan.',
            ], 409);
        }

        if ($bookingResult === 'no_room_for_guru') {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan sedang dipinjam oleh guru, namun tidak ada ruangan alternatif yang tersedia untuk relokasi otomatis.',
            ], 409);
        }

        $createdEvent = is_array($bookingResult) ? $bookingResult['event'] : $bookingResult;
        $relocations = is_array($bookingResult) ? $bookingResult['relocations'] : [];

        $msg = $isAdmin
            ? (count($relocations) > 0
                ? 'Agenda berhasil dibuat. Hak milik ruangan menjadi milik Admin dan ' . implode(', ', $relocations) . '.'
                : 'Agenda berhasil dibuat dan disetujui.')
            : 'Pengajuan peminjaman ruangan berhasil dikirim.';

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data' => $this->transformEvent($createdEvent->fresh(['rooms', 'room', 'pic'])),
        ], 201);

        return response()->json([
            'success' => true,
            'message' => $isAdmin
                ? 'Agenda berhasil dibuat dan statusnya menjadi sedang terlaksana.'
                : 'Pengajuan agenda berhasil dikirim.',
            'data' => $bookingResult->load(['room:id,room_name', 'rooms:id,room_name']),
        ], 201);
    }

    public function approve(Request $request, Event $event): JsonResponse
    {
        $this->ensureCanManage($request->user(), $event);

        if ($event->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan agenda ini sudah diproses.',
            ], 409);
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
            return response()->json([
                'success' => false,
                'message' => 'Agenda bertabrakan dengan jadwal yang sudah disetujui.',
            ], 409);
        }

        $event->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Agenda berhasil disetujui dan statusnya menjadi sedang terlaksana.',
            'data' => $event->fresh()->load('room:id,room_name'),
        ]);
    }

    public function reject(Request $request, Event $event): JsonResponse
    {
        $this->ensureAssignedPicCanManage($request->user(), $event);
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($event->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan agenda ini sudah diproses.',
            ], 409);
        }

        $event->update([
            'status' => 'rejected',
            'approved_by' => null,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Agenda berhasil ditolak.',
            'data' => $event->fresh()->load('room:id,room_name'),
        ]);
    }

    private function ensureGuruOrAdmin(Request $request): void
    {
        $roleName = strtolower($request->user()->role?->role_name ?? '');

        abort_unless(
            str_contains($roleName, 'guru')
                || str_contains($roleName, 'admin')
                || str_contains($roleName, 'waka'),
            403
        );
    }

    private function ensurePic(Request $request): void
    {
        abort_unless(str_contains(strtolower($request->user()->role?->role_name ?? ''), 'pic'), 403);
    }

    private function ensureCanManage(User $user, Event $event): void
    {
        $roleName = strtolower($user->role?->role_name ?? '');
        $isAdmin = str_contains($roleName, 'admin') || str_contains($roleName, 'waka');
        $isAssignedPic = str_contains($roleName, 'pic')
            && $user->rooms()->whereIn('rooms.id', $event->reservedRoomIds())->exists();

        abort_unless($isAdmin || $isAssignedPic, 403);
    }

    private function ensureAssignedPicCanManage(User $user, Event $event): void
    {
        abort_unless(
            str_contains(strtolower($user->role?->role_name ?? ''), 'pic')
                && $user->rooms()->whereIn('rooms.id', $event->reservedRoomIds())->exists(),
            403
        );
    }
}
