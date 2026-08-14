<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * User & Role Management.
 *
 * Part 8 – User Management. Creating, updating, assigning roles, toggling
 * activation and resetting passwords of portal / staff accounts. Role changes
 * are transactional and idempotent via syncRoles.
 */
class UserManagementService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * The paginated list of users.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles', 'schoolProfile:id,name,short_name'])
            ->when(filled($filters['search'] ?? null), fn (Builder $q) => $q->where(function (Builder $q) use ($filters): void {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('email', 'like', '%'.$filters['search'].'%');
            }))
            ->when(filled($filters['role'] ?? null), fn (Builder $q) => $q->whereHas('roles', fn (Builder $q) => $q->where('name', $filters['role'])))
            ->when(in_array($filters['status'] ?? null, ['active', 'inactive'], true), function (Builder $q) use ($filters): void {
                $q->where('is_active', $filters['status'] === 'active');
            })
            ->orderBy('created_at', 'desc')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    /**
     * Create a user account with roles.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = $this->users->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => (bool) ($data['is_active'] ?? true),
                'school_profile_id' => $data['school_profile_id'] ?? null,
            ]);

            if (! empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            return $user->load('roles');
        });
    }

    /**
     * Update a user account (and optionally its roles).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $attributes = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            ], fn ($value) => $value !== null);

            if (array_key_exists('school_profile_id', $data)) {
                $attributes['school_profile_id'] = $data['school_profile_id'];
            }

            if (filled($data['password'] ?? null)) {
                $attributes['password'] = $data['password'];
            }

            $this->users->update($user, $attributes);

            if (array_key_exists('roles', $data)) {
                $user->syncRoles($data['roles'] ?? []);
            }

            return $user->load('roles');
        });
    }

    /**
     * Replace the roles of a user.
     *
     * @param  list<string>  $roles
     */
    public function syncRoles(User $user, array $roles): User
    {
        $user->syncRoles($roles);

        return $user->load('roles');
    }

    /**
     * Toggle the active state of a user account.
     */
    public function toggleActive(User $user): User
    {
        $this->users->update($user, ['is_active' => ! (bool) $user->is_active]);

        return $user->fresh(['roles']);
    }

    /**
     * Reset the password of a user.
     */
    public function resetPassword(User $user, string $password): void
    {
        $this->users->update($user, ['password' => $password]);
    }

    /**
     * All assignable roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    public function roles()
    {
        return Role::orderBy('name')->get();
    }
}