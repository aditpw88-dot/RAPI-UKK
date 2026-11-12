<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Services\FonnteService;
use App\Support\WhatsAppLink;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminManagementController extends Controller
{
    public function rooms(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', 'in:active,inactive'],
            'active' => ['nullable', 'in:active,inactive'],
            'status' => ['nullable', 'in:available,unavailable'],
        ]);
        $search = trim($filters['search'] ?? '');
        $condition = $filters['condition'] ?? $filters['active'] ?? null;
        $status = $filters['status'] ?? null;

        $rooms = Room::with(['pics', 'pic'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('room_name', 'like', "%{$search}%")
                        ->orWhereHas('pics', fn ($query) => $query->where('fullname', 'like', "%{$search}%"))
                        ->orWhereHas('pic', fn ($query) => $query->where('fullname', 'like', "%{$search}%"));
                });
            })
            ->when($condition !== null, fn ($query) => $query->where(
                'is_active',
                $condition === 'active' ? 'Aktif' : 'Tidak aktif'
            ))
            ->get();

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
            ->with('rooms')
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

        if ($status !== null && $status !== '') {
            $rooms = $rooms->filter(function (Room $room) use ($status) {
                return $status === 'available' ? $room->is_available : ! $room->is_available;
            })->values();
        }

        $rooms = Room::paginateRooms($rooms, 10, $request);
        $pics = User::whereHas('role', fn ($query) => $query->where('role_name', 'like', '%PIC%'))
            ->orderBy('fullname')->get();

        return view('admin.rooms.index', compact('rooms', 'pics'));
    }

    public function storeRoom(Request $request)
    {
        if ($request->has('pic_ids') && ! $request->filled('pic_id')) {
            $picIds = array_filter((array) $request->input('pic_ids'));
            $request->merge(['pic_id' => reset($picIds) ?: null]);
        }

        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'pic_id' => ['required', 'integer', 'exists:users,id'],
        ], [
            'pic_id.required' => 'Pilih penanggung jawab (PIC) untuk ruangan ini.',
            'pic_id.exists' => 'PIC yang dipilih tidak ditemukan.',
        ]);

        $room = Room::create([
            'room_name' => $validated['room_name'],
            'capacity' => $validated['capacity'],
            'pic_id' => $validated['pic_id'],
            'is_active' => 'Aktif',
        ]);

        $room->pics()->sync([$validated['pic_id']]);

        return back()->with('success', 'Ruang berhasil ditambahkan.');
    }

    public function updateRoom(Request $request, Room $room)
    {
        if ($request->has('pic_ids') && ! $request->filled('pic_id')) {
            $picIds = array_filter((array) $request->input('pic_ids'));
            $request->merge(['pic_id' => reset($picIds) ?: null]);
        }

        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'pic_id' => ['required', 'integer', 'exists:users,id'],
        ], [
            'pic_id.required' => 'Pilih penanggung jawab (PIC) untuk ruangan ini.',
            'pic_id.exists' => 'PIC yang dipilih tidak ditemukan.',
        ]);

        $room->update([
            'room_name' => $validated['room_name'],
            'capacity' => $validated['capacity'],
            'pic_id' => $validated['pic_id'],
            'is_active' => $request->boolean('is_active') ? 'Aktif' : 'Tidak aktif',
        ]);

        $room->pics()->sync([$validated['pic_id']]);

        return back()->with('success', 'Ruang berhasil diperbarui.');
    }

    public function destroyRoom(Room $room)
    {
        $room->delete();

        return back()->with('success', 'Ruang berhasil dihapus.');
    }

    public function events(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,approved,upcoming,in_progress,completed,rejected,cancelled,cancellation_pending'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
        $search = trim($filters['search'] ?? '');

        $threshold = now()->subHours(24);
        $thresholdDate = $threshold->toDateString();
        $thresholdTime = $threshold->format('H:i:s');

        $events = Event::with(['room', 'rooms', 'pic.role', 'approver.role', 'cancellationApprover.role'])
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
                    'approved' => $query->where('status', 'approved'),
                    'cancellation_pending' => $query->where('cancellation_status', 'pending'),
                    'cancelled' => $query->where('status', 'cancelled'),
                    default => $query->where('status', $filters['status']),
                };
            })
            ->when(isset($filters['date_from']), fn ($query) => $query->whereRaw('COALESCE(end_date, date) >= ?', [$filters['date_from']]))
            ->when(isset($filters['date_to']), fn ($query) => $query->whereDate('date', '<=', $filters['date_to']))
            ->get();

        $events = $events->filter(function ($event) use ($threshold) {
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

        $sortedEvents = Event::sortByLifecycle($events);

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

        $rooms = Room::sortRooms(Room::with(['pic', 'pics'])->get());
        $bookedEvents = Event::getActiveBookedEvents();
        $selectedRoom = null;
        $teachersByMapel = $this->getTeachersGroupedByMapel();

        return view('admin.events.index', compact('events', 'rooms', 'bookedEvents', 'selectedRoom', 'teachersByMapel'));
    }

    public function createEvent(Request $request)
    {
        $rooms = Room::sortRooms(Room::with(['pic', 'pics'])->get());
        $selectedRoom = $rooms->firstWhere('id', $request->query('room_id'));
        $bookedEvents = Event::getActiveBookedEvents();
        $teachersByMapel = $this->getTeachersGroupedByMapel();

        return view('admin.events.create', compact('rooms', 'selectedRoom', 'bookedEvents', 'teachersByMapel'));
    }

    protected function getTeachersGroupedByMapel(): \Illuminate\Support\Collection
    {
        return User::whereHas('role', fn ($query) => $query->where('role_name', 'like', '%Guru%'))
            ->orderByRaw("CASE WHEN mapel IS NULL OR TRIM(mapel) = '' THEN 1 ELSE 0 END")
            ->orderBy('mapel')
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'username', 'phone_number', 'mapel'])
            ->groupBy(fn (User $user) => trim((string) $user->mapel) !== '' ? trim((string) $user->mapel) : 'Lainnya / Belum Diisi');
    }

    public function storeEvent(Request $request)
    {
        if (! $request->has('room_ids') && $request->filled('room_id')) {
            $request->merge(['room_ids' => [$request->input('room_id')]]);
        }

        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_type' => ['sometimes', 'string', 'in:'.implode(',', array_keys(config('event_types')))],
            'room_ids' => ['required', 'array', 'min:1'],
            'room_ids.*' => ['required', 'integer', 'distinct', 'exists:rooms,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'start_session' => ['required', 'integer', 'between:1,13'],
            'end_session' => ['required', 'integer', 'between:2,13', 'gte:start_session'],
            'notify_teacher_ids' => ['nullable', 'array'],
            'notify_teacher_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ], [
            'room_ids.required' => 'Pilih setidaknya satu ruangan.',
            'room_ids.min' => 'Pilih setidaknya satu ruangan.',
            'room_ids.*.distinct' => 'Ruangan yang sama tidak boleh dipilih lebih dari sekali.',
            'room_ids.*.exists' => 'Salah satu ruangan yang dipilih tidak ditemukan.',
        ]);

        $notifyTeacherIds = array_values(array_unique(array_map('intval', $validated['notify_teacher_ids'] ?? [])));
        unset($validated['notify_teacher_ids']);

        $validated['event_type'] = $validated['event_type'] ?? 'lainnya';
        $validated['end_date'] = $validated['end_date'] ?? $validated['date'];
        $roomIds = array_map('intval', $validated['room_ids']);
        sort($roomIds);
        $sessions = config('schedule.sessions');
        $validated['start_time'] = $sessions[$validated['start_session']]['start_time'];
        $validated['end_time'] = $sessions[$validated['end_session']]['end_time'];
        unset($validated['room_ids'], $validated['start_session'], $validated['end_session']);

        $bookingResult = DB::transaction(function () use ($validated, $roomIds, $request): array|string {
            $lockedRooms = Room::whereKey($roomIds)->lockForUpdate()->get();
            if ($lockedRooms->filter(fn (Room $room) => $room->getRawOriginal('is_active') === 'Aktif')->count() !== count($roomIds)) {
                return 'inactive';
            }

            $conflictingEvents = Event::forRooms($roomIds)
                ->where(function ($query) {
                    $query->where('status', 'pending')
                        ->orWhere(fn ($query) => $query->where('status', 'approved')->whereNull('completed_at'));
                })
                ->overlappingSchedule(
                    $validated['date'],
                    $validated['end_date'],
                    $validated['start_time'],
                    $validated['end_time']
                )
                ->with(['pic.role', 'rooms', 'room'])
                ->lockForUpdate()
                ->get();

            if ($conflictingEvents->isNotEmpty()) {
                // Periksa apakah konflik berasal dari sesama Admin/Waka
                $hasAdminConflict = $conflictingEvents->contains(function (Event $ev) {
                    $roleName = strtolower($ev->pic?->role?->role_name ?? '');
                    return str_contains($roleName, 'admin') || str_contains($roleName, 'waka');
                });

                if ($hasAdminConflict) {
                    return 'conflict';
                }

                // Jika ruangan dipinjam oleh Guru, hak milik menjadi milik Admin,
                // dan agenda guru otomatis dialokasikan ke ruangan terdekat selain ruangan tersebut.
                $relocations = [];
                $excludedRoomIds = [...$roomIds];

                foreach ($conflictingEvents as $guruEvent) {
                    $sourceRoom = $guruEvent->room ?? Room::find($guruEvent->room_id);
                    $gStartDate = is_string($guruEvent->date) ? $guruEvent->date : $guruEvent->date->format('Y-m-d');
                    $gEndDate = $guruEvent->end_date ? (is_string($guruEvent->end_date) ? $guruEvent->end_date : $guruEvent->end_date->format('Y-m-d')) : $gStartDate;
                    $gStartTime = $guruEvent->start_time;
                    $gEndTime = $guruEvent->end_time;

                    $nearestRoom = Room::findNearestAvailableRoom(
                        $sourceRoom ?: $lockedRooms->first(),
                        $gStartDate,
                        $gEndDate,
                        $gStartTime,
                        $gEndTime,
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
                    ...$validated,
                    'room_id' => $roomIds[0],
                    'pic_id' => $request->user()->id,
                    'status' => 'approved',
                    'approved_by' => $request->user()->id,
                ]);
                $event->rooms()->sync($roomIds);

                return ['status' => 'relocated', 'relocations' => $relocations, 'event' => $event];
            }

            $event = Event::create([
                ...$validated,
                'room_id' => $roomIds[0],
                'pic_id' => $request->user()->id,
                'status' => 'approved',
                'approved_by' => $request->user()->id,
            ]);
            $event->rooms()->sync($roomIds);

            return ['status' => 'stored', 'event' => $event];
        });

        if ($bookingResult === 'inactive') {
            return back()
                ->withInput()
                ->withErrors(['room_ids' => 'Salah satu ruangan yang dipilih sedang tidak aktif.']);
        }

        if ($bookingResult === 'conflict') {
            return back()
                ->withInput()
                ->withErrors(['room_ids' => 'Salah satu ruangan yang dipilih sudah dipakai pada waktu tersebut oleh agenda Admin lain. Silakan pilih ruangan atau jadwal lain.']);
        }

        if ($bookingResult === 'no_room_for_guru') {
            return back()
                ->withInput()
                ->withErrors(['room_ids' => 'Ruangan ini sedang dipinjam oleh guru, namun tidak ada ruangan alternatif yang tersedia untuk merelokasi agenda guru secara otomatis pada jadwal tersebut.']);
        }

        $createdEvent = is_array($bookingResult) ? ($bookingResult['event'] ?? null) : null;
        $waFeedback = '';

        if ($createdEvent && ! empty($notifyTeacherIds)) {
            $selectedTeachers = User::whereIn('id', $notifyTeacherIds)->get(['id', 'fullname', 'username', 'phone_number', 'mapel']);
            $waResult = app(FonnteService::class)->sendAgendaNotification($createdEvent, $selectedTeachers);

            if ($waResult['success']) {
                if (! empty($waResult['is_scheduled']) && ! empty($waResult['scheduled_at'])) {
                    $scheduleFormatted = $waResult['scheduled_at']->translatedFormat('d F Y') . ' pukul ' . $waResult['scheduled_at']->format('H:i');
                    $waFeedback = " Pengingat WhatsApp otomatis telah dijadwalkan untuk {$waResult['sent_count']} guru (akan otomatis terkirim 1 jam sebelum agenda: {$scheduleFormatted} WIB).";
                } else {
                    $waFeedback = " Pengingat WhatsApp otomatis berhasil dikirim ke {$waResult['sent_count']} guru.";
                }
            } else {
                $waFeedback = " (Catatan WhatsApp: {$waResult['error']})";
            }
        }

        if (is_array($bookingResult) && $bookingResult['status'] === 'relocated') {
            $relocationsStr = implode(', ', $bookingResult['relocations']);
            return redirect()
                ->route('admin.events')
                ->with('success', "Agenda berhasil ditambahkan. Hak milik ruangan menjadi milik Admin dan {$relocationsStr}.{$waFeedback}");
        }

        return redirect()
            ->route('admin.events')
            ->with('success', "Agenda berhasil ditambahkan dan statusnya menjadi sedang terlaksana.{$waFeedback}");
    }

    public function storeSchoolEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', Rule::in(array_keys(config('event_types')))],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $event = Event::create([
            'room_id' => null,
            'pic_id' => $request->user()->id,
            'event_name' => $validated['event_name'],
            'event_type' => $validated['event_type'],
            'date' => $validated['date'],
            'end_date' => $validated['date'],
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        return response()->json([
            'id' => $event->id,
            'title' => $event->event_name,
            'eventName' => $event->event_name,
            'roomName' => 'Seluruh sekolah',
            'eventType' => $event->event_type,
            'createdAt' => $event->created_at?->toIso8601String() ?? '',
            'start' => $event->date,
            'end' => \Illuminate\Support\Carbon::parse($event->date)->addDay()->toDateString(),
            'allDay' => true,
            ...$event->calendarColors(),
        ], 201);
    }

    public function approveEvent(Request $request, Event $event)
    {
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
            return back()->with('error', 'Agenda tidak dapat disetujui karena jadwalnya bertabrakan.');
        }

        $event->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Agenda Guru disetujui dan statusnya menjadi sedang terlaksana.');
    }

    public function rejectEvent(Request $request, Event $event)
    {
        if ($event->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Mohon isi alasan penolakan agenda.',
            'rejection_reason.min' => 'Alasan penolakan agenda minimal 3 karakter.',
            'rejection_reason.max' => 'Alasan penolakan agenda maksimal 1000 karakter.',
        ]);

        $event->update([
            'status' => 'rejected',
            'approved_by' => null,
            'rejection_reason' => trim($validated['rejection_reason']),
        ]);

        return back()->with('success', "Pengajuan agenda \"{$event->event_name}\" berhasil ditolak.");
    }

    public function cancelEvent(Request $request, Event $event)
    {
        if (! $event->canBeCancelledByAdmin()) {
            if ($event->lifecycle_status === 'in_progress') {
                return back()->with('error', 'Agenda yang sedang terlaksana tidak dapat dibatalkan.');
            }

            if ($event->lifecycle_status === 'completed') {
                return back()->with('error', 'Agenda yang sudah terlaksana tidak dapat dibatalkan.');
            }

            return back()->with('error', 'Agenda ini tidak dapat dibatalkan.');
        }

        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $reason = trim($validated['cancellation_reason'] ?? '');
        if ($reason === '') {
            $reason = 'Dibatalkan oleh Admin.';
        }

        $event->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancellation_status' => 'approved',
            'cancellation_requested_at' => now(),
            'cancellation_approved_by' => $request->user()->id,
            'cancellation_approved_at' => now(),
        ]);

        return back()->with('success', "Agenda \"{$event->event_name}\" berhasil dibatalkan dan jadwal ruangan telah dibebaskan.");
    }

    public function approveCancellation(Request $request, Event $event)
    {
        if ($event->cancellation_status !== 'pending') {
            return back()->with('error', 'Tidak ada pengajuan pembatalan aktif untuk agenda ini.');
        }

        $event->update([
            'status' => 'cancelled',
            'cancellation_status' => 'approved',
            'cancellation_approved_by' => $request->user()->id,
            'cancellation_approved_at' => now(),
        ]);

        return back()->with('success', 'Pembatalan agenda Guru berhasil disetujui. Jadwal ruangan telah dibebaskan.');
    }

    public function rejectCancellation(Request $request, Event $event)
    {
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

        return back()->with('success', 'Pengajuan pembatalan agenda ditolak. Agenda tetap berjalan sesuai jadwal.');
    }

    public function moveEvent(Request $request, Event $event)
    {
        if (! $request->has('room_ids') && $request->filled('room_id')) {
            $request->merge(['room_ids' => [$request->input('room_id')]]);
        }

        $validated = $request->validate([
            'room_ids' => ['required', 'array', 'min:1'],
            'room_ids.*' => ['required', 'integer', 'distinct', 'exists:rooms,id'],
            'move_reason' => ['required', 'string', 'max:2000'],
        ], [
            'room_ids.required' => 'Pilih setidaknya satu ruangan tujuan.',
            'room_ids.min' => 'Pilih setidaknya satu ruangan tujuan.',
            'room_ids.*.distinct' => 'Ruangan tujuan tidak boleh dipilih ganda.',
            'room_ids.*.exists' => 'Salah satu ruangan tujuan tidak ditemukan.',
            'move_reason.required' => 'Catatan alasan pemindahan wajib diisi.',
        ]);

        if ($event->status === 'rejected') {
            return back()->with('error', 'Agenda yang ditolak tidak dapat dipindahkan.');
        }

        if (! $event->canBeMovedByAdmin()) {
            return back()->with('error', 'Agenda yang sudah terlaksana tidak dapat dipindahkan ruangannya karena sudah berakhir.');
        }

        $newRoomIds = array_values(array_unique(array_map('intval', $validated['room_ids'])));
        $currentRoomIds = $event->reservedRoomIds();
        sort($currentRoomIds);
        $sortedNew = $newRoomIds;
        sort($sortedNew);

        if ($currentRoomIds === $sortedNew) {
            return back()->withErrors([
                'room_id' => 'Ruangan tujuan sama dengan ruangan saat ini.',
                'room_ids' => 'Ruangan tujuan sama dengan ruangan saat ini. Silakan pilih ruangan yang berbeda.',
            ]);
        }

        $inactiveRooms = Room::whereIn('id', $newRoomIds)
            ->where('is_active', '!=', 'Aktif')
            ->exists();

        if ($inactiveRooms) {
            return back()->withErrors([
                'room_id' => 'Salah satu ruangan tujuan sedang tidak aktif.',
                'room_ids' => 'Salah satu ruangan tujuan sedang tidak aktif.',
            ]);
        }

        $hasConflict = Event::where('id', '!=', $event->id)
            ->forRooms($newRoomIds)
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere(fn ($query) => $query->where('status', 'approved')->whereNull('completed_at'));
            })
            ->overlappingSchedule(
                $event->date,
                $event->end_date ?? $event->date,
                $event->start_time,
                $event->end_time
            )
            ->exists();

        if ($hasConflict) {
            return back()->withErrors([
                'room_id' => 'Salah satu ruangan tujuan sudah digunakan pada waktu tersebut.',
                'room_ids' => 'Salah satu ruangan tujuan sudah digunakan pada waktu tersebut.',
            ]);
        }

        $previousRoomName = $event->room_names ?: 'ruang sebelumnya';
        $destinationRooms = Room::whereIn('id', $newRoomIds)->get();
        $destinationRoomNames = $destinationRooms->pluck('room_name')->join(', ');

        $pic = $event->pic;
        $sessions = collect(config('schedule.sessions', []));
        $startSession = $sessions->search(
            fn (array $session): bool => $session['start_time'] === substr($event->start_time, 0, 5)
        );
        $endSession = $sessions->search(
            fn (array $session): bool => $session['end_time'] === substr($event->end_time, 0, 5)
        );
        $sessionLabel = $startSession === false || $endSession === false
            ? 'Sesi: tidak teridentifikasi'
            : 'Sesi: ' . ($startSession === $endSession ? $startSession : "{$startSession}-{$endSession}");

        $event->room_id = $newRoomIds[0];
        $event->move_reason = trim($validated['move_reason']);
        $event->save();
        $event->rooms()->sync($newRoomIds);

        $whatsAppUrl = WhatsAppLink::make(
            config('services.whatsapp.pic_phone_number'),
            "Yth. {$pic?->fullname},\n\n"
                . "Pemberitahuan pemindahan ruang agenda:\n"
                . "Agenda: {$event->event_name}\n"
                . 'Tanggal: ' . \Illuminate\Support\Carbon::parse($event->date)->format('d/m/Y')
                . (($event->end_date && $event->end_date !== $event->date)
                    ? ' - ' . \Illuminate\Support\Carbon::parse($event->end_date)->format('d/m/Y')
                    : '') . "\n"
                . $sessionLabel . "\n"
                . 'Waktu: ' . substr($event->start_time, 0, 5) . ' - ' . substr($event->end_time, 0, 5) . "\n"
                . "Ruang sebelumnya: {$previousRoomName}\n"
                . "Ruang baru: {$destinationRoomNames}\n"
                . 'Alasan: ' . trim($validated['move_reason'])
                . "\n\nMohon diperhatikan. Terima kasih."
        );

        if ($whatsAppUrl) {
            return redirect()->away($whatsAppUrl);
        }

        return back()
            ->with('success', 'Agenda berhasil dipindahkan. Alasan pemindahan tersimpan.')
            ->with('warning', 'Nomor WhatsApp PIC belum diatur atau formatnya tidak valid. Atur WHATSAPP_PIC_PHONE_NUMBER untuk mengirim pemberitahuan.');
    }

    public function saveMoveReason(Request $request, Event $event)
    {
        $validated = $request->validate([
            'move_reason' => ['required', 'string', 'max:2000'],
        ]);

        if (! $event->canBeMovedByAdmin()) {
            $msg = $event->lifecycle_status === 'completed'
                ? 'Agenda yang sudah terlaksana tidak dapat diubah catatan pemindahannya karena sudah berakhir.'
                : 'Agenda ini tidak dapat diubah catatan pemindahannya.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $event->move_reason = trim($validated['move_reason']);
        $event->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Catatan alasan pemindahan berhasil disimpan.',
                'move_reason' => $event->move_reason,
            ]);
        }

        return back()->with('success', 'Catatan alasan pemindahan berhasil disimpan.');
    }

    public function destroyEvent(Event $event)
    {
        if (! $event->canBeDeletedByAdmin()) {
            return back()->with('error', 'Agenda yang sedang berlangsung tidak dapat dihapus.');
        }

        $event->delete();

        return back()->with('success', 'Agenda berhasil dihapus.');
    }

    public function complaints(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:Belum Diperbaiki,Menunggu Spare Part,Sudah Diperbaiki'],
            'room_type' => ['nullable', 'in:lab,ruang'],
        ]);
        $search = trim($filters['search'] ?? '');

        $complaints = Complaint::with(['room.pic', 'reporter.role', 'handler'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhereHas('room', fn ($query) => $query->where('room_name', 'like', "%{$search}%"))
                        ->orWhereHas('reporter', fn ($query) => $query->where(function ($query) use ($search) {
                            $query->where('fullname', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%");
                        }))
                        ->orWhereHas('handler', fn ($query) => $query->where('fullname', 'like', "%{$search}%"));
                });
            })
            ->when(isset($filters['status']), fn ($query) => $query->where('status_repair', $filters['status']))
            ->when(isset($filters['room_type']), function ($query) use ($filters) {
                $query->whereHas('room', function ($query) use ($filters) {
                    $query->whereRaw(
                        $filters['room_type'] === 'lab'
                            ? 'LOWER(room_name) LIKE ?'
                            : 'LOWER(room_name) NOT LIKE ?',
                        ['lab%']
                    );
                });
            })
            ->latest('reported_at')
            ->paginate(5)
            ->withQueryString();
        $handlers = User::whereHas('role', fn ($query) => $query->where('role_name', 'like', '%PIC%'))
            ->orderBy('fullname')
            ->get();
        $escalationNotifications = $request->user()->unreadNotifications()
            ->where('type', \App\Notifications\ComplaintEscalated::class)
            ->latest()
            ->get();
        $rooms = Room::sortRooms(Room::where('is_active', 'Aktif')->get());

        return view('admin.complaints.index', compact('complaints', 'handlers', 'escalationNotifications', 'rooms'));
    }

    public function remindComplaintHandler(Request $request, Complaint $complaint)
    {
        if ($complaint->status_repair === 'Sudah Diperbaiki') {
            return back()->with('error', 'Teguran tidak dapat dikirim karena laporan sudah diperbaiki.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $complaint->loadMissing(['room.pic', 'handler']);
        $handler = $complaint->handler ?? $complaint->room?->pic;

        if (! $handler || ! str_contains(strtolower($handler->role?->role_name ?? ''), 'pic')) {
            return back()->with('error', 'PIC penanggung jawab laporan ini belum ditentukan.');
        }

        $whatsAppUrl = WhatsAppLink::make(
            $handler->phone_number ?: config('services.whatsapp.pic_phone_number'),
            "Yth. {$handler->fullname},\n\n"
                . "Teguran terkait laporan kerusakan:\n"
                . "Laporan #{$complaint->id}\n"
                . 'Ruang: ' . ($complaint->room?->room_name ?? 'Ruang tidak diketahui') . "\n"
                . "Deskripsi: {$complaint->description}\n"
                . "Pesan Admin: " . trim($validated['message']) . "\n\n"
                . "Mohon segera ditindaklanjuti dan perbarui status perbaikannya. Terima kasih."
        );

        if (! $whatsAppUrl) {
            return back()->with('error', 'Nomor WhatsApp PIC belum diisi atau formatnya tidak valid. Perbarui nomor telepon PIC terlebih dahulu.');
        }

        return redirect()->away($whatsAppUrl);
    }

    public function reassignEscalatedComplaint(Request $request, Complaint $complaint)
    {
        abort_unless($complaint->is_escalated && $complaint->status_repair === 'Belum Diperbaiki', 404);

        $picRoleIds = Role::query()
            ->where('role_name', 'like', '%PIC%')
            ->pluck('id');

        $validated = $request->validate([
            'handler_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereIn('role_id', $picRoleIds->all()),
                Rule::notIn([$complaint->handler_id ?? $complaint->room()->value('pic_id')]),
            ],
        ]);

        $complaint->update(['handler_id' => $validated['handler_id']]);
        $wakaRoleIds = Role::query()->where('role_name', 'like', '%Waka%')->pluck('id');
        User::whereIn('role_id', $wakaRoleIds)->get()->each(function (User $waka) use ($complaint): void {
            $waka->unreadNotifications
                ->where('type', \App\Notifications\ComplaintEscalated::class)
                ->filter(fn ($notification) => (int) ($notification->data['complaint_id'] ?? 0) === $complaint->id)
                ->each->markAsRead();
        });

        return back()->with('success', 'Laporan eskalasi berhasil dialihkan ke PIC yang dipilih.');
    }
}
