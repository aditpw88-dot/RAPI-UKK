<div class="calendar-legend" aria-label="Keterangan warna agenda">
    @foreach (config('event_types') as $type)
        <span class="calendar-legend-item">
            <span class="calendar-legend-swatch" style="background-color: {{ $type['background'] }}; border-color: {{ $type['border'] }};"></span>
            {{ $type['label'] }}
        </span>
    @endforeach
    <span class="calendar-legend-item">
        <span class="calendar-legend-swatch" style="background-color: #ffe4e6; border-color: #e11d48;"></span>
        Hari libur nasional / cuti bersama
    </span>
</div>
