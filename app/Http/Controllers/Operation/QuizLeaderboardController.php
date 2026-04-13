<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizParticipant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizLeaderboardController extends Controller
{
    public function index(Request $request, Quiz $quiz): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $questionCount = $quiz->questions()->count();

        $leaderboardQuery = QuizParticipant::query()
            ->where('quiz_participants.quiz_id', $quiz->id)
            ->leftJoin(
                'quiz_participant_answers',
                'quiz_participants.id',
                '=',
                'quiz_participant_answers.quiz_participant_id'
            )
            ->leftJoin('quiz_options', function ($join) {
                $join->on('quiz_participant_answers.quiz_option_id', '=', 'quiz_options.id')
                    ->on('quiz_participant_answers.quiz_question_id', '=', 'quiz_options.question_id');
            })
            ->select(
                'quiz_participants.id',
                'quiz_participants.quiz_id',
                'quiz_participants.name',
                'quiz_participants.phone',
                'quiz_participants.email',
                'quiz_participants.created_at',
                'quiz_participants.updated_at',
                DB::raw('COUNT(DISTINCT quiz_participant_answers.quiz_question_id) as answered_questions'),
                DB::raw("
                    ROUND(
                        (
                            COALESCE(SUM(CASE WHEN quiz_options.is_correct = 1 THEN 1 ELSE 0 END), 0)
                            / NULLIF({$questionCount}, 0)
                        ) * 100
                    ) as score
                ")
            )
            ->groupBy(
                'quiz_participants.id',
                'quiz_participants.quiz_id',
                'quiz_participants.name',
                'quiz_participants.phone',
                'quiz_participants.email',
                'quiz_participants.created_at',
                'quiz_participants.updated_at'
            )
            ->orderByDesc('score')
            ->orderByDesc('answered_questions')
            ->orderBy('quiz_participants.created_at');

        $participants = $leaderboardQuery
            ->paginate($perPage)
            ->withQueryString();

        $participants->getCollection()->transform(function ($participant, $index) use ($participants, $questionCount) {
            $participant->score = (int) ($participant->score ?? 0);
            $participant->answered_questions = (int) ($participant->answered_questions ?? 0);
            $participant->total_questions = $questionCount;
            $participant->rank = (($participants->currentPage() - 1) * $participants->perPage()) + $index + 1;

            $participant->answered_percentage = $questionCount > 0
                ? (int) round(($participant->answered_questions / $questionCount) * 100)
                : 0;

            $participant->score_percentage = $participant->score;

            return $participant;
        });

        $highestScore = QuizParticipant::query()
            ->where('quiz_participants.quiz_id', $quiz->id)
            ->leftJoin(
                'quiz_participant_answers',
                'quiz_participants.id',
                '=',
                'quiz_participant_answers.quiz_participant_id'
            )
            ->leftJoin('quiz_options', function ($join) {
                $join->on('quiz_participant_answers.quiz_option_id', '=', 'quiz_options.id')
                    ->on('quiz_participant_answers.quiz_question_id', '=', 'quiz_options.question_id');
            })
            ->selectRaw("
                ROUND(
                    (
                        COALESCE(SUM(CASE WHEN quiz_options.is_correct = 1 THEN 1 ELSE 0 END), 0)
                        / NULLIF({$questionCount}, 0)
                    ) * 100
                ) as total_score
            ")
            ->groupBy('quiz_participants.id')
            ->orderByDesc('total_score')
            ->value('total_score') ?? 0;

        $stats = [
            'total_participants' => QuizParticipant::where('quiz_id', $quiz->id)->count(),
            'total_questions' => $questionCount,
            'highest_score' => (int) $highestScore,
        ];

        return view('quiz.leaderboard', compact('quiz', 'participants', 'stats'));
    }
}