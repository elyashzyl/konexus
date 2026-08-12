<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\GradeCorrectionActionRequest;
use App\Http\Requests\Api\GradeCorrectionRequest;
use App\Http\Resources\GradeCorrectionResource;
use App\Models\GradeCorrection;
use App\Models\GradeRecord;
use App\Services\GradeCorrectionService;
use App\Services\GradeRecordService;
use Illuminate\Http\JsonResponse;

class GradeCorrectionController extends CrudController
{
    public function __construct(GradeCorrectionService $service)
    {
        $this->modelClass = GradeCorrection::class;
        $this->resourceClass = GradeCorrectionResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return GradeCorrectionRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Grade correction';
    }

    /**
     * Open a correction request against a finalized grade record.
     *
     * @param  \App\Http\Requests\Api\GradeCorrection  $request
     */
    public function store(\Illuminate\Http\Request $request): JsonResponse
    {
        $this->authorize('create', GradeCorrection::class);

        $formRequest = $this->resolveFormRequest($request);

        $record = GradeRecord::query()->findOrFail($formRequest->validated('grade_record_id'));

        $this->authorize('update', $record);

        /** @var GradeCorrectionService $service */
        $service = $this->service;

        $correction = $service->request($record, $formRequest->validated());

        return $this->success(new GradeCorrectionResource($correction), 'Grade correction requested.', 201);
    }

    /**
     * Approve a pending correction, applying the new grade.
     */
    public function approve(GradeCorrectionActionRequest $request, int $id): JsonResponse
    {
        $model = $this->service->find($id);
        $this->authorize('update', $model);

        /** @var GradeCorrectionService $service */
        $service = $this->service;

        $correction = $service->approve($model, $request->validated());

        return $this->success(new GradeCorrectionResource($correction), 'Grade correction approved and applied.');
    }

    /**
     * Reject a pending correction.
     */
    public function reject(GradeCorrectionActionRequest $request, int $id): JsonResponse
    {
        $model = $this->service->find($id);
        $this->authorize('update', $model);

        /** @var GradeCorrectionService $service */
        $service = $this->service;

        $correction = $service->reject($model, $request->validated());

        return $this->success(new GradeCorrectionResource($correction), 'Grade correction rejected.');
    }

    /**
     * The correction history of a grade record.
     */
    public function historyForGradeRecord(int $gradeRecordId): JsonResponse
    {
        $this->authorize('viewAny', GradeCorrection::class);

        /** @var GradeCorrectionService $service */
        $service = $this->service;

        return $this->success(
            GradeCorrectionResource::collection($service->historyForGradeRecord($gradeRecordId)),
            'Grade correction history retrieved.'
        );
    }
}