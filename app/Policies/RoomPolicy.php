<?php

namespace App\Policies;

class RoomPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'room.view-any';

    protected string $viewPermission = 'room.view';

    protected string $createPermission = 'room.create';

    protected string $updatePermission = 'room.update';

    protected string $deletePermission = 'room.delete';

    protected string $restorePermission = 'room.restore';

    protected string $forceDeletePermission = 'room.force-delete';
}
