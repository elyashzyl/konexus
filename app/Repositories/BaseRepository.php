<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base repository providing the default implementation of the common
 * data-access operations shared by every repository in the application.
 *
 * @template TModel of Model
 *
 * @implements RepositoryInterface<TModel>
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The Eloquent model handled by this repository.
     *
     * @var class-string<TModel>
     */
    protected string $model;

    public function __construct()
    {
        $this->model = $this->model();
    }

    /**
     * Resolve the model class name.
     *
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    /**
     * Return a new query builder instance.
     *
     * @return Builder<TModel>
     */
    public function query(): Builder
    {
        return $this->model::query();
    }

    /**
     * Retrieve all records.
     *
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    /**
     * Retrieve all records as a paginated result.
     *
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Find a record by its primary key.
     *
     * @return TModel|null
     */
    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->query()->find($id, $columns);
    }

    /**
     * Find a record by its primary key or fail.
     *
     * @return TModel
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        return $this->query()->findOrFail($id, $columns);
    }

    /**
     * Find the first record matching the given attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel|null
     */
    public function findBy(array $attributes, array $columns = ['*']): ?Model
    {
        return $this->query()->where($attributes)->first($columns);
    }

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    /**
     * Update an existing record.
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes)->save();

        return $model;
    }

    /**
     * Update a record by primary key.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function updateById(int|string $id, array $attributes): Model
    {
        return $this->update($this->findOrFail($id), $attributes);
    }

    /**
     * Create a record or update it when a matching one exists.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     * @return TModel
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->query()->updateOrCreate($attributes, $values);
    }

    /**
     * Delete a record.
     *
     * @param  TModel  $model
     */
    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    /**
     * Delete a record by primary key.
     */
    public function deleteById(int|string $id): bool
    {
        return (bool) $this->query()->whereKey($id)->delete();
    }

    /**
     * Count the number of records.
     */
    public function count(): int
    {
        return $this->query()->count();
    }
}
