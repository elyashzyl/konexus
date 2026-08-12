<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassScheduleRequest extends FormRequest
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
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'academic_term_id' => ['nullable', 'integer', 'exists:academic_terms,id'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'subject_offering_id' => ['nullable', 'integer', 'exists:subject_offerings,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'day' => ['required', 'string', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'conflict_override' => ['sometimes', 'boolean'],
            'conflict_reason' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', 'in:active,archived'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Data for the request.
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated();

        if (isset($data['start_time'])) {
            $data['start_time'] = $this->normalizeTime($data['start_time']);
        }

        if (isset($data['end_time'])) {
            $data['end_time'] = $this->normalizeTime($data['end_time']);
        }

        return is_string($key) ? data_get($data, $key, $default) : $data;
    }

    /**
     * Normalize to a parseable time string.
     */
    protected function normalizeTime(string $value): string
    {
        return substr($value, 0, 5);
    }
}