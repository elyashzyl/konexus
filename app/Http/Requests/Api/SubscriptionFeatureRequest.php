<?php

namespace App\Http\Requests\Api;

use App\Enums\Platform\SubscriptionFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionFeatureRequest extends FormRequest
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
        $codes = array_column(SubscriptionFeature::toOptions(), 'value');

        return [
            'feature_code' => ['required', 'string', Rule::in($codes)],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }
}