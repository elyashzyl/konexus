<?php

namespace App\Repositories;

use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    protected function model(): string
    {
        return Subscription::class;
    }
}