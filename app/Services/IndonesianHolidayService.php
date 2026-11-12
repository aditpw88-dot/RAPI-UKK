<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndonesianHolidayService
{
    /**
     * Database Hari Libur Nasional & Cuti Bersama Resmi Indonesia (SKB 3 Menteri)
     * Format: YYYY-MM-DD => Nama Hari Libur / Acara Nasional
     */
    protected static array $nationalHolidays = [
        // ── 2024 ─────────────────────────────────────────────────────────────
        '2024-01-01' => 'Tahun Baru 2024 Masehi',
        '2024-02-08' => 'Isra Mi\'raj Nabi Muhammad SAW',
        '2024-02-09' => 'Cuti Bersama Tahun Baru Imlek 2575 Kongzili',
        '2024-02-10' => 'Tahun Baru Imlek 2575 Kongzili',
        '2024-03-11' => 'Hari Suci Nyepi (Tahun Baru Saka 1946)',
        '2024-03-12' => 'Cuti Bersama Hari Suci Nyepi',
        '2024-03-29' => 'Wafat Yesus Kristus',
        '2024-03-31' => 'Hari Paskah',
        '2024-04-08' => 'Cuti Bersama Idul Fitri 1445 H',
        '2024-04-09' => 'Cuti Bersama Idul Fitri 1445 H',
        '2024-04-10' => 'Hari Raya Idul Fitri 1445 H',
        '2024-04-11' => 'Hari Raya Idul Fitri 1445 H (Hari Ke-2)',
        '2024-04-12' => 'Cuti Bersama Idul Fitri 1445 H',
        '2024-04-15' => 'Cuti Bersama Idul Fitri 1445 H',
        '2024-05-01' => 'Hari Buruh Internasional',
        '2024-05-09' => 'Kenaikan Yesus Kristus',
        '2024-05-10' => 'Cuti Bersama Kenaikan Yesus Kristus',
        '2024-05-23' => 'Hari Raya Waisak 2568 BE',
        '2024-05-24' => 'Cuti Bersama Hari Raya Waisak',
        '2024-06-01' => 'Hari Lahir Pancasila',
        '2024-06-17' => 'Hari Raya Idul Adha 1445 H',
        '2024-06-18' => 'Cuti Bersama Idul Adha',
        '2024-07-07' => 'Tahun Baru Islam 1446 H',
        '2024-08-17' => 'Hari Kemerdekaan Republik Indonesia Ke-79',
        '2024-09-16' => 'Maulid Nabi Muhammad SAW',
        '2024-12-25' => 'Hari Raya Natal',
        '2024-12-26' => 'Cuti Bersama Hari Raya Natal',

        // ── 2025 ─────────────────────────────────────────────────────────────
        '2025-01-01' => 'Tahun Baru 2025 Masehi',
        '2025-01-27' => 'Isra Mi\'raj Nabi Muhammad SAW',
        '2025-01-28' => 'Cuti Bersama Tahun Baru Imlek 2576 Kongzili',
        '2025-01-29' => 'Tahun Baru Imlek 2576 Kongzili',
        '2025-03-28' => 'Cuti Bersama Hari Suci Nyepi',
        '2025-03-29' => 'Hari Suci Nyepi (Tahun Baru Saka 1947)',
        '2025-03-31' => 'Hari Raya Idul Fitri 1446 H',
        '2025-04-01' => 'Hari Raya Idul Fitri 1446 H (Hari Ke-2)',
        '2025-04-02' => 'Cuti Bersama Idul Fitri 1446 H',
        '2025-04-03' => 'Cuti Bersama Idul Fitri 1446 H',
        '2025-04-04' => 'Cuti Bersama Idul Fitri 1446 H',
        '2025-04-07' => 'Cuti Bersama Idul Fitri 1446 H',
        '2025-04-18' => 'Wafat Yesus Kristus',
        '2025-04-20' => 'Kebangkitan Yesus Kristus (Hari Paskah)',
        '2025-05-01' => 'Hari Buruh Internasional',
        '2025-05-12' => 'Hari Raya Waisak 2569 BE',
        '2025-05-13' => 'Cuti Bersama Hari Raya Waisak',
        '2025-05-29' => 'Kenaikan Yesus Kristus',
        '2025-05-30' => 'Cuti Bersama Kenaikan Yesus Kristus',
        '2025-06-01' => 'Hari Lahir Pancasila',
        '2025-06-06' => 'Hari Raya Idul Adha 1446 H',
        '2025-06-09' => 'Cuti Bersama Idul Adha',
        '2025-06-27' => 'Tahun Baru Islam 1447 H',
        '2025-08-17' => 'Hari Kemerdekaan Republik Indonesia Ke-80',
        '2025-09-05' => 'Maulid Nabi Muhammad SAW',
        '2025-12-25' => 'Hari Raya Natal',
        '2025-12-26' => 'Cuti Bersama Hari Raya Natal',

        // ── 2026 (Tahun Aktif Kalender Agenda Sekolah) ───────────────────────
        '2026-01-01' => 'Tahun Baru 2026 Masehi',
        '2026-01-16' => 'Isra Mi\'raj Nabi Muhammad SAW',
        '2026-02-16' => 'Cuti Bersama Tahun Baru Imlek',
        '2026-02-17' => 'Tahun Baru Imlek 2577 Kongzili',
        '2026-03-18' => 'Cuti Bersama Nyepi',
        '2026-03-19' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)',
        '2026-03-20' => 'Hari Raya Idul Fitri 1447 H',
        '2026-03-21' => 'Hari Raya Idul Fitri 1447 H (Hari Ke-2)',
        '2026-03-22' => 'Cuti Bersama Idul Fitri 1447 H',
        '2026-03-23' => 'Cuti Bersama Idul Fitri 1447 H',
        '2026-03-24' => 'Cuti Bersama Idul Fitri 1447 H',
        '2026-04-03' => 'Wafat Yesus Kristus (Jumat Agung)',
        '2026-04-05' => 'Kebangkitan Yesus Kristus (Hari Paskah)',
        '2026-05-01' => 'Hari Buruh Internasional',
        '2026-05-14' => 'Kenaikan Yesus Kristus',
        '2026-05-15' => 'Cuti Bersama Kenaikan Yesus Kristus',
        '2026-05-27' => 'Hari Raya Idul Adha 1447 H',
        '2026-05-28' => 'Cuti Bersama Idul Adha 1447 H',
        '2026-05-31' => 'Hari Raya Waisak 2570 BE',
        '2026-06-01' => 'Hari Lahir Pancasila',
        '2026-06-16' => 'Tahun Baru Islam 1448 H (1 Muharram)',
        '2026-08-17' => 'Hari Kemerdekaan Republik Indonesia Ke-81',
        '2026-08-25' => 'Maulid Nabi Muhammad SAW',
        '2026-11-10' => 'Hari Pahlawan Nasional',
        '2026-12-24' => 'Cuti Bersama Hari Raya Natal',
        '2026-12-25' => 'Hari Raya Natal',
        '2026-12-26' => 'Cuti Bersama Hari Raya Natal',

        // ── 2027 ─────────────────────────────────────────────────────────────
        '2027-01-01' => 'Tahun Baru 2027 Masehi',
        '2027-02-05' => 'Isra Mi\'raj Nabi Muhammad SAW',
        '2027-02-06' => 'Tahun Baru Imlek 2578 Kongzili',
        '2027-03-09' => 'Hari Raya Idul Fitri 1448 H',
        '2027-03-10' => 'Hari Raya Idul Fitri 1448 H (Hari Ke-2)',
        '2027-03-26' => 'Wafat Yesus Kristus',
        '2027-03-28' => 'Hari Paskah',
        '2027-04-09' => 'Hari Suci Nyepi (Tahun Baru Saka 1949)',
        '2027-05-01' => 'Hari Buruh Internasional',
        '2027-05-06' => 'Kenaikan Yesus Kristus',
        '2027-05-17' => 'Hari Raya Idul Adha 1448 H',
        '2027-05-20' => 'Hari Raya Waisak 2571 BE',
        '2027-06-01' => 'Hari Lahir Pancasila',
        '2027-06-06' => 'Tahun Baru Islam 1449 H',
        '2027-08-15' => 'Maulid Nabi Muhammad SAW',
        '2027-08-17' => 'Hari Kemerdekaan RI Ke-82',
        '2027-11-10' => 'Hari Pahlawan Nasional',
        '2027-12-24' => 'Cuti Bersama Hari Raya Natal',
        '2027-12-25' => 'Hari Raya Natal',
        '2027-12-26' => 'Cuti Bersama Hari Raya Natal',
    ];

    /**
     * Dapatkan daftar hari libur nasional dalam rentang tanggal tertentu.
     */
    public static function getHolidays(string $startDate, string $endDate): array
    {
        $all = self::$nationalHolidays;

        // Tambahkan hari libur tetap tahunan untuk tahun yang dicakup rentang waktu
        $startYear = (int) substr($startDate, 0, 4);
        $endYear = (int) substr($endDate, 0, 4);

        for ($year = $startYear; $year <= $endYear; $year++) {
            $fixedHolidays = [
                sprintf('%04d-01-01', $year) => "Tahun Baru {$year} Masehi",
                sprintf('%04d-05-01', $year) => 'Hari Buruh Internasional',
                sprintf('%04d-06-01', $year) => 'Hari Lahir Pancasila',
                sprintf('%04d-08-17', $year) => 'Hari Kemerdekaan Republik Indonesia',
                sprintf('%04d-11-10', $year) => 'Hari Pahlawan Nasional',
                sprintf('%04d-12-24', $year) => 'Cuti Bersama Hari Raya Natal',
                sprintf('%04d-12-25', $year) => 'Hari Raya Natal',
                sprintf('%04d-12-26', $year) => 'Cuti Bersama Hari Raya Natal',
            ];

            foreach ($fixedHolidays as $d => $name) {
                if (! isset($all[$d])) {
                    $all[$d] = $name;
                }
            }
        }

        // Coba gabungkan dengan API eksternal (misal saat di-mock pada automated test)
        try {
            $remoteHolidays = Cache::remember('indonesia-holidays-remote', now()->addHours(6), function () {
                $response = Http::timeout(2)->get(
                    'https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/holidays.json'
                );

                if ($response->successful() && is_array($response->json())) {
                    return $response->json();
                }

                return null;
            });

            if (is_array($remoteHolidays)) {
                foreach ($remoteHolidays as $date => $holiday) {
                    if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && is_array($holiday) && ! empty($holiday['summary'])) {
                        $all[$date] = $holiday['summary'];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Abaikan error eksternal secara tenang
        }

        ksort($all);

        $events = [];
        foreach ($all as $date => $title) {
            if ($date < $startDate || $date >= $endDate) {
                continue;
            }

            $events[] = [
                'id' => 'holiday-' . $date,
                'title' => $title,
                'start' => $date,
                'allDay' => true,
                'backgroundColor' => '#ffe4e6',
                'borderColor' => '#e11d48',
                'textColor' => '#9f1239',
                'extendedProps' => [
                    'isHoliday' => true,
                    'holidayName' => $title,
                ],
            ];
        }

        return $events;
    }

    /**
     * Cek apakah suatu tanggal adalah hari libur nasional.
     */
    public static function isNationalHoliday(string $date): bool
    {
        if (isset(self::$nationalHolidays[$date])) {
            return true;
        }

        $monthDay = substr($date, 5);
        $fixedMonthDays = ['01-01', '05-01', '06-01', '08-17', '11-10', '12-24', '12-25', '12-26'];

        return in_array($monthDay, $fixedMonthDays, true);
    }

    /**
     * Ambil nama hari libur pada tanggal tertentu.
     */
    public static function getHolidayName(string $date): ?string
    {
        if (isset(self::$nationalHolidays[$date])) {
            return self::$nationalHolidays[$date];
        }

        $year = substr($date, 0, 4);
        $monthDay = substr($date, 5);

        return match ($monthDay) {
            '01-01' => "Tahun Baru {$year} Masehi",
            '05-01' => 'Hari Buruh Internasional',
            '06-01' => 'Hari Lahir Pancasila',
            '08-17' => 'Hari Kemerdekaan Republik Indonesia',
            '11-10' => 'Hari Pahlawan Nasional',
            '12-24' => 'Cuti Bersama Hari Raya Natal',
            '12-25' => 'Hari Raya Natal',
            '12-26' => 'Cuti Bersama Hari Raya Natal',
            default => null,
        };
    }
}

