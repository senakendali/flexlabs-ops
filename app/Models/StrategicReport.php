<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategicReport extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

    public const TYPE_MONTHLY = 'monthly';

    public const TYPE_QUARTERLY = 'quarterly';

    protected $fillable = [
        'title', 'period_type', 'period_start', 'period_end', 'revision', 'status',
        'overall_business_health', 'data_confidence', 'data_coverage', 'kpi_snapshot',
        'centre_performance_snapshot', 'trend_snapshot', 'cross_functional_snapshot',
        'executive_summary', 'wins', 'risks', 'opportunities', 'management_decisions',
        'action_plan', 'data_freshness', 'source_limitations', 'ai_metadata',
        'generated_by', 'generated_at', 'finalized_by', 'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date', 'period_end' => 'date', 'generated_at' => 'datetime', 'finalized_at' => 'datetime',
            'kpi_snapshot' => 'array', 'centre_performance_snapshot' => 'array', 'trend_snapshot' => 'array',
            'cross_functional_snapshot' => 'array', 'wins' => 'array', 'risks' => 'array',
            'opportunities' => 'array', 'management_decisions' => 'array', 'action_plan' => 'array',
            'data_freshness' => 'array', 'source_limitations' => 'array', 'ai_metadata' => 'array',
        ];
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }
}
