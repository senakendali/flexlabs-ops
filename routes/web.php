<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Program\ProgramController;
use App\Http\Controllers\Instructor\InstructorController;
use App\Http\Controllers\Equipment\EquipmentController;
use App\Http\Controllers\Equipment\EquipmentBorrowingController;
use App\Http\Controllers\Trial\TrialThemeController;
use App\Http\Controllers\Trial\TrialScheduleController;
use App\Http\Controllers\Trial\TrialParticipantController;
use App\Http\Controllers\Trial\PublicTrialRegistrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Operation\QuizController;
use App\Http\Controllers\Operation\QuizQuestionController;
use App\Http\Controllers\Operation\QuizOptionController;
use App\Http\Controllers\Operation\QuizPlayController;
use App\Http\Controllers\Operation\QuizLeaderboardController;
use App\Http\Controllers\Operation\MeetingMinuteController;
use App\Http\Controllers\Enrollment\BatchController;
use App\Http\Controllers\Enrollment\StudentController;
use App\Http\Controllers\Payment\OrderController;
use App\Http\Controllers\Payment\PaymentScheduleController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Payment\PublicPaymentController;
use App\Http\Controllers\Payment\XenditWebhookController;
use App\Http\Controllers\Sales\SalesDailyReportController;
use App\Http\Controllers\Sales\SalesPerformanceController;
use App\Http\Controllers\Academic\CurriculumController;
use App\Http\Controllers\Academic\AssignmentController;
use App\Http\Controllers\Academic\BatchAssignmentController;
use App\Http\Controllers\Academic\AssignmentSubmissionController;
use App\Http\Controllers\Academic\LearningQuizController;
use App\Http\Controllers\Academic\LearningQuizQuestionController;
use App\Http\Controllers\Academic\BatchLearningQuizController;
use App\Http\Controllers\Academic\LearningQuizAttemptController;
use App\Http\Controllers\Academic\InstructorAvailabilitySlotController;
use App\Http\Controllers\Academic\StudentMentoringSessionController;
use App\Http\Controllers\Academic\AnnouncementController;
use App\Http\Controllers\Academic\InstructorTrackingController;
use App\Http\Controllers\Academic\AssessmentTemplateController;
use App\Http\Controllers\Academic\AssessmentComponentController;
use App\Http\Controllers\Academic\AssessmentRubricController;
use App\Http\Controllers\Academic\AssessmentRubricCriteriaController;
use App\Http\Controllers\Academic\AssessmentRubricLevelController;
use App\Http\Controllers\Academic\AssessmentScoreController;
use App\Http\Controllers\Academic\ReportCardController;
use App\Http\Controllers\Academic\CertificateController;
use App\Http\Controllers\Inventory\AtkItemController;
use App\Http\Controllers\Inventory\AtkRequestController;
use App\Http\Controllers\Marketing\MarketingDashboardController;
use App\Http\Controllers\Marketing\MarketingPlanController;
use App\Http\Controllers\Marketing\MarketingCampaignController;
use App\Http\Controllers\Marketing\MarketingActivityController;
use App\Http\Controllers\Marketing\MarketingAdController;
use App\Http\Controllers\Marketing\MarketingEventController;
use App\Http\Controllers\Marketing\MarketingLeadSourceController;
use App\Http\Controllers\Marketing\MarketingReportController;
use App\Http\Controllers\Marketing\MarketingSetupCampaignController;
use App\Http\Controllers\Marketing\MarketingSetupAdController;
use App\Http\Controllers\Academic\InstructorScheduleController;
use App\Http\Controllers\PublicWorkshopController;
use App\Http\Controllers\Academic\WorkshopController;





/*
|--------------------------------------------------------------------------
| Public Route
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('dashboard');
});

/*
|--------------------------------------------------------------------------
| Public Certificate Verification
|--------------------------------------------------------------------------
*/
Route::get('/certificates/verify/{token}', [CertificateController::class, 'verify'])
    ->name('public.certificates.verify');


/*
|--------------------------------------------------------------------------
| Public Trial Registration
|--------------------------------------------------------------------------
*/
Route::get('/trial-class', [PublicTrialRegistrationController::class, 'index'])
    ->name('trial-class.index');

Route::post('/trial-class', [PublicTrialRegistrationController::class, 'store'])
    ->name('trial-class.store');


Route::get('/workshop', [PublicWorkshopController::class, 'index'])->name('workshop.index');
Route::get('/workshop/{slug}', [PublicWorkshopController::class, 'show'])->name('workshop.show');

/*
|--------------------------------------------------------------------------
| Public Payment
|--------------------------------------------------------------------------
*/
Route::get('/pay/{token}', [PublicPaymentController::class, 'show'])
    ->name('public.payments.show');


Route::post('/webhooks/xendit/invoice', [XenditWebhookController::class, 'handle'])
    ->name('webhooks.xendit.invoice');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Master Data
    |--------------------------------------------------------------------------
    */
    Route::prefix('programs')->name('programs.')->group(function () {
        Route::get('/', [ProgramController::class, 'index'])->name('index');
        Route::get('/{program}', [ProgramController::class, 'show'])->name('show');
        Route::post('/', [ProgramController::class, 'store'])->name('store');
        Route::put('/{program}', [ProgramController::class, 'update'])->name('update');
        Route::delete('/{program}', [ProgramController::class, 'destroy'])->name('destroy');
    });


   /*
    |--------------------------------------------------------------------------
    | Trial Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('trial')->name('trial-')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Trial Themes
        |--------------------------------------------------------------------------
        */
        Route::get('/themes', [TrialThemeController::class, 'index'])->name('themes.index');
        Route::get('/themes/{trialTheme}', [TrialThemeController::class, 'show'])->name('themes.show');
        Route::post('/themes', [TrialThemeController::class, 'store'])->name('themes.store');
        Route::put('/themes/{trialTheme}', [TrialThemeController::class, 'update'])->name('themes.update');
        Route::delete('/themes/{trialTheme}', [TrialThemeController::class, 'destroy'])->name('themes.destroy');

        /*
        |--------------------------------------------------------------------------
        | Trial Schedules
        |--------------------------------------------------------------------------
        */
        Route::get('/schedules', [TrialScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/schedules/{trialSchedule}', [TrialScheduleController::class, 'show'])->name('schedules.show');
        Route::post('/schedules', [TrialScheduleController::class, 'store'])->name('schedules.store');
        Route::put('/schedules/{trialSchedule}', [TrialScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{trialSchedule}', [TrialScheduleController::class, 'destroy'])->name('schedules.destroy');

        /*
        |--------------------------------------------------------------------------
        | Trial Participants
        |--------------------------------------------------------------------------
        */
        Route::get('/participants', [TrialParticipantController::class, 'index'])->name('participants.index');
        Route::get('/participants/{trialParticipant}', [TrialParticipantController::class, 'show'])->name('participants.show');
        Route::post('/participants', [TrialParticipantController::class, 'store'])->name('participants.store');
        Route::put('/participants/{trialParticipant}', [TrialParticipantController::class, 'update'])->name('participants.update');
        Route::delete('/participants/{trialParticipant}', [TrialParticipantController::class, 'destroy'])->name('participants.destroy');
    });


    /*
    |--------------------------------------------------------------------------
    | Enrollment
    |--------------------------------------------------------------------------
    */
    Route::prefix('enrollment')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->name('enrollments.index');

        /*
        |--------------------------------------------------------------------------
        | Batches
        |--------------------------------------------------------------------------
        */
        Route::prefix('batches')->name('batches.')->group(function () {
            Route::get('/', [BatchController::class, 'index'])->name('index');
            Route::get('/{batch}', [BatchController::class, 'show'])->name('show');
            Route::post('/', [BatchController::class, 'store'])->name('store');
            Route::put('/{batch}', [BatchController::class, 'update'])->name('update');
            Route::delete('/{batch}', [BatchController::class, 'destroy'])->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Students
        |--------------------------------------------------------------------------
        */
        Route::prefix('students')->name('students.')->group(function () {
            Route::get('/', [StudentController::class, 'index'])->name('index');
            Route::get('/{student}', [StudentController::class, 'show'])->name('show');
            Route::post('/', [StudentController::class, 'store'])->name('store');
            Route::put('/{student}', [StudentController::class, 'update'])->name('update');
            Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy');

            Route::post('/{student}/enroll', [StudentController::class, 'enroll'])
                ->name('enroll');
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */
    Route::prefix('payments')->group(function () {
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/{order}', [OrderController::class, 'show'])->name('show');
            Route::post('/', [OrderController::class, 'store'])->name('store');
            Route::put('/{order}', [OrderController::class, 'update'])->name('update');
            Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('schedules')->name('payment-schedules.')->group(function () {
            Route::get('/', [PaymentScheduleController::class, 'index'])->name('index');
            Route::get('/{paymentSchedule}', [PaymentScheduleController::class, 'show'])->name('show');
            Route::post('/', [PaymentScheduleController::class, 'store'])->name('store');
            Route::put('/{paymentSchedule}', [PaymentScheduleController::class, 'update'])->name('update');
            Route::delete('/{paymentSchedule}', [PaymentScheduleController::class, 'destroy'])->name('destroy');
        });

        Route::get('/', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/{payment}/invoice', [PaymentController::class, 'invoice'])->name('payments.invoice');
        Route::get('/{payment}', [PaymentController::class, 'show'])->whereNumber('payment')->name('payments.show');
        Route::put('/{payment}', [PaymentController::class, 'update'])->whereNumber('payment')->name('payments.update');
        Route::delete('/{payment}', [PaymentController::class, 'destroy'])->whereNumber('payment')->name('payments.destroy');
    });

   
    /*
    |--------------------------------------------------------------------------
    | Sales Tools (Reporting Only)
    |--------------------------------------------------------------------------
    */
    Route::prefix('sales-tools')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Daily Reports
        |--------------------------------------------------------------------------
        */
        Route::prefix('daily-reports')->name('sales-daily-reports.')->group(function () {
            Route::get('/', [SalesDailyReportController::class, 'index'])->name('index');
            Route::get('/create', [SalesDailyReportController::class, 'create'])->name('create');
            Route::post('/', [SalesDailyReportController::class, 'store'])->name('store');
            Route::get('/{salesDailyReport}', [SalesDailyReportController::class, 'show'])->name('show');
            Route::get('/{salesDailyReport}/edit', [SalesDailyReportController::class, 'edit'])->name('edit');
            Route::put('/{salesDailyReport}', [SalesDailyReportController::class, 'update'])->name('update');
            Route::delete('/{salesDailyReport}', [SalesDailyReportController::class, 'destroy'])->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Performance Dashboard
        |--------------------------------------------------------------------------
        */
        Route::prefix('performance')->name('sales-performance.')->group(function () {
            Route::get('/', [SalesPerformanceController::class, 'index'])->name('index');
            Route::get('/chart-data', [SalesPerformanceController::class, 'chartData'])->name('chart-data');
        });

        /*
        |--------------------------------------------------------------------------
        | Sales Orders
        |--------------------------------------------------------------------------
        */
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/create', [OrderController::class, 'create'])->name('create');
            Route::post('/', [OrderController::class, 'store'])->name('store');
            Route::get('/{order}', [OrderController::class, 'show'])->name('show');
            Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('edit');
            Route::put('/{order}', [OrderController::class, 'update'])->name('update');
            Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
        });

    });


    /*
    |--------------------------------------------------------------------------
    | Instructors
    |--------------------------------------------------------------------------
    */
    Route::prefix('instructors')->name('instructors.')->group(function () {
        Route::get('/', [InstructorController::class, 'index'])->name('index');
        Route::get('/{instructor}', [InstructorController::class, 'show'])->name('show');
        Route::post('/', [InstructorController::class, 'store'])->name('store');
        Route::put('/{instructor}', [InstructorController::class, 'update'])->name('update');
        Route::delete('/{instructor}', [InstructorController::class, 'destroy'])->name('destroy');
        Route::post('/{instructor}/assign-teaching-scope', [InstructorController::class, 'assignTeachingScope'])
        ->name('assign-teaching-scope');
    });


    /*
    |--------------------------------------------------------------------------
    | Equipment
    |--------------------------------------------------------------------------
    */
    Route::prefix('equipment')->name('equipment.')->group(function () {
        Route::get('/', [EquipmentController::class, 'index'])->name('index');
        Route::get('/{equipment}', [EquipmentController::class, 'show'])->name('show');
        Route::post('/', [EquipmentController::class, 'store'])->name('store');
        Route::put('/{equipment}', [EquipmentController::class, 'update'])->name('update');
        Route::delete('/{equipment}', [EquipmentController::class, 'destroy'])->name('destroy');

        Route::post('/{equipment}/borrow', [EquipmentBorrowingController::class, 'borrow'])->name('borrow');
        Route::post('/borrowings/{borrowing}/return', [EquipmentBorrowingController::class, 'returnEquipment'])->name('return');
    });

    /*
    |--------------------------------------------------------------------------
    | Operations - Gear Borrowing
    |--------------------------------------------------------------------------
    */
    Route::prefix('borrowings')->name('borrowings.')->group(function () {
        Route::get('/', [EquipmentBorrowingController::class, 'index'])->name('index');
        Route::get('/{borrowing}', [EquipmentBorrowingController::class, 'show'])->name('show');
        Route::post('/', [EquipmentBorrowingController::class, 'store'])->name('store');
        Route::post('/{borrowing}/return', [EquipmentBorrowingController::class, 'returnEquipment'])->name('return');
    });

    /*
    |--------------------------------------------------------------------------
    | Operations - Meeting Minutes / MOM
    |--------------------------------------------------------------------------
    */
    Route::prefix('operation/meeting-minutes')
        ->name('operation.meeting-minutes.')
        ->group(function () {
            Route::get('/', [MeetingMinuteController::class, 'index'])->name('index');
            Route::get('/create', [MeetingMinuteController::class, 'create'])->name('create');
            Route::post('/', [MeetingMinuteController::class, 'store'])->name('store');

            // Download PDF harus sebelum route show
            Route::get('/{meetingMinute}/download-pdf', [MeetingMinuteController::class, 'downloadPdf'])
                ->name('download-pdf');

            Route::get('/{meetingMinute}', [MeetingMinuteController::class, 'show'])->name('show');
            Route::get('/{meetingMinute}/edit', [MeetingMinuteController::class, 'edit'])->name('edit');
            Route::put('/{meetingMinute}', [MeetingMinuteController::class, 'update'])->name('update');
            Route::delete('/{meetingMinute}', [MeetingMinuteController::class, 'destroy'])->name('destroy');
        });

    Route::patch(
        'operation/meeting-minute-action-items/{actionItem}/status',
        [MeetingMinuteController::class, 'updateActionItemStatus']
    )->name('operation.meeting-minute-action-items.update-status');


    /*
    |--------------------------------------------------------------------------
    | Operations - Quiz Management
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth')->group(function () {
        Route::prefix('quiz')->name('quiz.')->group(function () {
            Route::get('/', [QuizController::class, 'index'])->name('index');
            Route::get('/{quiz}', [QuizController::class, 'show'])->name('show');
            Route::post('/', [QuizController::class, 'store'])->name('store');
            Route::put('/{quiz}', [QuizController::class, 'update'])->name('update');
            Route::delete('/{quiz}', [QuizController::class, 'destroy'])->name('destroy');

            Route::get('/{quiz}/questions', [QuizQuestionController::class, 'index'])->name('questions.index');
            Route::post('/{quiz}/questions', [QuizQuestionController::class, 'store'])->name('questions.store');

            Route::get('/questions/{question}', [QuizQuestionController::class, 'show'])->name('questions.show');
            Route::put('/questions/{question}', [QuizQuestionController::class, 'update'])->name('questions.update');
            Route::delete('/questions/{question}', [QuizQuestionController::class, 'destroy'])->name('questions.destroy');

            Route::get('/questions/{question}/options', [QuizOptionController::class, 'index'])->name('options.index');
            Route::post('/questions/{question}/options', [QuizOptionController::class, 'store'])->name('options.store');

            Route::get('/options/{option}', [QuizOptionController::class, 'show'])->name('options.show');
            Route::put('/options/{option}', [QuizOptionController::class, 'update'])->name('options.update');
            Route::delete('/options/{option}', [QuizOptionController::class, 'destroy'])->name('options.destroy');

            //Route::get('/{quiz}/play', [QuizPlayController::class, 'show'])->name('play');
            Route::get('/{quiz}/leaderboard', [QuizLeaderboardController::class, 'index'])->name('leaderboard');
            
        });
    });

    

    Route::prefix('marketing/reports')->name('marketing.reports.')->group(function () {
        Route::get('/', [MarketingReportController::class, 'index'])->name('index');
        Route::get('/create', [MarketingReportController::class, 'create'])->name('create');
        Route::post('/', [MarketingReportController::class, 'store'])->name('store');
        Route::get('/{report}', [MarketingReportController::class, 'show'])->name('show');
        Route::get('/{report}/edit', [MarketingReportController::class, 'edit'])->name('edit');
        Route::put('/{report}', [MarketingReportController::class, 'update'])->name('update');
        Route::delete('/{report}', [MarketingReportController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('marketing/setup')->name('marketing.setup.')->group(function () {
        Route::prefix('campaigns')->name('campaigns.')->group(function () {
            Route::get('/', [MarketingSetupCampaignController::class, 'index'])->name('index');
            Route::post('/', [MarketingSetupCampaignController::class, 'store'])->name('store');
            Route::put('/{campaign}', [MarketingSetupCampaignController::class, 'update'])->name('update');
            Route::delete('/{campaign}', [MarketingSetupCampaignController::class, 'destroy'])->name('destroy');
            Route::get('/options/by-period', [MarketingSetupCampaignController::class, 'options'])->name('options');
        });

        Route::prefix('ads')->name('ads.')->group(function () {
            Route::get('/', [MarketingSetupAdController::class, 'index'])->name('index');
            Route::post('/', [MarketingSetupAdController::class, 'store'])->name('store');
            Route::put('/{ad}', [MarketingSetupAdController::class, 'update'])->name('update');
            Route::delete('/{ad}', [MarketingSetupAdController::class, 'destroy'])->name('destroy');
            Route::get('/options/by-period', [MarketingSetupAdController::class, 'options'])->name('options');
        });
    });

    

    /*
    |--------------------------------------------------------------------------
    | Inventory - ATK
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/atk-items', [AtkItemController::class, 'index'])->name('atk-items.index');
        Route::post('/atk-items', [AtkItemController::class, 'store'])->name('atk-items.store');
        Route::put('/atk-items/{atkItem}', [AtkItemController::class, 'update'])->name('atk-items.update');
        Route::delete('/atk-items/{atkItem}', [AtkItemController::class, 'destroy'])->name('atk-items.destroy');

        Route::get('/atk-requests', [AtkRequestController::class, 'index'])->name('atk-requests.index');
        Route::post('/atk-requests', [AtkRequestController::class, 'store'])->name('atk-requests.store');
        Route::post('/atk-requests/{atkRequest}/approve', [AtkRequestController::class, 'approve'])->name('atk-requests.approve');
        Route::post('/atk-requests/{atkRequest}/reject', [AtkRequestController::class, 'reject'])->name('atk-requests.reject');
        Route::post('/atk-requests/{atkRequest}/cancel', [AtkRequestController::class, 'cancel'])->name('atk-requests.cancel');
    });

    //Route::get('/play-quiz/{quiz}', [QuizPlayController::class, 'show'])->name('quiz.play');
    Route::get('quiz/{quiz}/play', [QuizPlayController::class, 'show'])->name('quiz.play');

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    */
    Route::prefix('monitoring')->group(function () {
        Route::get('/', fn () => view('monitoring.index'))->name('monitoring.index');
    });


    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->group(function () {
        Route::get('/', fn () => view('settings.index'))->name('settings.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Curriculum
    |--------------------------------------------------------------------------
    */
    Route::prefix('curriculum')->name('curriculum.')->group(function () {
        Route::get('/', [CurriculumController::class, 'index'])->name('index');

        Route::post('/stages', [CurriculumController::class, 'storeStage'])->name('stages.store');
        Route::put('/stages/{stage}', [CurriculumController::class, 'updateStage'])->name('stages.update');
        Route::delete('/stages/{stage}', [CurriculumController::class, 'destroyStage'])->name('stages.destroy');

        Route::post('/modules', [CurriculumController::class, 'storeModule'])->name('modules.store');
        Route::put('/modules/{module}', [CurriculumController::class, 'updateModule'])->name('modules.update');
        Route::delete('/modules/{module}', [CurriculumController::class, 'destroyModule'])->name('modules.destroy');

        Route::post('/topics', [CurriculumController::class, 'storeTopic'])->name('topics.store');
        Route::put('/topics/{topic}', [CurriculumController::class, 'updateTopic'])->name('topics.update');
        Route::delete('/topics/{topic}', [CurriculumController::class, 'destroyTopic'])->name('topics.destroy');

        Route::post('/sub-topics', [CurriculumController::class, 'storeSubTopic'])->name('sub-topics.store');
        Route::put('/sub-topics/{subTopic}', [CurriculumController::class, 'updateSubTopic'])->name('sub-topics.update');
        Route::delete('/sub-topics/{subTopic}', [CurriculumController::class, 'destroySubTopic'])->name('sub-topics.destroy');

        
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Assignments
    |--------------------------------------------------------------------------
    */
    Route::prefix('assignments')->name('assignments.')->group(function () {
        Route::get('/', [AssignmentController::class, 'index'])->name('index');
        Route::post('/', [AssignmentController::class, 'store'])->name('store');
        Route::put('/{assignment}', [AssignmentController::class, 'update'])->name('update');
        Route::delete('/{assignment}', [AssignmentController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Assignment Submissions
    |--------------------------------------------------------------------------
    */
    Route::prefix('assignment-submissions')->name('assignment-submissions.')->group(function () {
        Route::get('/', [AssignmentSubmissionController::class, 'index'])->name('index');

        Route::post('/{assignmentSubmission}/review', [AssignmentSubmissionController::class, 'review'])
            ->name('review');

        Route::post('/{assignmentSubmission}/return-revision', [AssignmentSubmissionController::class, 'returnRevision'])
            ->name('return-revision');

        Route::post('/{assignmentSubmission}/mark-submitted', [AssignmentSubmissionController::class, 'markSubmitted'])
            ->name('mark-submitted');

        Route::delete('/{assignmentSubmission}', [AssignmentSubmissionController::class, 'destroy'])
            ->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Learning Quizzes
    |--------------------------------------------------------------------------
    */
    Route::prefix('learning-quizzes')->name('learning-quizzes.')->group(function () {
        Route::get('/', [LearningQuizController::class, 'index'])->name('index');
        Route::post('/', [LearningQuizController::class, 'store'])->name('store');
        Route::put('/{learningQuiz}', [LearningQuizController::class, 'update'])->name('update');
        Route::delete('/{learningQuiz}', [LearningQuizController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Learning Quiz Questions & Options
    |--------------------------------------------------------------------------
    */
    Route::prefix('learning-quizzes/{learningQuiz}/questions')
        ->name('learning-quizzes.questions.')
        ->group(function () {
            Route::get('/', [LearningQuizQuestionController::class, 'index'])
                ->name('index');

            Route::post('/', [LearningQuizQuestionController::class, 'storeQuestion'])
                ->name('store');

            Route::put('/{question}', [LearningQuizQuestionController::class, 'updateQuestion'])
                ->name('update');

            Route::delete('/{question}', [LearningQuizQuestionController::class, 'destroyQuestion'])
                ->name('destroy');

            Route::post('/{question}/options', [LearningQuizQuestionController::class, 'storeOption'])
                ->name('options.store');

            Route::put('/{question}/options/{option}', [LearningQuizQuestionController::class, 'updateOption'])
                ->name('options.update');

            Route::delete('/{question}/options/{option}', [LearningQuizQuestionController::class, 'destroyOption'])
                ->name('options.destroy');
        });


    /*
    |--------------------------------------------------------------------------
    | Academic - Batch Assignments
    |--------------------------------------------------------------------------
    */
    Route::prefix('batch-assignments')->name('batch-assignments.')->group(function () {
        Route::get('/', [BatchAssignmentController::class, 'index'])->name('index');
        Route::post('/', [BatchAssignmentController::class, 'store'])->name('store');
        Route::put('/{batchAssignment}', [BatchAssignmentController::class, 'update'])->name('update');
        Route::delete('/{batchAssignment}', [BatchAssignmentController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Batch Learning Quizzes
    |--------------------------------------------------------------------------
    */
    Route::prefix('batch-learning-quizzes')->name('batch-learning-quizzes.')->group(function () {
        Route::get('/', [BatchLearningQuizController::class, 'index'])->name('index');
        Route::post('/', [BatchLearningQuizController::class, 'store'])->name('store');
        Route::put('/{batchLearningQuiz}', [BatchLearningQuizController::class, 'update'])->name('update');
        Route::delete('/{batchLearningQuiz}', [BatchLearningQuizController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Learning Quiz Attempts / Results
    |--------------------------------------------------------------------------
    */
    Route::prefix('learning-quiz-attempts')->name('learning-quiz-attempts.')->group(function () {
        Route::get('/', [LearningQuizAttemptController::class, 'index'])
            ->name('index');

        Route::get('/{attempt}', [LearningQuizAttemptController::class, 'show'])
            ->name('show');

        Route::post('/{attempt}/grade', [LearningQuizAttemptController::class, 'gradeAttempt'])
            ->name('grade');

        Route::post('/{attempt}/status', [LearningQuizAttemptController::class, 'updateStatus'])
            ->name('status');

        Route::post('/{attempt}/answers/{answer}/grade', [LearningQuizAttemptController::class, 'gradeAnswer'])
            ->name('answers.grade');

        Route::delete('/{attempt}', [LearningQuizAttemptController::class, 'destroy'])
            ->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Instructor Availability
    |--------------------------------------------------------------------------
    */

    Route::prefix('academic')->name('academic.')->group(function () {
        Route::prefix('instructor-availability')->name('instructor-availability.')->group(function () {
            Route::get('/', [InstructorAvailabilitySlotController::class, 'index'])->name('index');
            Route::get('/{instructorAvailabilitySlot}', [InstructorAvailabilitySlotController::class, 'show'])->name('show');
            Route::post('/', [InstructorAvailabilitySlotController::class, 'store'])->name('store');
            Route::put('/{instructorAvailabilitySlot}', [InstructorAvailabilitySlotController::class, 'update'])->name('update');
            Route::delete('/{instructorAvailabilitySlot}', [InstructorAvailabilitySlotController::class, 'destroy'])->name('destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Student Mentoring Sessions
    |--------------------------------------------------------------------------
    */
    Route::prefix('academic')->name('academic.')->group(function () {
        Route::prefix('mentoring-sessions')->name('mentoring-sessions.')->group(function () {
            Route::get('/', [StudentMentoringSessionController::class, 'index'])->name('index');
            Route::get('/{studentMentoringSession}', [StudentMentoringSessionController::class, 'show'])->name('show');

            Route::patch('/{studentMentoringSession}/approve', [StudentMentoringSessionController::class, 'approve'])->name('approve');
            Route::patch('/{studentMentoringSession}/reject', [StudentMentoringSessionController::class, 'reject'])->name('reject');
            Route::patch('/{studentMentoringSession}/complete', [StudentMentoringSessionController::class, 'complete'])->name('complete');
            Route::patch('/{studentMentoringSession}/cancel', [StudentMentoringSessionController::class, 'cancel'])->name('cancel');
            Route::patch('/{studentMentoringSession}/meeting-url', [StudentMentoringSessionController::class, 'updateMeetingUrl'])->name('meeting-url');
            Route::patch('/{studentMentoringSession}/status', [StudentMentoringSessionController::class, 'updateStatus'])->name('status');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Assessment Templates, Components, Rubrics, Scores, Report Cards, Certificates
    |--------------------------------------------------------------------------
    */
    Route::prefix('academic')->name('academic.')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Assessment Templates
        |--------------------------------------------------------------------------
        */
        Route::prefix('assessment-templates')
            ->name('assessment-templates.')
            ->group(function () {
                Route::get('/', [AssessmentTemplateController::class, 'index'])
                    ->name('index');

                Route::get('/create', [AssessmentTemplateController::class, 'create'])
                    ->name('create');

                Route::post('/', [AssessmentTemplateController::class, 'store'])
                    ->name('store');

                Route::get('/{assessmentTemplate}/edit', [AssessmentTemplateController::class, 'edit'])
                    ->whereNumber('assessmentTemplate')
                    ->name('edit');

                Route::put('/{assessmentTemplate}', [AssessmentTemplateController::class, 'update'])
                    ->whereNumber('assessmentTemplate')
                    ->name('update');

                Route::delete('/{assessmentTemplate}', [AssessmentTemplateController::class, 'destroy'])
                    ->whereNumber('assessmentTemplate')
                    ->name('destroy');

                /*
                |--------------------------------------------------------------------------
                | Assessment Template Components + Rubrics
                |--------------------------------------------------------------------------
                */
                Route::prefix('/{assessmentTemplate}/components')
                    ->whereNumber('assessmentTemplate')
                    ->name('components.')
                    ->group(function () {
                        Route::post('/', [AssessmentComponentController::class, 'store'])
                            ->name('store');

                        /*
                        |--------------------------------------------------------------------------
                        | Component Rubric
                        |--------------------------------------------------------------------------
                        |
                        | Final route names:
                        | - academic.assessment-templates.components.rubric.show
                        | - academic.assessment-templates.components.rubric.store
                        | - academic.assessment-templates.components.rubric.update
                        | - academic.assessment-templates.components.rubric.activate
                        |
                        */
                        Route::prefix('/{component}/rubric')
                            ->whereNumber('component')
                            ->name('rubric.')
                            ->group(function () {
                                Route::get('/', [AssessmentRubricController::class, 'show'])
                                    ->name('show');

                                Route::post('/', [AssessmentRubricController::class, 'store'])
                                    ->name('store');

                                Route::put('/{rubric}', [AssessmentRubricController::class, 'update'])
                                    ->whereNumber('rubric')
                                    ->name('update');

                                Route::patch('/{rubric}/activate', [AssessmentRubricController::class, 'activate'])
                                    ->whereNumber('rubric')
                                    ->name('activate');

                                /*
                                |--------------------------------------------------------------------------
                                | Rubric Criteria
                                |--------------------------------------------------------------------------
                                */
                                Route::post('/{rubric}/criteria', [AssessmentRubricCriteriaController::class, 'store'])
                                    ->whereNumber('rubric')
                                    ->name('criteria.store');

                                Route::put('/{rubric}/criteria/{criterion}', [AssessmentRubricCriteriaController::class, 'update'])
                                    ->whereNumber('rubric')
                                    ->whereNumber('criterion')
                                    ->name('criteria.update');

                                Route::delete('/{rubric}/criteria/{criterion}', [AssessmentRubricCriteriaController::class, 'destroy'])
                                    ->whereNumber('rubric')
                                    ->whereNumber('criterion')
                                    ->name('criteria.destroy');

                                /*
                                |--------------------------------------------------------------------------
                                | Rubric Levels
                                |--------------------------------------------------------------------------
                                */
                                Route::post('/{rubric}/levels', [AssessmentRubricLevelController::class, 'store'])
                                    ->whereNumber('rubric')
                                    ->name('levels.store');

                                Route::put('/{rubric}/levels/{level}', [AssessmentRubricLevelController::class, 'update'])
                                    ->whereNumber('rubric')
                                    ->whereNumber('level')
                                    ->name('levels.update');

                                Route::delete('/{rubric}/levels/{level}', [AssessmentRubricLevelController::class, 'destroy'])
                                    ->whereNumber('rubric')
                                    ->whereNumber('level')
                                    ->name('levels.destroy');
                            });

                        Route::put('/{component}', [AssessmentComponentController::class, 'update'])
                            ->whereNumber('component')
                            ->name('update');

                        Route::delete('/{component}', [AssessmentComponentController::class, 'destroy'])
                            ->whereNumber('component')
                            ->name('destroy');
                    });

                /*
                |--------------------------------------------------------------------------
                | Show Template
                |--------------------------------------------------------------------------
                | Harus di bawah nested components supaya /{assessmentTemplate}/components
                | tidak kebaca sebagai show template.
                */
                Route::get('/{assessmentTemplate}', [AssessmentTemplateController::class, 'show'])
                    ->whereNumber('assessmentTemplate')
                    ->name('show');
            });

        /*
        |--------------------------------------------------------------------------
        | Assessment Scores
        |--------------------------------------------------------------------------
        */
        Route::prefix('assessment-scores')
            ->name('assessment-scores.')
            ->group(function () {
                Route::get('/', [AssessmentScoreController::class, 'index'])
                    ->name('index');

                Route::get('/preview', [AssessmentScoreController::class, 'preview'])
                    ->name('preview');

                Route::post('/', [AssessmentScoreController::class, 'store'])
                    ->name('store');

                Route::post('/bulk', [AssessmentScoreController::class, 'bulkStore'])
                    ->name('bulk-store');
            });

        /*
        |--------------------------------------------------------------------------
        | Report Cards
        |--------------------------------------------------------------------------
        */
        Route::prefix('report-cards')
            ->name('report-cards.')
            ->group(function () {
                Route::get('/', [ReportCardController::class, 'index'])
                    ->name('index');

                Route::post('/generate', [ReportCardController::class, 'generate'])
                    ->name('generate');

                Route::post('/{reportCard}/regenerate', [ReportCardController::class, 'regenerate'])
                    ->whereNumber('reportCard')
                    ->name('regenerate');

                Route::post('/{reportCard}/publish', [ReportCardController::class, 'publish'])
                    ->whereNumber('reportCard')
                    ->name('publish');

                Route::post('/{reportCard}/cancel', [ReportCardController::class, 'cancel'])
                    ->whereNumber('reportCard')
                    ->name('cancel');

                Route::get('/{reportCard}', [ReportCardController::class, 'show'])
                    ->whereNumber('reportCard')
                    ->name('show');
            });

        /*
        |--------------------------------------------------------------------------
        | Certificates
        |--------------------------------------------------------------------------
        */
        Route::prefix('certificates')
            ->name('certificates.')
            ->group(function () {
                Route::get('/', [CertificateController::class, 'index'])
                    ->name('index');

                Route::post('/issue', [CertificateController::class, 'issue'])
                    ->name('issue');

                Route::post('/{certificate}/reissue', [CertificateController::class, 'reissue'])
                    ->whereNumber('certificate')
                    ->name('reissue');

                Route::post('/{certificate}/revoke', [CertificateController::class, 'revoke'])
                    ->whereNumber('certificate')
                    ->name('revoke');

                Route::post('/{certificate}/regenerate-qr', [CertificateController::class, 'regenerateQr'])
                    ->whereNumber('certificate')
                    ->name('regenerate-qr');

                Route::post('/{certificate}/generate-image', [CertificateController::class, 'generateImage'])
                    ->whereNumber('certificate')
                    ->name('generate-image');

                Route::get('/{certificate}/download-image', [CertificateController::class, 'downloadImage'])
                    ->whereNumber('certificate')
                    ->name('download-image');

                Route::get('/{certificate}/download-pdf', [CertificateController::class, 'downloadPdf'])
                    ->whereNumber('certificate')
                    ->name('download-pdf');

                Route::get('/{certificate}', [CertificateController::class, 'show'])
                    ->whereNumber('certificate')
                    ->name('show');
            });
    });

    /*
    |--------------------------------------------------------------------------
    | Public Certificate Verification
    |--------------------------------------------------------------------------
    */
    Route::get('/certificates/verify/{token}', [CertificateController::class, 'verify'])
        ->name('public.certificates.verify');

    /*
    |--------------------------------------------------------------------------
    | Announcements
    |--------------------------------------------------------------------------
    */

    Route::prefix('academic')->name('academic.')->group(function () {
        Route::prefix('announcements')
            ->name('announcements.')
            ->controller(AnnouncementController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::get('/{announcement}', 'show')
                    ->whereNumber('announcement')
                    ->name('show');

                Route::post('/', 'store')->name('store');

                Route::put('/{announcement}', 'update')
                    ->whereNumber('announcement')
                    ->name('update');

                Route::delete('/{announcement}', 'destroy')
                    ->whereNumber('announcement')
                    ->name('destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Workshops
    |--------------------------------------------------------------------------
    */
    Route::prefix('academic/workshops')->name('academic.workshops.')->group(function () {
        Route::get('/', [WorkshopController::class, 'index'])->name('index');
        Route::get('/create', [WorkshopController::class, 'create'])->name('create');
        Route::post('/', [WorkshopController::class, 'store'])->name('store');

        Route::get('/{workshop}', [WorkshopController::class, 'show'])->name('show');
        Route::get('/{workshop}/edit', [WorkshopController::class, 'edit'])->name('edit');
        Route::put('/{workshop}', [WorkshopController::class, 'update'])->name('update');
        Route::delete('/{workshop}', [WorkshopController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Instructor Schedules
    |--------------------------------------------------------------------------
    */
    Route::prefix('instructor-schedules')->name('instructor-schedules.')->group(function () {
        Route::get('/', [InstructorScheduleController::class, 'index'])->name('index');
        Route::get('/create', [InstructorScheduleController::class, 'create'])->name('create');
        Route::post('/', [InstructorScheduleController::class, 'store'])->name('store');

        Route::get('/{instructorSchedule}/edit', [InstructorScheduleController::class, 'edit'])->name('edit');
        Route::put('/{instructorSchedule}', [InstructorScheduleController::class, 'update'])->name('update');
        Route::delete('/{instructorSchedule}', [InstructorScheduleController::class, 'destroy'])->name('destroy');

        Route::get('/{instructorSchedule}', [InstructorScheduleController::class, 'show'])->name('show');
    });

     /*
    |--------------------------------------------------------------------------
    | Operations - General Affairs
    |--------------------------------------------------------------------------
    */

    Route::prefix('operations')->group(function () {

        // Internal Memo
        Route::resource('internal-memos', InternalMemoController::class);

        /*
        |--------------------------------------------------------------------------
        | Operations - Inventory
        |--------------------------------------------------------------------------
        */

        // Equipment (Master)
        Route::resource('equipments', EquipmentController::class);

        // Borrowing (Pinjam Barang)
        Route::resource('borrowings', BorrowingController::class);

        /*
        |--------------------------------------------------------------------------
        | Operations - Requests
        |--------------------------------------------------------------------------
        */

        // ATK Request
        Route::resource('atk-requests', AtkRequestController::class);
    });


    /*
    |--------------------------------------------------------------------------
    | Academic - Instructor Tracking
    |--------------------------------------------------------------------------
    */
    Route::prefix('instructor-tracking')->name('instructor-tracking.')->group(function () {
        Route::get('/', [InstructorTrackingController::class, 'index'])->name('index');

        // session flow
        Route::post('/start', [InstructorTrackingController::class, 'startSession'])->name('start');
        Route::post('/{session}/end', [InstructorTrackingController::class, 'endSession'])->name('end');

        // checklist sub topic
        Route::post('/{session}/checklist', [InstructorTrackingController::class, 'updateChecklist'])->name('checklist');

        // logs
        Route::post('/{session}/logs', [InstructorTrackingController::class, 'storeLog'])->name('logs.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Marketing
    |--------------------------------------------------------------------------
    */
    Route::prefix('marketing')->name('marketing.')->group(function () {
        Route::get('/dashboard', [MarketingDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/', [MarketingPlanController::class, 'index'])->name('index');
            Route::post('/', [MarketingPlanController::class, 'store'])->name('store');
            Route::put('/{marketingPlan}', [MarketingPlanController::class, 'update'])->name('update');
            Route::delete('/{marketingPlan}', [MarketingPlanController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('campaigns')->name('campaigns.')->group(function () {
            Route::get('/', [MarketingCampaignController::class, 'index'])->name('index');
            Route::post('/', [MarketingCampaignController::class, 'store'])->name('store');
            Route::put('/{marketingCampaign}', [MarketingCampaignController::class, 'update'])->name('update');
            Route::delete('/{marketingCampaign}', [MarketingCampaignController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('activities')->name('activities.')->group(function () {
            Route::get('/', [MarketingActivityController::class, 'index'])->name('index');
            Route::post('/', [MarketingActivityController::class, 'store'])->name('store');
            Route::put('/{marketingActivity}', [MarketingActivityController::class, 'update'])->name('update');
            Route::delete('/{marketingActivity}', [MarketingActivityController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('ads')->name('ads.')->group(function () {
            Route::get('/', [MarketingAdController::class, 'index'])->name('index');
            Route::post('/', [MarketingAdController::class, 'store'])->name('store');
            Route::put('/{marketingAd}', [MarketingAdController::class, 'update'])->name('update');
            Route::delete('/{marketingAd}', [MarketingAdController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [MarketingEventController::class, 'index'])->name('index');
            Route::post('/', [MarketingEventController::class, 'store'])->name('store');
            Route::put('/{marketingEvent}', [MarketingEventController::class, 'update'])->name('update');
            Route::delete('/{marketingEvent}', [MarketingEventController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('leads')->name('leads.')->group(function () {
            Route::get('/', [MarketingLeadSourceController::class, 'index'])->name('index');
            Route::post('/', [MarketingLeadSourceController::class, 'store'])->name('store');
            Route::put('/{marketingLeadSource}', [MarketingLeadSourceController::class, 'update'])->name('update');
            Route::delete('/{marketingLeadSource}', [MarketingLeadSourceController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::post('/sync-period-data', [MarketingReportController::class, 'syncPeriodData'])->name('sync-period-data');

            Route::get('/', [MarketingReportController::class, 'index'])->name('index');
            Route::get('/create', [MarketingReportController::class, 'create'])->name('create');
            Route::post('/', [MarketingReportController::class, 'store'])->name('store');
            Route::get('/{marketingReport}', [MarketingReportController::class, 'show'])->name('show');
            Route::get('/{marketingReport}/edit', [MarketingReportController::class, 'edit'])->name('edit');
            Route::put('/{marketingReport}', [MarketingReportController::class, 'update'])->name('update');
            Route::delete('/{marketingReport}', [MarketingReportController::class, 'destroy'])->name('destroy');
        });
    });
    


    /*
    |--------------------------------------------------------------------------
    | Profile (default Breeze)
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});


require __DIR__.'/auth.php';