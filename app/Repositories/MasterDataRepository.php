<?php

namespace App\Repositories;

use App\Models\MasterData;
use App\Repositories\Contracts\MasterDataRepositoryInterface;

class MasterDataRepository extends BaseRepository implements MasterDataRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return MasterData::class;
    }
}
