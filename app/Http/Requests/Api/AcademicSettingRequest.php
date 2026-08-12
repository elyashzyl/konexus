<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicSettingRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:120', Rule::unique('academic_settings', 'key')->withoutTrashed()->ignore($ignore)],
            'group' => ['required', 'string', 'max:80'],
            'value' => ['sometimes', 'nullable'],
            'type' => ['required', 'string', 'in:string,boolean,integer,decimal,json'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}