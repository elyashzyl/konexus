<?php

namespace App\Console\Commands;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Purges online enrollment applications that were never pursued.
 *
 * Per the public form's Data Privacy notice, an application that is not
 * completed within the retention window (default 30 days) is permanently
 * deleted from the system.
 */
class PurgeAbandonedEnrollments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrollments:purge-abandoned
                            {--days=30 : Only purge applications older than this many days}
                            {--dry-run : Report what would be purged without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete unfinished online enrollment applications past the retention window';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $query = Enrollment::query()
            ->where('status', EnrollmentStatus::PENDING->value)
            ->where(function ($q) use ($cutoff): void {
                $q->where('application_expires_at', '<', $cutoff)
                    ->orWhereNull('application_expires_at')
                    ->where('created_at', '<', $cutoff);
            });

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('No abandoned enrollment applications found.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("Would purge {$count} abandoned enrollment application(s).");

            return self::SUCCESS;
        }

        $query->forceDelete();

        $this->info("Purged {$count} abandoned enrollment application(s).");

        return self::SUCCESS;
    }
}