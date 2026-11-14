<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AssignmentRuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\MyAlertsController;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------------
// Rotas Públicas (acessíveis sem login - Painel / TV)
// -------------------------------------------------------------
Route::get('/', [IndexController::class, 'index'])->name('home');
Route::get('/api/public-alerts', [IndexController::class, 'publicAlertsJson'])->name('api.public-alerts');

// -------------------------------------------------------------
// Rotas Autenticadas (usuários logados)
// -------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    // Meus Alertas
    Route::get('/meus-alertas', [MyAlertsController::class, 'index'])->name('alerts.my');

    // Ações de resolução de alerta (admin ou pessoa atribuída)
    Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::post('/alerts/{alert}/reopen', [AlertController::class, 'reopen'])->name('alerts.reopen');

    // Detalhes, comentários e "atribuir a mim" (admin ou responsável pelo alerta)
    Route::get('/alerts/{alert}', [AlertController::class, 'show'])->whereNumber('alert')->name('alerts.show');
    Route::post('/alerts/{alert}/assign-me', [AlertController::class, 'assignToMe'])->name('alerts.assign-me');
    Route::post('/alerts/{alert}/comments', [AlertController::class, 'storeComment'])->name('alerts.comments.store');

    // -------------------------------------------------------------
    // Rotas de Administrador (somente admins)
    // -------------------------------------------------------------
    Route::middleware(['can:admin'])->group(function () {
        // Dashboard dedicado
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Alertas pendentes e resolvidos
        Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
        Route::get('/alerts/resolvidos', [AlertController::class, 'resolved'])->name('alerts.resolved');
        Route::get('/alertas/resolvidos', fn() => redirect()->route('alerts.resolved'));

        // CRUD Alertas
        Route::get('/alerts/create', [AlertController::class, 'create'])->name('alerts.create');
        Route::post('/alerts', [AlertController::class, 'store'])->name('alerts.store');
        Route::get('/alerts/{alert}/edit', [AlertController::class, 'edit'])->name('alerts.edit');
        Route::patch('/alerts/{alert}', [AlertController::class, 'update'])->name('alerts.update');
        Route::delete('/alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');

        // Atribuição de responsável
        Route::post('/alerts/{alert}/assign-user', [AlertController::class, 'assignUser'])->name('alerts.assign-user');

        // Regras de atribuição automática
        Route::resource('assignment-rules', AssignmentRuleController::class);

        // Resolução em lote
        Route::post('/alerts/bulk-resolve', [AlertController::class, 'bulkResolve'])->name('alerts.bulk-resolve');
    });
});
