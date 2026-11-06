@props(['compact' => false])

@php
    $start = date('H:i', strtotime($startTime));
    $end = date('H:i', strtotime($endTime));
    $sessions = collect(config('schedule.sessions'));
    $startSession = $sessions->search(fn ($times) => $times['start_time'] === $start);
    $endSession = $sessions->search(fn ($times) => $times['end_time'] === $end);
@endphp

<div @class(['space-y-1' => ! $compact, 'space-y-1 whitespace-nowrap' => $compact])>
    @if ($startSession !== false && $endSession !== false)
        @if ($compact)
            <p class="font-semibold text-slate-800">
                Sesi {{ $startSession }}{{ $startSession !== $endSession ? '–' . $endSession : '' }}
            </p>
        @else
            <p class="font-semibold">
                Sesi {{ $startSession }}{{ $startSession !== $endSession ? ' - Sesi ' . $endSession : '' }}
            </p>
        @endif
    @endif
    @if ($compact)
        <p class="text-slate-600">{{ $start }}–{{ $end }}</p>
    @else
        <p>Mulai: {{ $start }}</p>
        <p>Selesai: {{ $end }}</p>
    @endif
</div>
