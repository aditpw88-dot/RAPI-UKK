@props(['startDate', 'endDate' => null])

@php
    $start = \Illuminate\Support\Carbon::parse($startDate);
    $end = $endDate ? \Illuminate\Support\Carbon::parse($endDate) : $start;
@endphp

{{ $start->format('d/m/Y') }}@if ($start->toDateString() !== $end->toDateString()) – {{ $end->format('d/m/Y') }}@endif
