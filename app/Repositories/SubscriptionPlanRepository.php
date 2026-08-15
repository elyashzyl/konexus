<?php

namespace App\Repositories;

use App\Models\SubscriptionPlan;
use App\Repositories\Contracts\SubscriptionPlanRepositoryInterface;

class SubscriptionPlanRepository extends BaseRepository implements SubscriptionPlanRepositoryInterface
{
    protected function model(): string
    {
        return SubscriptionPlan::class;
    }
}