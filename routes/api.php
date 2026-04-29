<?php

use App\Http\Controllers\Api\Lms\StudentAnnouncementController;
use App\Http\Controllers\Api\Lms\StudentAssignmentController;
use App\Http\Controllers\Api\Lms\StudentAuthController;
use App\Http\Controllers\Api\Lms\StudentCourseController;
use App\Http\Controllers\Api\Lms\StudentDashboardController;
use App\Http\Controllers\Api\Lms\StudentLearningController;
use App\Http\Controllers\Api\Lms\StudentMentoringController;
use App\Http\Controllers\Api\Lms\StudentQuizController;
use App\Http\Controllers\Api\Lms\StudentScheduleController;
use App\Http\Controllers\Api\Lms\StudentUpcomingSessionController;
use App\Http\Controllers\Api\PublicQuizController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Quiz API
|--------------------------------------------------------------------------
*/
Route::prefix('public/quizzes/{quiz}')->group(function () {
    Route::post('/participants', [PublicQuizController::class, 'storeParticipant']);
    Route::post('/submit', [PublicQuizController::class, 'submitAnswers']);
});

/*
|--------------------------------------------------------------------------
| LMS Student API
|--------------------------------------------------------------------------
*/
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
        Route::get('/learning-timeline', [StudentDashboardController::class, 'learningTimeline']);

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
        | Quizzes
        |--------------------------------------------------------------------------
        */
        Route::get('/quizzes/{quiz}', [StudentQuizController::class, 'show'])
            ->whereNumber('quiz');

        Route::post('/quizzes/{quiz}/start', [StudentQuizController::class, 'start'])
            ->whereNumber('quiz');

        Route::post('/quizzes/{quiz}/submit', [StudentQuizController::class, 'submit'])
            ->whereNumber('quiz');

        /*
        |--------------------------------------------------------------------------
        | Announcements
        |--------------------------------------------------------------------------
        */
        Route::get('/announcements', [StudentAnnouncementController::class, 'index']);
        Route::get('/announcements/{announcement:slug}', [StudentAnnouncementController::class, 'show']);

        /*
        |--------------------------------------------------------------------------
        | Schedule
        |--------------------------------------------------------------------------
        | Gabungan live session dari instructor schedule dan mentoring session,
        | termasuk one-on-one.
        */
        Route::get('/schedules', [StudentScheduleController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | Upcoming Sessions
        |--------------------------------------------------------------------------
        | Ringkasan upcoming session untuk dashboard student.
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
