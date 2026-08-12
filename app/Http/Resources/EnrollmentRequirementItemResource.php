<?php

namespace App\Http\Resources;

use App\Enums\RequirementItemStatus;
use App\Models\EnrollmentRequirementItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnrollmentRequirementItem */
class EnrollmentRequirementItemResource extends JsonResource
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
            'enrollment_id' => $this->enrollment_id,
            'status' => $this->status,
            'status_label' => RequirementItemStatus::tryFrom($this->status)?->label() ?? ucfirst((string) $this->status),
            'is_satisfied' => in_array($this->status, RequirementItemStatus::satisfiedStatuses(), true),
            'remarks' => $this->remarks,
            'requirement' => $this->whenLoaded('requirement', fn () => $this->requirement ? [
                'id' => $this->requirement->id,
                'name' => $this->requirement->name,
                'code' => $this->requirement->code,
                'is_required' => $this->requirement->is_required,
            ] : null),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->values()->map(fn ($doc) => new EnrollmentDocumentResource($doc))),
            'verified_by' => $this->whenLoaded('verifiedBy', fn () => $this->verifiedBy?->name ?? null),
            'verified_at' => $this->verified_at?->toISOString(),
            'rejected_by' => $this->whenLoaded('rejectedBy', fn () => $this->rejectedBy?->name ?? null),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}