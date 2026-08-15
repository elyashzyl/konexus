<?php

namespace App\Repositories;

use App\Models\SubscriptionSetting;
use App\Repositories\Contracts\SubscriptionSettingRepositoryInterface;

class SubscriptionSettingRepository extends BaseRepository implements SubscriptionSettingRepositoryInterface
{
    protected function model(): string
    {
        return SubscriptionSetting::class;
    }
}