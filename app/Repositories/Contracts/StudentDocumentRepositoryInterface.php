<?php

namespace App\Repositories\Contracts;

interface StudentDocumentRepositoryInterface extends RepositoryInterface
{
    /**
     * Delete any physical file referenced by a document path.
     */
    public function deleteFileIfExists(string $path): void;
}