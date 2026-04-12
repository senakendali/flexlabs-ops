<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $quizzes = Quiz::withCount('questions')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('quiz.index', compact('quizzes'));
    }

    public function show(Quiz $quiz): JsonResponse
    {
        $quiz->loadCount('questions');

        return response()->json([
            'success' => true,
            'data' => $quiz,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,active,finished'],
            'opens_at' => ['nullable', 'date'],
            'quota' => ['nullable', 'integer', 'min:1'],
        ]);

        $validated['status'] = $validated['status'] ?? 'draft';

        $quiz = Quiz::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'opens_at' => $validated['opens_at'] ?? null,
            'quota' => $validated['quota'] ?? null,
        ]);

        $quiz->loadCount('questions');

        return response()->json([
            'success' => true,
            'message' => 'Quiz created successfully.',
            'data' => $quiz,
        ]);
    }

    public function update(Request $request, Quiz $quiz): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,active,finished'],
            'opens_at' => ['nullable', 'date'],
            'quota' => ['nullable', 'integer', 'min:1'],
        ]);

        $quiz->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? $quiz->status,
            'opens_at' => $validated['opens_at'] ?? null,
            'quota' => $validated['quota'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quiz updated successfully.',
            'data' => $quiz->fresh()->loadCount('questions'),
        ]);
    }

    public function destroy(Quiz $quiz): JsonResponse
    {
        $quiz->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quiz deleted successfully.',
        ]);
    }
}