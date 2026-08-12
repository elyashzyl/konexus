<?php

namespace App\Repositories\Contracts;

use App\Models\Student;

interface StudentRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a student by its student number.
     */
    public function findByStudentNumber(string $studentNumber): ?Student;

    /**
     * Find a student by its learner reference number (LRN).
     */
    public function findByLrn(string $lrn): ?Student;
}
