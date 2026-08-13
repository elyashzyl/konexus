<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Models\User;
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
        $user = User::query()->with(['roles', 'permissions'])->findOrFail($id);

        $this->authorize('view', $user);

        return $this->success(UserResource::make($user)->resolve(), 'User retrieved.');
    }

    /**
     * Create a user account.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

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

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

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
}