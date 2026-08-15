<?php

namespace App\Models\Scopes;

use App\Enums\RoleEnum;
use App\Models\SchoolProfile;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts school-owned records to the authenticated user's school.
 *
 * Platform administrators (super / platform administrator) manage every school
 * and are exempt. Users without a school anchor are left unfiltered so legacy
 * and unassigned accounts keep working during the move to per-school isolation.
 */
class SchoolScope implements Scope
{
    /**
     * Apply the school constraint to the given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = SchoolContext::user();

        if ($user === null) {
            return;
        }

        if ($this->isPlatformAdmin($user)) {
            return;
        }

        $schoolProfileId = $user->school_profile_id;

        if ($schoolProfileId === null) {
            return;
        }

        if ($model instanceof SchoolProfile) {
            $builder->whereKey($schoolProfileId);

            return;
        }

        $builder->where($model->qualifyColumn('school_profile_id'), $schoolProfileId);
    }

    private function isPlatformAdmin(mixed $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())
            || $user->hasRole(RoleEnum::PLATFORM_ADMINISTRATOR->roleName());
    }
}
