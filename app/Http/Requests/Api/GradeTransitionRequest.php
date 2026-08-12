<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GradeTransitionRequest extends FormRequest
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
            'status' => ['required', 'string', 'in:draft,in-progress,submitted,for-review,approved,published,returned'],
            'reason' => ['nullable', 'string', 'max:500'],
            'actor_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}