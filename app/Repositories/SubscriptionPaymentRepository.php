<?php

namespace App\Repositories;

use App\Models\SubscriptionPayment;
use App\Repositories\Contracts\SubscriptionPaymentRepositoryInterface;

class SubscriptionPaymentRepository extends BaseRepository implements SubscriptionPaymentRepositoryInterface
{
    protected function model(): string
    {
        return SubscriptionPayment::class;
    }
}