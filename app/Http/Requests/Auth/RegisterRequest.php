<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
     * Registration is school-based: the school profile is created first and the
     * registering administrator is anchored to it.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:80'],
            'school_id' => ['nullable', 'string', 'max:80'],
            'region' => ['nullable', 'string', 'max:120'],
            'division' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_number' => ['nullable', 'string', 'max:60'],
            'school_email' => ['nullable', 'string', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:8', 'max:255'],
        ];
    }
}