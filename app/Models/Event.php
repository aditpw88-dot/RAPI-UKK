<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'room_id',
        'pic_id',
        'event_name',
        'event_type',
        'date',
        'end_date',
        'start_time',
        'end_time',
        'status',
        'approved_by',
        'completed_at',
        'move_reason',
        'rejection_reason',
        'cancellation_reason',
        'cancellation_status',
        'cancellation_requested_at',
        'cancellation_approved_by',
        'cancellation_approved_at',
        'cancellation_rejected_reason',
    ];

    protected $appends = [
        'lifecycle_status',
        'lifecycle_status_label',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'cancellation_requested_at' => 'datetime',
            'cancellation_approved_at' => 'datetime',
        ];
    }

    public function getLifecycleStatusAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->cancellation_status === 'pending') {
            return 'cancellation_pending';
        }

        if ($this->status !== 'approved') {
            return $this->status;
        }

        if ($this->completed_at !== null) {
            return 'completed';
        }

        $now = now();
        $startDate = is_string($this->date) ? $this->date : ($this->date?->format('Y-m-d') ?? '');
        $endDate = $this->end_date ? (is_string($this->end_date) ? $this->end_date : $this->end_date->format('Y-m-d')) : $startDate;
        $startTime = $this->start_time ?: '00:00:00';
        $endTime = $this->end_time ?: '23:59:59';

        $startDateTime = \Illuminate\Support\Carbon::parse("{$startDate} {$startTime}");
        $endDateTime = \Illuminate\Support\Carbon::parse("{$endDate} {$endTime}");

        if ($now->greaterThanOrEqualTo($endDateTime)) {
            return 'completed';
        }

        if ($now->lessThan($startDateTime)) {
            return 'upcoming';
        }

        return 'in_progress';
    }

    public function isCompletedOver24Hours(?\Illuminate\Support\Carbon $now = null): bool
    {
        if ($this->lifecycle_status !== 'completed') {
            return false;
        }

        $now = $now ?: now();
        $threshold = $now->copy()->subHours(24);

        $startDate = is_string($this->date) ? $this->date : ($this->date?->format('Y-m-d') ?? '');
        $endDate = $this->end_date ? (is_string($this->end_date) ? $this->end_date : $this->end_date->format('Y-m-d')) : $startDate;
        $endTime = $this->end_time ?: '23:59:59';
        $endDateTime = \Illuminate\Support\Carbon::parse("{$endDate} {$endTime}");

        $finishedAt = $this->completed_at
            ? \Illuminate\Support\Carbon::parse($this->completed_at)
            : $endDateTime;

        return $finishedAt->lessThanOrEqualTo($threshold);
    }

    public function getLifecycleStatusLabelAttribute(): string
    {
        return match ($this->lifecycle_status) {
            'pending' => 'Menunggu Persetujuan',
            'cancellation_pending' => 'Menunggu Pembatalan',
            'upcoming' => 'Belum Terlaksana',
            'in_progress' => 'Sedang Terlaksana',
            'completed' => 'Sudah Terlaksana',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->lifecycle_status),
        };
    }

    public function getLifecycleBadgeClassAttribute(): string
    {
        return match ($this->lifecycle_status) {
            'pending' => 'bg-amber-100 text-amber-800',
            'cancellation_pending' => 'bg-purple-100 text-purple-800',
            'upcoming' => 'bg-amber-100 text-amber-800',
            'in_progress' => 'bg-emerald-100 text-emerald-800',
            'completed' => 'bg-blue-100 text-blue-800',
            'rejected' => 'bg-red-100 text-red-700',
            'cancelled' => 'bg-rose-100 text-rose-800',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public static function sortByLifecycle(iterable $events): \Illuminate\Support\Collection
    {
        return collect($events)->sort(function ($a, $b) {
            $getWeight = function ($e) {
                $status = is_array($e) ? ($e['lifecycle_status'] ?? $e['status'] ?? '') : ($e->lifecycle_status ?? $e->status ?? '');
                return match ($status) {
                    'in_progress' => 0,
                    'upcoming', 'pending', 'cancellation_pending' => 1,
                    'completed' => 2,
                    'rejected', 'cancelled' => 3,
                    default => 2,
                };
            };

            $wA = $getWeight($a);
            $wB = $getWeight($b);

            if ($wA !== $wB) {
                return $wA <=> $wB;
            }

            $dateA = is_array($a) ? ($a['date'] ?? '') : ($a->date ?? '');
            $dateB = is_array($b) ? ($b['date'] ?? '') : ($b->date ?? '');
            $startTimeA = is_array($a) ? ($a['start_time'] ?? '00:00:00') : ($a->start_time ?? '00:00:00');
            $startTimeB = is_array($b) ? ($b['start_time'] ?? '00:00:00') : ($b->start_time ?? '00:00:00');
            $endTimeA = is_array($a) ? ($a['end_time'] ?? '23:59:59') : ($a->end_time ?? '23:59:59');
            $endTimeB = is_array($b) ? ($b['end_time'] ?? '23:59:59') : ($b->end_time ?? '23:59:59');

            if ($wA === 0 || $wA === 1) {
                $dtA = $dateA . ' ' . $startTimeA;
                $dtB = $dateB . ' ' . $startTimeB;
                return $dtA <=> $dtB;
            }

            $endDateA = is_array($a) ? ($a['end_date'] ?? $dateA) : ($a->end_date ?? $dateA);
            $endDateB = is_array($b) ? ($b['end_date'] ?? $dateB) : ($b->end_date ?? $dateB);
            $dtA = $endDateA . ' ' . $endTimeA;
            $dtB = $endDateB . ' ' . $endTimeB;
            return $dtB <=> $dtA;
        })->values();
    }

    public static function getActiveBookedEvents(): \Illuminate\Support\Collection
    {
        return self::with('rooms')
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere(fn ($query) => $query->where('status', 'approved')->whereNull('completed_at'));
            })
            ->whereRaw('COALESCE(end_date, date) >= ?', [now()->toDateString()])
            ->get()
            ->map(function ($event) {
                $startDate = is_string($event->date) ? $event->date : $event->date->format('Y-m-d');
                $endDate = $event->end_date ? (is_string($event->end_date) ? $event->end_date : $event->end_date->format('Y-m-d')) : $startDate;

                return [
                    'id' => $event->id,
                    'event_name' => $event->event_name,
                    'room_ids' => $event->reservedRoomIds(),
                    'date' => $startDate,
                    'end_date' => $endDate,
                    'start_time' => substr($event->start_time ?: '00:00:00', 0, 5),
                    'end_time' => substr($event->end_time ?: '23:59:59', 0, 5),
                    'lifecycle_status' => $event->lifecycle_status,
                ];
            })
            ->values();
    }

    public function scopeApprovedUpcoming(Builder $query): Builder
    {
        $now = now();
        $date = $now->toDateString();
        $time = $now->format('H:i:s');

        return $query->where('status', 'approved')
            ->whereNull('completed_at')
            ->where(function ($q) use ($date, $time) {
                $q->whereDate('date', '>', $date)
                    ->orWhere(function ($q) use ($date, $time) {
                        $q->whereDate('date', '=', $date)
                            ->where('start_time', '>', $time);
                    });
            });
    }

    public function scopeApprovedInProgress(Builder $query): Builder
    {
        $now = now();
        $date = $now->toDateString();
        $time = $now->format('H:i:s');

        return $query->where('status', 'approved')
            ->whereNull('completed_at')
            ->where(function ($q) use ($date, $time) {
                $q->whereDate('date', '<', $date)
                    ->orWhere(function ($q) use ($date, $time) {
                        $q->whereDate('date', '=', $date)
                            ->where('start_time', '<=', $time);
                    });
            })
            ->where(function ($q) use ($date, $time) {
                $q->whereRaw('COALESCE(end_date, date) > ?', [$date])
                    ->orWhere(function ($q) use ($date, $time) {
                        $q->whereRaw('COALESCE(end_date, date) = ?', [$date])
                            ->where('end_time', '>', $time);
                    });
            });
    }

    public function scopeApprovedCompleted(Builder $query): Builder
    {
        $now = now();
        $date = $now->toDateString();
        $time = $now->format('H:i:s');

        return $query->where('status', 'approved')
            ->where(function ($q) use ($date, $time) {
                $q->whereNotNull('completed_at')
                    ->orWhere(function ($q) use ($date, $time) {
                        $q->whereRaw('COALESCE(end_date, date) < ?', [$date])
                            ->orWhere(function ($q) use ($date, $time) {
                                $q->whereRaw('COALESCE(end_date, date) = ?', [$date])
                                    ->whereRaw("COALESCE(end_time, '23:59:59') <= ?", [$time]);
                            });
                    });
            });
    }

    public function scopeOverlappingSchedule(
        Builder $query,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime
    ): Builder {
        return $query
            ->whereDate('date', '<=', $endDate)
            ->whereRaw('COALESCE(end_date, date) >= ?', [$startDate])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);
    }

    public function scopeForRooms(Builder $query, array $roomIds): Builder
    {
        return $query->where(function (Builder $query) use ($roomIds): void {
            $query->whereIn('room_id', $roomIds)
                ->orWhereHas('rooms', fn (Builder $rooms) => $rooms->whereIn('rooms.id', $roomIds));
        });
    }

    public function reservedRoomIds(): array
    {
        $roomIds = $this->relationLoaded('rooms')
            ? $this->rooms->pluck('id')
            : $this->rooms()->pluck('rooms.id');

        return $roomIds
            ->when($this->room_id !== null, fn ($roomIds) => $roomIds->push($this->room_id))
            ->unique()
            ->values()
            ->all();
    }

    public function getRoomNamesAttribute(): string
    {
        $rooms = $this->relationLoaded('rooms') ? $this->rooms : $this->rooms()->get();

        if ($rooms->isEmpty() && $this->room) {
            $rooms = collect([$this->room]);
        }

        return $rooms->pluck('room_name')->join(', ');
    }

    public function calendarColors(): array
    {
        $colors = config('event_types.'.$this->event_type, config('event_types.lainnya'));

        return [
            'backgroundColor' => $colors['background'],
            'borderColor' => $colors['border'],
            'textColor' => $colors['text'],
        ];
    }

    /**
     * Relasi ke Model Room 
     * Nama fungsi 'room()' disesuaikan agar cocok dengan penulisan $events->room di routes/web.php
     */
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class);
    }

    /**
     * Relasi ke Model User (Pengaju / PIC Agenda)
     */
    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancellationApprover()
    {
        return $this->belongsTo(User::class, 'cancellation_approved_by');
    }

    /**
     * Memeriksa apakah agenda dapat diajukan pembatalan oleh Guru
     */
    public function canBeCancelledByGuru(): bool
    {
        if ($this->status === 'cancelled' || $this->status === 'rejected') {
            return false;
        }

        if ($this->cancellation_status === 'pending') {
            return false;
        }

        if ($this->completed_at !== null) {
            return false;
        }

        $now = now();
        $startDate = is_string($this->date) ? $this->date : ($this->date?->format('Y-m-d') ?? '');
        $endDate = $this->end_date ? (is_string($this->end_date) ? $this->end_date : $this->end_date->format('Y-m-d')) : $startDate;
        $endTime = $this->end_time ?: '23:59:59';
        $endDateTime = \Illuminate\Support\Carbon::parse("{$endDate} {$endTime}");

        return $now->lessThan($endDateTime);
    }

    /**
     * Memeriksa apakah agenda dapat dibatalkan langsung oleh Admin.
     * Hanya bisa dibatalkan jika agenda berstatus disetujui (approved) dan belum terlaksana (upcoming).
     * Jika agenda sudah terlaksana (completed) atau sedang terlaksana (in_progress), tidak bisa dibatalkan.
     */
    public function canBeCancelledByAdmin(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        if ($this->cancellation_status === 'pending') {
            return false;
        }

        if ($this->completed_at !== null) {
            return false;
        }

        return $this->lifecycle_status === 'upcoming';
    }

    /**
     * Memeriksa apakah agenda dapat dihapus oleh Admin / Waka.
     * Agenda yang sedang berlangsung (in_progress) tidak dapat dihapus.
     */
    public function canBeDeletedByAdmin(): bool
    {
        return $this->lifecycle_status !== 'in_progress';
    }

    /**
     * Memeriksa apakah ruangan agenda dapat dipindahkan oleh Admin / Waka.
     * Agenda yang berstatus ditolak, dibatalkan, atau sudah terlaksana (completed/expired) tidak dapat dipindahkan.
     */
    public function canBeMovedByAdmin(): bool
    {
        if (in_array($this->status, ['rejected', 'cancelled'])) {
            return false;
        }

        if ($this->cancellation_status === 'pending') {
            return false;
        }

        if ($this->completed_at !== null) {
            return false;
        }

        return $this->lifecycle_status !== 'completed';
    }
}
