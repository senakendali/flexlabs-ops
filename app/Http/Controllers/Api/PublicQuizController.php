<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizParticipant;
use App\Models\QuizParticipantAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicQuizController extends Controller
{
    public function storeParticipant(Request $request, Quiz $quiz): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $participant = QuizParticipant::create([
            'quiz_id' => $quiz->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Participant created successfully.',
            'data' => $participant,
        ]);
    }

    public function submitAnswers(Request $request, Quiz $quiz): JsonResponse
    {
        $validated = $request->validate([
            'participant_id' => ['required', 'integer', 'exists:quiz_participants,id'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:quiz_questions,id'],
            'answers.*.option_id' => ['required', 'integer', 'exists:quiz_options,id'],
        ]);

        $participant = QuizParticipant::query()
            ->where('id', $validated['participant_id'])
            ->where('quiz_id', $quiz->id)
            ->first();

        if (!$participant) {
            throw ValidationException::withMessages([
                'participant_id' => 'Participant tidak valid untuk quiz ini.',
            ]);
        }

        DB::transaction(function () use ($participant, $validated, $quiz): void {
            QuizParticipantAnswer::where('quiz_participant_id', $participant->id)->delete();

            foreach ($validated['answers'] as $answer) {
                $question = $quiz->questions()
                    ->where('id', $answer['question_id'])
                    ->first();

                if (!$question) {
                    continue;
                }

                $option = QuizOption::query()
                    ->where('id', $answer['option_id'])
                    ->where('question_id', $question->id)
                    ->first();

                if (!$option) {
                    continue;
                }

                QuizParticipantAnswer::create([
                    'quiz_id' => $quiz->id,
                    'quiz_participant_id' => $participant->id,
                    'quiz_question_id' => $question->id,
                    'quiz_option_id' => $option->id,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Quiz answers submitted successfully.',
        ]);
    }
}