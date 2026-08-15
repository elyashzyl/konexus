<?php

namespace App\Repositories;

use App\Models\License;
use App\Repositories\Contracts\LicenseRepositoryInterface;

class LicenseRepository extends BaseRepository implements LicenseRepositoryInterface
{
    protected function model(): string
    {
        return License::class;
    }
}