<?php

namespace App\Policies;

class GradeRecordPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'grade-record.view-any';

    protected string $viewPermission = 'grade-record.view';

    protected string $createPermission = 'grade-record.create';

    protected string $updatePermission = 'grade-record.update';

    protected string $deletePermission = 'grade-record.delete';

    protected string $restorePermission = 'grade-record.restore';

    protected string $forceDeletePermission = 'grade-record.force-delete';
}