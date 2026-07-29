<?php

namespace App\Http\Requests\ExecutiveCenter;

use App\Models\StrategicReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateStrategicReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && in_array((string) $this->user()->role, ['super_admin', 'admin'], true);
    }

    public function rules(): array
    {
        return ['period_type' => ['required', Rule::in([StrategicReport::TYPE_MONTHLY, StrategicReport::TYPE_QUARTERLY])], 'period' => ['required', 'date_format:Y-m']];
    }
}
