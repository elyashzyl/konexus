<?php

namespace App\Services;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Support\CampusContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmployeeService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'position',
    ];

    protected array $sortable = [
        'id',
        'created_at',
        'updated_at',
        'employee_number',
        'last_name',
        'first_name',
        'employment_type',
        'date_hired',
        'status',
    ];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['department', 'campuses'];

    protected string $defaultSortBy = 'last_name';

    public function __construct(private readonly EmployeeRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Create a new employee record, generating an employee number when absent.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $data['employee_number'] = $data['employee_number']
            ?? $this->generateEmployeeNumber();

        /** @var Employee $employee */
        $employee = $this->repository()->create($data);

        $this->syncCampuses($employee, $data['campus_ids'] ?? null);

        return $employee->load($this->with);
    }

    /**
     * Update an existing employee record.
     *
     * @param  Employee  $model
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        /** @var Employee $employee */
        $employee = $this->repository()->update($model, $data);

        if (array_key_exists('campus_ids', $data)) {
            $this->syncCampuses($employee, $data['campus_ids']);
        }

        return $employee->load($this->with);
    }

    /**
     * Restrict employee listings to the active campus when one is selected.
     *
     * @param  Builder<Employee>  $query
     */
    protected function applyPeopleCampusScope(Builder $query): void
    {
        $campusId = CampusContext::id();

        if ($campusId === null) {
            return;
        }

        $query->whereHas('campuses', fn (Builder $q) => $q->whereKey($campusId));
    }

    /**
     * Sync the campuses an employee belongs to, defaulting to the active
     * campus when the payload does not specify any.
     *
     * @param  array<int, int>|int|string|null  $campusIds
     */
    private function syncCampuses(Employee $employee, array|int|string|null $campusIds): void
    {
        $ids = $this->normalizeCampusIds($campusIds);

        if ($ids === null) {
            $activeCampusId = CampusContext::id();

            if ($activeCampusId !== null) {
                $ids = [$activeCampusId];
            }
        }

        if ($ids !== null) {
            $employee->campuses()->sync($ids);
        }
    }

    /**
     * Normalize a campus_ids payload into a list of integer ids, or null when
     * the payload is absent or empty.
     *
     * @param  array<int, int>|int|string|null  $campusIds
     * @return list<int>|null
     */
    private function normalizeCampusIds(array|int|string|null $campusIds): ?array
    {
        if (is_array($campusIds)) {
            $ids = array_values(array_map('intval', $campusIds));
        } elseif (is_numeric($campusIds)) {
            $ids = [(int) $campusIds];
        } else {
            return null;
        }

        return $ids === [] ? null : $ids;
    }

    /**
     * Generate a unique, human-friendly employee number.
     */
    public function generateEmployeeNumber(): string
    {
        do {
            $number = 'EMP-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while ($this->repo->findByEmployeeNumber($number) !== null);

        return $number;
    }
}
