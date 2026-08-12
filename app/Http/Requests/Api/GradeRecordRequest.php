<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeRecordRequest extends FormRequest
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
        $ignore = $this->route('id');

        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'subject_offering_id' => ['required', 'integer', 'exists:subject_offerings,id', Rule::unique('grade_records', 'subject_offering_id')->where(fn ($query) => $query->where('student_id', $this->input('student_id')))->withoutTrashed()->ignore($ignore)],
            'raw_grade' => ['required', 'numeric'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', 'in:draft,in-progress,submitted,for-review,approved,published,returned,corrected'],
        ];
    }
}