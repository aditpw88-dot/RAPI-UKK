<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'room_id',
        'reporter_id',
        'description',
        'photo_path',
        'status_repair',
        'handler_id',
        'reported_at',
        'is_escalated',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'is_escalated' => 'boolean',
    ];

    public function scopeOverdueForEscalation(Builder $query, int $days = 3): Builder
    {
        return $query
            ->where('status_repair', 'Belum Diperbaiki')
            ->where('reported_at', '<=', now()->subDays($days));
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handler_id');
    }
}
