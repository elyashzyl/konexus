<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\GradeScaleEntryRequest;
use App\Http\Requests\Api\GradeScaleRequest;
use App\Http\Resources\GradeScaleEntryResource;
use App\Http\Resources\GradeScaleResource;
use App\Models\GradeScale;
use App\Models\GradeScaleEntry;
use App\Services\GradeScaleService;
use Illuminate\Http\JsonResponse;

class GradeScaleController extends CrudController
{
    public function __construct(GradeScaleService $service)
    {
        $this->modelClass = GradeScale::class;
        $this->resourceClass = GradeScaleResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return GradeScaleRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Grade scale';
    }

    /**
     * The bands of a grade scale.
     */
    public function entries(int $id): JsonResponse
    {
        $scale = $this->service->find($id);

        $this->authorize('view', $scale);

        /** @var GradeScaleService $service */
        $service = $this->service;

        return $this->success(
            GradeScaleEntryResource::collection($service->entries($scale->id)),
            'Grade scale entries retrieved.'
        );
    }

    /**
     * Add a band to a grade scale.
     */
    public function storeEntry(GradeScaleEntryRequest $request, int $id): JsonResponse
    {
        $scale = $this->service->find($id);

        $this->authorize('update', $scale);

        /** @var GradeScaleService $service */
        $service = $this->service;

        $entry = $service->addEntry($scale, $request->validated());

        return $this->success(new GradeScaleEntryResource($entry), 'Grade scale entry created.', 201);
    }

    /**
     * Update a band of a grade scale.
     */
    public function updateEntry(GradeScaleEntryRequest $request, int $id, int $entryId): JsonResponse
    {
        $scale = $this->service->find($id);

        $this->authorize('update', $scale);

        $entry = GradeScaleEntry::query()->findOrFail($entryId);

        if ($entry->grade_scale_id !== $scale->id) {
            return $this->error('The entry does not belong to the given grade scale.', null, 422);
        }

        /** @var GradeScaleService $service */
        $service = $this->service;

        $entry = $service->updateEntry($entry, $request->validated());

        return $this->success(new GradeScaleEntryResource($entry), 'Grade scale entry updated.');
    }

    /**
     * Remove a band of a grade scale.
     */
    public function destroyEntry(int $id, int $entryId): JsonResponse
    {
        $scale = $this->service->find($id);

        $this->authorize('update', $scale);

        $entry = GradeScaleEntry::query()->findOrFail($entryId);

        if ($entry->grade_scale_id !== $scale->id) {
            return $this->error('The entry does not belong to the given grade scale.', null, 422);
        }

        /** @var GradeScaleService $service */
        $service = $this->service;

        $service->deleteEntry($entry);

        return $this->success(null, 'Grade scale entry deleted.');
    }

    /**
     * Resolve a raw grade against the active grade scale.
     */
    public function resolve(\Illuminate\Http\Request $request): JsonResponse
    {
        $this->authorize('viewAny', GradeScale::class);

        $this->validate($request, [
            'raw_grade' => ['required', 'numeric'],
            'grade_scale_id' => ['nullable', 'integer', 'exists:grade_scales,id'],
        ]);

        /** @var GradeScaleService $service */
        $service = $this->service;

        $scale = $request->filled('grade_scale_id')
            ? GradeScale::query()->findOrFail($request->input('grade_scale_id'))
            : $service->activeScale();

        return $this->success(
            $service->finalizeGrade($request->input('raw_grade'), $scale),
            'Grade resolved.'
        );
    }
}