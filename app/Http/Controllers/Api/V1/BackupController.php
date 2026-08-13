<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\BackupService;
use App\Models\Backup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Backup & Maintenance API.
 *
 * Part 8 – Backup / Maintenance. Restricted to Super Administrators (the
 * middleware registered on the routes enforces it).
 */
class BackupController extends ApiController
{
    public function __construct(
        private readonly BackupService $service,
    ) {}

    /**
     * The paginated backup history.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $backups = $this->service->paginate($perPage);

        return $this->success([
            'items' => $backups->map(fn (Backup $backup) => [
                'id' => $backup->id,
                'file_name' => $backup->file_name,
                'size' => $backup->size,
                'size_human' => $backup->size ? round($backup->size / 1048576, 2).' MB' : '0 B',
                'status' => $backup->status,
                'type' => $backup->type,
                'created_by' => $backup->creator?->name,
                'notes' => $backup->notes,
                'created_at' => $backup->created_at?->toISOString(),
            ]),
            'pagination' => [
                'current_page' => $backups->currentPage(),
                'per_page' => $backups->perPage(),
                'total' => $backups->total(),
                'last_page' => $backups->lastPage(),
            ],
        ], 'Backups retrieved.');
    }

    /**
     * Create a new backup archive.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'string', 'in:manual,scheduled'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $backup = $this->service->create($request->user(), $validated['type'] ?? 'manual', $validated['notes'] ?? null);

        return $this->success([
            'id' => $backup->id,
            'file_name' => $backup->file_name,
            'status' => $backup->status,
            'size_human' => $backup->size ? round($backup->size / 1048576, 2).' MB' : '0 B',
        ], 'Backup created.', 201);
    }

    /**
     * Download a backup archive.
     */
    public function download(Request $request, int $id)
    {
        $backup = Backup::query()->findOrFail($id);

        return $this->service->download($backup);
    }

    /**
     * Delete a backup archive and its record.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $backup = Backup::query()->findOrFail($id);

        $this->service->delete($backup);

        return $this->success(null, 'Backup deleted.');
    }
}