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
use App\Http\Controllers\Academic\StudentAttendanceController;
use App\Http\Controllers\Academic\AssessmentScoreController;
use App\Http\Controllers\Academic\ReportCardController;
use App\Http\Controllers\Academic\CertificateController;
use App\Http\Controllers\Academic\PublicLearningMaterialController;
use App\Http\Controllers\Academic\PublicLearningMaterialBlockController;
use App\Http\Controllers\Academic\PublicLearningMaterialImageController;
use App\Http\Controllers\Academic\PublicLearningMaterialPageController;
use App\Http\Controllers\Academic\LearningVideoController;
use App\Http\Controllers\Academic\StudentProgressMonitoringController;
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
use App\Http\Controllers\Academic\WorkshopScheduleController;
use App\Http\Controllers\Academic\WorkshopParticipantController;
use App\Http\Controllers\Academic\AcademicDashboardController;
use App\Http\Controllers\Settings\UserManagementController;
use App\Http\Controllers\PublicEventLeadController;



/*
|--------------------------------------------------------------------------
| Public Workshop & Webinar Routes
|--------------------------------------------------------------------------
|
| Production:
| - https://workshop.flexlabs.co.id
| - https://workshop.flexlabs.co.id/{slug}
| - https://webinar.flexlabs.co.id
| - https://webinar.flexlabs.co.id/{slug}
|
| Local:
| - http://127.0.0.1:8000/workshop
| - http://127.0.0.1:8000/workshop/{slug}
| - http://127.0.0.1:8000/webinar
| - http://127.0.0.1:8000/webinar/{slug}
|
| Important:
| - Jangan register route name yang sama untuk domain production dan
|   prefix local dalam environment yang sama.
| - Kalau route domain production tetap aktif di local, Laravel bisa generate
|   URL aneh seperti webinar.flexlabs.co.id:8007/{slug}.
|--------------------------------------------------------------------------
*/

if (app()->environment('production')) {
    /*
    |--------------------------------------------------------------------------
    | Public Workshop - Production Subdomain
    |--------------------------------------------------------------------------
    */
    Route::domain('workshop.flexlabs.co.id')
        ->name('workshop.')
        ->group(function () {
            Route::get('/', [PublicWorkshopController::class, 'index'])
                ->name('index');

            Route::get('/{slug}', [PublicWorkshopController::class, 'show'])
                ->where('slug', '[A-Za-z0-9\-]+')
                ->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | Public Webinar - Production Subdomain
    |--------------------------------------------------------------------------
    |
    | Production URL:
    | - https://webinar.flexlabs.co.id
    | - https://webinar.flexlabs.co.id/{slug}
    |
    | Route names:
    | - webinar.index
    | - webinar.show
    | - webinar.store
    |--------------------------------------------------------------------------
    */
    Route::domain('webinar.flexlabs.co.id')
        ->name('webinar.')
        ->group(function () {
            Route::get('/', [PublicTrialRegistrationController::class, 'index'])
                ->name('index');

            Route::post('/', [PublicTrialRegistrationController::class, 'store'])
                ->name('store');

            Route::get('/{slug}', [PublicTrialRegistrationController::class, 'show'])
                ->where('slug', '[A-Za-z0-9\-]+')
                ->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | Public Webinar - Legacy Trial Class Routes on Production Subdomain
    |--------------------------------------------------------------------------
    |
    | Old production URL:
    | - https://webinar.flexlabs.co.id/trial-class
    |--------------------------------------------------------------------------
    */
    Route::domain('webinar.flexlabs.co.id')
        ->name('trial-class.')
        ->group(function () {
            Route::get('/trial-class', function () {
                return redirect()->route('webinar.index');
            })->name('index');

            Route::post('/trial-class', [PublicTrialRegistrationController::class, 'store'])
                ->name('store');
        });

    /*
    |--------------------------------------------------------------------------
    | Public Workshop - Production Legacy URL on Ops Domain
    |--------------------------------------------------------------------------
    |
    | Old production URL:
    | - https://ops.flexlabs.co.id/workshop
    | - https://ops.flexlabs.co.id/workshop/{slug}
    |--------------------------------------------------------------------------
    */
    Route::prefix('workshop')
        ->name('legacy.workshop.')
        ->group(function () {
            Route::get('/', [PublicWorkshopController::class, 'index'])
                ->name('index');

            Route::get('/{slug}', [PublicWorkshopController::class, 'show'])
                ->where('slug', '[A-Za-z0-9\-]+')
                ->name('show');
        });
} else {
    /*
    |--------------------------------------------------------------------------
    | Public Workshop - Local Development URL
    |--------------------------------------------------------------------------
    |
    | Local URL:
    | - /workshop
    | - /workshop/{slug}
    |
    | Route names:
    | - workshop.index
    | - workshop.show
    |--------------------------------------------------------------------------
    */
    Route::prefix('workshop')
        ->name('workshop.')
        ->group(function () {
            Route::get('/', [PublicWorkshopController::class, 'index'])
                ->name('index');

            Route::get('/{slug}', [PublicWorkshopController::class, 'show'])
                ->where('slug', '[A-Za-z0-9\-]+')
                ->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | Public Webinar - Local Development URL
    |--------------------------------------------------------------------------
    |
    | Local URL:
    | - /webinar
    | - /webinar/{slug}
    |
    | Route names:
    | - webinar.index
    | - webinar.show
    | - webinar.store
    |--------------------------------------------------------------------------
    */
    Route::prefix('webinar')
        ->name('webinar.')
        ->group(function () {
            Route::get('/', [PublicTrialRegistrationController::class, 'index'])
                ->name('index');

            Route::post('/', [PublicTrialRegistrationController::class, 'store'])
                ->name('store');

            Route::get('/{slug}', [PublicTrialRegistrationController::class, 'show'])
                ->where('slug', '[A-Za-z0-9\-]+')
                ->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | Public Webinar - Legacy Trial Class Routes on Local
    |--------------------------------------------------------------------------
    |
    | Old local URL:
    | - /webinar/trial-class
    |--------------------------------------------------------------------------
    */
    Route::prefix('webinar')
        ->name('trial-class.')
        ->group(function () {
            Route::get('/trial-class', function () {
                return redirect()->route('webinar.index');
            })->name('index');

            Route::post('/trial-class', [PublicTrialRegistrationController::class, 'store'])
                ->name('store');
        });
}

/*
|--------------------------------------------------------------------------
| Public Trial Registration - Legacy URL on Ops Domain
|--------------------------------------------------------------------------
|
| Old URL still works:
| - https://ops.flexlabs.co.id/trial-class
| - http://127.0.0.1:8000/trial-class
|
| These names intentionally use legacy.trial-class.* so they do not override
| webinar.* or trial-class.* route names above.
|--------------------------------------------------------------------------
*/
Route::get('/trial-class', [PublicTrialRegistrationController::class, 'index'])
    ->name('legacy.trial-class.index');

Route::post('/trial-class', [PublicTrialRegistrationController::class, 'store'])
    ->name('legacy.trial-class.store');

/*
|--------------------------------------------------------------------------
| Public Event Routes
|--------------------------------------------------------------------------
| Production/Public URL:
| - https://event.flexlabs.co.id
| - https://event.flexlabs.co.id/{slug}
|
| Local Development URL:
| - /event
| - /event/{slug}
|
| Important:
| - Production routes use name: events.*
| - Local routes use name: local.events.*
| - This prevents route-name collisions that can make production links
|   generate with /event/{slug}.
|--------------------------------------------------------------------------
*/
Route::domain('event.flexlabs.co.id')
    ->name('events.')
    ->group(function () {
        Route::get('/', [PublicEventLeadController::class, 'index'])
            ->name('index');

        Route::get('/{slug}', [PublicEventLeadController::class, 'show'])
            ->where('slug', '[A-Za-z0-9\-]+')
            ->name('show');

        Route::post('/{slug}', [PublicEventLeadController::class, 'store'])
            ->where('slug', '[A-Za-z0-9\-]+')
            ->name('leads.store');

        /*
        |----------------------------------------------------------------------
        | Backward Compatible URL on Event Subdomain
        |----------------------------------------------------------------------
        | If an old link still points to /event/{slug} on event.flexlabs.co.id,
        | redirect it to /{slug}.
        |----------------------------------------------------------------------
        */
        Route::get('/event/{slug}', function (string $slug) {
            return redirect()->route('events.show', $slug, 301);
        })->where('slug', '[A-Za-z0-9\-]+')
            ->name('legacy.redirect');
    });

/*
|--------------------------------------------------------------------------
| Public Event Routes - Local Development URL
|--------------------------------------------------------------------------
| Local URL:
| /event
| /event/{slug}
|
| Note:
| These routes intentionally use local.events.* route names so they do not
| override production event route names.
|--------------------------------------------------------------------------
*/
Route::prefix('event')
    ->name('local.events.')
    ->group(function () {
        Route::get('/', [PublicEventLeadController::class, 'index'])
            ->name('index');

        Route::get('/{slug}', [PublicEventLeadController::class, 'show'])
            ->where('slug', '[A-Za-z0-9\-]+')
            ->name('show');

        Route::post('/{slug}', [PublicEventLeadController::class, 'store'])
            ->where('slug', '[A-Za-z0-9\-]+')
            ->name('leads.store');
    });

/*
|--------------------------------------------------------------------------
| Public Route - Ops Default
|--------------------------------------------------------------------------
| Root URL for the main ops app stays protected and goes to dashboard.
| The dashboard route itself still controls auth/permission.
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
| Public Trial / Workshop Materials
|--------------------------------------------------------------------------
*/
Route::get(
    '/materials/{token}/{slug}',
    [PublicLearningMaterialPageController::class, 'show']
)->name('public-learning-materials.show');

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
    ->middleware(['auth', 'verified', 'permission:dashboard.view'])
    ->name('dashboard');


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Academic Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/academic/dashboard', [AcademicDashboardController::class, 'index'])
        ->middleware('permission:academic.dashboard.view')
        ->name('academic.dashboard');

    /*
    |--------------------------------------------------------------------------
    | Master Data
    |--------------------------------------------------------------------------
    */
    Route::prefix('programs')->name('programs.')->middleware('permission:programs.view')->group(function () {
        Route::get('/', [ProgramController::class, 'index'])->name('index');
        Route::get('/{program}', [ProgramController::class, 'show'])->name('show');
        Route::post('/', [ProgramController::class, 'store'])->middleware('permission:programs.create')->name('store');
        Route::put('/{program}', [ProgramController::class, 'update'])->middleware('permission:programs.update')->name('update');
        Route::delete('/{program}', [ProgramController::class, 'destroy'])->middleware('permission:programs.delete')->name('destroy');
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
        Route::get('/themes', [TrialThemeController::class, 'index'])->middleware('permission:trial_themes.view')->name('themes.index');
        Route::get('/themes/{trialTheme}', [TrialThemeController::class, 'show'])->middleware('permission:trial_themes.view')->name('themes.show');
        Route::post('/themes', [TrialThemeController::class, 'store'])->middleware('permission:trial_themes.create')->name('themes.store');
        Route::put('/themes/{trialTheme}', [TrialThemeController::class, 'update'])->middleware('permission:trial_themes.update')->name('themes.update');
        Route::delete('/themes/{trialTheme}', [TrialThemeController::class, 'destroy'])->middleware('permission:trial_themes.delete')->name('themes.destroy');

        /*
        |--------------------------------------------------------------------------
        | Trial Schedules
        |--------------------------------------------------------------------------
        */
        Route::get('/schedules', [TrialScheduleController::class, 'index'])->middleware('permission:trial_schedules.view')->name('schedules.index');
        Route::get('/schedules/{trialSchedule}', [TrialScheduleController::class, 'show'])->middleware('permission:trial_schedules.view')->name('schedules.show');
        Route::post('/schedules', [TrialScheduleController::class, 'store'])->middleware('permission:trial_schedules.create')->name('schedules.store');
        Route::put('/schedules/{trialSchedule}', [TrialScheduleController::class, 'update'])->middleware('permission:trial_schedules.update')->name('schedules.update');
        Route::delete('/schedules/{trialSchedule}', [TrialScheduleController::class, 'destroy'])->middleware('permission:trial_schedules.delete')->name('schedules.destroy');

        /*
        |--------------------------------------------------------------------------
        | Trial Participants
        |--------------------------------------------------------------------------
        */
        Route::get('/participants', [TrialParticipantController::class, 'index'])->middleware('permission:trial_participants.view')->name('participants.index');
        Route::get('/participants/{trialParticipant}', [TrialParticipantController::class, 'show'])->middleware('permission:trial_participants.view')->name('participants.show');
        Route::post('/participants', [TrialParticipantController::class, 'store'])->middleware('permission:trial_participants.create')->name('participants.store');
        Route::put('/participants/{trialParticipant}', [TrialParticipantController::class, 'update'])->middleware('permission:trial_participants.update')->name('participants.update');
        Route::delete('/participants/{trialParticipant}', [TrialParticipantController::class, 'destroy'])->middleware('permission:trial_participants.delete')->name('participants.destroy');
    });


    /*
    |--------------------------------------------------------------------------
    | Enrollment
    |--------------------------------------------------------------------------
    */
    Route::prefix('enrollment')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->middleware('permission:enrollments.view')->name('enrollments.index');

        /*
        |--------------------------------------------------------------------------
        | Batches
        |--------------------------------------------------------------------------
        */
        Route::prefix('batches')->name('batches.')->middleware('permission:batches.view')->group(function () {
            Route::get('/', [BatchController::class, 'index'])->name('index');
            Route::get('/{batch}', [BatchController::class, 'show'])->name('show');
            Route::post('/', [BatchController::class, 'store'])->middleware('permission:batches.create')->name('store');
            Route::put('/{batch}', [BatchController::class, 'update'])->middleware('permission:batches.update')->name('update');
            Route::delete('/{batch}', [BatchController::class, 'destroy'])->middleware('permission:batches.delete')->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Students
        |--------------------------------------------------------------------------
        */
        Route::prefix('students')->name('students.')->middleware('permission:students.view')->group(function () {
            Route::get('/', [StudentController::class, 'index'])->name('index');
            Route::get('/{student}', [StudentController::class, 'show'])->name('show');
            Route::post('/', [StudentController::class, 'store'])->middleware('permission:students.create')->name('store');
            Route::put('/{student}', [StudentController::class, 'update'])->middleware('permission:students.update')->name('update');
            Route::delete('/{student}', [StudentController::class, 'destroy'])->middleware('permission:students.delete')->name('destroy');

            Route::post('/{student}/enroll', [StudentController::class, 'enroll'])
                ->middleware('permission:enrollments.create')
                ->name('enroll');
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */
    Route::prefix('payments')->group(function () {
        Route::prefix('orders')->name('orders.')->middleware('permission:orders.view')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/{order}', [OrderController::class, 'show'])->name('show');
            Route::post('/', [OrderController::class, 'store'])->middleware('permission:orders.create')->name('store');
            Route::put('/{order}', [OrderController::class, 'update'])->middleware('permission:orders.update')->name('update');
            Route::delete('/{order}', [OrderController::class, 'destroy'])->middleware('permission:orders.delete')->name('destroy');
        });

        Route::prefix('schedules')->name('payment-schedules.')->middleware('permission:payment_schedules.view')->group(function () {
            Route::get('/', [PaymentScheduleController::class, 'index'])->name('index');
            Route::get('/{paymentSchedule}', [PaymentScheduleController::class, 'show'])->name('show');
            Route::post('/', [PaymentScheduleController::class, 'store'])->middleware('permission:payment_schedules.create')->name('store');
            Route::put('/{paymentSchedule}', [PaymentScheduleController::class, 'update'])->middleware('permission:payment_schedules.update')->name('update');
            Route::delete('/{paymentSchedule}', [PaymentScheduleController::class, 'destroy'])->middleware('permission:payment_schedules.delete')->name('destroy');
        });

        Route::get('/', [PaymentController::class, 'index'])->middleware('permission:payments.view')->name('payments.index');
        Route::post('/', [PaymentController::class, 'store'])->middleware('permission:payments.create')->name('payments.store');

        Route::get('/{payment}/invoice', [PaymentController::class, 'invoice'])
            ->middleware('permission:payments.view')
            ->whereNumber('payment')
            ->name('payments.invoice');

        Route::get('/{payment}/invoice/download-pdf', [PaymentController::class, 'downloadInvoicePdf'])
            ->middleware('permission:payments.view')
            ->whereNumber('payment')
            ->name('payments.invoice.download-pdf');

        Route::get('/{payment}/receipt', [PaymentController::class, 'receipt'])
            ->middleware('permission:payments.view')
            ->whereNumber('payment')
            ->name('payments.receipt');

        Route::get('/{payment}/receipt/download-pdf', [PaymentController::class, 'downloadReceiptPdf'])
            ->middleware('permission:payments.view')
            ->whereNumber('payment')
            ->name('payments.receipt.download-pdf');

        Route::get('/{payment}', [PaymentController::class, 'show'])->middleware('permission:payments.view')->whereNumber('payment')->name('payments.show');
        Route::put('/{payment}', [PaymentController::class, 'update'])->middleware('permission:payments.update')->whereNumber('payment')->name('payments.update');
        Route::delete('/{payment}', [PaymentController::class, 'destroy'])->middleware('permission:payments.delete')->whereNumber('payment')->name('payments.destroy');
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
        Route::prefix('daily-reports')->name('sales-daily-reports.')->middleware('permission:sales_daily_reports.view')->group(function () {
            Route::get('/', [SalesDailyReportController::class, 'index'])->name('index');
            Route::get('/create', [SalesDailyReportController::class, 'create'])->middleware('permission:sales_daily_reports.create')->name('create');
            Route::post('/', [SalesDailyReportController::class, 'store'])->middleware('permission:sales_daily_reports.create')->name('store');
            Route::get('/{salesDailyReport}', [SalesDailyReportController::class, 'show'])->name('show');
            Route::get('/{salesDailyReport}/edit', [SalesDailyReportController::class, 'edit'])->middleware('permission:sales_daily_reports.update')->name('edit');
            Route::put('/{salesDailyReport}', [SalesDailyReportController::class, 'update'])->middleware('permission:sales_daily_reports.update')->name('update');
            Route::delete('/{salesDailyReport}', [SalesDailyReportController::class, 'destroy'])->middleware('permission:sales_daily_reports.delete')->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Performance Dashboard
        |--------------------------------------------------------------------------
        */
        Route::prefix('performance')->name('sales-performance.')->middleware('permission:sales_performance.view')->group(function () {
            Route::get('/', [SalesPerformanceController::class, 'index'])->name('index');
            Route::get('/chart-data', [SalesPerformanceController::class, 'chartData'])->name('chart-data');
        });

        /*
        |--------------------------------------------------------------------------
        | Sales Orders
        |--------------------------------------------------------------------------
        */
        Route::prefix('orders')->name('orders.')->middleware('permission:orders.view')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/create', [OrderController::class, 'create'])->middleware('permission:orders.create')->name('create');
            Route::post('/', [OrderController::class, 'store'])->middleware('permission:orders.create')->name('store');
            Route::get('/{order}', [OrderController::class, 'show'])->name('show');
            Route::get('/{order}/edit', [OrderController::class, 'edit'])->middleware('permission:orders.update')->name('edit');
            Route::put('/{order}', [OrderController::class, 'update'])->middleware('permission:orders.update')->name('update');
            Route::delete('/{order}', [OrderController::class, 'destroy'])->middleware('permission:orders.delete')->name('destroy');
        });

    });


    /*
    |--------------------------------------------------------------------------
    | Instructors
    |--------------------------------------------------------------------------
    */
    Route::prefix('instructors')->name('instructors.')->middleware('permission:instructors.view')->group(function () {
        Route::get('/', [InstructorController::class, 'index'])->name('index');
        Route::get('/{instructor}', [InstructorController::class, 'show'])->name('show');
        Route::post('/', [InstructorController::class, 'store'])->middleware('permission:instructors.create')->name('store');
        Route::put('/{instructor}', [InstructorController::class, 'update'])->middleware('permission:instructors.update')->name('update');
        Route::delete('/{instructor}', [InstructorController::class, 'destroy'])->middleware('permission:instructors.delete')->name('destroy');
        Route::post('/{instructor}/assign-teaching-scope', [InstructorController::class, 'assignTeachingScope'])
        ->middleware('permission:instructors.update')
        ->name('assign-teaching-scope');
    });


    /*
    |--------------------------------------------------------------------------
    | Equipment
    |--------------------------------------------------------------------------
    */
    Route::prefix('equipment')->name('equipment.')->middleware('permission:equipment.view')->group(function () {
        Route::get('/', [EquipmentController::class, 'index'])->name('index');
        Route::get('/{equipment}', [EquipmentController::class, 'show'])->name('show');
        Route::post('/', [EquipmentController::class, 'store'])->middleware('permission:equipment.create')->name('store');
        Route::put('/{equipment}', [EquipmentController::class, 'update'])->middleware('permission:equipment.update')->name('update');
        Route::delete('/{equipment}', [EquipmentController::class, 'destroy'])->middleware('permission:equipment.delete')->name('destroy');

        Route::post('/{equipment}/borrow', [EquipmentBorrowingController::class, 'borrow'])->middleware('permission:equipment_borrowings.create')->name('borrow');
        Route::post('/borrowings/{borrowing}/return', [EquipmentBorrowingController::class, 'returnEquipment'])->middleware('permission:equipment_borrowings.update')->name('return');
    });

    /*
    |--------------------------------------------------------------------------
    | Operations - Gear Borrowing
    |--------------------------------------------------------------------------
    */
    Route::prefix('borrowings')->name('borrowings.')->middleware('permission:equipment_borrowings.view')->group(function () {
        Route::get('/', [EquipmentBorrowingController::class, 'index'])->name('index');
        Route::get('/{borrowing}', [EquipmentBorrowingController::class, 'show'])->name('show');
        Route::post('/', [EquipmentBorrowingController::class, 'store'])->middleware('permission:equipment_borrowings.create')->name('store');
        Route::post('/{borrowing}/return', [EquipmentBorrowingController::class, 'returnEquipment'])->middleware('permission:equipment_borrowings.update')->name('return');
    });

    /*
    |--------------------------------------------------------------------------
    | Operations - Meeting Minutes / MOM
    |--------------------------------------------------------------------------
    */
    Route::prefix('operation/meeting-minutes')
        ->name('operation.meeting-minutes.')
        ->middleware('permission:meeting_minutes.view')
        ->group(function () {
            Route::get('/', [MeetingMinuteController::class, 'index'])->name('index');
            Route::get('/create', [MeetingMinuteController::class, 'create'])->middleware('permission:meeting_minutes.create')->name('create');
            Route::post('/', [MeetingMinuteController::class, 'store'])->middleware('permission:meeting_minutes.create')->name('store');

            // Download PDF harus sebelum route show
            Route::get('/{meetingMinute}/download-pdf', [MeetingMinuteController::class, 'downloadPdf'])
                ->name('download-pdf');

            Route::get('/{meetingMinute}', [MeetingMinuteController::class, 'show'])->name('show');
            Route::get('/{meetingMinute}/edit', [MeetingMinuteController::class, 'edit'])->middleware('permission:meeting_minutes.update')->name('edit');
            Route::put('/{meetingMinute}', [MeetingMinuteController::class, 'update'])->middleware('permission:meeting_minutes.update')->name('update');
            Route::delete('/{meetingMinute}', [MeetingMinuteController::class, 'destroy'])->middleware('permission:meeting_minutes.delete')->name('destroy');
        });

    Route::patch(
        'operation/meeting-minute-action-items/{actionItem}/status',
        [MeetingMinuteController::class, 'updateActionItemStatus']
    )->middleware('permission:meeting_minutes.update')->name('operation.meeting-minute-action-items.update-status');


    /*
    |--------------------------------------------------------------------------
    | Operations - Quiz Management
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth')->group(function () {
        Route::prefix('quiz')->name('quiz.')->middleware('permission:quizzes.view')->group(function () {
            Route::get('/', [QuizController::class, 'index'])->name('index');
            Route::get('/{quiz}', [QuizController::class, 'show'])->name('show');
            Route::post('/', [QuizController::class, 'store'])->middleware('permission:quizzes.create')->name('store');
            Route::put('/{quiz}', [QuizController::class, 'update'])->middleware('permission:quizzes.update')->name('update');
            Route::delete('/{quiz}', [QuizController::class, 'destroy'])->middleware('permission:quizzes.delete')->name('destroy');

            Route::get('/{quiz}/questions', [QuizQuestionController::class, 'index'])->name('questions.index');
            Route::post('/{quiz}/questions', [QuizQuestionController::class, 'store'])->middleware('permission:quizzes.create')->name('questions.store');

            Route::get('/questions/{question}', [QuizQuestionController::class, 'show'])->name('questions.show');
            Route::put('/questions/{question}', [QuizQuestionController::class, 'update'])->middleware('permission:quizzes.update')->name('questions.update');
            Route::delete('/questions/{question}', [QuizQuestionController::class, 'destroy'])->middleware('permission:quizzes.delete')->name('questions.destroy');

            Route::get('/questions/{question}/options', [QuizOptionController::class, 'index'])->name('options.index');
            Route::post('/questions/{question}/options', [QuizOptionController::class, 'store'])->middleware('permission:quizzes.create')->name('options.store');

            Route::get('/options/{option}', [QuizOptionController::class, 'show'])->name('options.show');
            Route::put('/options/{option}', [QuizOptionController::class, 'update'])->middleware('permission:quizzes.update')->name('options.update');
            Route::delete('/options/{option}', [QuizOptionController::class, 'destroy'])->middleware('permission:quizzes.delete')->name('options.destroy');

            //Route::get('/{quiz}/play', [QuizPlayController::class, 'show'])->name('play');
            Route::get('/{quiz}/leaderboard', [QuizLeaderboardController::class, 'index'])->name('leaderboard');
            
        });
    });

    

    Route::prefix('marketing/reports')->name('marketing.reports.')->middleware('permission:marketing_reports.view')->group(function () {
        Route::get('/', [MarketingReportController::class, 'index'])->name('index');
        Route::get('/create', [MarketingReportController::class, 'create'])->middleware('permission:marketing_reports.create')->name('create');
        Route::post('/', [MarketingReportController::class, 'store'])->middleware('permission:marketing_reports.create')->name('store');
        Route::get('/{report}', [MarketingReportController::class, 'show'])->name('show');
        Route::get('/{report}/edit', [MarketingReportController::class, 'edit'])->middleware('permission:marketing_reports.update')->name('edit');
        Route::put('/{report}', [MarketingReportController::class, 'update'])->middleware('permission:marketing_reports.update')->name('update');
        Route::delete('/{report}', [MarketingReportController::class, 'destroy'])->middleware('permission:marketing_reports.delete')->name('destroy');
    });

    Route::prefix('marketing/setup')->name('marketing.setup.')->group(function () {
        Route::prefix('campaigns')->name('campaigns.')->middleware('permission:campaigns.view')->group(function () {
            Route::get('/', [MarketingSetupCampaignController::class, 'index'])->name('index');
            Route::post('/', [MarketingSetupCampaignController::class, 'store'])->middleware('permission:campaigns.create')->name('store');
            Route::put('/{campaign}', [MarketingSetupCampaignController::class, 'update'])->middleware('permission:campaigns.update')->name('update');
            Route::delete('/{campaign}', [MarketingSetupCampaignController::class, 'destroy'])->middleware('permission:campaigns.delete')->name('destroy');
            Route::get('/options/by-period', [MarketingSetupCampaignController::class, 'options'])->name('options');
        });

        Route::prefix('ads')->name('ads.')->middleware('permission:ads.view')->group(function () {
            Route::get('/', [MarketingSetupAdController::class, 'index'])->name('index');
            Route::post('/', [MarketingSetupAdController::class, 'store'])->middleware('permission:ads.create')->name('store');
            Route::put('/{ad}', [MarketingSetupAdController::class, 'update'])->middleware('permission:ads.update')->name('update');
            Route::delete('/{ad}', [MarketingSetupAdController::class, 'destroy'])->middleware('permission:ads.delete')->name('destroy');
            Route::get('/options/by-period', [MarketingSetupAdController::class, 'options'])->name('options');
        });
    });

    

    /*
    |--------------------------------------------------------------------------
    | Inventory - ATK
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/atk-items', [AtkItemController::class, 'index'])->middleware('permission:atk_items.view')->name('atk-items.index');
        Route::post('/atk-items', [AtkItemController::class, 'store'])->middleware('permission:atk_items.create')->name('atk-items.store');
        Route::put('/atk-items/{atkItem}', [AtkItemController::class, 'update'])->middleware('permission:atk_items.update')->name('atk-items.update');
        Route::delete('/atk-items/{atkItem}', [AtkItemController::class, 'destroy'])->middleware('permission:atk_items.delete')->name('atk-items.destroy');

        Route::get('/atk-requests', [AtkRequestController::class, 'index'])->middleware('permission:atk_requests.view')->name('atk-requests.index');
        Route::post('/atk-requests', [AtkRequestController::class, 'store'])->middleware('permission:atk_requests.create')->name('atk-requests.store');
        Route::post('/atk-requests/{atkRequest}/approve', [AtkRequestController::class, 'approve'])->middleware('permission:atk_requests.approve')->name('atk-requests.approve');
        Route::post('/atk-requests/{atkRequest}/reject', [AtkRequestController::class, 'reject'])->middleware('permission:atk_requests.approve')->name('atk-requests.reject');
        Route::post('/atk-requests/{atkRequest}/cancel', [AtkRequestController::class, 'cancel'])->middleware('permission:atk_requests.update')->name('atk-requests.cancel');
    });

    //Route::get('/play-quiz/{quiz}', [QuizPlayController::class, 'show'])->name('quiz.play');
    Route::get('quiz/{quiz}/play', [QuizPlayController::class, 'show'])->middleware('permission:quizzes.view')->name('quiz.play');

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    */
    Route::prefix('monitoring')->group(function () {
        Route::get('/', fn () => view('monitoring.index'))->middleware('permission:dashboard.view')->name('monitoring.index');
    });


        /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')
    ->name('settings.')
    ->middleware('permission:users.view')
    ->group(function () {
        Route::get('/', fn () => view('settings.index'))
            ->name('index');

        /*
        |--------------------------------------------------------------------------
        | Settings - User Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('users')
            ->name('users.')
            ->controller(UserManagementController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->middleware('permission:users.create')->name('create');
                Route::post('/', 'store')->middleware('permission:users.create')->name('store');

                Route::get('/{user}', 'show')->name('show');
                Route::get('/{user}/edit', 'edit')->middleware('permission:users.update')->name('edit');
                Route::put('/{user}', 'update')->middleware('permission:users.update')->name('update');
                Route::patch('/{user}', 'update')->middleware('permission:users.update')->name('patch');

                Route::patch('/{user}/password', 'updatePassword')
                    ->middleware('permission:users.update')
                    ->name('password.update');

                Route::delete('/{user}', 'destroy')->middleware('permission:users.delete')->name('destroy');
            });
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Curriculum
    |--------------------------------------------------------------------------
    */
    Route::prefix('curriculum')->name('curriculum.')->middleware('permission:curriculum.view')->group(function () {
        Route::get('/', [CurriculumController::class, 'index'])->name('index');

        Route::post('/stages', [CurriculumController::class, 'storeStage'])->middleware('permission:curriculum.create')->name('stages.store');
        Route::put('/stages/{stage}', [CurriculumController::class, 'updateStage'])->middleware('permission:curriculum.update')->name('stages.update');
        Route::delete('/stages/{stage}', [CurriculumController::class, 'destroyStage'])->middleware('permission:curriculum.delete')->name('stages.destroy');

        Route::post('/modules', [CurriculumController::class, 'storeModule'])->middleware('permission:curriculum.create')->name('modules.store');
        Route::put('/modules/{module}', [CurriculumController::class, 'updateModule'])->middleware('permission:curriculum.update')->name('modules.update');
        Route::delete('/modules/{module}', [CurriculumController::class, 'destroyModule'])->middleware('permission:curriculum.delete')->name('modules.destroy');

        Route::post('/topics', [CurriculumController::class, 'storeTopic'])->middleware('permission:curriculum.create')->name('topics.store');
        Route::put('/topics/{topic}', [CurriculumController::class, 'updateTopic'])->middleware('permission:curriculum.update')->name('topics.update');
        Route::delete('/topics/{topic}', [CurriculumController::class, 'destroyTopic'])->middleware('permission:curriculum.delete')->name('topics.destroy');

        Route::post('/sub-topics', [CurriculumController::class, 'storeSubTopic'])->middleware('permission:curriculum.create')->name('sub-topics.store');
        Route::put('/sub-topics/{subTopic}', [CurriculumController::class, 'updateSubTopic'])->middleware('permission:curriculum.update')->name('sub-topics.update');
        Route::delete('/sub-topics/{subTopic}', [CurriculumController::class, 'destroySubTopic'])->middleware('permission:curriculum.delete')->name('sub-topics.destroy');

        Route::get('/server-videos', [CurriculumController::class, 'serverVideos'])
        ->name('server-videos');
        
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Learning Videos
    |--------------------------------------------------------------------------
    | Upload manager untuk file video materi.
    | Lokasi file:
    | - storage/app/private/learning-videos
    |
    | Route names:
    | - academic.learning-videos.index
    | - academic.learning-videos.store
    | - academic.learning-videos.stream
    | - academic.learning-videos.destroy
    |--------------------------------------------------------------------------
    */
    Route::prefix('academic/learning-videos')
        ->name('academic.learning-videos.')
        ->middleware('permission:learning_videos.view')
        ->controller(LearningVideoController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->name('index');

            Route::post('/', 'store')
                ->middleware('permission:learning_videos.create')
                ->name('store');

            Route::get('/{filename}/stream', 'stream')
                ->where('filename', '[A-Za-z0-9_\-\.]+')
                ->name('stream');

            Route::delete('/{filename}', 'destroy')
                ->where('filename', '[A-Za-z0-9_\-\.]+')
                ->middleware('permission:learning_videos.delete')
                ->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Academic - Assignments
    |--------------------------------------------------------------------------
    */
    Route::prefix('assignments')->name('assignments.')->middleware('permission:assignments.view')->group(function () {
        Route::get('/', [AssignmentController::class, 'index'])->name('index');
        Route::post('/', [AssignmentController::class, 'store'])->middleware('permission:assignments.create')->name('store');
        Route::put('/{assignment}', [AssignmentController::class, 'update'])->middleware('permission:assignments.update')->name('update');
        Route::delete('/{assignment}', [AssignmentController::class, 'destroy'])->middleware('permission:assignments.delete')->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Assignment Submissions
    |--------------------------------------------------------------------------
    */
    Route::prefix('assignment-submissions')->name('assignment-submissions.')->middleware('permission:assignment_submissions.view')->group(function () {
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
    Route::prefix('learning-quizzes')->name('learning-quizzes.')->middleware('permission:learning_quizzes.view')->group(function () {
        Route::get('/', [LearningQuizController::class, 'index'])->name('index');
        Route::post('/', [LearningQuizController::class, 'store'])->middleware('permission:learning_quizzes.create')->name('store');
        Route::put('/{learningQuiz}', [LearningQuizController::class, 'update'])->middleware('permission:learning_quizzes.update')->name('update');
        Route::delete('/{learningQuiz}', [LearningQuizController::class, 'destroy'])->middleware('permission:learning_quizzes.delete')->name('destroy');
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
    Route::prefix('batch-assignments')->name('batch-assignments.')->middleware('permission:batch_assignments.view')->group(function () {
        Route::get('/', [BatchAssignmentController::class, 'index'])->name('index');
        Route::post('/', [BatchAssignmentController::class, 'store'])->middleware('permission:batch_assignments.create')->name('store');
        Route::put('/{batchAssignment}', [BatchAssignmentController::class, 'update'])->middleware('permission:batch_assignments.update')->name('update');
        Route::delete('/{batchAssignment}', [BatchAssignmentController::class, 'destroy'])->middleware('permission:batch_assignments.delete')->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Batch Learning Quizzes
    |--------------------------------------------------------------------------
    */
    Route::prefix('batch-learning-quizzes')->name('batch-learning-quizzes.')->middleware('permission:batch_learning_quizzes.view')->group(function () {
        Route::get('/', [BatchLearningQuizController::class, 'index'])->name('index');
        Route::post('/', [BatchLearningQuizController::class, 'store'])->middleware('permission:batch_learning_quizzes.create')->name('store');
        Route::put('/{batchLearningQuiz}', [BatchLearningQuizController::class, 'update'])->middleware('permission:batch_learning_quizzes.update')->name('update');
        Route::delete('/{batchLearningQuiz}', [BatchLearningQuizController::class, 'destroy'])->middleware('permission:batch_learning_quizzes.delete')->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Learning Quiz Attempts / Results
    |--------------------------------------------------------------------------
    */
    Route::prefix('learning-quiz-attempts')->name('learning-quiz-attempts.')->middleware('permission:learning_quiz_attempts.view')->group(function () {
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

                Route::get('/{reportCard}/download-pdf', [ReportCardController::class, 'downloadPdf'])
                    ->whereNumber('reportCard')
                    ->name('download-pdf');

                Route::get('/{reportCard}', [ReportCardController::class, 'show'])
                    ->whereNumber('reportCard')
                    ->name('show');
            });

        /*
        |--------------------------------------------------------------------------
        | Academic - Student Attendances
        |--------------------------------------------------------------------------
        */
        Route::prefix('attendances')
            ->name('attendances.')
            ->controller(StudentAttendanceController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->name('index');

                Route::get('/schedules/{instructorSchedule}', 'record')
                    ->whereNumber('instructorSchedule')
                    ->name('record');

                Route::post('/schedules/{instructorSchedule}', 'store')
                    ->whereNumber('instructorSchedule')
                    ->name('store');
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

                Route::post('/', 'store')->middleware('permission:users.create')->name('store');

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
    | Academic - Public Learning Materials
    |--------------------------------------------------------------------------
    | Materi public untuk Trial dan Workshop.
    | Admin URL:
    | - /academic/public-learning-materials
    |
    | Public URL:
    | - /materials/{token}/{slug}
    |--------------------------------------------------------------------------
    */
    Route::prefix('academic/public-learning-materials')
        ->name('public-learning-materials.')
        ->middleware('permission:curriculum.view')
        ->group(function () {
            Route::get('/', [PublicLearningMaterialController::class, 'index'])
                ->name('index');

            Route::get('/create', [PublicLearningMaterialController::class, 'create'])
                ->middleware('permission:curriculum.create')
                ->name('create');

            Route::post('/', [PublicLearningMaterialController::class, 'store'])
                ->middleware('permission:curriculum.create')
                ->name('store');

            Route::get('/{publicLearningMaterial}/edit', [PublicLearningMaterialController::class, 'edit'])
                ->whereNumber('publicLearningMaterial')
                ->middleware('permission:curriculum.update')
                ->name('edit');

            Route::put('/{publicLearningMaterial}', [PublicLearningMaterialController::class, 'update'])
                ->whereNumber('publicLearningMaterial')
                ->middleware('permission:curriculum.update')
                ->name('update');

            Route::patch('/{publicLearningMaterial}', [PublicLearningMaterialController::class, 'update'])
                ->whereNumber('publicLearningMaterial')
                ->middleware('permission:curriculum.update')
                ->name('patch');

            Route::delete('/{publicLearningMaterial}', [PublicLearningMaterialController::class, 'destroy'])
                ->whereNumber('publicLearningMaterial')
                ->middleware('permission:curriculum.delete')
                ->name('destroy');

            Route::post('/{publicLearningMaterial}/publish', [PublicLearningMaterialController::class, 'publish'])
                ->whereNumber('publicLearningMaterial')
                ->middleware('permission:curriculum.update')
                ->name('publish');

            Route::post('/{publicLearningMaterial}/archive', [PublicLearningMaterialController::class, 'archive'])
                ->whereNumber('publicLearningMaterial')
                ->middleware('permission:curriculum.update')
                ->name('archive');

            Route::post('/{publicLearningMaterial}/duplicate', [PublicLearningMaterialController::class, 'duplicate'])
                ->whereNumber('publicLearningMaterial')
                ->middleware('permission:curriculum.create')
                ->name('duplicate');

            /*
            |--------------------------------------------------------------------------
            | Material Blocks
            |--------------------------------------------------------------------------
            */
            Route::post('/{material}/blocks', [PublicLearningMaterialBlockController::class, 'store'])
                ->whereNumber('material')
                ->middleware('permission:curriculum.create')
                ->name('blocks.store');

            Route::put('/blocks/{block}', [PublicLearningMaterialBlockController::class, 'update'])
                ->whereNumber('block')
                ->middleware('permission:curriculum.update')
                ->name('blocks.update');

            Route::patch('/blocks/{block}', [PublicLearningMaterialBlockController::class, 'update'])
                ->whereNumber('block')
                ->middleware('permission:curriculum.update')
                ->name('blocks.patch');

            Route::delete('/blocks/{block}', [PublicLearningMaterialBlockController::class, 'destroy'])
                ->whereNumber('block')
                ->middleware('permission:curriculum.delete')
                ->name('blocks.destroy');

            Route::post('/{material}/blocks/reorder', [PublicLearningMaterialBlockController::class, 'reorder'])
                ->whereNumber('material')
                ->middleware('permission:curriculum.update')
                ->name('blocks.reorder');

            /*
            |--------------------------------------------------------------------------
            | Material Gallery Images
            |--------------------------------------------------------------------------
            */
            Route::post('/{material}/images', [PublicLearningMaterialImageController::class, 'store'])
                ->whereNumber('material')
                ->middleware('permission:curriculum.create')
                ->name('images.store');

            Route::put('/images/{image}', [PublicLearningMaterialImageController::class, 'update'])
                ->whereNumber('image')
                ->middleware('permission:curriculum.update')
                ->name('images.update');

            Route::patch('/images/{image}', [PublicLearningMaterialImageController::class, 'update'])
                ->whereNumber('image')
                ->middleware('permission:curriculum.update')
                ->name('images.patch');

            Route::delete('/images/{image}', [PublicLearningMaterialImageController::class, 'destroy'])
                ->whereNumber('image')
                ->middleware('permission:curriculum.delete')
                ->name('images.destroy');

            Route::post('/{material}/images/reorder', [PublicLearningMaterialImageController::class, 'reorder'])
                ->whereNumber('material')
                ->middleware('permission:curriculum.update')
                ->name('images.reorder');
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Workshops
    |--------------------------------------------------------------------------
    */
    Route::prefix('academic')->name('academic.')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Workshop Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('workshops')
            ->name('workshops.')
            ->middleware('permission:workshops.view')
            ->controller(WorkshopController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->name('index');

                Route::get('/create', 'create')
                    ->middleware('permission:workshops.create')
                    ->name('create');

                Route::post('/', 'store')
                    ->middleware('permission:workshop_schedules.create')
                    ->name('store');

                Route::get('/{workshop}', 'show')
                    ->whereNumber('workshop')
                    ->name('show');

                Route::get('/{workshop}/edit', 'edit')
                    ->whereNumber('workshop')
                    ->middleware('permission:workshops.update')
                    ->name('edit');

                Route::put('/{workshop}', 'update')
                    ->whereNumber('workshop')
                    ->middleware('permission:workshop_schedules.update')
                    ->name('update');

                Route::patch('/{workshop}', 'update')
                    ->whereNumber('workshop')
                    ->middleware('permission:workshop_schedules.update')
                    ->name('patch');

                Route::delete('/{workshop}', 'destroy')
                    ->whereNumber('workshop')
                    ->middleware('permission:workshop_schedules.delete')
                    ->name('destroy');
            });

        /*
        |--------------------------------------------------------------------------
        | Workshop Schedules
        |--------------------------------------------------------------------------
        |
        | Route names:
        | - academic.workshop-schedules.index
        | - academic.workshop-schedules.show
        | - academic.workshop-schedules.store
        | - academic.workshop-schedules.update
        | - academic.workshop-schedules.patch
        | - academic.workshop-schedules.destroy
        | - academic.workshop-schedules.workshops.pricing
        |
        | Notes:
        | - Index bisa render Blade atau JSON untuk async table.
        | - Store/update/delete dibuat async lewat JSON.
        | - Pricing endpoint dipakai untuk auto-fill harga dari workshop.
        |--------------------------------------------------------------------------
        */
        Route::prefix('workshop-schedules')
            ->name('workshop-schedules.')
            ->middleware('permission:workshop_schedules.view')
            ->controller(WorkshopScheduleController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->name('index');

                /*
                |--------------------------------------------------------------------------
                | Helper: Get Workshop Pricing
                |--------------------------------------------------------------------------
                | Must stay before /{workshopSchedule} routes.
                | Used by async form when admin selects a workshop.
                |--------------------------------------------------------------------------
                */
                Route::get('/workshops/{workshop}/pricing', 'workshopPricing')
                    ->whereNumber('workshop')
                    ->name('workshops.pricing');

                Route::get('/{workshopSchedule}', 'show')
                    ->whereNumber('workshopSchedule')
                    ->name('show');

                Route::post('/', 'store')
                    ->middleware('permission:workshop_schedules.create')
                    ->name('store');

                Route::put('/{workshopSchedule}', 'update')
                    ->whereNumber('workshopSchedule')
                    ->middleware('permission:workshop_schedules.update')
                    ->name('update');

                Route::patch('/{workshopSchedule}', 'update')
                    ->whereNumber('workshopSchedule')
                    ->middleware('permission:workshop_schedules.update')
                    ->name('patch');

                Route::delete('/{workshopSchedule}', 'destroy')
                    ->whereNumber('workshopSchedule')
                    ->middleware('permission:workshop_schedules.delete')
                    ->name('destroy');
            });

        /*
        |--------------------------------------------------------------------------
        | Student Progress Monitoring
        |--------------------------------------------------------------------------
        | Route names used by menu config:
        | - academic.student-progress.index
        | - academic.student-progress.show
        */

        Route::get('/student-progress', [StudentProgressMonitoringController::class, 'index'])
            ->middleware('permission:student_progress.view')
            ->name('student-progress.index');

        Route::get('/student-progress/{student}', [StudentProgressMonitoringController::class, 'show'])
            ->whereNumber('student')
            ->middleware('permission:student_progress.view')
            ->name('student-progress.show');

        /*
        |--------------------------------------------------------------------------
        | Workshop Participants
        |--------------------------------------------------------------------------
        |
        | Route names used by menu config:
        | - academic.workshop-participants.index
        | - academic.workshop-participants.create
        | - academic.workshop-participants.store
        | - academic.workshop-participants.show
        | - academic.workshop-participants.edit
        | - academic.workshop-participants.update
        | - academic.workshop-participants.destroy
        |
        */
        Route::prefix('workshop-participants')
            ->name('workshop-participants.')
            ->middleware('permission:workshop_participants.view')
            ->controller(WorkshopParticipantController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->name('index');

                Route::get('/create', 'create')
                    ->middleware('permission:workshop_participants.create')
                    ->name('create');

                Route::post('/', 'store')
                    ->middleware('permission:workshop_participants.create')
                    ->name('store');

                Route::get('/{workshopParticipant}', 'show')
                    ->whereNumber('workshopParticipant')
                    ->name('show');

                Route::get('/{workshopParticipant}/edit', 'edit')
                    ->whereNumber('workshopParticipant')
                    ->middleware('permission:workshop_participants.update')
                    ->name('edit');

                Route::put('/{workshopParticipant}', 'update')
                    ->whereNumber('workshopParticipant')
                    ->middleware('permission:workshop_participants.update')
                    ->name('update');

                Route::patch('/{workshopParticipant}', 'update')
                    ->whereNumber('workshopParticipant')
                    ->middleware('permission:workshop_participants.update')
                    ->name('patch');

                Route::delete('/{workshopParticipant}', 'destroy')
                    ->whereNumber('workshopParticipant')
                    ->middleware('permission:workshop_participants.delete')
                    ->name('destroy');
            });

        /*
        |--------------------------------------------------------------------------
        | Workshop Participants by Workshop
        |--------------------------------------------------------------------------
        |
        | Optional helper routes kalau nanti dari detail workshop mau langsung
        | lihat / tambah peserta untuk workshop tertentu.
        |
        | Route names:
        | - academic.workshops.participants.index
        | - academic.workshops.participants.create
        | - academic.workshops.participants.store
        |
        */
        Route::prefix('workshops/{workshop}/participants')
            ->whereNumber('workshop')
            ->name('workshops.participants.')
            ->middleware('permission:workshop_participants.view')
            ->controller(WorkshopParticipantController::class)
            ->group(function () {
                Route::get('/', 'byWorkshop')
                    ->name('index');

                Route::get('/create', 'createForWorkshop')
                    ->middleware('permission:workshop_participants.create')
                    ->name('create');

                Route::post('/', 'storeForWorkshop')
                    ->middleware('permission:workshop_participants.create')
                    ->name('store');
            });
    });

    /*
    |--------------------------------------------------------------------------
    | Academic - Instructor Schedules
    |--------------------------------------------------------------------------
    */
    Route::prefix('instructor-schedules')->name('instructor-schedules.')->middleware('permission:instructor_schedules.view')->group(function () {
        Route::get('/material-topics', [InstructorScheduleController::class, 'materialTopics'])
            ->name('material-topics');

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
    |
    | Route names follow the navigation configuration:
    | - instructor-tracking.index
    | - instructor-tracking.show
    | - instructor-tracking.check-in
    | - instructor-tracking.save-draft
    | - instructor-tracking.check-out
    |
    | Important:
    | The route parameter is {schedule} because the controller methods use
    | InstructorSchedule $schedule for implicit route model binding.
    |
    */
    Route::prefix('instructor-tracking')
        ->name('instructor-tracking.')
        ->middleware('permission:instructor_tracking.view')
        ->controller(InstructorTrackingController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->name('index');

            Route::get('/{schedule}', 'show')
                ->whereNumber('schedule')
                ->name('show');

            Route::post('/{schedule}/check-in', 'checkIn')
                ->whereNumber('schedule')
                ->name('check-in');

            Route::post('/{schedule}/save-draft', 'saveDraft')
                ->whereNumber('schedule')
                ->name('save-draft');

            Route::post('/{schedule}/check-out', 'checkOut')
                ->whereNumber('schedule')
                ->name('check-out');
        });

    /*
    |--------------------------------------------------------------------------
    | Marketing
    |--------------------------------------------------------------------------
    */
    Route::prefix('marketing')->name('marketing.')->middleware('permission:marketing.view')->group(function () {
        Route::get('/dashboard', [MarketingDashboardController::class, 'index'])->middleware('permission:marketing.dashboard.view')->name('dashboard');

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
            Route::get('/create', [MarketingReportController::class, 'create'])->middleware('permission:marketing_reports.create')->name('create');
            Route::post('/', [MarketingReportController::class, 'store'])->middleware('permission:marketing_reports.create')->name('store');
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