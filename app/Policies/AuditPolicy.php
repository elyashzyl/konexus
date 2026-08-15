<?php

namespace App\Policies;

class AuditPolicy extends PlatformPolicy
{
    protected string $viewAnyPermission = 'platform.audit.viewAny';

    protected string $viewPermission = 'platform.audit.view';

    protected string $createPermission = 'platform.audit.create';

    protected string $updatePermission = 'platform.audit.update';

    protected string $deletePermission = 'platform.audit.delete';

    protected string $restorePermission = 'platform.audit.restore';

    protected string $forceDeletePermission = 'platform.audit.forceDelete';
}