<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CampusRequest extends FormRequest
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
            'school_profile_id' => ['nullable', 'integer', 'exists:school_profiles,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('campuses', 'code')->withoutTrashed()->ignore($this->route('id'))],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Keep a school administrator from attaching a campus to another school.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $schoolProfileId = $this->user()?->school_profile_id;

            if ($schoolProfileId !== null
                && $this->filled('school_profile_id')
                && (int) $this->input('school_profile_id') !== (int) $schoolProfileId) {
                $validator->errors()->add('school_profile_id', 'A campus must belong to your school profile.');
            }
        }];
    }
}
