<?php

namespace App\Services;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
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
    protected array $with = ['department'];

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

        return $this->repository()->create($data);
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
