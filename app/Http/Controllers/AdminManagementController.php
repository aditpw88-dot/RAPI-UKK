<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
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
            'active' => ['nullable', 'in:active,inactive'],
        ]);
        $search = trim($filters['search'] ?? '');

        $rooms = Room::with('pic')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('room_name', 'like', "%{$search}%")
                        ->orWhereHas('pic', fn ($query) => $query->where('fullname', 'like', "%{$search}%"));
                });
            })
            ->when(isset($filters['active']), fn ($query) => $query->where(
                'is_active',
                $filters['active'] === 'active' ? 'Aktif' : 'Tidak aktif'
            ))
            ->latest()
            ->get();
        $pics = User::whereHas('role', fn ($query) => $query->where('role_name', 'like', '%PIC%'))
            ->orderBy('fullname')->get();

        return view('admin.rooms.index', compact('rooms', 'pics'));
    }

    public function storeRoom(Request $request)
    {
        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'pic_id' => ['required', 'exists:users,id'],
        ]);

        Room::create($validated + ['is_active' => 'Aktif']);

        return back()->with('success', 'Ruang berhasil ditambahkan.');
    }

    public function updateRoom(Request $request, Room $room)
    {
        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'pic_id' => ['required', 'exists:users,id'],
        ]);

        $room->update($validated + ['is_active' => $request->boolean('is_active') ? 'Aktif' : 'Tidak aktif']);

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
            'status' => ['nullable', 'in:pending,approved,in_progress,completed,rejected'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
        $search = trim($filters['search'] ?? '');

        $events = Event::with(['room', 'rooms', 'pic', 'approver.role'])
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
                    'in_progress', 'approved' => $query->where('status', 'approved')->whereNull('completed_at'),
                    'completed' => $query->where('status', 'approved')->whereNotNull('completed_at'),
                    default => $query->where('status', $filters['status']),
                };
            })
            ->when(isset($filters['date_from']), fn ($query) => $query->whereRaw('COALESCE(end_date, date) >= ?', [$filters['date_from']]))
            ->when(isset($filters['date_to']), fn ($query) => $query->whereDate('date', '<=', $filters['date_to']))
            ->latest('date')
            ->latest('start_time')
            ->get();
        $rooms = Room::where('is_active', 'Aktif')->orderBy('room_name')->get();

        return view('admin.events.index', compact('events', 'rooms'));
    }

    public function createEvent(Request $request)
    {
        $rooms = Room::where('is_active', 'Aktif')->orderBy('room_name')->get();
        $selectedRoom = $rooms->firstWhere('id', $request->query('room_id'));

        return view('admin.events.create', compact('rooms', 'selectedRoom'));
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
        ], [
            'room_ids.required' => 'Pilih setidaknya satu ruangan.',
            'room_ids.min' => 'Pilih setidaknya satu ruangan.',
            'room_ids.*.distinct' => 'Ruangan yang sama tidak boleh dipilih lebih dari sekali.',
            'room_ids.*.exists' => 'Salah satu ruangan yang dipilih tidak ditemukan.',
        ]);

        $validated['event_type'] = $validated['event_type'] ?? 'lainnya';
        $validated['end_date'] = $validated['end_date'] ?? $validated['date'];
        $roomIds = array_map('intval', $validated['room_ids']);
        sort($roomIds);
        $sessions = config('schedule.sessions');
        $validated['start_time'] = $sessions[$validated['start_session']]['start_time'];
        $validated['end_time'] = $sessions[$validated['end_session']]['end_time'];
        unset($validated['room_ids'], $validated['start_session'], $validated['end_session']);

        $bookingResult = DB::transaction(function () use ($validated, $roomIds, $request): string {
            $lockedRooms = Room::whereKey($roomIds)->lockForUpdate()->get();
            if ($lockedRooms->filter(fn (Room $room) => $room->getRawOriginal('is_active') === 'Aktif')->count() !== count($roomIds)) {
                return 'inactive';
            }

            $hasConflict = Event::forRooms($roomIds)
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
                ->exists();

            if ($hasConflict) {
                return 'conflict';
            }

            $event = Event::create([
                ...$validated,
                'room_id' => $roomIds[0],
                'pic_id' => $request->user()->id,
                'status' => 'approved',
                'approved_by' => $request->user()->id,
            ]);
            $event->rooms()->sync($roomIds);

            return 'stored';
        });

        if ($bookingResult === 'inactive') {
            return back()
                ->withInput()
                ->withErrors(['room_ids' => 'Salah satu ruangan yang dipilih sedang tidak aktif.']);
        }

        if ($bookingResult === 'conflict') {
            return back()
                ->withInput()
                ->withErrors(['room_ids' => 'Salah satu ruangan yang dipilih sudah dipakai pada waktu tersebut. Silakan pilih ruangan atau jadwal lain.']);
        }

        return redirect()
            ->route('admin.events')
            ->with('success', 'Agenda berhasil ditambahkan dan statusnya menjadi sedang terlaksana.');
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

    public function moveEvent(Request $request, Event $event)
    {
        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id', Rule::notIn($event->reservedRoomIds())],
            'move_reason' => ['required', 'string', 'max:2000'],
        ]);

        if ($event->status === 'rejected') {
            return back()->with('error', 'Agenda yang ditolak tidak dapat dipindahkan.');
        }

        $roomIsActive = Room::whereKey($validated['room_id'])
            ->where('is_active', 'Aktif')
            ->exists();

        if (! $roomIsActive) {
            return back()->withErrors(['room_id' => 'Ruang tujuan sedang tidak aktif.']);
        }

        $hasConflict = Event::where('id', '!=', $event->id)
            ->forRooms([(int) $validated['room_id']])
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
            return back()->withErrors(['room_id' => 'Ruang tujuan sudah digunakan pada waktu tersebut.']);
        }

        $previousRoomName = $event->room_names ?: 'ruang sebelumnya';
        $destinationRoom = Room::findOrFail($validated['room_id']);
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

        $event->room_id = $destinationRoom->id;
        $event->move_reason = $validated['move_reason'];
        $event->save();
        $event->rooms()->sync([$destinationRoom->id]);

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
                . "Ruang baru: {$destinationRoom->room_name}\n"
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

        if ($event->status === 'rejected') {
            return response()->json([
                'message' => 'Agenda yang ditolak tidak dapat diubah catatan pemindahannya.',
            ], 422);
        }

        $event->move_reason = trim($validated['move_reason']);
        $event->save();

        return back()->with('success', 'Catatan alasan pemindahan berhasil disimpan.');
    }

    public function destroyEvent(Event $event)
    {
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

        return view('admin.complaints.index', compact('complaints', 'handlers', 'escalationNotifications'));
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
