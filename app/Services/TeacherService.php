<?php

namespace App\Services;

use App\Models\Teacher;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\TeacherRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class TeacherService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'prc_number',
        'major',
        'minor',
        'specialization',
    ];

    protected array $searchableRelations = [
        'employee' => ['first_name', 'middle_name', 'last_name', 'employee_number', 'email'],
    ];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'prc_number', 'major', 'minor'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['employee', 'advisoryClass', 'department'];

    public function __construct(
        private readonly TeacherRepositoryInterface $repo,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly EmployeeService $employeeService,
    ) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Create a teacher profile, resolving (or creating) the backing employee.
     *
     * @param  array<string, mixed>  $data
     * @return Teacher
     */
    public function create(array $data): Model
    {
        $data['employee_id'] = $this->resolveEmployeeId($data);

        /** @var Teacher $teacher */
        $teacher = $this->repository()->create($data);

        return $teacher->load($this->with);
    }

    /**
     * Update a teacher profile.
     *
     * @param  Teacher  $model
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        /** @var Teacher $teacher */
        $teacher = $this->repository()->update($model, $data);

        if (isset($data['employee_id']) && (int) $data['employee_id'] > 0 && $model->employee_id !== (int) $data['employee_id']) {
            $teacher->forceFill(['employee_id' => (int) $data['employee_id']])->save();
        }

        return $teacher->load($this->with);
    }

    /**
     * Resolve an existing employee or create a new teaching employee.
     *
     * @param  array<string, mixed>  $data
     */
    protected function resolveEmployeeId(array $data): int
    {
        if (isset($data['employee_id']) && (int) $data['employee_id'] > 0) {
            return (int) $data['employee_id'];
        }

        if (! empty($data['employee_number'])) {
            $existing = $this->employeeRepository->findByEmployeeNumber($data['employee_number']);

            if ($existing !== null) {
                return $existing->id;
            }
        }

        $employee = $this->employeeService->create([
            'employee_number' => $data['employee_number'] ?? null,
            'first_name' => $data['first_name'] ?? 'Unnamed',
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'] ?? 'Employee',
            'gender' => $data['gender'] ?? null,
            'mobile_number' => $data['mobile_number'] ?? null,
            'telephone_number' => $data['telephone_number'] ?? null,
            'email' => $data['email'] ?? null,
            'employment_type' => 'teaching',
            'department_id' => isset($data['department_id']) && (int) $data['department_id'] > 0 ? (int) $data['department_id'] : null,
            'position' => $data['position'] ?? 'Teacher',
            'is_active' => true,
        ]);

        return $employee->id;
    }
}
