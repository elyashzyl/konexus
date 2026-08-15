<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Subscription;
use App\Models\User;

class InvoicePolicy extends PlatformPolicy
{
    protected string $viewAnyPermission = 'platform.billing.viewAny';

    protected string $viewPermission = 'platform.billing.view';

    protected string $createPermission = 'platform.billing.create';

    protected string $updatePermission = 'platform.billing.update';

    protected string $deletePermission = 'platform.billing.delete';

    protected string $restorePermission = 'platform.billing.restore';

    protected string $forceDeletePermission = 'platform.billing.forceDelete';

    public function markPaid(User $user, \App\Models\SubscriptionInvoice $invoice): bool
    {
        return $this->hasPermission($user, 'platform.billing.manage');
    }

    public function generate(User $user, Subscription $subscription): bool
    {
        return $this->hasPermission($user, 'platform.billing.create');
    }
}