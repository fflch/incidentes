<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [IndexController::class, 'index'])->name('home');

    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/resolvidos', [AlertController::class, 'resolved'])->name('alerts.resolved');
    Route::get('/alerts/create', [AlertController::class, 'create'])->name('alerts.create');
    Route::post('/alerts', [AlertController::class, 'store'])->name('alerts.store');
    Route::get('/alerts/{alert}', [AlertController::class, 'show'])->name('alerts.show');
    Route::get('/alerts/{alert}/edit', [AlertController::class, 'edit'])->name('alerts.edit');
    Route::patch('/alerts/{alert}', [AlertController::class, 'update'])->name('alerts.update');
    Route::post('/alerts/{alert}/toggle-status', [AlertController::class, 'toggleStatus'])->name('alerts.toggle-status');
    Route::post('/alerts/{alert}/assign-user', [AlertController::class, 'assignUser'])->name('alerts.assign-user');
    Route::delete('/alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');
});
