<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use App\Models\QuizQuestion;
use App\Models\QuizOption;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class QuizOptionController extends Controller
{
    public function index(Request $request, QuizQuestion $question): View
    {
        $options = QuizOption::where('question_id', $question->id)
            ->latest()
            ->get();

        return view('quiz.options.index', compact('question', 'options'));
    }

    public function show(QuizOption $option): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $option,
        ]);
    }

    public function store(Request $request, QuizQuestion $question): JsonResponse
    {
        $validated = $request->validate([
            'option_text' => ['required', 'string'],
            'is_correct' => ['nullable', 'boolean'],
        ]);

        // kalau ini benar, reset yang lain
        if ($request->boolean('is_correct')) {
            QuizOption::where('question_id', $question->id)
                ->update(['is_correct' => false]);
        }

        $option = QuizOption::create([
            'question_id' => $question->id,
            'option_text' => $validated['option_text'],
            'is_correct' => $request->boolean('is_correct'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Option created successfully.',
            'data' => $option,
        ]);
    }

    public function update(Request $request, QuizOption $option): JsonResponse
    {
        $validated = $request->validate([
            'option_text' => ['required', 'string'],
            'is_correct' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_correct')) {
            QuizOption::where('question_id', $option->question_id)
                ->update(['is_correct' => false]);
        }

        $option->update([
            'option_text' => $validated['option_text'],
            'is_correct' => $request->boolean('is_correct'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Option updated successfully.',
        ]);
    }

    public function destroy(QuizOption $option): JsonResponse
    {
        $option->delete();

        return response()->json([
            'success' => true,
            'message' => 'Option deleted successfully.',
        ]);
    }
}