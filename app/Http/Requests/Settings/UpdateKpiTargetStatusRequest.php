<?php

namespace App\Http\Requests\Settings;

use App\Models\KpiTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKpiTargetStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    KpiTarget::STATUS_DRAFT,
                    KpiTarget::STATUS_ACTIVE,
                    KpiTarget::STATUS_LOCKED,
                ]),
            ],
            'history_notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status target wajib dipilih.',
            'status.in' => 'Status target harus draft, active, atau locked.',
        ];
    }
}
