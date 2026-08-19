<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the Parts 4-8 online enrollment details payload (siblings,
 * tuition plan, medical history, Chinese class details, and agreements).
 */
class PublicEnrollmentDetailsRequest extends FormRequest
{
    public const FAMILY_HISTORY_CONDITIONS = [
        'asthma',
        'diabetes',
        'hypertension',
        'heart-disease',
        'cancer',
        'tuberculosis',
        'epilepsy-seizures',
        'kidney-disease',
        'mental-health',
    ];

    public const EMERGENCY_HOSPITALS = [
        'notre-dame-de-chartres',
        'baguio-general',
        'slu-sacred-heart',
        'pines',
        'nearest-hospital',
    ];

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
        $rules = [
            'siblings' => ['nullable', 'array', 'max:20'],
            'siblings.*.last_name' => ['required', 'string', 'max:255'],
            'siblings.*.first_name' => ['required', 'string', 'max:255'],
            'siblings.*.middle_name' => ['nullable', 'string', 'max:255'],
            'siblings.*.extension_name' => ['nullable', 'string', 'max:20'],
            'siblings.*.grade_level' => ['required', 'string', 'max:100'],

            'tuition_plan' => ['nullable', 'string', 'max:255'],

            'medical_history' => ['nullable', 'array'],
            'medical_history.allergies' => ['nullable', 'string', 'max:1000'],
            'medical_history.family_history' => ['nullable', 'array'],
            'medical_history.family_history.*' => ['required', Rule::in(self::FAMILY_HISTORY_CONDITIONS)],
            'medical_history.family_history_others' => ['nullable', 'string', 'max:1000'],
            'medical_history.emergency_hospital' => ['nullable', Rule::in(self::EMERGENCY_HOSPITALS)],

            'chinese_details' => ['nullable', 'array'],
            'chinese_details.grade_level' => ['nullable', 'string', 'max:100'],
            'chinese_details.english_name' => ['nullable', 'string', 'max:255'],
            'chinese_details.chinese_name' => ['nullable', 'string', 'max:255'],
            'chinese_details.father_chinese_name' => ['nullable', 'string', 'max:255'],
            'chinese_details.mother_chinese_name' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->has('agreement')) {
            $rules['agreement'] = ['nullable', 'array'];
            $rules['agreement.photo_consent'] = ['required', 'boolean'];
            $rules['agreement.online_photo_sharing'] = ['nullable', 'boolean'];
            $rules['agreement.registration_consent'] = ['required', 'accepted'];
            $rules['agreement.credentialing_consent'] = ['required', 'accepted'];
            $rules['agreement.rules_consent'] = ['required', 'accepted'];
            $rules['agreement.mother_confirmation'] = ['nullable', 'boolean'];
            $rules['agreement.father_confirmation'] = ['nullable', 'boolean'];
            $rules['agreement.date_of_registration'] = ['nullable', 'date'];
            $rules['agreement.initial_payment'] = ['nullable', 'numeric', 'min:0'];
            $rules['agreement.initial_payment_status'] = ['nullable', 'string', Rule::in(['unpaid', 'pending', 'paid', 'waived'])];
        }

        return $rules;
    }
}