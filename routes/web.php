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
use App\Http\Controllers\Academic\InstructorTrackingController;

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
| Public Trial Registration
|--------------------------------------------------------------------------
*/
Route::get('/trial-class', [PublicTrialRegistrationController::class, 'index'])
    ->name('trial-class.index');

Route::post('/trial-class', [PublicTrialRegistrationController::class, 'store'])
    ->name('trial-class.store');

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
        Route::get('/', fn () => view('enrollment.index'))->name('enrollments.index');

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
    | Profile (default Breeze)
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});


require __DIR__.'/auth.php';