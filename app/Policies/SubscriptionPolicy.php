<?php

namespace App\Policies;

class SubscriptionPolicy extends PlatformPolicy
{
    protected string $viewAnyPermission = 'platform.subscriptions.viewAny';

    protected string $viewPermission = 'platform.subscriptions.view';

    protected string $createPermission = 'platform.subscriptions.create';

    protected string $updatePermission = 'platform.subscriptions.update';

    protected string $deletePermission = 'platform.subscriptions.delete';

    protected string $restorePermission = 'platform.subscriptions.restore';

    protected string $forceDeletePermission = 'platform.subscriptions.forceDelete';

    public function renew(\App\Models\User $user, \App\Models\Subscription $subscription): bool
    {
        return $this->hasPermission($user, 'platform.subscriptions.manage');
    }

    public function generate(\App\Models\User $user, \App\Models\Subscription $subscription): bool
    {
        return $this->hasPermission($user, 'platform.billing.create');
    }

    public function suspend(\App\Models\User $user, \App\Models\Subscription $subscription): bool
    {
        return $this->hasPermission($user, 'platform.subscriptions.manage');
    }

    public function resume(\App\Models\User $user, \App\Models\Subscription $subscription): bool
    {
        return $this->hasPermission($user, 'platform.subscriptions.manage');
    }

    public function cancel(\App\Models\User $user, \App\Models\Subscription $subscription): bool
    {
        return $this->hasPermission($user, 'platform.subscriptions.manage');
    }

    public function changePlan(\App\Models\User $user, \App\Models\Subscription $subscription): bool
    {
        return $this->hasPermission($user, 'platform.subscriptions.manage');
    }

    public function toggleFeature(\App\Models\User $user, \App\Models\Subscription $subscription): bool
    {
        return $this->hasPermission($user, 'platform.subscriptions.manage');
    }

    public function history(\App\Models\User $user, \App\Models\Subscription $subscription): bool
    {
        return $this->hasPermission($user, 'platform.subscriptions.view');
    }
}