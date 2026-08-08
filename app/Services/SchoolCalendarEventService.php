<?php

namespace App\Services;

use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\SchoolCalendarEventRepositoryInterface;

class SchoolCalendarEventService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['title', 'description', 'location'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'title', 'description', 'location'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['academicYear'];

    public function __construct(private readonly SchoolCalendarEventRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }
}
