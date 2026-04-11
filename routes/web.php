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
        Route::get('/batches', fn () => view('enrollment.batches.index'))->name('batches.index');
        Route::get('/students', fn () => view('enrollment.students.index'))->name('students.index');
    });


    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */
    Route::prefix('payments')->group(function () {
        Route::get('/', fn () => view('payments.index'))->name('payments.index');
        Route::get('/orders', fn () => view('payments.orders.index'))->name('orders.index');
        Route::get('/schedules', fn () => view('payments.schedules.index'))->name('payment-schedules.index');
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
    | Profile (default Breeze)
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});


require __DIR__.'/auth.php';