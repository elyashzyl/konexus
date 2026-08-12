<?php

namespace App\Repositories;

use App\Models\EnrollmentSignature;
use App\Repositories\Contracts\EnrollmentSignatureRepositoryInterface;

class EnrollmentSignatureRepository extends BaseRepository implements EnrollmentSignatureRepositoryInterface
{
    protected function model(): string
    {
        return EnrollmentSignature::class;
    }
}