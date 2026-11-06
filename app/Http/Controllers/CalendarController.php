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
        $events = $this->approvedEvents();

        return view('pic.calendar', compact('events'));
    }

    public function picIndex(Request $request)
    {
        $events = $this->approvedEvents();

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

        try {
            $holidays = Cache::remember('indonesia-holidays', now()->addHours(6), function (): array {
                $response = Http::timeout(8)->get(
                    'https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/holidays.json'
                );

                if (! $response->successful()) {
                    throw new RequestException($response);
                }

                $data = $response->json();
                if (! is_array($data)) {
                    throw new UnexpectedValueException('The holiday API returned an invalid response.');
                }

                return $data;
            });
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            Log::warning('Unable to load Indonesian public holidays.', [
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Data hari libur nasional sedang tidak dapat dimuat.',
            ], 503);
        }

        $events = [];
        foreach ($holidays as $date => $holiday) {
            if (
                ! is_string($date)
                || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                || $date < $startDate
                || $date >= $endDate
                || ! is_array($holiday)
                || ! is_string($holiday['summary'] ?? null)
            ) {
                continue;
            }

            $events[] = [
                'id' => 'holiday-'.$date,
                'title' => $holiday['summary'],
                'start' => $date,
                'allDay' => true,
                'backgroundColor' => '#ffe4e6',
                'borderColor' => '#e11d48',
                'textColor' => '#9f1239',
                'extendedProps' => ['isHoliday' => true],
            ];
        }

        return response()->json($events);
    }

    private function approvedEvents()
    {
        return \App\Models\Event::with(['room', 'rooms', 'pic'])
            ->where('status', 'approved')
            ->get()
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
                    ...$event->calendarColors(),
                ];
            });
    }
}