<?php

namespace Database\Seeders;

use App\Models\KpiDefinition;
use Illuminate\Database\Seeder;

class KpiDefinitionSeeder extends Seeder
{
    /**
     * Seed the default KPI definitions used by FlexOps.
     */
    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            $kpiDefinition = KpiDefinition::query()
                ->withTrashed()
                ->firstOrNew(['code' => $definition['code']]);

            $kpiDefinition->fill($definition);
            $kpiDefinition->deleted_at = null;
            $kpiDefinition->save();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            [
                'code' => 'confirmed_revenue',
                'name' => 'Confirmed Revenue',
                'description' => 'Total pembayaran yang sudah terkonfirmasi pada periode target.',
                'division' => 'sales',
                'category' => 'financial',
                'unit' => KpiDefinition::UNIT_CURRENCY,
                'direction' => KpiDefinition::DIRECTION_HIGHER,
                'frequency' => KpiDefinition::FREQUENCY_MONTHLY,
                'calculation_type' => KpiDefinition::CALCULATION_AUTOMATIC,
                'data_source_key' => 'payments',
                'calculation_key' => 'confirmed_revenue',
                'calculation_settings' => [
                    'value_type' => 'sum',
                    'date_basis' => 'payment_date',
                    'statuses' => ['confirmed', 'paid', 'settled'],
                ],
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'total_leads',
                'name' => 'Total Leads',
                'description' => 'Jumlah lead unik dan valid yang masuk selama periode target dari seluruh sumber pemasaran.',
                'division' => 'marketing',
                'category' => 'growth',
                'unit' => KpiDefinition::UNIT_NUMBER,
                'direction' => KpiDefinition::DIRECTION_HIGHER,
                'frequency' => KpiDefinition::FREQUENCY_MONTHLY,
                'calculation_type' => KpiDefinition::CALCULATION_AUTOMATIC,
                'data_source_key' => 'leads',
                'calculation_key' => 'total_leads',
                'calculation_settings' => [
                    'value_type' => 'count',
                    'date_basis' => 'created_at',
                ],
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'code' => 'interacted_leads',
                'name' => 'Interacted Leads',
                'description' => 'Jumlah lead yang sudah memperoleh aktivitas atau interaksi dari tim sales.',
                'division' => 'sales',
                'category' => 'sales',
                'unit' => KpiDefinition::UNIT_NUMBER,
                'direction' => KpiDefinition::DIRECTION_HIGHER,
                'frequency' => KpiDefinition::FREQUENCY_MONTHLY,
                'calculation_type' => KpiDefinition::CALCULATION_AUTOMATIC,
                'data_source_key' => 'leads',
                'calculation_key' => 'interacted_leads',
                'calculation_settings' => [
                    'value_type' => 'count',
                    'date_basis' => 'first_interacted_at',
                ],
                'sort_order' => 30,
                'is_active' => false,
            ],
            [
                'code' => 'closed_deals',
                'name' => 'Closed Deals',
                'description' => 'Jumlah lead unik yang berhasil menjadi won atau closed deal selama periode target.',
                'division' => 'sales',
                'category' => 'sales',
                'unit' => KpiDefinition::UNIT_NUMBER,
                'direction' => KpiDefinition::DIRECTION_HIGHER,
                'frequency' => KpiDefinition::FREQUENCY_MONTHLY,
                'calculation_type' => KpiDefinition::CALCULATION_AUTOMATIC,
                'data_source_key' => 'orders',
                'calculation_key' => 'closed_deals',
                'calculation_settings' => [
                    'value_type' => 'count',
                    'date_basis' => 'closed_at',
                ],
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'code' => 'paid_students',
                'name' => 'Paid Students',
                'description' => 'Jumlah student unik yang melakukan pembayaran pertama terkonfirmasi pada periode target.',
                'division' => 'sales',
                'category' => 'sales',
                'unit' => KpiDefinition::UNIT_NUMBER,
                'direction' => KpiDefinition::DIRECTION_HIGHER,
                'frequency' => KpiDefinition::FREQUENCY_MONTHLY,
                'calculation_type' => KpiDefinition::CALCULATION_AUTOMATIC,
                'data_source_key' => 'payments',
                'calculation_key' => 'paid_students',
                'calculation_settings' => [
                    'value_type' => 'distinct_count',
                    'distinct_by' => 'student_id',
                    'date_basis' => 'payment_date',
                    'statuses' => ['confirmed', 'paid', 'settled'],
                    'first_payment_only' => true,
                ],
                'sort_order' => 50,
                'is_active' => true,
            ],
            [
                'code' => 'sales_conversion_rate',
                'name' => 'Sales Conversion Rate',
                'description' => 'Persentase closed deal dibandingkan dengan total lead pada periode target.',
                'division' => 'sales',
                'category' => 'sales',
                'unit' => KpiDefinition::UNIT_PERCENTAGE,
                'direction' => KpiDefinition::DIRECTION_HIGHER,
                'frequency' => KpiDefinition::FREQUENCY_MONTHLY,
                'calculation_type' => KpiDefinition::CALCULATION_AUTOMATIC,
                'data_source_key' => 'sales_funnel',
                'calculation_key' => 'sales_conversion_rate',
                'calculation_settings' => [
                    'value_type' => 'ratio',
                    'numerator_key' => 'closed_deals',
                    'denominator_key' => 'total_leads',
                    'multiplier' => 100,
                    'precision' => 2,
                ],
                'sort_order' => 60,
                'is_active' => false,
            ],
            [
                'code' => 'marketing_spend',
                'name' => 'Marketing Spend',
                'description' => 'Total biaya iklan dan aktivitas pemasaran yang tercatat selama periode target.',
                'division' => 'marketing',
                'category' => 'growth',
                'unit' => KpiDefinition::UNIT_CURRENCY,
                'direction' => KpiDefinition::DIRECTION_LOWER,
                'frequency' => KpiDefinition::FREQUENCY_MONTHLY,
                'calculation_type' => KpiDefinition::CALCULATION_AUTOMATIC,
                'data_source_key' => 'marketing_platforms',
                'calculation_key' => 'marketing_spend',
                'calculation_settings' => [
                    'value_type' => 'sum',
                    'sources' => ['meta_ads', 'google_ads'],
                    'date_basis' => 'insight_date',
                ],
                'sort_order' => 70,
                'is_active' => true,
            ],
            [
                'code' => 'new_students',
                'name' => 'New Students',
                'description' => 'Jumlah student baru yang mulai aktif pada program atau batch selama periode target.',
                'division' => 'sales',
                'category' => 'learning',
                'unit' => KpiDefinition::UNIT_NUMBER,
                'direction' => KpiDefinition::DIRECTION_HIGHER,
                'frequency' => KpiDefinition::FREQUENCY_MONTHLY,
                'calculation_type' => KpiDefinition::CALCULATION_AUTOMATIC,
                'data_source_key' => 'students',
                'calculation_key' => 'new_students',
                'calculation_settings' => [
                    'value_type' => 'distinct_count',
                    'distinct_by' => 'student_id',
                    'date_basis' => 'enrolled_at',
                ],
                'sort_order' => 80,
                'is_active' => false,
            ],
            [
                'code' => 'student_completion_rate',
                'name' => 'Student Completion Rate',
                'description' => 'Persentase student eligible yang mencapai minimal 95% penyelesaian program dibandingkan dengan seluruh student eligible yang dijadwalkan selesai pada periode target.',
                'division' => 'academic',
                'category' => 'learning',
                'unit' => KpiDefinition::UNIT_PERCENTAGE,
                'direction' => KpiDefinition::DIRECTION_HIGHER,
                'frequency' => KpiDefinition::FREQUENCY_MONTHLY,
                'calculation_type' => KpiDefinition::CALCULATION_AUTOMATIC,
                'data_source_key' => 'student_progress',
                'calculation_key' => 'student_completion_rate',
                'calculation_settings' => [
                    'value_type' => 'ratio',
                    'numerator_key' => 'completed_eligible_students',
                    'denominator_key' => 'eligible_students_scheduled_to_finish',
                    'eligibility_basis' => 'scheduled_completion_period',
                    'completion_threshold_percentage' => 95,
                    'multiplier' => 100,
                    'precision' => 2,
                ],
                'sort_order' => 90,
                'is_active' => true,
            ],
            [
                'code' => 'attendance_rate',
                'name' => 'Attendance Rate',
                'description' => 'Persentase kehadiran employee dibandingkan dengan total hari kerja yang seharusnya dipenuhi.',
                'division' => 'hr',
                'category' => 'people',
                'unit' => KpiDefinition::UNIT_PERCENTAGE,
                'direction' => KpiDefinition::DIRECTION_HIGHER,
                'frequency' => KpiDefinition::FREQUENCY_MONTHLY,
                'calculation_type' => KpiDefinition::CALCULATION_AUTOMATIC,
                'data_source_key' => 'attendances',
                'calculation_key' => 'attendance_rate',
                'calculation_settings' => [
                    'value_type' => 'ratio',
                    'numerator_key' => 'present_workdays',
                    'denominator_key' => 'expected_workdays',
                    'multiplier' => 100,
                    'precision' => 2,
                ],
                'sort_order' => 100,
                'is_active' => false,
            ],
        ];
    }
}
