<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminFeedbackResponseController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());
        $nextStep = $request->string('next_step')->toString();

        $perPage = (int) $request->integer('per_page', 15);
        $perPage = in_array($perPage, [15, 25, 50, 100], true) ? $perPage : 15;

        $responses = FeedbackResponse::query()
            ->with([
                'form',
                'answers.question',
            ])
            ->withCount('answers')
            ->when(in_array($status, ['draft', 'submitted'], true), function (Builder $query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery
                        ->where('student_name', 'like', "%{$search}%")
                        ->orWhere('student_email', 'like', "%{$search}%")
                        ->orWhere('token', 'like', "%{$search}%");
                });
            })
            ->when(in_array($nextStep, ['interested', 'maybe', 'not_interested'], true), function (Builder $query) use ($nextStep) {
                $this->applyNextStepFilter($query, $nextStep);
            })
            ->orderByRaw('CASE WHEN submitted_at IS NULL THEN 1 ELSE 0 END')
            ->latest('submitted_at')
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $responses->getCollection()->transform(function (FeedbackResponse $response) {
            $response->setAttribute('next_step_answer', $this->resolveNextStepAnswer($response));

            return $response;
        });

        return view('feedback.admin.responses.index', [
            'responses' => $responses,
            'summary' => $this->buildSummary(),
            'filters' => [
                'status' => $status,
                'search' => $search,
                'next_step' => $nextStep,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(FeedbackResponse $response): View
    {
        $response->load([
            'form',
            'answers.question',
        ]);

        $answers = $response->answers
            ->sortBy(function (FeedbackAnswer $answer) {
                return $answer->question?->sort_order ?? $answer->id;
            })
            ->groupBy(function (FeedbackAnswer $answer) {
                return $answer->question?->section ?: 'Feedback';
            });

        $nextStepAnswers = $response->answers
            ->filter(function (FeedbackAnswer $answer) {
                return ($answer->question?->section === 'Next Step')
                    || $this->isNextStepQuestion($answer);
            })
            ->sortBy(function (FeedbackAnswer $answer) {
                return $answer->question?->sort_order ?? $answer->id;
            })
            ->values();

        return view('feedback.admin.responses.show', [
            'response' => $response,
            'answers' => $answers,
            'nextStepAnswers' => $nextStepAnswers,
        ]);
    }

    private function buildSummary(): array
    {
        $total = FeedbackResponse::query()->count();

        $submitted = FeedbackResponse::query()
            ->where('status', 'submitted')
            ->count();

        $draft = FeedbackResponse::query()
            ->where('status', 'draft')
            ->count();

        $averageScore = FeedbackResponse::query()
            ->where('status', 'submitted')
            ->whereNotNull('overall_score')
            ->avg('overall_score');

        $averageNps = FeedbackResponse::query()
            ->where('status', 'submitted')
            ->whereNotNull('nps_score')
            ->avg('nps_score');

        $promoterCount = FeedbackResponse::query()
            ->where('status', 'submitted')
            ->whereNotNull('nps_score')
            ->where('nps_score', '>=', 9)
            ->count();

        $passiveCount = FeedbackResponse::query()
            ->where('status', 'submitted')
            ->whereNotNull('nps_score')
            ->whereBetween('nps_score', [7, 8])
            ->count();

        $detractorCount = FeedbackResponse::query()
            ->where('status', 'submitted')
            ->whereNotNull('nps_score')
            ->where('nps_score', '<=', 6)
            ->count();

        $npsAnsweredCount = $promoterCount + $passiveCount + $detractorCount;

        $promoterRate = $npsAnsweredCount > 0
            ? ($promoterCount / $npsAnsweredCount) * 100
            : 0;

        $detractorRate = $npsAnsweredCount > 0
            ? ($detractorCount / $npsAnsweredCount) * 100
            : 0;

        $npsScore = $npsAnsweredCount > 0
            ? round($promoterRate - $detractorRate)
            : null;

        $needsAttentionCount = FeedbackResponse::query()
            ->where('status', 'submitted')
            ->where(function (Builder $query) {
                $query
                    ->where(function (Builder $subQuery) {
                        $subQuery
                            ->whereNotNull('overall_score')
                            ->where('overall_score', '<=', 3);
                    })
                    ->orWhere(function (Builder $subQuery) {
                        $subQuery
                            ->whereNotNull('nps_score')
                            ->where('nps_score', '<=', 6);
                    });
            })
            ->count();

        $interestedCount = $this->countNextStepAnswers('interested');
        $maybeInterestedCount = $this->countNextStepAnswers('maybe');
        $notInterestedCount = $this->countNextStepAnswers('not_interested');

        $testimonialCount = $this->testimonialAnswerQuery()
            ->distinct('feedback_response_id')
            ->count('feedback_response_id');

        return [
            'total' => $total,
            'submitted' => $submitted,
            'draft' => $draft,

            'average_score' => $averageScore !== null ? round((float) $averageScore, 2) : null,
            'average_nps' => $averageNps !== null ? round((float) $averageNps, 1) : null,

            'promoter_count' => $promoterCount,
            'passive_count' => $passiveCount,
            'detractor_count' => $detractorCount,
            'nps_answered_count' => $npsAnsweredCount,
            'nps_score' => $npsScore,

            'needs_attention_count' => $needsAttentionCount,

            'next_program_leads' => $interestedCount + $maybeInterestedCount,
            'interested_count' => $interestedCount,
            'maybe_interested_count' => $maybeInterestedCount,
            'not_interested_count' => $notInterestedCount,

            'testimonial_count' => $testimonialCount,
        ];
    }

    private function applyNextStepFilter(Builder $query, string $nextStep): void
    {
        $query->whereHas('answers', function (Builder $answerQuery) use ($nextStep) {
            $this->applyNextStepQuestionScope($answerQuery);
            $this->applyNextStepAnswerScope($answerQuery, $nextStep);
        });
    }

    private function countNextStepAnswers(string $type): int
    {
        $query = $this->nextStepAnswerQuery();

        $this->applyNextStepAnswerScope($query, $type);

        return (int) $query
            ->distinct('feedback_response_id')
            ->count('feedback_response_id');
    }

    private function nextStepAnswerQuery(): Builder
    {
        $query = FeedbackAnswer::query()
            ->whereHas('response', function (Builder $responseQuery) {
                $responseQuery->where('status', 'submitted');
            });

        $this->applyNextStepQuestionScope($query);

        return $query;
    }

    private function applyNextStepQuestionScope(Builder $query): void
    {
        $query->where(function (Builder $subQuery) {
            $subQuery
                ->where(function (Builder $textQuery) {
                    $textQuery
                        ->where('question_text_snapshot', 'like', '%tertarik%')
                        ->where(function (Builder $keywordQuery) {
                            $keywordQuery
                                ->where('question_text_snapshot', 'like', '%program lanjutan%')
                                ->orWhere('question_text_snapshot', 'like', '%program selanjutnya%')
                                ->orWhere('question_text_snapshot', 'like', '%FlexLabs%');
                        });
                })
                ->orWhereHas('question', function (Builder $questionQuery) {
                    $questionQuery
                        ->where('section', 'Next Step')
                        ->where('question_text', 'like', '%tertarik%');
                });
        });
    }

    private function applyNextStepAnswerScope(Builder $query, string $type): void
    {
        $query->where(function (Builder $answerQuery) use ($type) {
            match ($type) {
                'interested' => $answerQuery
                    ->whereRaw("LOWER(COALESCE(answer_text, answer_value, '')) LIKE ?", ['ya%']),

                'maybe' => $answerQuery
                    ->whereRaw("LOWER(COALESCE(answer_text, answer_value, '')) LIKE ?", ['%mungkin%'])
                    ->orWhereRaw("LOWER(COALESCE(answer_text, answer_value, '')) LIKE ?", ['%ingin tahu%'])
                    ->orWhereRaw("LOWER(COALESCE(answer_text, answer_value, '')) LIKE ?", ['%cocok%']),

                'not_interested' => $answerQuery
                    ->whereRaw("LOWER(COALESCE(answer_text, answer_value, '')) LIKE ?", ['%belum%'])
                    ->orWhereRaw("LOWER(COALESCE(answer_text, answer_value, '')) LIKE ?", ['%tidak%']),

                default => null,
            };
        });
    }

    private function testimonialAnswerQuery(): Builder
    {
        return FeedbackAnswer::query()
            ->whereHas('response', function (Builder $responseQuery) {
                $responseQuery->where('status', 'submitted');
            })
            ->where(function (Builder $query) {
                $query
                    ->where('question_text_snapshot', 'like', '%testimonial%')
                    ->orWhereHas('question', function (Builder $questionQuery) {
                        $questionQuery->where('section', 'Testimonial');
                    });
            })
            ->where(function (Builder $query) {
                $query
                    ->whereNotNull('answer_text')
                    ->where('answer_text', '!=', '');
            });
    }

    private function resolveNextStepAnswer(FeedbackResponse $response): ?string
    {
        if (! $response->relationLoaded('answers')) {
            $response->load('answers.question');
        }

        $answer = $response->answers->first(function (FeedbackAnswer $answer) {
            return $this->isNextStepInterestQuestion($answer);
        });

        return $answer?->display_answer
            ? (string) $answer->display_answer
            : null;
    }

    private function isNextStepQuestion(FeedbackAnswer $answer): bool
    {
        return ($answer->question?->section === 'Next Step')
            || $this->isNextStepInterestQuestion($answer);
    }

    private function isNextStepInterestQuestion(FeedbackAnswer $answer): bool
    {
        $questionText = Str::lower((string) ($answer->question_text_snapshot ?: $answer->question?->question_text));

        if ($questionText === '') {
            return false;
        }

        return Str::contains($questionText, 'tertarik')
            && (
                Str::contains($questionText, 'program lanjutan')
                || Str::contains($questionText, 'program selanjutnya')
                || Str::contains($questionText, 'flexlabs')
            );
    }
}