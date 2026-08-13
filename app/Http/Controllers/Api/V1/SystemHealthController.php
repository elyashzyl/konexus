<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The System Health API.
 *
 * Part 8 – Maintenance. Reports environment, connectivity and resource usage
 * of the installation. Never exposes credentials or stack traces.
 */
class SystemHealthController extends ApiController
{
    public function __construct(
        private readonly BackupService $backups,
    ) {}

    /**
     * The overall system health snapshot.
     */
    public function __invoke(): JsonResponse
    {
        $dbConnected = true;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $dbConnected = false;
        }

        $pendingJobs = 0;
        try {
            $pendingJobs = (int) DB::table('jobs')->count();
        } catch (\Throwable) {
            // jobs table may be absent before migrations.
        }

        $rootFree = disk_free_space(base_path());
        $rootTotal = disk_total_space(base_path());

        return $this->success([
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'debug' => (bool) config('app.debug'),
                'url' => config('app.url'),
            ],
            'database' => [
                'connection' => config('database.default'),
                'connected' => $dbConnected,
            ],
            'cache' => ['store' => config('cache.default')],
            'queue' => [
                'connection' => config('queue.default'),
                'pending_jobs' => $pendingJobs,
            ],
            'mail' => ['default' => config('mail.default')],
            'storage' => [
                'disk' => config('filesystems.default'),
                'backup_disk' => BackupService::DISK,
                'backup_usage' => $this->backups->diskUsage(),
            ],
            'disk_space' => [
                'free' => $rootFree,
                'total' => $rootTotal,
                'free_human' => $rootFree ? round($rootFree / 1073741824, 2).' GB' : null,
            ],
            'time' => now()->toISOString(),
            'last_backup' => \App\Models\Backup::query()->latest('created_at')->value('created_at')?->toISOString(),
        ], 'System health retrieved.');
    }
}