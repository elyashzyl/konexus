<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StudentRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends PeopleCrudController
{
    public function __construct(StudentService $service)
    {
        $this->modelClass = Student::class;
        $this->resourceClass = StudentResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return StudentRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Student';
    }

    /**
     * The CSV columns exported for the students module.
     *
     * @return array<string, string>
     */
    protected function exportColumns(): array
    {
        return [
            'Student Number' => 'student_number',
            'LRN' => 'lrn',
            'School ID' => 'school_student_id',
            'Last Name' => 'last_name',
            'First Name' => 'first_name',
            'Middle Name' => 'middle_name',
            'Gender' => 'gender',
            'Birth Date' => 'birth_date',
            'Email' => 'email',
            'Mobile' => 'mobile_number',
            'Status' => 'status',
        ];
    }

    /**
     * The activity-log timeline of a student.
     */
    public function activities(int $id): JsonResponse
    {
        $student = $this->service->find($id);

        $this->authorize('view', $student);

        $activities = $student->activitiesAsSubject()->orderByDesc('created_at')->paginate(20);

        return $this->success([
            'items' => ActivityResource::collection($activities->items()),
            'pagination' => [
                'current_page' => $activities->currentPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
                'last_page' => $activities->lastPage(),
            ],
        ], 'Student activities retrieved.');
    }

    /**
     * Upload a profile picture for a student.
     */
    public function storePhoto(Request $request, int $id): JsonResponse
    {
        $student = $this->service->find($id);

        $this->authorize('update', $student);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        /** @var StudentService $service */
        $service = $this->service;
        $path = $service->storePhoto($student, $request->file('photo'));

        return $this->success([
            'id' => $student->id,
            'profile_picture_path' => $path,
            'profile_picture_url' => url('storage/'.$path),
        ], 'Profile picture updated.');
    }

    /**
     * Link a parent to a student.
     */
    public function linkParent(int $id, int $parentId): JsonResponse
    {
        $student = $this->service->find($id);

        $this->authorize('update', $student);

        $student->parents()->syncWithoutDetaching([$parentId]);

        return $this->success(
            (new StudentResource($student->load('parents', 'guardians')))->resolve(),
            'Parent linked.'
        );
    }

    /**
     * Unlink a parent from a student.
     */
    public function unlinkParent(int $id, int $parentId): JsonResponse
    {
        $student = $this->service->find($id);

        $this->authorize('update', $student);

        $student->parents()->detach($parentId);

        return $this->success(
            (new StudentResource($student->load('parents', 'guardians')))->resolve(),
            'Parent unlinked.'
        );
    }

    /**
     * Link a guardian to a student.
     */
    public function linkGuardian(int $id, int $guardianId): JsonResponse
    {
        $student = $this->service->find($id);

        $this->authorize('update', $student);

        $student->guardians()->syncWithoutDetaching([$guardianId]);

        return $this->success(
            (new StudentResource($student->load('parents', 'guardians')))->resolve(),
            'Guardian linked.'
        );
    }

    /**
     * Unlink a guardian from a student.
     */
    public function unlinkGuardian(int $id, int $guardianId): JsonResponse
    {
        $student = $this->service->find($id);

        $this->authorize('update', $student);

        $student->guardians()->detach($guardianId);

        return $this->success(
            (new StudentResource($student->load('parents', 'guardians')))->resolve(),
            'Guardian unlinked.'
        );
    }
}
