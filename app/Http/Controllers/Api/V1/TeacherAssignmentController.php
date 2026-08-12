<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\IndexRequest;
use App\Http\Requests\Api\TeacherAssignmentRequest;
use App\Http\Resources\TeacherAssignmentResource;
use App\Models\TeacherAssignment;
use App\Services\TeacherAssignmentService;
use Illuminate\Http\JsonResponse;

class TeacherAssignmentController extends CrudController
{
    public function __construct(TeacherAssignmentService $service)
    {
        $this->modelClass = TeacherAssignment::class;
        $this->resourceClass = TeacherAssignmentResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return TeacherAssignmentRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Teacher assignment';
    }

    /**
     * A per-teacher summary of assigned units for the academic context.
     */
    public function load(IndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', $this->modelClass);

        /** @var TeacherAssignmentService $service */
        $service = $this->service;

        return $this->success(
            $service->loadSummary($request->input('filter', [])),
            'Teacher load summary retrieved.'
        );
    }
}