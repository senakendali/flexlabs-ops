<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublicQuizController;
use App\Http\Controllers\Api\Lms\StudentAuthController;
use App\Http\Controllers\Api\Lms\StudentDashboardController;
use App\Http\Controllers\Api\Lms\StudentCourseController;
use App\Http\Controllers\Api\Lms\StudentLearningController;

Route::prefix('public/quizzes/{quiz}')->group(function () {
    Route::post('/participants', [PublicQuizController::class, 'storeParticipant']);
    Route::post('/submit', [PublicQuizController::class, 'submitAnswers']);
});



Route::prefix('lms/student')->group(function () {
    Route::post('/login', [StudentAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [StudentAuthController::class, 'me']);
        Route::get('/dashboard', [StudentDashboardController::class, 'index']);

        Route::get('/courses', [StudentCourseController::class, 'index']);
        Route::get('/courses/{slug}', [StudentCourseController::class, 'show']);

        Route::get('/learn/{courseSlug}/{lessonSlug}', [StudentLearningController::class, 'show']);
        Route::post('/learn/{courseSlug}/{lessonSlug}/progress', [StudentLearningController::class, 'saveProgress']);

        Route::post('/logout', [StudentAuthController::class, 'logout']);
    });
});