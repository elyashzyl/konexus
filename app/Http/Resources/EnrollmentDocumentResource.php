<?php

namespace App\Http\Resources;

use App\Models\EnrollmentDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnrollmentDocument */
class EnrollmentDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $size = $this->file_size;

        return [
            'id' => $this->id,
            'enrollment_id' => $this->enrollment_id,
            'requirement_item_id' => $this->requirement_item_id,
            'name' => $this->name,
            'mime_type' => $this->mime_type,
            'file_size' => $size,
            'file_size_human' => $size !== null ? $this->formatSize($size) : null,
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn () => $this->uploadedBy?->name ?? null),
            'requirement' => $this->whenLoaded('requirementItem', fn () => $this->requirementItem?->requirement?->name ?? null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Format a byte count into a human friendly string.
     */
    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i > 0 ? 1 : 0).' '.$units[$i];
    }
}