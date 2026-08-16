<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ExposesCampusCatalog;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Room */
class RoomResource extends JsonResource
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
            'building_id' => $this->building_id,
            'building' => $this->whenLoaded('building', fn () => $this->building ? [
                'id' => $this->building->id,
                'name' => $this->building->name,
            ] : null),
            'room_type' => $this->room_type,
            'capacity' => $this->capacity,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
