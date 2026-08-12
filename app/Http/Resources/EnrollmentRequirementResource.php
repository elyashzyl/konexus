<?php

namespace App\Http\Resources;

use App\Models\EnrollmentRequirement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnrollmentRequirement */
class EnrollmentRequirementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_required' => $this->is_required,
            'type' => $this->type,
            'applicable_grade_levels' => $this->applicable_grade_levels ?: [],
            'applicable_enrollment_types' => $this->applicable_enrollment_types ?: [],
            'applicable_academic_year_id' => $this->applicable_academic_year_id,
            'applicable_campus_ids' => $this->applicable_campus_ids,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}