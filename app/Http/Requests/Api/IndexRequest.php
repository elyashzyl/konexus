<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared query validation for all list endpoints.
 *
 * Supports free-text search, sorting, pagination and an arbitrary `filter`
 * array of equality constraints (e.g. `filter[grade_level_id]=1`).
 */
class IndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort_dir' => ['sometimes', 'nullable', 'string', 'in:asc,desc'],
            'filter' => ['sometimes', 'array'],
            'filter.*' => ['nullable'],
            'trashed' => ['sometimes', 'in:true,false,1,0'],
        ];
    }

    /**
     * The free-text search term, normalized.
     */
    public function search(): ?string
    {
        $search = trim((string) $this->string('search'));

        return $search === '' ? null : $search;
    }

    /**
     * The number of records per page.
     */
    public function perPage(): int
    {
        return $this->integer('per_page', 15);
    }

    /**
     * The column used for ordering.
     */
    public function sortBy(): ?string
    {
        return $this->filled('sort_by') ? (string) $this->string('sort_by') : null;
    }

    /**
     * The ordering direction.
     */
    public function sortDir(): string
    {
        return strtolower((string) $this->input('sort_dir', 'asc'));
    }

    /**
     * Whether only soft-deleted records should be returned.
     */
    public function trashed(): bool
    {
        return $this->boolean('trashed');
    }

    /**
     * The equality filters to apply to the query.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $filters = $this->input('filter', []);

        if (! is_array($filters)) {
            $filters = [];
        }

        if ($this->has('is_active')) {
            $filters['is_active'] = $this->boolean('is_active');
        }

        return $filters;
    }
}
