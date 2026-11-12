<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $events = self::approvedEvents();

        return view('pic.calendar', compact('events'));
    }

    public function picIndex(Request $request)
    {
        $events = self::approvedEvents();

        return view('pic.calendar', compact('events'));
    }

    public function holidays(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $startDate = Carbon::parse($validated['start'])->toDateString();
        $endDate = Carbon::parse($validated['end'])->toDateString();

        $events = \App\Services\IndonesianHolidayService::getHolidays($startDate, $endDate);

        return response()->json($events);
    }

    public static function approvedEvents(bool $includeCompleted = false)
    {
        return \App\Models\Event::with(['room', 'rooms', 'pic'])
            ->where('status', 'approved')
            ->get()
            ->filter(function ($event) use ($includeCompleted) {
                if ($includeCompleted) {
                    return true;
                }
                // Agenda yang sudah selesai di jam-jam sebelumnya (waktu selesai sudah lewat)
                // otomatis terhapus dari tampilan kalender utama dan masuk ke laporan admin.
                return $event->lifecycle_status !== 'completed';
            })
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->event_name,
                    'eventName' => $event->event_name,
                    'roomName' => $event->room_id ? ($event->room_names ?: 'Ruang') : 'Seluruh sekolah',
                    'eventType' => $event->event_type,
                    'picName' => $event->pic?->fullname ?? $event->pic?->name ?? 'Pihak Sekolah',
                    'createdAt' => $event->created_at?->toIso8601String() ?? '',
                    'event_type' => $event->event_type,
                    'start' => $event->room_id ? $event->date . 'T' . $event->start_time : $event->date,
                    'end' => $event->room_id
                        ? ($event->end_date ?? $event->date) . 'T' . $event->end_time
                        : Carbon::parse($event->end_date ?? $event->date)->addDay()->toDateString(),
                    'allDay' => $event->room_id === null,
                    'date' => $event->date,
                    'end_date' => $event->end_date ?? $event->date,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'completed_at' => $event->completed_at?->toIso8601String(),
                    'is_completed_over_24h' => $event->isCompletedOver24Hours(),
                    'lifecycle_status' => $event->lifecycle_status,
                    'lifecycle_status_label' => $event->lifecycle_status_label,
                    'lifecycle_badge_class' => $event->lifecycle_badge_class,
                    'lifecycleStatus' => $event->lifecycle_status,
                    'lifecycleStatusLabel' => $event->lifecycle_status_label,
                    ...$event->calendarColors(),
                ];
            })
            ->values();
    }
}