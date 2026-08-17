<?php

namespace App\Http\Requests\Api;

use App\Enums\EnrollmentType;
use Illuminate\Validation\Rule;

/**
 * Validates the Part 1 walk-in enrollment application payload. Unlike the
 * online flow which is limited to continuing/returning/transferee applicants,
 * walk-in enrollments may use any configured enrollment type.
 */
class WalkInEnrollmentRequest extends PublicEnrollmentRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['status'] = ['required', 'string', Rule::in(EnrollmentType::values())];

        return $rules;
    }
}