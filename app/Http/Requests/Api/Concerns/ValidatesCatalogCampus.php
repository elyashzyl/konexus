<?php

namespace App\Http\Requests\Api\Concerns;

trait ValidatesCatalogCampus
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function catalogCampusRules(): array
    {
        return [
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
        ];
    }
}
