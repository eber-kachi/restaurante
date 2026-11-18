<?php

use App\Http\Middleware\SuperAdmin;
use Illuminate\Support\Facades\Route;
use Modules\ErrorTracker\Http\Controllers\ErrorEventController;

Route::middleware([
    'auth',
    config('jetstream.auth_session'),
    'verified',
    SuperAdmin::class,
])->prefix('error-tracker')->name('superadmin.error-tracker.')->group(function () {
    Route::get('/', [ErrorEventController::class, 'index'])->name('index');
    Route::get('{event}', [ErrorEventController::class, 'show'])->name('show')->whereNumber('event');
    Route::post('{event}/resolve', [ErrorEventController::class, 'resolve'])->name('resolve')->whereNumber('event');
    Route::post('{event}/reopen', [ErrorEventController::class, 'reopen'])->name('reopen')->whereNumber('event');
    Route::delete('{event}', [ErrorEventController::class, 'destroy'])->name('destroy')->whereNumber('event');
    Route::post('resolve-all', [ErrorEventController::class, 'resolveAll'])->name('resolve-all');
});
