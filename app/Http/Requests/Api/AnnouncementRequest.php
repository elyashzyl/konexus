<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\Concerns\ValidatesCatalogCampus;
use Illuminate\Foundation\Http\FormRequest;

class AnnouncementRequest extends FormRequest
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
            'content' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['sometimes', 'string', 'max:20'],
            'target_audience' => ['nullable', 'string', 'max:50'],
            'audience' => ['nullable', 'array'],
            'audience.roles' => ['nullable', 'array'],
            'audience.roles.*' => ['string', 'max:60'],
            'audience.grade_level_ids' => ['nullable', 'array'],
            'audience.grade_level_ids.*' => ['integer'],
            'audience.section_ids' => ['nullable', 'array'],
            'audience.section_ids.*' => ['integer'],
            'audience.campus_ids' => ['nullable', 'array'],
            'audience.campus_ids.*' => ['integer'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'published' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:draft,scheduled,published,archived'],
            'scheduled_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
