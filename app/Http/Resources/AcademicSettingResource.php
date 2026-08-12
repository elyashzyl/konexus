<?php

namespace App\Http\Resources;

use App\Models\AcademicSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AcademicSetting */
class AcademicSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $value = match ($this->type) {
            'json' => json_decode((string) $this->value, true),
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $this->value,
            'decimal' => (float) $this->value,
            default => $this->value,
        };

        return [
            'id' => $this->id,
            'key' => $this->key,
            'group' => $this->group,
            'value' => $value,
            'type' => $this->type,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}