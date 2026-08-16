<?php

namespace App\Services;

use App\Repositories\Contracts\GradeLevelRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;

class GradeLevelService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name', 'code', 'short_name'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'name', 'code', 'short_name'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['campus', 'schoolProfile'];

    public function __construct(private readonly GradeLevelRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }
}
