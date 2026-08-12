<?php

namespace App\Repositories;

use App\Models\StudentDocument;
use App\Repositories\Contracts\StudentDocumentRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class StudentDocumentRepository extends BaseRepository implements StudentDocumentRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return StudentDocument::class;
    }

    /**
     * Delete the physical file referenced by a document path (if present).
     */
    public function deleteFileIfExists(string $path): void
    {
        $disk = Storage::disk(StudentDocument::storageDisk());

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}