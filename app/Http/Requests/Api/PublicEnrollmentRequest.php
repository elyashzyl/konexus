<?php

namespace App\Http\Requests\Api;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\GradeLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates the Part 1 online enrollment application payload.
 */
class PublicEnrollmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'school_profile_id' => ['required', 'integer', Rule::exists('school_profiles', 'id')->where('is_active', true)],
            'campus_id' => ['required', 'integer', Rule::exists('campuses', 'id')->where('is_active', true)],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'department' => ['required', 'string', Rule::in(['pre-school', 'grade-school', 'junior-high', 'senior-high'])],
            'strand' => ['nullable', 'string', 'max:100', Rule::requiredIf($this->input('department') === 'senior-high')],
            'status' => ['required', 'string', Rule::in(['new', 'new-student', 'continuing', 'returning', 'transferee'])],
            'incoming_level' => ['required', 'string', 'max:50'],
            'track' => ['required', 'string', Rule::in(['english', 'chinese', 'integrated'])],
            'email' => ['required', 'email', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 ()-]{7,18}$/'],
        ];
    }

    /**
     * Ensure the selected campus, school year, and incoming level belong together.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $schoolId = (int) $this->input('school_profile_id');
            $campusId = (int) $this->input('campus_id');
            $academicYearId = (int) $this->input('academic_year_id');
            $incomingLevel = (string) $this->input('incoming_level');

            $campus = Campus::query()->find($campusId);
            if ($campus !== null && (int) $campus->school_profile_id !== $schoolId) {
                $validator->errors()->add('campus_id', 'The selected campus does not belong to the chosen school.');
            }

            $academicYear = AcademicYear::query()->find($academicYearId);
            if ($academicYear !== null && (int) $academicYear->school_profile_id !== $schoolId) {
                $validator->errors()->add('academic_year_id', 'The selected school year does not belong to the chosen school.');
            }

            $gradeLevelExists = GradeLevel::query()
                ->where('school_profile_id', $schoolId)
                ->where(function ($query) use ($campusId): void {
                    $query->where('campus_id', $campusId)
                        ->orWhereNull('campus_id');
                })
                ->where('name', $incomingLevel)
                ->where('is_active', true)
                ->exists();

            if (! $gradeLevelExists) {
                $validator->errors()->add('incoming_level', 'The selected incoming level is not offered at this campus.');
            }
        });
    }
}
