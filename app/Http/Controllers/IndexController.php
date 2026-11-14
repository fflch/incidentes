<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IndexController extends Controller
{
    /**
     * Home Pública - Painel / TV de alertas pendentes
     * Acessível publicamente (anônimos) e com recursos extras para administradores logados.
     */
    public function index(Request $request)
    {
        // Se for requisição JSON (para auto-refresh do painel de TV ou API pública segura)
        if ($request->expectsJson() || $request->query('format') === 'json') {
            return $this->publicAlertsJson();
        }

        $isAdmin = auth()->check() && Gate::allows('admin');

        return view('home', [
            'panel' => $this->panelPayload(),
            'isAdmin' => $isAdmin,
            'users' => $isAdmin ? User::getAdmins()->map->only(['id', 'name'])->values() : collect(),
        ]);
    }

    /**
     * Endpoint JSON seguro para atualização automática do painel sem F5
     * Retorna SOMENTE dados não sensíveis: data da ocorrência, nome do serviço e responsável atribuído.
     * Não expõe a mensagem de erro (que pode conter credenciais/senhas).
     */
    public function publicAlertsJson()
    {
        return response()->json($this->panelPayload());
    }

    protected function panelPayload(): array
    {
        $now = now();

        $alerts = Alert::with('atribuidoUser')
            ->withCount('children')
            ->withMax('children', 'data_ocorrencia')
            ->whereNull('parent_id')
            ->where('status_resolucao', 'pendente')
            ->orderByDesc('data_ocorrencia')
            ->get()
            ->map(function (Alert $alert) use ($now) {
                $ultima = $alert->children_max_data_ocorrencia
                    ? Carbon::parse($alert->children_max_data_ocorrencia)
                    : $alert->data_ocorrencia;

                return [
                    'id' => $alert->id,
                    'service_name' => $alert->service_name ?? $alert->type,
                    'type' => $alert->type,
                    'data_ocorrencia' => $alert->data_ocorrencia?->toIso8601String(),
                    'data_ocorrencia_formatada' => $alert->data_ocorrencia?->format('d/m/Y H:i'),
                    'data_ocorrencia_relativa' => $alert->data_ocorrencia?->diffForHumans(),
                    'idade_minutos' => $alert->data_ocorrencia ? (int) $alert->data_ocorrencia->diffInMinutes($now) : 0,
                    'repeticoes' => (int) $alert->children_count,
                    'ultima_ocorrencia_relativa' => $alert->children_count > 0 ? $ultima?->diffForHumans() : null,
                    'atribuido_nome' => $alert->atribuidoUser ? $alert->atribuidoUser->name : 'Não atribuído',
                    'atribuido_id' => $alert->atribuido,
                    'status_resolucao' => $alert->status_resolucao,
                ];
            })
            ->values();

        $oldest = $alerts->sortByDesc('idade_minutos')->first();

        return [
            'total' => $alerts->count(),
            'nao_atribuidos' => $alerts->whereNull('atribuido_id')->count(),
            'ocorrencias' => $alerts->sum(fn ($a) => $a['repeticoes'] + 1),
            'mais_antigo_relativo' => $oldest['data_ocorrencia_relativa'] ?? null,
            'updated_at' => $now->format('H:i:s'),
            'updated_at_full' => $now->format('d/m/Y H:i:s'),
            'alerts' => $alerts,
        ];
    }
}
