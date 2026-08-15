<?php

namespace App\Repositories;

use App\Models\SubscriptionUsage;
use App\Repositories\Contracts\SubscriptionUsageRepositoryInterface;

class SubscriptionUsageRepository extends BaseRepository implements SubscriptionUsageRepositoryInterface
{
    protected function model(): string
    {
        return SubscriptionUsage::class;
    }
}