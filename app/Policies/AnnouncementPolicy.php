<?php

namespace App\Policies;

class AnnouncementPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'announcement.view-any';

    protected string $viewPermission = 'announcement.view';

    protected string $createPermission = 'announcement.create';

    protected string $updatePermission = 'announcement.update';

    protected string $deletePermission = 'announcement.delete';

    protected string $restorePermission = 'announcement.restore';

    protected string $forceDeletePermission = 'announcement.force-delete';
}
