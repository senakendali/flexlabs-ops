<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKpiTargetRequest extends FormRequest
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
        $requiredForPut = $this->isMethod('patch')
            ? 'sometimes'
            : 'required';

        return [
            'kpi_definition_id' => [
                $requiredForPut,
                'integer',
                Rule::exists('kpi_definitions', 'id')
                    ->whereNull('deleted_at')
                    ->where('is_active', true),
            ],
            'period_month' => [
                $requiredForPut,
                'date',
            ],
            'target_value' => [
                $requiredForPut,
                'numeric',
                'min:0',
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'history_notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Return only fields accepted by TargetService::update().
     *
     * @return array<string, mixed>
     */
    public function targetAttributes(): array
    {
        return collect($this->validated())
            ->only([
                'kpi_definition_id',
                'period_month',
                'target_value',
                'notes',
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kpi_definition_id.required' => 'KPI wajib dipilih.',
            'kpi_definition_id.exists' => 'KPI yang dipilih tidak tersedia atau sudah tidak aktif.',
            'period_month.required' => 'Periode target wajib diisi.',
            'period_month.date' => 'Format periode target tidak valid.',
            'target_value.required' => 'Nilai target wajib diisi.',
            'target_value.numeric' => 'Nilai target harus berupa angka.',
            'target_value.min' => 'Nilai target tidak boleh kurang dari 0.',
        ];
    }
}
