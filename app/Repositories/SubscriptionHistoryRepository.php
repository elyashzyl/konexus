<?php

namespace App\Repositories;

use App\Models\SubscriptionHistory;
use App\Repositories\Contracts\SubscriptionHistoryRepositoryInterface;

class SubscriptionHistoryRepository extends BaseRepository implements SubscriptionHistoryRepositoryInterface
{
    protected function model(): string
    {
        return SubscriptionHistory::class;
    }
}