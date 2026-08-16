<?php

namespace App\Http\Resources\Concerns;

/**
 * Shared campus and school profile fields for foundation catalog resources.
 */
trait ExposesCampusCatalog
{
    /**
     * @return array<string, mixed>
     */
    protected function campusCatalogAttributes(): array
    {
        return [
            'campus_id' => $this->campus_id,
            'campus' => $this->whenLoaded('campus', fn () => $this->campus ? [
                'id' => $this->campus->id,
                'name' => $this->campus->name,
            ] : null),
            'school_profile_id' => $this->school_profile_id,
            'school_profile' => $this->whenLoaded('schoolProfile', fn () => $this->schoolProfile ? [
                'id' => $this->schoolProfile->id,
                'name' => $this->schoolProfile->name,
                'short_name' => $this->schoolProfile->short_name,
            ] : null),
        ];
    }
}
