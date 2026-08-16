<?php

namespace App\Http\Requests\Api;

use App\Enums\SchoolCalendarCategory;
use App\Http\Requests\Api\Concerns\ValidatesCatalogCampus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolCalendarEventRequest extends FormRequest
{
    use ValidatesCatalogCampus;

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
            ...$this->catalogCampusRules(),
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_column(SchoolCalendarCategory::toSeedData(), 'value'))],
            'description' => ['nullable', 'string', 'max:5000'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'all_day' => ['sometimes', 'boolean'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
