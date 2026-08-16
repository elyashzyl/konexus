<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ExposesCampusCatalog;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Department */
class DepartmentResource extends JsonResource
{
    use ExposesCampusCatalog;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            ...$this->campusCatalogAttributes(),
            'name' => $this->name,
            'code' => $this->code,
            'head_user_id' => $this->head_user_id,
            'head' => $this->whenLoaded('head', fn () => $this->head ? [
                'id' => $this->head->id,
                'name' => $this->head->name,
            ] : null),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
