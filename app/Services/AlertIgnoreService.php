<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\IgnoreRule;
use Illuminate\Support\Facades\Cache;

class AlertIgnoreService
{
    public const LAST_RUN_CACHE_KEY = 'alerts:apply-ignore-rules:last-run';

    /**
     * Aplica as regras para ignorar aos pendentes e guarda o resultado em
     * cache. Usado pelo agendamento (antes do agrupamento e da atribuição) e
     * pela interface.
     */
    public function run(string $origem = 'agendamento'): array
    {
        $result = [
            'executado_em' => now()->toIso8601String(),
            'origem' => $origem,
            'ignorados' => $this->ignorePending(),
        ];

        Cache::forever(self::LAST_RUN_CACHE_KEY, $result);

        return $result;
    }

    public function lastRun(): ?array
    {
        return Cache::get(self::LAST_RUN_CACHE_KEY);
    }

    /**
     * Ignora os alertas principais pendentes cuja mensagem contém o texto de
     * alguma regra (ex.: inseridos direto via SQL, sem passar pelo evento
     * "creating" do model). O grupo inteiro (principal + repetições) é
     * ignorado e perde o responsável.
     *
     * Alertas reativados manualmente depois de ignorados (nao_ignorar) são
     * respeitados e não são ignorados de novo.
     *
     * Se $only for informada, aplica somente essa regra.
     * Retorna quantos grupos foram ignorados.
     */
    public function ignorePending(?IgnoreRule $only = null): int
    {
        $rules = $only ? collect([$only]) : IgnoreRule::query()->orderBy('id')->get();

        if ($rules->isEmpty()) {
            return 0;
        }

        $ignored = 0;

        Alert::query()
            ->whereNull('parent_id')
            ->where('status_resolucao', Alert::STATUS_PENDENTE)
            ->where('nao_ignorar', false)
            ->orderBy('id')
            ->chunkById(500, function ($alerts) use ($rules, &$ignored) {
                foreach ($alerts as $alert) {
                    $rule = $rules->first(fn (IgnoreRule $r) => $r->matches($alert->mensagem));

                    if ($rule) {
                        $alert->ignoreGroup($rule);
                        $ignored++;
                    }
                }
            });

        return $ignored;
    }
}
