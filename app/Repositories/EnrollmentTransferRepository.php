<?php

namespace App\Repositories;

use App\Models\EnrollmentTransfer;
use App\Repositories\Contracts\EnrollmentTransferRepositoryInterface;

class EnrollmentTransferRepository extends BaseRepository implements EnrollmentTransferRepositoryInterface
{
    protected function model(): string
    {
        return EnrollmentTransfer::class;
    }
}