<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizQuestionController extends Controller
{
    public function index(Request $request, Quiz $quiz): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $questions = QuizQuestion::withCount('options')
            ->where('quiz_id', $quiz->id)
            ->orderBy('order')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('quiz.questions.index', compact('quiz', 'questions'));
    }

    public function show(QuizQuestion $question): JsonResponse
    {
        $question->loadCount('options');

        return response()->json([
            'success' => true,
            'data' => $question,
        ]);
    }

    public function store(Request $request, Quiz $quiz): JsonResponse
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'order' => ['nullable', 'integer', 'min:1'],
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => $validated['question_text'],
            'order' => $validated['order'] ?? ((int) QuizQuestion::where('quiz_id', $quiz->id)->max('order') + 1),
        ]);

        $question->loadCount('options');

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully.',
            'data' => $question,
        ]);
    }

    public function update(Request $request, QuizQuestion $question): JsonResponse
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'order' => ['nullable', 'integer', 'min:1'],
        ]);

        $question->update([
            'question_text' => $validated['question_text'],
            'order' => $validated['order'] ?? $question->order,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully.',
            'data' => $question->fresh()->loadCount('options'),
        ]);
    }

    public function destroy(QuizQuestion $question): JsonResponse
    {
        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully.',
        ]);
    }
}