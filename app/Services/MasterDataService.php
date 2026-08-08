<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\MasterData;
use App\Repositories\Contracts\MasterDataRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class MasterDataService extends CrudService
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

    public function __construct(private readonly MasterDataRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * System-defined master data entries cannot be removed.
     *
     * @param  MasterData  $model
     */
    public function delete(Model $model): bool
    {
        if ($model->is_system) {
            throw ApiException::unprocessable('System-defined master data entries cannot be deleted.');
        }

        return parent::delete($model);
    }

    /**
     * System-defined master data entries cannot be permanently removed.
     *
     * @param  MasterData  $model
     */
    public function forceDelete(Model $model): bool
    {
        if ($model->is_system) {
            throw ApiException::unprocessable('System-defined master data entries cannot be permanently deleted.');
        }

        return parent::forceDelete($model);
    }
}
