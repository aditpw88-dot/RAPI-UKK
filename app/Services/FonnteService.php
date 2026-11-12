<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    /**
     * Kirim pesan WhatsApp otomatis ke daftar guru yang dipilih menggunakan Fonnte API.
     * Pesan dijadwalkan otomatis terkirim 1 jam sebelum agenda dimulai sebagai pengingat.
     *
     * @param  \App\Models\Event  $event
     * @param  iterable<\App\Models\User>  $teachers
     * @return array{success: bool, sent_count: int, skipped_count: int, is_scheduled?: bool, scheduled_at?: \Illuminate\Support\Carbon|null, error?: string}
     */
    public function sendAgendaNotification(Event $event, iterable $teachers): array
    {
        $token = trim((string) config('services.whatsapp.fonnte_token'));

        $targets = [];
        $skippedCount = 0;

        foreach ($teachers as $teacher) {
            $rawPhone = preg_replace('/[^0-9]/', '', (string) ($teacher->phone_number ?? ''));
            if ($rawPhone === '') {
                $skippedCount++;
                continue;
            }

            // Sanitasi karakter koma dan pipa pada nama agar format target Fonnte (nomor|nama,nomor|nama) aman
            $cleanName = str_replace([',', '|'], [' ', '-'], trim((string) ($teacher->fullname ?: $teacher->username ?: 'Bapak/Ibu Guru')));
            $targets[] = "{$rawPhone}|{$cleanName}";
        }

        if (empty($targets)) {
            return [
                'success' => false,
                'sent_count' => 0,
                'skipped_count' => $skippedCount,
                'error' => 'Tidak ada guru terpilih yang memiliki nomor WhatsApp aktif.',
            ];
        }

        if ($token === '') {
            return [
                'success' => false,
                'sent_count' => 0,
                'skipped_count' => $skippedCount,
                'error' => 'Token Fonnte belum dikonfigurasi.',
            ];
        }

        // Hitung waktu pengingat: 1 jam sebelum agenda dimulai
        $eventDateStr = is_string($event->date) ? $event->date : $event->date?->format('Y-m-d');
        $eventStartTime = $event->start_time ?: '00:00:00';
        $eventStartDateTime = Carbon::parse("{$eventDateStr} {$eventStartTime}", config('app.timezone', 'Asia/Jakarta'));
        $reminderDateTime = $eventStartDateTime->copy()->subHour();

        $isScheduled = $reminderDateTime->isFuture();
        $message = $this->buildAgendaMessage($event, $isScheduled);

        $payload = [
            'target' => implode(',', $targets),
            'message' => $message,
            'delay' => '2-4',
            'countryCode' => '62',
        ];

        if ($isScheduled) {
            $payload['schedule'] = $reminderDateTime->getTimestamp();
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->asForm()->timeout(15)->post('https://api.fonnte.com/send', $payload);

            $body = $response->json();
            $isOk = $response->successful() && ($body['status'] ?? true) !== false;

            if (! $isOk) {
                $reason = is_array($body) ? ($body['reason'] ?? 'Gagal mengirim pesan ke Fonnte.') : 'Respons tidak valid dari server WhatsApp.';
                Log::warning('Fonnte WhatsApp API gagal mengirim pesan agenda.', [
                    'event_id' => $event->id,
                    'reason' => $reason,
                    'status_code' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'sent_count' => 0,
                    'skipped_count' => $skippedCount,
                    'error' => (string) $reason,
                ];
            }

            return [
                'success' => true,
                'sent_count' => count($targets),
                'skipped_count' => $skippedCount,
                'is_scheduled' => $isScheduled,
                'scheduled_at' => $isScheduled ? $reminderDateTime : null,
            ];
        } catch (\Throwable $e) {
            Log::error('Fonnte WhatsApp API exception saat mengirim notifikasi agenda.', [
                'event_id' => $event->id,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'sent_count' => 0,
                'skipped_count' => $skippedCount,
                'error' => 'Koneksi ke server WhatsApp gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Susun teks pesan pemberitahuan agenda dengan variabel {name} bawaan Fonnte.
     */
    public function buildAgendaMessage(Event $event, bool $isScheduled = false): string
    {
        $event->loadMissing(['rooms', 'room', 'pic']);

        $roomNames = $event->room_names ?: ($event->room?->room_name ?: 'Seluruh Sekolah');
        $typeLabel = config("event_types.{$event->event_type}.label", ucfirst((string) $event->event_type));

        $startDateStr = is_string($event->date) ? $event->date : $event->date->format('Y-m-d');
        $endDateStr = $event->end_date
            ? (is_string($event->end_date) ? $event->end_date : $event->end_date->format('Y-m-d'))
            : $startDateStr;

        $startFormatted = Carbon::parse($startDateStr)->translatedFormat('d F Y');
        $endFormatted = Carbon::parse($endDateStr)->translatedFormat('d F Y');
        $dateDisplay = $startDateStr === $endDateStr
            ? $startFormatted
            : "{$startFormatted} s/d {$endFormatted}";

        $startTime = substr((string) $event->start_time, 0, 5);
        $endTime = substr((string) $event->end_time, 0, 5);
        $sessionLabel = $this->resolveSessionLabel($startTime, $endTime);
        $timeDisplay = $sessionLabel
            ? "{$sessionLabel} ({$startTime} - {$endTime} WIB)"
            : "{$startTime} - {$endTime} WIB";

        $intro = $isScheduled
            ? "Pemberitahuan bahwa agenda kegiatan sekolah berikut akan dimulai dalam *1 jam ke depan*:"
            : "Pemberitahuan bahwa agenda kegiatan sekolah berikut akan segera dimulai:";

        return "Halo Bapak/Ibu *{name}*,\n\n"
            . "🔔 *PENGINGAT AGENDA KEGIATAN SEKOLAH*\n"
            . "{$intro}\n\n"
            . "📌 *Agenda:* {$event->event_name}\n"
            . "🏷️ *Jenis:* {$typeLabel}\n"
            . "🏢 *Ruangan:* {$roomNames}\n"
            . "📅 *Tanggal:* {$dateDisplay}\n"
            . "⏰ *Waktu:* {$timeDisplay}\n\n"
            . "Mohon perhatian dan kehadirannya tepat waktu sesuai jadwal tersebut. Terima kasih.\n\n"
            . "_Pesan otomatis Sistem Manajemen Ruangan & Agenda_";
    }

    /**
     * Cari label Sesi X - Sesi Y berdasarkan jam mulai dan selesai.
     */
    protected function resolveSessionLabel(string $startTime, string $endTime): ?string
    {
        $sessions = config('schedule.sessions', []);
        $startSession = null;
        $endSession = null;

        foreach ($sessions as $num => $times) {
            if (($times['start_time'] ?? '') === $startTime) {
                $startSession = $num;
            }
            if (($times['end_time'] ?? '') === $endTime) {
                $endSession = $num;
            }
        }

        if ($startSession && $endSession) {
            return $startSession === $endSession
                ? "Sesi {$startSession}"
                : "Sesi {$startSession} - Sesi {$endSession}";
        }

        return null;
    }
}

