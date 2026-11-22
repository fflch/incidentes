<?php

use App\Http\Controllers\Api\AlertIngestController;
use App\Http\Middleware\VerifyAlertsApiKey;
use Illuminate\Support\Facades\Route;

// Entrada de alertas dos monitores externos (autenticada por ALERTS_API_KEY)
Route::post('/alerts', [AlertIngestController::class, 'store'])
    ->middleware(VerifyAlertsApiKey::class)
    ->name('api.alerts.store');
