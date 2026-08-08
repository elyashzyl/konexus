<?php

namespace App\Services;

use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\SectionRepositoryInterface;

class SectionService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name', 'code'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'name', 'code'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['gradeLevel', 'adviser', 'room'];

    public function __construct(private readonly SectionRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }
}
