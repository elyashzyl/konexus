<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemSettingRequest extends FormRequest
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
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:150', Rule::unique('system_settings', 'key')->where('group', $this->input('group'))->withoutTrashed()->ignore($this->route('id'))],
            'value' => ['nullable', 'string'],
            'type' => ['required', 'string', 'max:50'],
            'is_public' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
