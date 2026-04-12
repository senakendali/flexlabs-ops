<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizPlayController extends Controller
{
    public function show(Request $request, Quiz $quiz): View
    {
        $quiz->load([
            'questions' => function ($query) {
                $query->orderBy('order')->orderBy('id');
            },
            'questions.options' => function ($query) {
                $query->orderBy('id');
            },
        ]);

        $now = now();

        $isNotOpenedYet = $quiz->opens_at && $quiz->opens_at->gt($now);
        $isFinished = $quiz->status === 'finished';
        $isDraft = $quiz->status === 'draft';

        return view('quiz.play', [
            'quiz' => $quiz,
            'isNotOpenedYet' => $isNotOpenedYet,
            'isFinished' => $isFinished,
            'isDraft' => $isDraft,
        ]);
    }
}