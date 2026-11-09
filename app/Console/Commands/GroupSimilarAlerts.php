<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Services\AlertGroupingService;
use Illuminate\Console\Command;

class GroupSimilarAlerts extends Command
{
    protected $signature = 'alerts:group-similar {--threshold=80}';

    protected $description = 'Agrupa alertas pendentes e resolvidos similares pelo serviço e texto da mensagem.';

    public function handle(AlertGroupingService $groupingService): int
    {
        $threshold = (float) $this->option('threshold');

        $rootAlerts = Alert::query()->whereNull('parent_id')->count();
        $pendingBefore = Alert::query()->whereNull('parent_id')->where('status_resolucao', 'pendente')->count();
        $resolvedBefore = Alert::query()->whereNull('parent_id')->where('status_resolucao', 'resolvido')->count();

        $groupedPending = $groupingService->groupPendingAlerts($threshold);
        $groupedResolved = $groupingService->groupResolvedAlerts($threshold);

        $this->info("Agrupamento concluído: {$groupedPending} pendentes e {$groupedResolved} resolvidos agrupados.");
        $this->line("Raizes atuais: {$rootAlerts} | pendentes: {$pendingBefore} | resolvidos: {$resolvedBefore}");

        return self::SUCCESS;
    }
}
