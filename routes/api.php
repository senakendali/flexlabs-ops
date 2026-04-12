<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublicQuizController;

Route::prefix('public/quizzes/{quiz}')->group(function () {
    Route::post('/participants', [PublicQuizController::class, 'storeParticipant']);
    Route::post('/submit', [PublicQuizController::class, 'submitAnswers']);
});