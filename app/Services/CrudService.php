<?php

namespace App\Services;

use App\Http\Requests\Api\IndexRequest;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base service providing the default read/write operations shared by every
 * Phase 2 module. Concrete services configure their searchable/sortable
 * columns and may override create/update to enforce business rules.
 *
 * @template TModel of Model
 */
abstract class CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = [];

    /**
     * Relation columns included in free-text search (relation => columns).
     *
     * @var array<string, list<string>>
     */
    protected array $searchableRelations = [];

    /**
     * Columns that are allowed to be sorted on.
     *
     * @var list<string>
     */
    protected array $sortable = ['id', 'created_at', 'updated_at'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = [];

    protected string $defaultSortBy = 'id';

    protected string $defaultSortDir = 'asc';

    /**
     * The underlying repository for this service.
     *
     * @return RepositoryInterface<TModel>
     */
    abstract protected function repository(): RepositoryInterface;

    /**
     * Return a paginated, searchable, sortable list of records.
     *
     * @return LengthAwarePaginator<int, TModel>
     */
    public function index(IndexRequest $request): LengthAwarePaginator
    {
        /** @var Builder<TModel> $query */
        $query = $this->repository()->query();

        if ($this->with !== []) {
            $query->with($this->with);
        }

        if ($request->trashed()) {
            $query->onlyTrashed();
        }

        foreach ($this->filters($request) as $key => $value) {
            if ($value === null || $value === '' || $value === 'all') {
                continue;
            }

            $query->where($key, $this->normalizeFilterValue($value));
        }

        if ($search = $request->search()) {
            $query->where(function (Builder $query) use ($search): void {
                foreach ($this->searchable as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }

                foreach ($this->searchableRelations as $relation => $columns) {
                    $query->orWhereHas($relation, function (Builder $query) use ($columns, $search): void {
                        foreach ($columns as $column) {
                            $query->orWhere($column, 'like', "%{$search}%");
                        }
                    });
                }
            });
        }

        $sortBy = $request->sortBy();
        $sortBy = $sortBy !== null && in_array($sortBy, $this->sortable, true) ? $sortBy : $this->defaultSortBy;
        $query->orderBy($sortBy, $request->sortDir() === 'desc' ? 'desc' : 'asc');

        return $query->paginate($request->perPage());
    }

    /**
     * Return all matching records ordered by id (used for dropdowns).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, TModel>
     */
    public function all(array $filters = []): Collection
    {
        /** @var Builder<TModel> $query */
        $query = $this->repository()->query();

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '' || $value === 'all') {
                continue;
            }

            $query->where($key, $this->normalizeFilterValue($value));
        }

        return $query->orderBy($this->defaultSortBy, 'asc')->get();
    }

    /**
     * Return all matching records applying the same search/filter/sort rules
     * as index but without pagination (used for CSV export).
     *
     * @return Collection<int, TModel>
     */
    public function export(IndexRequest $request): Collection
    {
        /** @var Builder<TModel> $query */
        $query = $this->repository()->query();

        if ($this->with !== []) {
            $query->with($this->with);
        }

        if ($request->trashed()) {
            $query->onlyTrashed();
        }

        foreach ($this->filters($request) as $key => $value) {
            if ($value === null || $value === '' || $value === 'all') {
                continue;
            }

            $query->where($key, $this->normalizeFilterValue($value));
        }

        if ($search = $request->search()) {
            $query->where(function (Builder $query) use ($search): void {
                foreach ($this->searchable as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }

                foreach ($this->searchableRelations as $relation => $columns) {
                    $query->orWhereHas($relation, function (Builder $query) use ($columns, $search): void {
                        foreach ($columns as $column) {
                            $query->orWhere($column, 'like', "%{$search}%");
                        }
                    });
                }
            });
        }

        $sortBy = $request->sortBy();
        $sortBy = $sortBy !== null && in_array($sortBy, $this->sortable, true) ? $sortBy : $this->defaultSortBy;
        $query->orderBy($sortBy, $request->sortDir() === 'desc' ? 'desc' : 'asc');

        return $query->get();
    }

    /**
     * Find a single record by primary key.
     *
     * @return TModel
     */
    public function find(int|string $id): Model
    {
        $query = $this->repository()->query();

        if ($this->with !== []) {
            $query->with($this->with);
        }

        return $query->findOrFail($id);
    }

    /**
     * Find a soft-deleted record by primary key.
     *
     * @return TModel
     */
    public function trashed(int|string $id): Model
    {
        return $this->repository()->query()->onlyTrashed()->findOrFail($id);
    }

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(array $data): Model
    {
        return $this->repository()->create($data);
    }

    /**
     * Update an existing record.
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function update(Model $model, array $data): Model
    {
        return $this->repository()->update($model, $data);
    }

    /**
     * Soft-delete a record.
     *
     * @param  TModel  $model
     */
    public function delete(Model $model): bool
    {
        return $this->repository()->delete($model);
    }

    /**
     * Restore a soft-deleted record.
     *
     * @param  TModel  $model
     * @return TModel
     */
    public function restore(Model $model): Model
    {
        $model->restore();

        return $model;
    }

    /**
     * Permanently delete a soft-deleted record.
     *
     * @param  TModel  $model
     */
    public function forceDelete(Model $model): bool
    {
        return (bool) $model->forceDelete();
    }

    /**
     * The equality filters extracted from the request.
     *
     * @return array<string, mixed>
     */
    protected function filters(IndexRequest $request): array
    {
        return $request->filters();
    }

    /**
     * Normalize boolean-like filter values so SQLite comparisons work.
     */
    protected function normalizeFilterValue(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        return $value;
    }
}
