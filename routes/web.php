<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Program\ProgramController;

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
    Route::prefix('instructors')->group(function () {
        Route::get('/', fn () => view('instructors.index'))->name('instructors.index');
        Route::get('/sessions', fn () => view('instructors.sessions.index'))->name('sessions.index');
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