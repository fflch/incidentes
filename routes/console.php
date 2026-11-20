<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Único e-mail de pendências do dia (não há mais e-mail a cada atribuição)
Schedule::command('alerts:daily-summary')->dailyAt('12:00')->withoutOverlapping();
Schedule::command('alerts:group-similar --threshold=80')->everyFiveMinutes();
// Alertas inseridos via SQL não passam pelo evento "creating" do model, então
// as regras de atribuição são aplicadas aqui (depois do agrupamento, para que
// repetições já tenham herdado o responsável do principal).
Schedule::command('alerts:apply-assignment-rules')->everyFiveMinutes()->withoutOverlapping();
