<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EventController extends Controller
{
    public function create(): View
    {
        $this->ensureGuru();

        $rooms = Room::sortRooms(Room::where('is_active', 'Aktif')->get());
        $bookedEvents = Event::getActiveBookedEvents();

        return view('events.create', compact('rooms', 'bookedEvents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureGuru();

        if (! $request->has('room_ids') && $request->filled('room_id')) {
            $request->merge(['room_ids' => [$request->input('room_id')]]);
        }

        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_type' => ['sometimes', 'string', 'in:'.implode(',', array_keys(config('event_types')))],
            'room_ids' => ['required', 'array', 'min:1'],
            'room_ids.*' => ['required', 'integer', 'distinct', 'exists:rooms,id'],
            'date' => ['required', 'date', 'after:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'start_session' => ['required', 'integer', 'between:1,13'],
            'end_session' => ['required', 'integer', 'between:2,13', 'gte:start_session'],
        ], [
            'date.after' => 'Peminjaman harus diajukan paling lambat H-1 sebelum tanggal agenda.',
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
                'status' => 'pending',
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
            ->route('events.create')
            ->with('success', 'Pengajuan berhasil disimpan dan menunggu persetujuan.');
    }

    private function ensureGuru(): void
    {
        if (! str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'guru')) {
            abort(403, 'Pengajuan agenda hanya dapat dibuat oleh Guru/Organisasi.');
        }
    }
}