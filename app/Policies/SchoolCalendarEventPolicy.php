<?php

namespace App\Policies;

class SchoolCalendarEventPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'school-calendar.view-any';

    protected string $viewPermission = 'school-calendar.view';

    protected string $createPermission = 'school-calendar.create';

    protected string $updatePermission = 'school-calendar.update';

    protected string $deletePermission = 'school-calendar.delete';

    protected string $restorePermission = 'school-calendar.restore';

    protected string $forceDeletePermission = 'school-calendar.force-delete';
}
