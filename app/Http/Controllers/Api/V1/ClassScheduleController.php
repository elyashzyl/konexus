<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\ClassScheduleRequest;
use App\Http\Requests\Api\IndexRequest;
use App\Http\Resources\ClassScheduleResource;
use App\Models\ClassSchedule;
use App\Services\ClassScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassScheduleController extends CrudController
{
    public function __construct(ClassScheduleService $service)
    {
        $this->modelClass = ClassSchedule::class;
        $this->resourceClass = ClassScheduleResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return ClassScheduleRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Class schedule';
    }

    /**
     * Detect potential conflicts of a schedule payload without persisting.
     */
    public function conflicts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ClassSchedule::class);

        /** @var ClassScheduleService $service */
        $service = $this->service;

        return $this->success(
            $service->detectConflicts($request->all()),
            'Conflicts detected.'
        );
    }

    /**
     * The weekly timetable grid filtered by context.
     */
    public function timetable(IndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ClassSchedule::class);

        /** @var ClassScheduleService $service */
        $service = $this->service;

        $filters = $request->input('filter', []);

        return $this->success(
            $service->timetable($filters),
            'Weekly timetable retrieved.'
        );
    }

    /**
     * The timetable of a single section.
     */
    public function sectionTimetable(int $sectionId, Request $request): JsonResponse
    {
        $this->authorize('viewAny', ClassSchedule::class);

        /** @var ClassScheduleService $service */
        $service = $this->service;

        return $this->success(
            $service->sectionTimetable($sectionId, $request->input('filter', [])),
            'Section timetable retrieved.'
        );
    }

    /**
     * The calendar of a teacher.
     */
    public function teacherCalendar(int $teacherId, Request $request): JsonResponse
    {
        $this->authorize('viewAny', ClassSchedule::class);

        /** @var ClassScheduleService $service */
        $service = $this->service;

        return $this->success(
            $service->teacherCalendar($teacherId, $request->input('filter', [])),
            'Teacher calendar retrieved.'
        );
    }
}