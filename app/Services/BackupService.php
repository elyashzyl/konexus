<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * The Backup service.
 *
 * Part 8 – Backup / Maintenance. Produces a zip archive containing the SQLite
 * database and the private storage directory, stores it on the backup disk and
 * records a Backup row. Only Super Administrators may create or download.
 */
class BackupService
{
    /**
     * The disk used to store backup archives.
     */
    public const DISK = 'backups';

    /**
     * Create a backup archive and record it.
     */
    public function create(User $user, string $type = 'manual', ?string $notes = null): Backup
    {
        $fileName = 'konexus-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.zip';

        $path = $this->archive($fileName);

        $size = Storage::disk(self::DISK)->size($path);

        return Backup::query()->create([
            'file_name' => $fileName,
            'disk' => self::DISK,
            'size' => $size,
            'status' => 'completed',
            'type' => $type,
            'created_by' => $user->id,
            'notes' => $notes,
        ]);
    }

    /**
     * The paginated backup history.
     *
     * @return LengthAwarePaginator<int, Backup>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Backup::query()->with('creator')->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Download a backup archive as a streaming response.
     */
    public function download(Backup $backup): StreamedResponse
    {
        if (! Storage::disk($backup->disk)->exists($backup->file_name)) {
            throw new RuntimeException('The backup file is no longer available on disk.');
        }

        return Storage::disk($backup->disk)->download($backup->file_name);
    }

    /**
     * Delete a backup archive and its record.
     */
    public function delete(Backup $backup): void
    {
        Storage::disk($backup->disk)->delete($backup->file_name);
        $backup->delete();
    }

    /**
     * The space currently used by the backup disk, in bytes.
     */
    public function diskUsage(): int
    {
        return array_sum(array_map(
            fn (string $file) => (int) Storage::disk(self::DISK)->size($file),
            Storage::disk(self::DISK)->allFiles()
        ));
    }

    /**
     * Build the zip archive for the given file name.
     */
    protected function archive(string $fileName): string
    {
        $zip = new ZipArchive;
        $tmp = tempnam(sys_get_temp_dir(), 'bkup').'.zip';

        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the backup archive.');
        }

        // SQLite database (in-memory SQLite has no file to back up).
        $dbPath = database_path('database.sqlite');
        if (config('database.default') === 'sqlite' && file_exists($dbPath)) {
            $zip->addFile($dbPath, 'database/database.sqlite');
        }

        // Private storage (student documents, uploads, etc.).
        $privateRoot = storage_path('app/private');
        if (is_dir($privateRoot)) {
            $this->addDirectory($zip, $privateRoot, 'storage/private');
        }

        $zip->close();

        Storage::disk(self::DISK)->put($fileName, file_get_contents($tmp));

        @unlink($tmp);

        return $fileName;
    }

    /**
     * Recursively add a directory to the archive.
     */
    protected function addDirectory(ZipArchive $zip, string $directory, string $prefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = $prefix.'/'.Str::after($file->getPathname(), $directory.DIRECTORY_SEPARATOR);
            $zip->addFile($file->getPathname(), $relative);
        }
    }
}