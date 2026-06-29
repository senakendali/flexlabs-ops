<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Services\Feedback\FeedbackLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeedbackResponseLinkController extends Controller
{
    public function store(Request $request, FeedbackLinkService $feedbackLinkService): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'feedback_form_id' => ['nullable', 'integer'],
            'feedback_form_slug' => ['nullable', 'string', 'max:255'],

            'student_id' => ['nullable', 'integer'],
            'student_name' => ['nullable', 'string', 'max:255'],
            'student_email' => ['nullable', 'email', 'max:255'],

            'program_id' => ['nullable', 'integer'],
            'batch_id' => ['nullable', 'integer'],
            'instructor_id' => ['nullable', 'integer'],

            'generated_from' => ['nullable', 'string', 'max:100'],
        ]);

        if (
            blank($validated['student_id'] ?? null)
            && blank($validated['student_email'] ?? null)
            && blank($validated['student_name'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'student' => 'Minimal isi student_id, student_email, atau student_name untuk generate link feedback.',
            ]);
        }

        $response = $feedbackLinkService->createForStudent($validated);
        $link = $feedbackLinkService->publicUrl($response);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Link feedback berhasil dibuat.',
                'data' => [
                    'feedback_response_id' => $response->id,
                    'feedback_form_id' => $response->feedback_form_id,
                    'student_id' => $response->student_id,
                    'student_name' => $response->student_name,
                    'student_email' => $response->student_email,
                    'status' => $response->status,
                    'token' => $response->token,
                    'link' => $link,
                ],
            ]);
        }

        return back()
            ->with('success', 'Link feedback berhasil dibuat.')
            ->with('feedback_link', $link);
    }

    public function storeForBatch(
        Request $request,
        Batch $batch,
        FeedbackLinkService $feedbackLinkService
    ): JsonResponse {
        $validated = $request->validate([
            'feedback_form_id' => ['nullable', 'integer'],
            'feedback_form_slug' => ['nullable', 'string', 'max:255'],
            'instructor_id' => ['nullable', 'integer'],
        ]);

        $validated['generated_from'] = 'batch_list';

        $data = $feedbackLinkService->createForBatch($batch, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Feedback links berhasil dibuat.',
            'data' => $data,
        ]);
    }
}