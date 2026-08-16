<?php

namespace App\Http\Requests\Api;

use App\Enums\Platform\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantRequest extends FormRequest
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
            'school_profile_id' => [$this->route('id') ? 'nullable' : 'required', 'integer', 'exists:school_profiles,id'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('tenants', 'code')->withoutTrashed()->ignore($this->route('id'))],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(TenantStatus::class)],
            'settings' => ['sometimes', 'array'],
        ];
    }
}