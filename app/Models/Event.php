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
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function getLifecycleStatusAttribute(): string
    {
        if ($this->status !== 'approved') {
            return $this->status;
        }

        return $this->completed_at ? 'completed' : 'in_progress';
    }

    public function getLifecycleStatusLabelAttribute(): string
    {
        return match ($this->lifecycle_status) {
            'pending' => 'Menunggu Persetujuan',
            'in_progress' => 'Sedang Terlaksana',
            'completed' => 'Sudah Terlaksana',
            'rejected' => 'Ditolak',
            default => ucfirst($this->lifecycle_status),
        };
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
        return $this->rooms()
            ->pluck('rooms.id')
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
}
