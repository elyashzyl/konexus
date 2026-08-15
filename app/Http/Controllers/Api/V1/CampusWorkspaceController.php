<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SelectCampusWorkspaceRequest;
use App\Http\Resources\CampusResource;
use App\Http\Resources\UserResource;
use App\Services\CampusWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampusWorkspaceController extends ApiController
{
    public function __construct(private readonly CampusWorkspaceService $workspaces) {}

    /**
     * List the campus workspaces available to the current account.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $activeCampus = $this->workspaces->activeFor($user);

        return $this->success([
            'active_campus' => $activeCampus ? new CampusResource($activeCampus) : null,
            'campuses' => CampusResource::collection($this->workspaces->availableFor($user)),
        ], 'Campus workspaces retrieved.');
    }

    /**
     * Persist the active campus workspace for the current user.
     */
    public function select(SelectCampusWorkspaceRequest $request): JsonResponse
    {
        $user = $request->user();
        $campus = $this->workspaces->select($user, $request->integer('campus_id'));
        $user->load(['roles:id,name,label,description,guard_name', 'schoolProfile:id,name,short_name', 'activeCampus:id,name,code']);

        return $this->success([
            'active_campus' => new CampusResource($campus),
            'user' => new UserResource($user),
        ], 'Campus workspace changed.');
    }
}
