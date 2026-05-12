<?php

use App\Http\Controllers\Api\Lms\LmsCommunityController;
use App\Http\Controllers\Api\Lms\StudentAnnouncementController;
use App\Http\Controllers\Api\Lms\StudentAssignmentController;
use App\Http\Controllers\Api\Lms\StudentAuthController;
use App\Http\Controllers\Api\Lms\StudentCourseController;
use App\Http\Controllers\Api\Lms\StudentDashboardController;
use App\Http\Controllers\Api\Lms\StudentLearningController;
use App\Http\Controllers\Api\Lms\StudentLearningNoteController;
use App\Http\Controllers\Api\Lms\StudentMentoringController;
use App\Http\Controllers\Api\Lms\StudentQuizController;
use App\Http\Controllers\Api\Lms\StudentScheduleController;
use App\Http\Controllers\Api\Lms\StudentUpcomingSessionController;
use App\Http\Controllers\Api\Lms\StudentAcademicDocumentController;
use App\Http\Controllers\Api\Lms\StudentNotificationController;
use App\Http\Controllers\Api\Lms\Student\MrPioneerController;
use App\Http\Controllers\Api\Lms\Student\StudentProfileController;
use App\Http\Controllers\Api\Lms\Student\StudentSearchController;
use App\Http\Controllers\Api\Lms\StudentGradeController;
use App\Http\Controllers\Api\PublicQuizController;
use App\Models\Student;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
    | Public Student Avatar Proxy
    |--------------------------------------------------------------------------
    |
    | Dipakai Flutter Web untuk load profile picture tanpa kena CORS static
    | file /storage. URL:
    |
    | GET /api/lms/student/avatar/{student}
    |
    */
    Route::get('/avatar/{student}', function (Student $student) {
        $studentArray = $student->toArray();

        $avatarUrl = $student->avatar_url
            ?? data_get($studentArray, 'avatar_url')
            ?? data_get($studentArray, 'avatarUrl')
            ?? data_get($studentArray, 'profile_photo_path')
            ?? data_get($studentArray, 'profilePhotoPath')
            ?? data_get($studentArray, 'photo_url')
            ?? data_get($studentArray, 'photoUrl')
            ?? data_get($studentArray, 'image_url')
            ?? data_get($studentArray, 'imageUrl');

        abort_if(!$avatarUrl, 404);

        $avatarUrl = trim($avatarUrl);

        /*
        |--------------------------------------------------------------------------
        | Normalize Path
        |--------------------------------------------------------------------------
        |
        | Support format:
        | /storage/students/profile-photos/file.jpg
        | storage/students/profile-photos/file.jpg
        | students/profile-photos/file.jpg
        | http://127.0.0.1:8007/storage/students/profile-photos/file.jpg
        |
        */
        if (str_starts_with($avatarUrl, 'http://') || str_starts_with($avatarUrl, 'https://')) {
            $parsedPath = parse_url($avatarUrl, PHP_URL_PATH);
            $path = $parsedPath ?: $avatarUrl;
        } else {
            $path = $avatarUrl;
        }

        $path = str_replace('\\', '/', $path);
        $path = str_replace('/storage/', '', $path);
        $path = str_replace('storage/', '', $path);
        $path = ltrim($path, '/');

        abort_if(!$path, 404);
        abort_if(!Storage::disk('public')->exists($path), 404);

        $file = Storage::disk('public')->get($path);
        $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';

        return Response::make($file, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=3600',
            'Access-Control-Allow-Origin' => request()->headers->get('origin', '*'),
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept',
        ]);
    })->whereNumber('student');

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
        Route::get('/courses/{slug}/instructor', [StudentCourseController::class, 'instructor']);

        /*
        |--------------------------------------------------------------------------
        | Learning
        |--------------------------------------------------------------------------
        */
        Route::get('/learn/{courseSlug}/{lessonSlug}', [StudentLearningController::class, 'show']);
        Route::post('/learn/{courseSlug}/{lessonSlug}/progress', [StudentLearningController::class, 'saveProgress']);

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */
        Route::get('/notifications', [StudentNotificationController::class, 'index']);
        Route::patch('/notifications/read-all', [StudentNotificationController::class, 'markAllAsRead']);
        Route::patch('/notifications/{notificationId}/read', [StudentNotificationController::class, 'markAsRead']);

        /*
        |--------------------------------------------------------------------------
        | Mr. Pioneer
        |--------------------------------------------------------------------------
        */
        Route::post('/mr-pioneer/ask', [MrPioneerController::class, 'ask']);

        /*
        |--------------------------------------------------------------------------
        | Learning Notes
        |--------------------------------------------------------------------------
        */
        Route::get('/notes', [StudentLearningNoteController::class, 'index']);

        Route::get('/notes/{note}', [StudentLearningNoteController::class, 'show'])
            ->whereNumber('note');

        Route::put('/notes/{note}', [StudentLearningNoteController::class, 'update'])
            ->whereNumber('note');

        Route::delete('/notes/{note}', [StudentLearningNoteController::class, 'destroy'])
            ->whereNumber('note');

        Route::patch('/notes/{note}/archive', [StudentLearningNoteController::class, 'archive'])
            ->whereNumber('note');

        Route::patch('/notes/{note}/restore', [StudentLearningNoteController::class, 'restore'])
            ->whereNumber('note');

        Route::post('/learn/{courseSlug}/{lessonSlug}/notes', [StudentLearningNoteController::class, 'store']);

        /*
        |--------------------------------------------------------------------------
        | Student Profile
        |--------------------------------------------------------------------------
        */
        Route::get('/profile', [StudentProfileController::class, 'show']);
        Route::put('/profile', [StudentProfileController::class, 'update']);
        Route::post('/profile/photo', [StudentProfileController::class, 'updatePhoto']);
        Route::post('/profile/password', [StudentProfileController::class, 'changePassword']);
        Route::put('/profile/preferences', [StudentProfileController::class, 'updatePreferences']);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        Route::get('/search', [StudentSearchController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | Academic Documents
        |--------------------------------------------------------------------------
        */
        Route::get('/academic-documents', [StudentAcademicDocumentController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | Grades
        |--------------------------------------------------------------------------
        */
        Route::get('/grades', [StudentGradeController::class, 'index']);

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
        | Community / Pioneer Hub
        |--------------------------------------------------------------------------
        */
        Route::prefix('community')->group(function () {
            Route::get('/home', [LmsCommunityController::class, 'home']);
            Route::get('/channels', [LmsCommunityController::class, 'channels']);

            Route::get('/channels/{channel}/posts', [LmsCommunityController::class, 'posts'])
                ->whereNumber('channel');

            Route::post('/channels/{channel}/posts', [LmsCommunityController::class, 'storePost'])
                ->whereNumber('channel');

            Route::get('/posts/{post}', [LmsCommunityController::class, 'showPost'])
                ->whereNumber('post');

            Route::post('/posts/{post}/comments', [LmsCommunityController::class, 'storeComment'])
                ->whereNumber('post');

            Route::post('/posts/{post}/solve', [LmsCommunityController::class, 'markAsSolved'])
                ->whereNumber('post');
        });

        /*
        |--------------------------------------------------------------------------
        | Schedule
        |--------------------------------------------------------------------------
        */
        Route::get('/schedules', [StudentScheduleController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | Upcoming Sessions
        |--------------------------------------------------------------------------
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