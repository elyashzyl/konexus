<?php

namespace App\Repositories;

use App\Models\SubscriptionFeature;
use App\Repositories\Contracts\SubscriptionFeatureRepositoryInterface;

class SubscriptionFeatureRepository extends BaseRepository implements SubscriptionFeatureRepositoryInterface
{
    protected function model(): string
    {
        return SubscriptionFeature::class;
    }
}