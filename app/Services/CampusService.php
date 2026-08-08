<?php

namespace App\Services;

use App\Repositories\Contracts\CampusRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;

class CampusService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name', 'code', 'address'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'name', 'code', 'address'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = [];

    public function __construct(private readonly CampusRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }
}
