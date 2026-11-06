<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;

class CompleteExpiredEvents extends Command
{
    protected $signature = 'events:complete-expired';

    protected $description = 'Mark approved events as completed after their scheduled end time';

    public function handle(): int
    {
        $now = now();
        $date = $now->toDateString();
        $time = $now->format('H:i:s');

        $completedCount = Event::query()
            ->where('status', 'approved')
            ->whereNull('completed_at')
            ->where(function ($query) use ($date, $time): void {
                $query->whereRaw('COALESCE(end_date, date) < ?', [$date])
                    ->orWhere(function ($query) use ($date, $time): void {
                        $query->whereRaw('COALESCE(end_date, date) = ?', [$date])
                            ->where('end_time', '<=', $time);
                    });
            })
            ->update(['completed_at' => $now]);

        $this->info("{$completedCount} agenda ditandai sudah terlaksana.");

        return self::SUCCESS;
    }
}
