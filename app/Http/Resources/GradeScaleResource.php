<?php

namespace App\Http\Resources;

use App\Models\GradeScale;
use App\Models\GradeScaleEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GradeScale */
class GradeScaleResource extends JsonResource
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
            'min_grade' => (float) $this->min_grade,
            'max_grade' => (float) $this->max_grade,
            'minimum_passing_grade' => (float) $this->minimum_passing_grade,
            'decimal_precision' => $this->decimal_precision,
            'rounding' => $this->rounding,
            'academic_year_id' => $this->academic_year_id,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'entries' => $this->whenLoaded('entries', fn () => $this->entries
                ->map(fn (GradeScaleEntry $entry) => [
                    'id' => $entry->id,
                    'label' => $entry->label,
                    'remarks' => $entry->remarks,
                    'min_grade' => (float) $entry->min_grade,
                    'max_grade' => (float) $entry->max_grade,
                    'is_passing' => $entry->is_passing,
                    'sort_order' => $entry->sort_order,
                    'is_active' => $entry->is_active,
                ])
                ->values()),
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}