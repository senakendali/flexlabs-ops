<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

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
    | Trial Class Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('trial')->name('trial-')->group(function () {

        Route::get('/classes', fn () => view('trial.classes.index'))->name('classes.index');
        Route::get('/schedules', fn () => view('trial.schedules.index'))->name('schedules.index');
        Route::get('/participants', fn () => view('trial.participants.index'))->name('participants.index');

    });


    /*
    |--------------------------------------------------------------------------
    | Enrollment (Programs, Batches, Students)
    |--------------------------------------------------------------------------
    */
    Route::prefix('enrollment')->group(function () {

        Route::get('/programs', fn () => view('enrollment.programs.index'))->name('programs.index');
        Route::get('/batches', fn () => view('enrollment.batches.index'))->name('batches.index');
        Route::get('/students', fn () => view('enrollment.students.index'))->name('students.index');

        Route::get('/', fn () => view('enrollment.index'))->name('enrollments.index');
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