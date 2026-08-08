<?php

namespace App\Services;

use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;

class DepartmentService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name', 'code', 'description'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'name', 'code', 'description'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = [];

    public function __construct(private readonly DepartmentRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }
}
