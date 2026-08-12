<?php

namespace App\Repositories;

use App\Models\Student;
use App\Repositories\Contracts\StudentRepositoryInterface;

class StudentRepository extends BaseRepository implements StudentRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return Student::class;
    }

    /**
     * Find a student by its student number.
     */
    public function findByStudentNumber(string $studentNumber): ?Student
    {
        return $this->query()->where('student_number', $studentNumber)->first();
    }

    /**
     * Find a student by its learner reference number (LRN).
     */
    public function findByLrn(string $lrn): ?Student
    {
        return $this->query()->where('lrn', $lrn)->first();
    }
}
