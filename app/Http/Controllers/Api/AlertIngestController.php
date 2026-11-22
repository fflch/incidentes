<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\AlertGroupingService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertIngestController extends Controller
{
    /**
     * Recebe um alerta de um monitor externo.
     *
     * Equivale ao antigo INSERT IGNORE: se já existir um alerta com o mesmo
     * par (type, id_externo), nada é alterado e a resposta é 200. Um alerta
     * novo passa pelas regras de atribuição automática e pelo agrupamento de
     * alertas similares, e a resposta é 201.
     */
    public function store(Request $request, AlertGroupingService $grouping): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'id_externo' => ['required', 'string', 'max:255'],
            'service_name' => ['nullable', 'string', 'max:255'],
            'data_ocorrencia' => ['nullable', 'date'],
            'mensagem' => ['required', 'string', 'max:65000'],
        ]);

        $existing = $this->find($data);
        if ($existing) {
            return $this->response($existing, false);
        }

        $timezone = config('app.timezone');
        $data['data_ocorrencia'] = isset($data['data_ocorrencia'])
            // Data com fuso é convertida; data sem fuso é tratada como horário local
            ? Carbon::parse($data['data_ocorrencia'], $timezone)->setTimezone($timezone)
            : now();
        $data['status_resolucao'] = 'pendente';

        try {
            $alert = Alert::create($data);
        } catch (UniqueConstraintViolationException) {
            // Outra requisição criou o mesmo alerta ao mesmo tempo
            return $this->response($this->find($data), false);
        }

        $grouping->groupAlert($alert);

        return $this->response($alert->fresh(), true);
    }

    private function find(array $data): ?Alert
    {
        return Alert::where('type', $data['type'])
            ->where('id_externo', $data['id_externo'])
            ->first();
    }

    private function response(Alert $alert, bool $created): JsonResponse
    {
        return response()->json([
            'created' => $created,
            'id' => $alert->id,
            'parent_id' => $alert->parent_id,
            'atribuido' => $alert->atribuido,
            'status' => $alert->status_resolucao,
        ], $created ? 201 : 200);
    }
}
