<?php

namespace App\Http\Resources;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Student */
class StudentResource extends JsonResource
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
            'student_number' => $this->student_number,
            'lrn' => $this->lrn,
            'school_student_id' => $this->school_student_id,
            'rfid_number' => $this->rfid_number,
            'qr_code' => $this->qr_code,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'extension_name' => $this->extension_name,
            'name' => $this->full_name,
            'nickname' => $this->nickname,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->toDateString(),
            'age' => $this->age,
            'place_of_birth' => $this->place_of_birth,
            'civil_status' => $this->civil_status,
            'nationality' => $this->nationality,
            'citizenship' => $this->citizenship,
            'religion' => $this->religion,
            'ethnicity' => $this->ethnicity,
            'mother_tongue' => $this->mother_tongue,
            'blood_type' => $this->blood_type,
            'profile_picture_path' => $this->profile_picture_path,
            'profile_picture_url' => $this->profile_picture_path ? url('storage/'.$this->profile_picture_path) : null,
            'status' => $this->status,
            'mobile_number' => $this->mobile_number,
            'telephone_number' => $this->telephone_number,
            'email' => $this->email,
            'current_address' => $this->current_address,
            'current_province' => $this->current_province,
            'current_city' => $this->current_city,
            'current_municipality' => $this->current_municipality,
            'current_barangay' => $this->current_barangay,
            'current_zip_code' => $this->current_zip_code,
            'permanent_address' => $this->permanent_address,
            'permanent_province' => $this->permanent_province,
            'permanent_city' => $this->permanent_city,
            'permanent_municipality' => $this->permanent_municipality,
            'permanent_barangay' => $this->permanent_barangay,
            'permanent_zip_code' => $this->permanent_zip_code,
            'height' => $this->height,
            'weight' => $this->weight,
            'medical_conditions' => $this->medical_conditions,
            'food_allergies' => $this->food_allergies,
            'medicine_allergies' => $this->medicine_allergies,
            'preferred_hospital' => $this->preferred_hospital,
            'medical_notes' => $this->medical_notes,
            'emergency_medical_notes' => $this->emergency_medical_notes,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'emergency_contact_mobile' => $this->emergency_contact_mobile,
            'emergency_contact_telephone' => $this->emergency_contact_telephone,
            'emergency_contact_address' => $this->emergency_contact_address,
            'parents' => ParentResource::collection($this->whenLoaded('parents')),
            'guardians' => GuardianResource::collection($this->whenLoaded('guardians')),
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
