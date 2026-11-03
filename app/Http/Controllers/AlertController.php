<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlertRequest;
use App\Models\Alert;
use App\Models\User;
use App\Services\AlertNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin')->except(['resolve']);
    }

    public function index(Request $request)
    {
        $alerts = Alert::query()
            ->with(['atribuidoUser', 'resolvidoPor'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('id_externo', 'like', '%' . $request->search . '%')
                        ->orWhere('service_name', 'like', '%' . $request->search . '%')
                        ->orWhere('mensagem', 'like', '%' . $request->search . '%');
                });
            })
            ->where('status_resolucao', 'pendente')
            ->orderByDesc('data_ocorrencia')
            ->paginate(20)
            ->withQueryString();

        return view('alerts.index', [
            'alerts' => $alerts,
            'title' => 'Alertas pendentes',
            'statusFilter' => 'pendente',
            'users' => User::getAdmins(),
        ]);
    }

    public function resolved(Request $request)
    {
        $alerts = Alert::query()
            ->with(['atribuidoUser', 'resolvidoPor'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('id_externo', 'like', '%' . $request->search . '%')
                        ->orWhere('service_name', 'like', '%' . $request->search . '%')
                        ->orWhere('mensagem', 'like', '%' . $request->search . '%')
                        ->orWhere('comentario', 'like', '%' . $request->search . '%');
                });
            })
            ->where('status_resolucao', 'resolvido')
            ->orderByDesc('resolvido_em')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('alerts.resolved', [
            'alerts' => $alerts,
            'title' => 'Alertas resolvidos',
            'statusFilter' => 'resolvido',
            'users' => User::getAdmins(),
        ]);
    }

    public function create()
    {
        Gate::authorize('admin');

        $suggestedServices = Alert::query()
            ->whereNotNull('service_name')
            ->where('service_name', '!=', '')
            ->distinct()
            ->orderBy('service_name')
            ->pluck('service_name');

        return view('alerts.create', [
            'users' => User::getAdmins(),
            'suggestedServices' => $suggestedServices,
        ]);
    }

    public function store(AlertRequest $request)
    {
        $payload = $request->validated();
        $payload['status_resolucao'] = $payload['status_resolucao'] ?? 'pendente';
        $payload['comentario'] = $payload['comentario'] ?? null;
        $payload['service_name'] = $payload['service_name'] ?? null;
        $payload['atribuido'] = $payload['atribuido'] ?? null;
        $payload['resolvido_por_id'] = $payload['status_resolucao'] === 'resolvido' ? auth()->id() : null;
        $payload['resolvido_em'] = $payload['status_resolucao'] === 'resolvido' ? now() : null;

        $alert = Alert::create($payload);

        // Se atribuído (seja manualmente ou via AssignmentRule em booted/creating), notifica por e-mail
        if (!empty($alert->atribuido)) {
            AlertNotificationService::notifyAssignment($alert);
        }

        return redirect()->route('alerts.index')->with('success', 'Alerta cadastrado com sucesso.');
    }

    public function show(Alert $alert)
    {
        return view('alerts.show', [
            'alert' => $alert->load(['atribuidoUser', 'resolvidoPor']),
            'users' => User::getAdmins(),
        ]);
    }

    public function edit(Alert $alert)
    {
        $suggestedServices = Alert::query()
            ->whereNotNull('service_name')
            ->where('service_name', '!=', '')
            ->distinct()
            ->orderBy('service_name')
            ->pluck('service_name');

        return view('alerts.edit', [
            'alert' => $alert->load(['atribuidoUser', 'resolvidoPor']),
            'users' => User::getAdmins(),
            'suggestedServices' => $suggestedServices,
        ]);
    }

    public function update(AlertRequest $request, Alert $alert)
    {
        $data = $request->validated();
        $oldAtribuido = $alert->atribuido;

        if ($request->filled('status_resolucao')) {
            $alert->status_resolucao = $request->status_resolucao;
            if ($request->status_resolucao === 'resolvido') {
                if (empty($alert->resolvido_por_id)) {
                    $alert->resolvido_por_id = auth()->id();
                }
                if (empty($alert->resolvido_em)) {
                    $alert->resolvido_em = now();
                }
            }

            if ($request->status_resolucao === 'pendente') {
                $alert->resolvido_por_id = null;
                $alert->resolvido_em = null;
            }
        }

        if ($request->filled('comentario')) {
            $alert->comentario = $request->comentario;
        }

        if ($request->has('atribuido')) {
            $alert->atribuido = $request->atribuido;
        }

        $alert->fill($data);
        $alert->save();

        if (!empty($alert->atribuido) && $alert->atribuido != $oldAtribuido) {
            AlertNotificationService::notifyAssignment($alert);
        }

        return redirect()->route('alerts.show', ['alert' => $alert])->with('success', 'Alerta atualizado.');
    }

    public function destroy(Alert $alert)
    {
        $alert->delete();

        return redirect()->route('alerts.index')->with('success', 'Alerta removido.');
    }

    /**
     * Marca o alerta como resolvido com comentário obrigatório
     */
    public function resolve(Request $request, Alert $alert)
    {
        // Autorização: deve ser admin ou a pessoa atribuída
        if (!Gate::allows('admin') && $alert->atribuido !== auth()->id()) {
            abort(403, 'Ação não autorizada.');
        }

        $validated = $request->validate([
            'comentario' => ['required', 'string', 'min:3'],
        ], [
            'comentario.required' => 'É obrigatório informar um comentário descrevendo a resolução do alerta.',
            'comentario.min' => 'O comentário deve conter pelo menos 3 caracteres.',
        ]);

        $alert->status_resolucao = 'resolvido';
        $alert->comentario = trim($validated['comentario']);
        $alert->resolvido_por_id = auth()->id();
        $alert->resolvido_em = now();
        $alert->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Alerta marcado como resolvido com sucesso.',
                'alert' => $alert,
            ]);
        }

        return back()->with('success', 'Alerta marcado como resolvido com sucesso.');
    }

    /**
     * Reabre um alerta resolvido
     */
    public function reopen(Alert $alert)
    {
        Gate::authorize('admin');

        $alert->status_resolucao = 'pendente';
        $alert->resolvido_por_id = null;
        $alert->resolvido_em = null;
        $alert->save();

        return back()->with('success', 'Alerta reaberto com sucesso.');
    }

    /**
     * Atribui ou altera o responsável por um alerta
     */
    public function assignUser(Request $request, Alert $alert)
    {
        Gate::authorize('admin');

        $request->validate([
            'atribuido' => ['nullable', 'exists:users,id'],
        ]);

        $oldAtribuido = $alert->atribuido;
        $newAtribuido = $request->input('atribuido');

        $alert->atribuido = $newAtribuido ?: null;
        $alert->save();

        // Se atribuído a um novo responsável, notifica por e-mail
        if (!empty($newAtribuido) && $newAtribuido != $oldAtribuido) {
            AlertNotificationService::notifyAssignment($alert);
        }

        if ($request->expectsJson() || $request->ajax()) {
            $alert->load('atribuidoUser');
            return response()->json([
                'success' => true,
                'message' => 'Atribuição atualizada com sucesso.',
                'atribuido_nome' => $alert->atribuidoUser?->name ?? 'Não atribuído',
            ]);
        }

        return back()->with('success', 'Atribuição atualizada com sucesso.');
    }
}
