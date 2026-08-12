<?php

namespace App\Repositories;

use App\Models\EnrollmentDocument;
use App\Repositories\Contracts\EnrollmentDocumentRepositoryInterface;

class EnrollmentDocumentRepository extends BaseRepository implements EnrollmentDocumentRepositoryInterface
{
    protected function model(): string
    {
        return EnrollmentDocument::class;
    }
}