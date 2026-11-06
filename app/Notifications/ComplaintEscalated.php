<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintEscalated extends Notification
{
    use Queueable;

    public function __construct(private readonly Complaint $complaint)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'complaint_id' => $this->complaint->id,
            'room_name' => $this->complaint->room?->room_name ?? 'Ruang tidak diketahui',
            'reported_at' => $this->complaint->reported_at?->toISOString(),
            'message' => 'Laporan kerusakan belum diperbaiki melewati batas waktu.',
        ];
    }
}
