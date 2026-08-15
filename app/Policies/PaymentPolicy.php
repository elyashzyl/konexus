<?php

namespace App\Policies;

class PaymentPolicy extends PlatformPolicy
{
    protected string $viewAnyPermission = 'platform.billing.viewAny';

    protected string $viewPermission = 'platform.billing.view';

    protected string $createPermission = 'platform.billing.create';

    protected string $updatePermission = 'platform.billing.update';

    protected string $deletePermission = 'platform.billing.delete';

    protected string $restorePermission = 'platform.billing.restore';

    protected string $forceDeletePermission = 'platform.billing.forceDelete';
}