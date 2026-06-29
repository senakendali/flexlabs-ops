<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublicFeedbackController extends Controller
{
    public function show(string $token): View
    {
        $response = FeedbackResponse::query()
            ->with([
                'form',
                'form.activeQuestions',
            ])
            ->where('token', $token)
            ->firstOrFail();

        return view('feedback.public.show', [
            'response' => $response,
            'form' => $response->form,
            'questions' => $response->form?->activeQuestions ?? collect(),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $response = FeedbackResponse::query()
            ->with([
                'form',
                'form.activeQuestions',
            ])
            ->where('token', $token)
            ->firstOrFail();

        if ($response->status === 'submitted') {
            return redirect()
                ->route('feedback.public.show', $response->token)
                ->with('success', 'Feedback kamu sudah pernah dikirim. Terima kasih!');
        }

        $questions = $response->form?->activeQuestions ?? collect();

        if ($questions->isEmpty()) {
            return back()
                ->with('error', 'Form feedback belum memiliki pertanyaan aktif.');
        }

        $rules = [];

        foreach ($questions as $question) {
            $field = 'answers.' . $question->id;
            $requiredRule = $question->is_required ? 'required' : 'nullable';

            $rules[$field] = match ($question->question_type) {
                'rating_1_5' => [$requiredRule, 'integer', 'min:1', 'max:5'],
                'rating_0_10' => [$requiredRule, 'integer', 'min:0', 'max:10'],
                'text' => [$requiredRule, 'string', 'max:500'],
                'textarea' => [$requiredRule, 'string', 'max:5000'],
                'checkbox' => [$requiredRule, 'array'],
                default => [$requiredRule, 'string', 'max:1000'],
            };
        }

        $validated = $request->validate($rules, [
            'answers.*.required' => 'Pertanyaan ini wajib diisi.',
            'answers.*.integer' => 'Jawaban rating harus berupa angka.',
            'answers.*.min' => 'Nilai jawaban terlalu kecil.',
            'answers.*.max' => 'Nilai jawaban terlalu besar.',
        ]);

        $answers = $validated['answers'] ?? [];

        DB::transaction(function () use ($response, $questions, $answers) {
            $response->answers()->delete();

            $ratingScores = [];
            $npsScore = null;

            foreach ($questions as $question) {
                $rawAnswer = $answers[$question->id] ?? null;

                if ($rawAnswer === null || $rawAnswer === '') {
                    continue;
                }

                $payload = [
                    'feedback_response_id' => $response->id,
                    'feedback_question_id' => $question->id,
                    'question_text_snapshot' => $question->question_text,
                    'question_type_snapshot' => $question->question_type,
                    'answer_value' => null,
                    'answer_number' => null,
                    'answer_text' => null,
                    'answer_json' => null,
                ];

                if (in_array($question->question_type, ['rating_1_5', 'rating_0_10'], true)) {
                    $number = (int) $rawAnswer;

                    $payload['answer_value'] = (string) $number;
                    $payload['answer_number'] = $number;

                    if ($question->question_type === 'rating_1_5') {
                        $ratingScores[] = $number;
                    }

                    if ($question->question_type === 'rating_0_10') {
                        $npsScore = $number;
                    }
                } elseif (in_array($question->question_type, ['text', 'textarea'], true)) {
                    $payload['answer_text'] = trim((string) $rawAnswer);
                } elseif ($question->question_type === 'checkbox') {
                    $payload['answer_json'] = is_array($rawAnswer) ? $rawAnswer : [$rawAnswer];
                } else {
                    $payload['answer_value'] = is_array($rawAnswer)
                        ? json_encode($rawAnswer)
                        : (string) $rawAnswer;
                }

                FeedbackAnswer::query()->create($payload);
            }

            $overallScore = count($ratingScores)
                ? round(array_sum($ratingScores) / count($ratingScores), 2)
                : null;

            $response->forceFill([
                'status' => 'submitted',
                'overall_score' => $overallScore,
                'nps_score' => $npsScore,
                'submitted_at' => now(),
            ])->save();
        });

        return redirect()
            ->route('feedback.public.show', $response->token)
            ->with('success', 'Terima kasih! Feedback kamu sudah kami terima.');
    }
}