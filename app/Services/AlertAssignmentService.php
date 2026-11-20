<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AssignmentRule;

class AlertAssignmentService
{
    /**
     * Aplica as regras de atribuição aos alertas principais pendentes que
     * ainda não têm responsável (ex.: inseridos direto via SQL, sem passar
     * pelo evento "creating" do model).
     *
     * Cada alerta recebe a regra que receberia ao chegar agora (respeita a
     * prioridade nome do serviço > tipo). Se $only for informada, aplica
     * somente aos alertas cuja regra vencedora seja ela.
     *
     * As repetições (parent_id não nulo) herdam o responsável do principal.
     *
     * Retorna quantos alertas principais foram atribuídos.
     */
    public function assignPendingUnassigned(?AssignmentRule $only = null): int
    {
        if (!AssignmentRule::query()->exists()) {
            return 0;
        }

        $assigned = 0;

        Alert::query()
            ->whereNull('parent_id')
            ->where('status_resolucao', 'pendente')
            ->whereNull('atribuido')
            ->orderBy('id')
            ->chunkById(500, function ($alerts) use ($only, &$assigned) {
                foreach ($alerts as $alert) {
                    $rule = AssignmentRule::findForAlert($alert);

                    if (!$rule || ($only && !$rule->is($only))) {
                        continue;
                    }

                    $alert->assignGroup($rule->user_id);
                    $assigned++;
                }
            });

        return $assigned;
    }
}
