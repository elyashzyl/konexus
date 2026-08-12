<?php

namespace App\Policies;

class AcademicSettingPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'academic-setting.view-any';

    protected string $viewPermission = 'academic-setting.view';

    protected string $createPermission = 'academic-setting.create';

    protected string $updatePermission = 'academic-setting.update';

    protected string $deletePermission = 'academic-setting.delete';

    protected string $restorePermission = 'academic-setting.restore';

    protected string $forceDeletePermission = 'academic-setting.force-delete';
}