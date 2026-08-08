<?php

namespace App\Policies;

class SectionPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'section.view-any';

    protected string $viewPermission = 'section.view';

    protected string $createPermission = 'section.create';

    protected string $updatePermission = 'section.update';

    protected string $deletePermission = 'section.delete';

    protected string $restorePermission = 'section.restore';

    protected string $forceDeletePermission = 'section.force-delete';
}
