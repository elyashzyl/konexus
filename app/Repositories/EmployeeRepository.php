<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return Employee::class;
    }

    /**
     * Find an employee by its employee number.
     */
    public function findByEmployeeNumber(string $employeeNumber): ?Employee
    {
        return $this->query()->where('employee_number', $employeeNumber)->first();
    }
}
