<?php

namespace App\Services;

use App\Models\Staff;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class StaffService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'support_area',
    ];

    protected array $searchableRelations = [
        'employee' => ['first_name', 'middle_name', 'last_name', 'employee_number', 'email'],
    ];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'support_area'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['employee', 'employee.department'];

    public function __construct(
        private readonly StaffRepositoryInterface $repo,
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
     * Create a staff profile, resolving (or creating) the backing employee.
     *
     * @param  array<string, mixed>  $data
     * @return Staff
     */
    public function create(array $data): Model
    {
        $data['employee_id'] = $this->resolveEmployeeId($data);

        /** @var Staff $staff */
        $staff = $this->repository()->create($data);

        return $staff->load($this->with);
    }

    /**
     * Update a staff profile.
     *
     * @param  Staff  $model
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        /** @var Staff $staff */
        $staff = $this->repository()->update($model, $data);

        if (isset($data['employee_id']) && (int) $data['employee_id'] > 0 && $model->employee_id !== (int) $data['employee_id']) {
            $staff->forceFill(['employee_id' => (int) $data['employee_id']])->save();
        }

        return $staff->load($this->with);
    }

    /**
     * Resolve an existing employee or create a new non-teaching employee.
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
            'employment_type' => 'staff',
            'department_id' => isset($data['department_id']) && (int) $data['department_id'] > 0 ? (int) $data['department_id'] : null,
            'position' => $data['position'] ?? 'Staff',
            'is_active' => true,
        ]);

        return $employee->id;
    }
}
