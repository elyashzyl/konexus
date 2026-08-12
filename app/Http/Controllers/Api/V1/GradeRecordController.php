<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\GradeRecordBulkRequest;
use App\Http\Requests\Api\GradeRecordRequest;
use App\Http\Requests\Api\GradeTransitionRequest;
use App\Http\Resources\GradeRecordResource;
use App\Models\GradeRecord;
use App\Models\SubjectOffering;
use App\Services\GradeRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeRecordController extends CrudController
{
    public function __construct(GradeRecordService $service)
    {
        $this->modelClass = GradeRecord::class;
        $this->resourceClass = GradeRecordResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return GradeRecordRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Grade record';
    }

    /**
     * Display a single grade record.
     */
    public function show(int $id): JsonResponse
    {
        $model = $this->service->find($id);
        $this->authorize('view', $model);

        return $this->success(new GradeRecordResource($model), 'Grade record retrieved.');
    }

    /**
     * Bulk grade entry for a subject offering (teacher grade sheet).
     */
    public function bulkUpsert(GradeRecordBulkRequest $request, int $offeringId): JsonResponse
    {
        $this->authorize('create', GradeRecord::class);

        $offering = SubjectOffering::query()->findOrFail($offeringId);

        /** @var GradeRecordService $service */
        $service = $this->service;

        $result = $service->bulkUpsert($offering, $request->validated());

        return $this->success($result, 'Grades saved.');
    }

    /**
     * Transition a grade record through the workflow.
     */
    public function transition(GradeTransitionRequest $request, int $id): JsonResponse
    {
        $model = $this->service->find($id);
        $this->authorize('update', $model);

        /** @var GradeRecordService $service */
        $service = $this->service;

        $record = $service->transition($model, $request->input('status'), $request->validated());

        return $this->success(new GradeRecordResource($record), 'Grade record updated.');
    }

    /**
     * The report-card foundation payload of a student.
     */
    public function studentReport(int $studentId, Request $request): JsonResponse
    {
        $this->authorize('viewAny', GradeRecord::class);

        /** @var GradeRecordService $service */
        $service = $this->service;

        return $this->success(
            $service->studentReport(
                $studentId,
                $request->integer('academic_year_id') ?: null,
                $request->integer('academic_term_id') ?: null
            ),
            'Student report retrieved.'
        );
    }
}