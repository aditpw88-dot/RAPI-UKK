<?php

namespace App\Console\Commands;

use App\Models\Complaint;
use App\Models\User;
use App\Notifications\ComplaintEscalated;
use Illuminate\Console\Command;

class EscalateOverdueComplaints extends Command
{
    protected $signature = 'complaints:escalate {--days=3 : Minimum age in days before escalation}';

    protected $description = 'Mark unresolved complaints that have exceeded their response deadline';

    public function handle(): int
    {
        $days = $this->option('days');

        if (! is_numeric($days) || (int) $days < 1 || (string) (int) $days !== (string) $days) {
            $this->error('Option --days harus berupa bilangan bulat positif.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays((int) $days);

        Complaint::query()
            ->where('is_escalated', true)
            ->where(function ($query) use ($cutoff) {
                $query->where('status_repair', '!=', 'Belum Diperbaiki')
                    ->orWhere('reported_at', '>', $cutoff);
            })
            ->update(['is_escalated' => false]);

        $wakas = User::whereHas('role', fn ($query) => $query->where('role_name', 'like', '%Waka%'))->get();
        $escalatedCount = 0;

        Complaint::overdueForEscalation((int) $days)
            ->where('is_escalated', false)
            ->with('room')
            ->get()
            ->each(function (Complaint $complaint) use ($wakas, &$escalatedCount): void {
                $updated = Complaint::query()
                    ->whereKey($complaint->id)
                    ->where('is_escalated', false)
                    ->update(['is_escalated' => true]);

                if ($updated !== 1) {
                    return;
                }

                $escalatedCount++;
                foreach ($wakas as $waka) {
                    $waka->notify(new ComplaintEscalated($complaint));
                }
            });

        $this->info("{$escalatedCount} laporan dikirim sebagai notifikasi eskalasi kepada {$wakas->count()} Waka.");

        return self::SUCCESS;
    }
}
