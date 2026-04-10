<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Trial\TrialClassController;

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
| Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Trial Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('trial')->name('trial-')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Trial Classes
        |--------------------------------------------------------------------------
        */
        Route::get('/classes', [TrialClassController::class, 'index'])->name('classes.index');
        Route::post('/classes', [TrialClassController::class, 'store'])->name('classes.store');
        Route::get('/classes/{trialClass}', [TrialClassController::class, 'show'])->name('classes.show');
        Route::put('/classes/{trialClass}', [TrialClassController::class, 'update'])->name('classes.update');
        Route::delete('/classes/{trialClass}', [TrialClassController::class, 'destroy'])->name('classes.destroy');


        /*
        |--------------------------------------------------------------------------
        | Trial Themes (FIX ERROR DISINI)
        |--------------------------------------------------------------------------
        */
        Route::get('/themes', fn () => view('trial.themes.index'))->name('themes.index');


        /*
        |--------------------------------------------------------------------------
        | Trial Schedules
        |--------------------------------------------------------------------------
        */
        Route::get('/schedules', fn () => view('trial.schedules.index'))->name('schedules.index');


        /*
        |--------------------------------------------------------------------------
        | Trial Participants
        |--------------------------------------------------------------------------
        */
        Route::get('/participants', fn () => view('trial.participants.index'))->name('participants.index');

    });


    /*
    |--------------------------------------------------------------------------
    | Enrollment
    |--------------------------------------------------------------------------
    */
    Route::prefix('enrollment')->group(function () {

        Route::get('/', fn () => view('enrollment.index'))->name('enrollments.index');
        Route::get('/programs', fn () => view('enrollment.programs.index'))->name('programs.index');
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
    | Instructor Monitoring
    |--------------------------------------------------------------------------
    */
    Route::prefix('instructors')->group(function () {

        Route::get('/', fn () => view('instructors.index'))->name('instructors.index');
        Route::get('/sessions', fn () => view('instructors.sessions.index'))->name('sessions.index');
        Route::get('/monitoring', fn () => view('instructors.monitoring.index'))->name('monitoring.index');

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