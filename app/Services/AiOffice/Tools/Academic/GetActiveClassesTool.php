<?php

namespace App\Services\AiOffice\Tools\Academic;

use App\Models\Batch;

class GetActiveClassesTool
{
    /*
    |--------------------------------------------------------------------------
    | Handle
    |--------------------------------------------------------------------------
    */

    public function handle(array $input = []): array
    {
        $batches = Batch::query()
            ->with([
                'program:id,name',
            ])
            ->withCount([
                'activeStudentEnrollments',
            ])
            ->where('status', 'ongoing')
            ->orderBy('start_date')
            ->get([
                'id',
                'program_id',
                'name',
                'slug',
                'start_date',
                'end_date',
                'quota',
                'status',
            ]);

        return [
            'success' => true,

            'action' => 'get_active_classes',

            'data' => $batches
                ->map(function (Batch $batch) {
                    return [
                        'id' => $batch->id,

                        'name' => $batch->name,

                        'slug' => $batch->slug,

                        'status' => $batch->status,

                        'start_date' => $batch->start_date?->toDateString(),

                        'end_date' => $batch->end_date?->toDateString(),

                        'quota' => $batch->quota,

                        'active_students' =>
                            $batch->active_student_enrollments_count,

                        'program' => $batch->program
                            ? [
                                'id' => $batch->program->id,
                                'name' => $batch->program->name,
                            ]
                            : null,
                    ];
                })
                ->values()
                ->all(),

            'meta' => [
                'count' => $batches->count(),

                'filter' => [
                    'status' => 'ongoing',
                ],
            ],
        ];
    }
}