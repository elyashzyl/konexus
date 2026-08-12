<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;

interface EmployeeRepositoryInterface extends RepositoryInterface
{
    /**
     * Find an employee by its employee number.
     */
    public function findByEmployeeNumber(string $employeeNumber): ?Employee;
}
