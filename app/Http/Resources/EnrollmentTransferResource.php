<?php

namespace App\Http\Resources;

use App\Enums\TransferType;
use App\Models\EnrollmentTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnrollmentTransfer */
class EnrollmentTransferResource extends JsonResource
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
            'transfer_type' => $this->transfer_type,
            'transfer_type_label' => TransferType::tryFrom($this->transfer_type)?->label() ?? ucfirst((string) $this->transfer_type),
            'from_campus_name' => $this->from_campus_name,
            'from_grade_level_name' => $this->from_grade_level_name,
            'from_section_name' => $this->from_section_name,
            'to_campus_name' => $this->to_campus_name,
            'to_grade_level_name' => $this->to_grade_level_name,
            'to_section_name' => $this->to_section_name,
            'destination' => $this->destination,
            'transfer_date' => $this->transfer_date?->toDateString(),
            'reason' => $this->reason,
            'remarks' => $this->remarks,
            'processed_by' => $this->whenLoaded('processedBy', fn () => $this->processedBy?->name ?? null),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}