<?php

namespace App\Http\Requests\Settings;

use App\Models\KpiTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKpiTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input(
                'status',
                KpiTarget::STATUS_DRAFT
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kpi_definition_id' => [
                'required',
                'integer',
                Rule::exists('kpi_definitions', 'id')
                    ->whereNull('deleted_at')
                    ->where('is_active', true),
            ],
            'period_month' => [
                'required',
                'date',
            ],
            'target_value' => [
                'required',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                'string',
                Rule::in([
                    KpiTarget::STATUS_DRAFT,
                    KpiTarget::STATUS_ACTIVE,
                    KpiTarget::STATUS_LOCKED,
                ]),
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'history_notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Return only fields accepted by TargetService::create().
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
                'status',
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
            'status.required' => 'Status target wajib dipilih.',
            'status.in' => 'Status target harus draft, active, atau locked.',
        ];
    }
}
