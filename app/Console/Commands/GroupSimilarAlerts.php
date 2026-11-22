<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Services\AlertGroupingService;
use Illuminate\Console\Command;

class GroupSimilarAlerts extends Command
{
    protected $signature = 'alerts:group-similar {--threshold=80}';

    protected $description = 'Agrupa alertas similares (mesmo serviço e texto parecido) sob o alerta mais antigo do grupo.';

    public function handle(AlertGroupingService $groupingService): int
    {
        $threshold = (float) $this->option('threshold');

        $pendingBefore = Alert::query()->whereNull('parent_id')->where('status_resolucao', 'pendente')->count();

        $result = $groupingService->run($threshold);
        $fixedRoots = $result['raizes_corrigidas'];
        $groupedPending = $result['pendentes_agrupados'];
        $groupedIgnored = $result['ignorados_agrupados'];

        if ($fixedRoots > 0) {
            $this->warn("{$fixedRoots} grupo(s) tiveram a raiz corrigida para o alerta mais antigo.");
        }

        $this->info("Agrupamento concluído: {$groupedPending} pendente(s) e {$groupedIgnored} ignorado(s) agrupado(s).");
        $this->line("Alertas principais pendentes antes da varredura: {$pendingBefore}");

        return self::SUCCESS;
    }
}
