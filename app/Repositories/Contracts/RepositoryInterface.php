<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Contract implemented by every repository in the application.
 *
 * @template TModel of Model
 */
interface RepositoryInterface
{
    /**
     * Return a new query builder instance.
     *
     * @return Builder<TModel>
     */
    public function query(): Builder;

    /**
     * Retrieve all records.
     *
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Retrieve all records as a paginated result.
     *
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator;

    /**
     * Find a record by its primary key.
     *
     * @return TModel|null
     */
    public function find(int|string $id, array $columns = ['*']): ?Model;

    /**
     * Find a record by its primary key or fail.
     *
     * @return TModel
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    /**
     * Find the first record matching the given attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel|null
     */
    public function findBy(array $attributes, array $columns = ['*']): ?Model;

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * Update an existing record.
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model;

    /**
     * Update a record by primary key.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function updateById(int|string $id, array $attributes): Model;

    /**
     * Create a record or update it when a matching one exists.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     * @return TModel
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * Delete a record.
     *
     * @param  TModel  $model
     */
    public function delete(Model $model): bool;

    /**
     * Delete a record by primary key.
     */
    public function deleteById(int|string $id): bool;

    /**
     * Count the number of records.
     */
    public function count(): int;
}
