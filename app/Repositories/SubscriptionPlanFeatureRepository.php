<?php

namespace App\Repositories;

use App\Models\SubscriptionPlanFeature;
use App\Repositories\Contracts\SubscriptionPlanFeatureRepositoryInterface;

class SubscriptionPlanFeatureRepository extends BaseRepository implements SubscriptionPlanFeatureRepositoryInterface
{
    protected function model(): string
    {
        return SubscriptionPlanFeature::class;
    }
}