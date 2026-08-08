<?php

namespace App\Services;

use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\SystemSettingRepositoryInterface;

class SystemSettingService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['group', 'key', 'value'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'group', 'key', 'value'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = [];

    public function __construct(private readonly SystemSettingRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }
}
