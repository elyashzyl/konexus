<?php

namespace App\Repositories;

use App\Models\SubscriptionInvoice;
use App\Repositories\Contracts\SubscriptionInvoiceRepositoryInterface;

class SubscriptionInvoiceRepository extends BaseRepository implements SubscriptionInvoiceRepositoryInterface
{
    protected function model(): string
    {
        return SubscriptionInvoice::class;
    }
}