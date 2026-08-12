<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AcademicClassMemberRequest;
use App\Http\Requests\Api\AcademicClassRequest;
use App\Http\Resources\AcademicClassResource;
use App\Http\Resources\AcademicClassStudentResource;
use App\Models\AcademicClass;
use App\Services\AcademicClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicClassController extends CrudController
{
    public function __construct(AcademicClassService $service)
    {
        $this->modelClass = AcademicClass::class;
        $this->resourceClass = AcademicClassResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return AcademicClassRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Class';
    }

    /**
     * Display a single class with all auth views.
     */
    public function show(int $id): JsonResponse
    {
        $model = $this->service->find($id);
        $this->authorize('view', $model);
        $model->load(['activeMembers.student']);

        return $this->success(new AcademicClassResource($model), 'Class retrieved.');
    }

    /**
     * The roster of a class.
     */
    public function members(int $id): JsonResponse
    {
        $class = $this->service->find($id);
        $this->authorize('viewAny', AcademicClass::class);

        /** @var AcademicClassService $service */
        $service = $this->service;

        return $this->success(
            AcademicClassStudentResource::collection($service->members($class, request()->boolean('include_inactive'))),
            'Class roster retrieved.'
        );
    }

    /**
     * Assign a student to the class roster.
     */
    public function assignMember(AcademicClassMemberRequest $request, int $id): JsonResponse
    {
        $class = $this->service->find($id);
        $this->authorize('create', \App\Models\AcademicClassStudent::class);

        /** @var AcademicClassService $service */
        $service = $this->service;

        $result = $service->assignMember($class, $request->validated());

        return $this->success($result, $result['action'] === 'added'
            ? 'Student added to class roster.'
            : 'Student reactivated in class roster.', $result['action'] === 'added' ? 201 : 200);
    }

    /**
     * Remove a student from the class roster.
     */
    public function unassignMember(Request $request, int $id, int $studentId): JsonResponse
    {
        $class = $this->service->find($id);
        $this->authorize('delete', new \App\Models\AcademicClassStudent());

        /** @var AcademicClassService $service */
        $service = $this->service;

        $member = $service->unassignMember($class, $studentId, $request->input('remarks'));

        if ($member === null) {
            return $this->success(null, 'Student is not an active member of this class.');
        }

        return $this->success(null, 'Student removed from class roster.');
    }

    /**
     * Rebuild the roster from the official enrollments of the class' section.
     */
    public function syncMembers(int $id): JsonResponse
    {
        $class = $this->service->find($id);
        $this->authorize('update', $class);

        /** @var AcademicClassService $service */
        $service = $this->service;

        $result = $service->syncFromEnrollments($class);

        return $this->success($result, 'Class roster synchronized from enrollments.');
    }
}