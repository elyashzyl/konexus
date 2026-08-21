<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleEnum;
use App\Exceptions\ApiException;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\LicenseRestrictionService;
use App\Services\UserManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * User Management API (admin).
 *
 * Part 8 – User Management. Super Administrators manage accounts and roles;
 * School Administrators may manage accounts but only the Super Administrator
 * may delete users or change roles of fellow administrators.
 */
class UserManagementController extends ApiController
{
    use AuthorizesRequests;

    /**
     * Roles that operate at the platform level and never belong to a school.
     *
     * @var list<string>
     */
    private const PLATFORM_ROLES = ['super-administrator', 'platform-administrator'];

    public function __construct(
        private readonly UserManagementService $service,
    ) {}

    /**
     * The paginated list of users.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = $this->service->paginate($validated);

        return $this->success([
            'items' => UserResource::collection($users->items())->resolve(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ], 'Users retrieved.');
    }

    /**
     * A single user with roles loaded.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = User::query()->with(['roles', 'permissions', 'schoolProfile:id,name,short_name'])->findOrFail($id);

        $this->authorize('view', $user);

        return $this->success(UserResource::make($user)->resolve(), 'User retrieved.');
    }

    /**
     * Create a user account.
     *
     * Every school-level user must be assigned to a school at creation.
     * Platform-level roles (super/platform administrator) cannot belong to a
     * school, and email uniqueness is scoped per school.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $roles = $request->input('roles', []);
        $schoolProfileId = $this->effectiveSchoolId($request->user(), $request->input('school_profile_id'), $roles);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $this->schoolScopedEmailRule($schoolProfileId)],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'school_profile_id' => ['nullable', 'integer', 'exists:school_profiles,id'],
        ]);

        $this->assertSchoolAssignment($roles, $schoolProfileId);

        if ($schoolProfileId !== null) {
            app(LicenseRestrictionService::class)->assertCanCreate($request->user(), 'users', (int) $schoolProfileId);
        }

        $validated['school_profile_id'] = $schoolProfileId;

        return $this->success(
            UserResource::make($this->service->create($validated))->resolve(),
            'User created.',
            201,
        );
    }

    /**
     * Update a user account.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        $this->authorize('update', $user);

        $roles = $request->has('roles') ? $request->input('roles') : $user->roles()->pluck('name')->all();
        $schoolProfileId = $this->effectiveSchoolId($request->user(), $request->input('school_profile_id', $user->school_profile_id), $roles);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', $this->schoolScopedEmailRule($schoolProfileId, $user->id)],
            'password' => ['sometimes', 'string', 'min:8', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'school_profile_id' => ['nullable', 'integer', 'exists:school_profiles,id'],
        ]);

        $this->assertSchoolAssignment($roles, $schoolProfileId);

        $validated['school_profile_id'] = $schoolProfileId;

        return $this->success(
            UserResource::make($this->service->update($user, $validated))->resolve(),
            'User updated.'
        );
    }

    /**
     * Replace the roles of a user.
     */
    public function roles(Request $request, int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        $this->authorize('manageRoles', $user);

        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $this->assertSchoolAssignment($validated['roles'], $user->school_profile_id);

        return $this->success(
            UserResource::make($this->service->syncRoles($user, $validated['roles']))->resolve(),
            'User roles updated.'
        );
    }

    /**
     * Toggle the active state of a user.
     */
    public function toggleActive(Request $request, int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        $this->authorize('update', $user);

        return $this->success(
            UserResource::make($this->service->toggleActive($user))->resolve(),
            'User status updated.'
        );
    }

    /**
     * Reset the password of a user.
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        $this->authorize('update', $user);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $this->service->resetPassword($user, $validated['password']);

        return $this->success(null, 'Password reset.');
    }

    /**
     * Delete a user account.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        $this->authorize('delete', $user);

        $user->delete();

        return $this->success(null, 'User deleted.');
    }

    /**
     * The assignable roles for the management UI.
     */
    public function roleOptions(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return $this->success(['items' => $this->service->roles()], 'Role options retrieved.');
    }

    /**
     * Impersonate a user account.
     *
     * Only admins may impersonate, and they cannot impersonate another admin,
     * their own account, or a deactivated account. The issued token is marked
     * with the `impersonation` ability so the client can restore the original
     * session afterwards.
     */
    public function impersonate(Request $request, int $id): JsonResponse
    {
        $this->authorize('impersonate', User::class);

        $target = User::query()->findOrFail($id);

        if ($target->id === $request->user()->id) {
            throw ApiException::badRequest('You cannot impersonate your own account.');
        }

        if ($target->hasAnyRole(['super-administrator', 'school-administrator'])) {
            throw ApiException::forbidden('You cannot impersonate another administrator.');
        }

        if (! $target->is_active) {
            throw ApiException::forbidden('You cannot impersonate a deactivated account.');
        }

        $token = $target->createToken('impersonation', ['impersonation']);

        activity('user_sessions')
            ->causedBy($request->user())
            ->performedOn($target)
            ->withProperties(['impersonated_email' => $target->email])
            ->log('Impersonated user account.');

        return $this->success([
            'token' => $token->plainTextToken,
            'user' => UserResource::make($target->load('roles:id,name,label,description,guard_name'))->resolve(),
            'impersonator' => UserResource::make($request->user()->load('roles:id,name,label,description,guard_name'))->resolve(),
        ], 'Impersonation started.');
    }

    /**
     * Stop an active impersonation session.
     *
     * Revokes the impersonation token currently being used so the original
     * administrator session can be restored by the client.
     */
    public function stopImpersonating(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token && in_array('impersonation', $token->abilities, true)) {
            $token->delete();
        }

        return $this->success(null, 'Impersonation stopped.');
    }

    /**
     * The school the user should actually be assigned to.
     *
     * School administrators are always confined to their own school; platform
     * administrators may pick any school for school-level accounts or none for
     * platform-level accounts.
     */
    private function effectiveSchoolId(User $actor, mixed $requestedSchoolId, array $roles): ?int
    {
        if ($actor->hasRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName())) {
            return $actor->school_profile_id;
        }

        return $requestedSchoolId !== null ? (int) $requestedSchoolId : null;
    }

    /**
     * Email uniqueness scoped to the school (null = platform-level users).
     */
    private function schoolScopedEmailRule(?int $schoolProfileId, ?int $ignoreId = null): \Illuminate\Validation\Rules\Unique
    {
        return Rule::unique('users', 'email')
            ->ignore($ignoreId)
            ->where(fn ($query) => $schoolProfileId === null
                ? $query->whereNull('school_profile_id')
                : $query->where('school_profile_id', $schoolProfileId));
    }

    /**
     * Assert a user's school assignment is consistent with its roles.
     */
    private function assertSchoolAssignment(array $roles, ?int $schoolProfileId): void
    {
        $platformOnly = $roles !== [] && collect($roles)->every(
            fn (string $role) => in_array($role, self::PLATFORM_ROLES, true),
        );

        if (! $platformOnly && $schoolProfileId === null) {
            throw ApiException::unprocessable('A school is required when assigning school-level roles.', [
                'school_profile_id' => ['A school is required when assigning school-level roles.'],
            ]);
        }

        if ($platformOnly && $schoolProfileId !== null) {
            throw ApiException::unprocessable('Platform-level roles cannot be assigned to a school.', [
                'school_profile_id' => ['Platform-level roles cannot be assigned to a school.'],
            ]);
        }
    }
}