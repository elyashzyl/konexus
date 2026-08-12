<?php

namespace App\Policies;

class ClassSchedulePolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'class-schedule.view-any';

    protected string $viewPermission = 'class-schedule.view';

    protected string $createPermission = 'class-schedule.create';

    protected string $updatePermission = 'class-schedule.update';

    protected string $deletePermission = 'class-schedule.delete';

    protected string $restorePermission = 'class-schedule.restore';

    protected string $forceDeletePermission = 'class-schedule.force-delete';
}