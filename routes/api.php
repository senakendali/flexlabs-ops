<?php

use App\Http\Controllers\Api\Lms\StudentAnnouncementController;
use App\Http\Controllers\Api\Lms\StudentAuthController;
use App\Http\Controllers\Api\Lms\StudentCourseController;
use App\Http\Controllers\Api\Lms\StudentDashboardController;
use App\Http\Controllers\Api\Lms\StudentLearningController;
use App\Http\Controllers\Api\Lms\StudentMentoringController;
use App\Http\Controllers\Api\Lms\StudentUpcomingSessionController;
use App\Http\Controllers\Api\Lms\StudentAssignmentController;
use App\Http\Controllers\Api\PublicQuizController;
use Illuminate\Support\Facades\Route;

Route::prefix('public/quizzes/{quiz}')->group(function () {
    Route::post('/participants', [PublicQuizController::class, 'storeParticipant']);
    Route::post('/submit', [PublicQuizController::class, 'submitAnswers']);
});

Route::prefix('lms/student')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */
    Route::post('/login', [StudentAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [StudentAuthController::class, 'me']);
        Route::post('/logout', [StudentAuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', [StudentDashboardController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | Courses
        |--------------------------------------------------------------------------
        */
        Route::get('/courses', [StudentCourseController::class, 'index']);
        Route::get('/courses/{slug}', [StudentCourseController::class, 'show']);

        /*
        |--------------------------------------------------------------------------
        | Learning
        |--------------------------------------------------------------------------
        */
        Route::get('/learn/{courseSlug}/{lessonSlug}', [StudentLearningController::class, 'show']);
        Route::post('/learn/{courseSlug}/{lessonSlug}/progress', [StudentLearningController::class, 'saveProgress']);

        /*
        |--------------------------------------------------------------------------
        | Assignments
        |--------------------------------------------------------------------------
        */
        Route::get('/assignments/{batchAssignment}', [StudentAssignmentController::class, 'show'])
            ->whereNumber('batchAssignment');

        Route::post('/assignments/{batchAssignment}/submit', [StudentAssignmentController::class, 'submit'])
            ->whereNumber('batchAssignment');

        /*
        |--------------------------------------------------------------------------
        | Announcements
        |--------------------------------------------------------------------------
        */
        Route::get('/announcements', [StudentAnnouncementController::class, 'index']);
        Route::get('/announcements/{announcement:slug}', [StudentAnnouncementController::class, 'show']);

        /*
        |--------------------------------------------------------------------------
        | Upcoming Sessions
        |--------------------------------------------------------------------------
        | Gabungan live session program/batch + 1-on-1 mentoring session.
        */
        Route::get('/upcoming-sessions', [StudentUpcomingSessionController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | Mentoring / 1-on-1 Booking
        |--------------------------------------------------------------------------
        */
        Route::get('/mentoring/instructors', [StudentMentoringController::class, 'instructors']);
        Route::get('/mentoring/slots', [StudentMentoringController::class, 'slots']);
        Route::post('/mentoring/book', [StudentMentoringController::class, 'book']);
    });
});