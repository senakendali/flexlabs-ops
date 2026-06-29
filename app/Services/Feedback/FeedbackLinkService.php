<?php

namespace App\Services\Feedback;

use App\Models\Batch;
use App\Models\FeedbackForm;
use App\Models\FeedbackResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FeedbackLinkService
{
    public function createForStudent(array $data): FeedbackResponse
    {
        return DB::transaction(function () use ($data) {
            $form = $this->resolveForm($data);

            $existingResponse = $this->findExistingResponse($form, $data);

            if ($existingResponse) {
                return $existingResponse;
            }

            return FeedbackResponse::query()->create([
                'feedback_form_id' => $form->id,

                'student_id' => $data['student_id'] ?? null,
                'program_id' => $data['program_id'] ?? $form->program_id,
                'batch_id' => $data['batch_id'] ?? $form->batch_id,
                'instructor_id' => $data['instructor_id'] ?? null,

                'token' => null,

                'student_name' => $data['student_name'] ?? null,
                'student_email' => $data['student_email'] ?? null,

                'status' => 'draft',

                'metadata' => [
                    'generated_from' => $data['generated_from'] ?? 'manual',
                    'generated_by_user_id' => auth()->id(),
                    'generated_at' => now()->toDateTimeString(),
                ],
            ]);
        });
    }

    public function createForBatch(Batch $batch, array $data = []): array
    {
        $batch->loadMissing('program:id,name');

        $form = $this->resolveForm($data);

        $enrollments = $batch->activeStudentEnrollments()
            ->with('student:id,full_name,email,phone')
            ->get();

        $links = [];
        $skipped = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student ?? null;

            if (! $student) {
                $skipped[] = [
                    'enrollment_id' => $enrollment->id,
                    'reason' => 'Student data not found.',
                ];

                continue;
            }

            $response = $this->createForStudent([
                'feedback_form_id' => $form->id,

                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'student_email' => $student->email,

                'program_id' => $batch->program_id,
                'batch_id' => $batch->id,
                'instructor_id' => $data['instructor_id'] ?? null,

                'generated_from' => $data['generated_from'] ?? 'batch',
            ]);

            $links[] = [
                'feedback_response_id' => $response->id,
                'token' => $response->token,
                'link' => $this->publicUrl($response),
                'status' => $response->status,
                'submitted_at' => optional($response->submitted_at)->format('d M Y H:i'),

                'student' => [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                ],
            ];
        }

        $submittedCount = collect($links)
            ->where('status', 'submitted')
            ->count();

        $pendingCount = collect($links)
            ->where('status', 'draft')
            ->count();

        return [
            'batch' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'program_id' => $batch->program_id,
                'program_name' => $batch->program?->name,
            ],

            'form' => [
                'id' => $form->id,
                'title' => $form->title,
                'slug' => $form->slug,
            ],

            'total_students' => $enrollments->count(),
            'total_links' => count($links),
            'submitted_count' => $submittedCount,
            'pending_count' => $pendingCount,
            'skipped_count' => count($skipped),
            'skipped' => $skipped,

            'links' => $links,
        ];
    }

    public function publicUrl(FeedbackResponse $response): string
    {
        $baseUrl = rtrim((string) config('feedback.public_base_url'), '/');

        if (blank($baseUrl)) {
            $baseUrl = rtrim((string) config('app.url'), '/');
        }

        return $baseUrl . '/f/' . $response->token;
    }

    private function resolveForm(array $data): FeedbackForm
    {
        if (! blank($data['feedback_form_id'] ?? null)) {
            return FeedbackForm::query()
                ->whereKey($data['feedback_form_id'])
                ->firstOrFail();
        }

        $slug = $data['feedback_form_slug'] ?? 'default-program-feedback';

        $form = FeedbackForm::query()
            ->where('slug', $slug)
            ->first();

        if (! $form) {
            throw new RuntimeException("Feedback form dengan slug [{$slug}] tidak ditemukan.");
        }

        return $form;
    }

    private function findExistingResponse(FeedbackForm $form, array $data): ?FeedbackResponse
    {
        $query = FeedbackResponse::query()
            ->where('feedback_form_id', $form->id);

        if (! blank($data['student_id'] ?? null)) {
            $query->where('student_id', $data['student_id']);
        } elseif (! blank($data['student_email'] ?? null)) {
            $query->where('student_email', $data['student_email']);
        } else {
            return null;
        }

        $query
            ->when(! blank($data['program_id'] ?? null), function (Builder $query) use ($data) {
                $query->where('program_id', $data['program_id']);
            })
            ->when(! blank($data['batch_id'] ?? null), function (Builder $query) use ($data) {
                $query->where('batch_id', $data['batch_id']);
            })
            ->when(! blank($data['instructor_id'] ?? null), function (Builder $query) use ($data) {
                $query->where('instructor_id', $data['instructor_id']);
            });

        return $query->latest()->first();
    }
}