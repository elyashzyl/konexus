<?php

namespace App\Http\Resources;

use App\Models\Tuition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tuition */
class TuitionResource extends JsonResource
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
            'student_id' => $this->student_id,
            'student' => $this->student ? new StudentSearchResource($this->student) : null,
            'academic_year_id' => $this->academic_year_id,
            'academic_year' => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
                'code' => $this->academicYear->code,
            ] : null,
            'reference_number' => $this->reference_number,
            'tuition_fee' => (float) $this->tuition_fee,
            'misc_fee' => (float) $this->misc_fee,
            'other_fees' => (float) $this->other_fees,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'amount_paid' => (float) $this->amount_paid,
            'balance' => (float) $this->balance,
            'status' => $this->status,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}