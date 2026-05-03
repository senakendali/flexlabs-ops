<?php

namespace App\Http\Controllers\Api\Lms\Student;

use App\Http\Controllers\Controller;
use App\Services\Lms\MrPioneerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class MrPioneerController extends Controller
{
    public function ask(Request $request, MrPioneerService $mrPioneerService): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:2000'],
            'scope' => ['nullable', 'string', 'in:material_only'],
            'context' => ['required', 'array'],

            'context.course_id' => ['nullable'],
            'context.course_slug' => ['nullable', 'string', 'max:255'],
            'context.course_title' => ['nullable', 'string', 'max:255'],

            'context.module_id' => ['nullable'],
            'context.module_title' => ['nullable', 'string', 'max:255'],

            'context.topic_id' => ['nullable'],
            'context.topic_slug' => ['nullable', 'string', 'max:255'],
            'context.topic_title' => ['nullable', 'string', 'max:255'],

            'context.sub_topic_id' => ['nullable'],
            'context.sub_topic_slug' => ['nullable', 'string', 'max:255'],
            'context.sub_topic_title' => ['nullable', 'string', 'max:255'],

            'context.lesson_id' => ['nullable'],
            'context.lesson_slug' => ['nullable', 'string', 'max:255'],
            'context.lesson_title' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $mrPioneerService->answer(
                user: $request->user(),
                payload: $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Mr. Pioneer berhasil menjawab pertanyaan.',
                'data' => $result,
            ]);
        } catch (HttpExceptionInterface $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ], $exception->getStatusCode());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Mr. Pioneer belum bisa menjawab saat ini. Silakan coba lagi.',
                'data' => null,
            ], 500);
        }
    }
}