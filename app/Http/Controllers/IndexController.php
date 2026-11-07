<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\User;
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

        $alerts = Alert::with('atribuidoUser')
            ->where('status_resolucao', 'pendente')
            ->orderByDesc('data_ocorrencia')
            ->get();

        $isAdmin = auth()->check() && Gate::allows('admin');
        $users = $isAdmin ? User::getAdmins() : collect();

        return view('home', [
            'alerts' => $alerts,
            'isAdmin' => $isAdmin,
            'users' => $users,
        ]);
    }

    /**
     * Endpoint JSON seguro para atualização automática do painel sem F5
     * Retorna SOMENTE dados não sensíveis: data da ocorrência, nome do serviço e responsável atribuído.
     * Não expõe a mensagem de erro (que pode conter credenciais/senhas).
     */
    public function publicAlertsJson()
    {
        $alerts = Alert::with('atribuidoUser')
            ->where('status_resolucao', 'pendente')
            ->orderByDesc('data_ocorrencia')
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'service_name' => $alert->service_name ?? $alert->type,
                    'type' => $alert->type,
                    'data_ocorrencia' => $alert->data_ocorrencia ? $alert->data_ocorrencia->toIso8601String() : null,
                    'data_ocorrencia_formatada' => $alert->data_ocorrencia ? $alert->data_ocorrencia->format('d/m/Y H:i') : null,
                    'data_ocorrencia_relativa' => $alert->data_ocorrencia ? $alert->data_ocorrencia->diffForHumans() : null,
                    'atribuido_nome' => $alert->atribuidoUser ? $alert->atribuidoUser->name : 'Não atribuído',
                    'atribuido_id' => $alert->atribuido,
                    'status_resolucao' => $alert->status_resolucao,
                ];
            });

        return response()->json([
            'total' => $alerts->count(),
            'updated_at' => now()->format('H:i:s'),
            'updated_at_full' => now()->format('d/m/Y H:i:s'),
            'alerts' => $alerts,
        ]);
    }
}
