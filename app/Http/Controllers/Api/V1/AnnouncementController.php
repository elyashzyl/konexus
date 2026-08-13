<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends CrudController
{
    public function __construct(AnnouncementService $service)
    {
        $this->modelClass = Announcement::class;
        $this->resourceClass = AnnouncementResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return AnnouncementRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Announcement';
    }

    /**
     * Store a new announcement, stamping the authoring user.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', $this->modelClass);

        $data = $this->resolveFormRequest($request)->validated();
        $data['created_by'] = $request->user()->id;
        $data['author_id'] ??= $request->user()->id;

        $model = $this->service->create($data);

        return $this->success(new $this->resourceClass($model), 'Announcement created.', 201);
    }

    /**
     * The announcements visible to the authenticated user (targeted feed).
     */
    public function mine(Request $request): JsonResponse
    {
        $signature = app(\App\Services\PortalIdentityService::class)->audienceSignature($request->user());

        $items = $this->service->visibleFor($signature);

        return $this->success(
            $this->resourceClass::collection($items),
            'Visible announcements retrieved.'
        );
    }
}
