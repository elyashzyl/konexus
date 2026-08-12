<?php

namespace App\Services;

use App\Repositories\Contracts\ParentRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;

class ParentService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'mobile_number',
    ];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'last_name', 'first_name', 'relationship'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['students'];

    protected string $defaultSortBy = 'last_name';

    public function __construct(private readonly ParentRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }
}
