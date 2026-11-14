<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlertRequest;
use App\Models\Alert;
use App\Models\AlertComment;
use App\Models\User;
use App\Services\AlertDiagnosticsService;
use App\Services\AlertGroupingService;
use App\Services\AlertNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin')->except(['resolve', 'show', 'assignToMe', 'storeComment']);
    }

    public function index(Request $request)
    {
        $alerts = Alert::query()
            ->with(['atribuidoUser', 'resolvidoPor'])
            ->whereNull('parent_id')
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
            ->whereNull('parent_id')
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

        $relatedAlerts = Alert::query()
            ->whereNull('parent_id')
            ->orderByDesc('data_ocorrencia')
            ->limit(200)
            ->get();

        return view('alerts.create', [
            'users' => User::getAdmins(),
            'suggestedServices' => $suggestedServices,
            'relatedAlerts' => $relatedAlerts,
        ]);
    }

    public function store(AlertRequest $request)
    {
        $payload = $request->validated();
        $payload['status_resolucao'] = $payload['status_resolucao'] ?? 'pendente';
        $payload['comentario'] = $payload['comentario'] ?? null;
        $payload['service_name'] = $payload['service_name'] ?? null;
        $payload['atribuido'] = $payload['atribuido'] ?? null;

        if ($payload['status_resolucao'] === 'resolvido') {
            $payload['atribuido'] = $payload['atribuido'] ?? auth()->id();
            $payload['resolvido_por_id'] = auth()->id();
            $payload['resolvido_em'] = now();
        } else {
            $payload['resolvido_por_id'] = null;
            $payload['resolvido_em'] = null;
        }

        $alert = Alert::create($payload);

        // Se for similar a um grupo já existente, agrega ao alerta mais antigo do grupo
        $groupRoot = empty($payload['parent_id'])
            ? app(AlertGroupingService::class)->groupAlert($alert)
            : null;

        if ($groupRoot) {
            return redirect()
                ->route('alerts.show', $groupRoot)
                ->with('success', "Alerta cadastrado e agrupado ao alerta #{$groupRoot->id} (similar e mais antigo).");
        }

        // Se atribuído (seja manualmente ou via AssignmentRule em booted/creating), notifica por e-mail
        if (!empty($alert->atribuido)) {
            AlertNotificationService::notifyAssignment($alert);
        }

        return redirect()->route('alerts.index')->with('success', 'Alerta cadastrado com sucesso.');
    }

    public function show(Alert $alert)
    {
        abort_unless($alert->canBeAccessedBy(auth()->user()), 403, 'Você só pode ver alertas atribuídos a você.');

        return view('alerts.show', [
            'alert' => $alert->load(['atribuidoUser', 'resolvidoPor']),
            'users' => User::getAdmins(),
            'comments' => $alert->groupComments(),
            'diag' => app(AlertDiagnosticsService::class)->forGroup($alert),
        ]);
    }

    /**
     * Atribui o alerta (o grupo inteiro) ao usuário logado
     */
    public function assignToMe(Request $request, Alert $alert)
    {
        $root = $alert->groupRoot();
        $userId = auth()->id();

        if ((int) $root->atribuido !== (int) $userId) {
            $root->atribuido = $userId;
            $root->save();

            // Quem se atribuiu não precisa receber e-mail; apenas a gestão é avisada
            AlertNotificationService::notifyAssignment($root, notifyAssignee: false);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Alerta atribuído a você.',
                'atribuido_id' => $userId,
                'atribuido_nome' => auth()->user()->name,
            ]);
        }

        return back()->with('success', 'Alerta atribuído a você.');
    }

    /**
     * Adiciona um comentário ao alerta sem resolvê-lo e avisa o responsável por e-mail
     */
    public function storeComment(Request $request, Alert $alert)
    {
        abort_unless($alert->canBeAccessedBy(auth()->user()), 403);

        $validated = $request->validate([
            'comentario' => ['required', 'string', 'max:5000'],
        ], [
            'comentario.required' => 'Escreva um comentário.',
        ]);

        $root = $alert->groupRoot();

        $comment = AlertComment::create([
            'alert_id' => $root->id,
            'user_id' => auth()->id(),
            'comentario' => trim($validated['comentario']),
        ]);

        AlertNotificationService::notifyComment($root, $comment);

        return redirect()
            ->to(route('alerts.show', $alert) . '#comentarios')
            ->with('success', 'Comentário adicionado.');
    }

    public function edit(Alert $alert)
    {
        $suggestedServices = Alert::query()
            ->whereNotNull('service_name')
            ->where('service_name', '!=', '')
            ->distinct()
            ->orderBy('service_name')
            ->pluck('service_name');

        $relatedAlerts = Alert::query()
            ->whereNull('parent_id')
            ->whereKeyNot($alert->id)
            ->orderByDesc('data_ocorrencia')
            ->limit(200)
            ->get();

        return view('alerts.edit', [
            'alert' => $alert->load(['atribuidoUser', 'resolvidoPor']),
            'users' => User::getAdmins(),
            'suggestedServices' => $suggestedServices,
            'relatedAlerts' => $relatedAlerts,
        ]);
    }

    public function update(AlertRequest $request, Alert $alert)
    {
        $data = $request->validated();
        $oldAtribuido = $alert->atribuido;

        if ($request->filled('status_resolucao')) {
            $alert->status_resolucao = $request->status_resolucao;
            if ($request->status_resolucao === 'resolvido') {
                $alert->resolvido_por_id = auth()->id();
                $alert->resolvido_em = now();
                $alert->atribuido = $alert->atribuido ?: auth()->id();
                if ($alert->atribuido !== auth()->id()) {
                    $alert->atribuido = auth()->id();
                }
            }

            if ($request->status_resolucao === 'pendente') {
                $alert->resolvido_por_id = null;
                $alert->resolvido_em = null;
            }
        }

        if ($request->has('parent_id')) {
            $alert->parent_id = $request->parent_id ?: null;
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
        $validated = $request->validate([
            'comentario' => ['required', 'string', 'min:3'],
        ], [
            'comentario.required' => 'É obrigatório informar um comentário descrevendo a resolução do alerta.',
            'comentario.min' => 'O comentário deve conter pelo menos 3 caracteres.',
        ]);

        $userId = auth()->id();
        $root = $alert->groupRoot();
        $root->resolveGroup(trim($validated['comentario']), $userId);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Alerta marcado como resolvido com sucesso.',
                'alert' => $root,
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

        $alert->groupRoot()->reopenGroup();

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
                'atribuido_nome' => $alert->atribuidoUser ? $alert->atribuidoUser->name : 'Não atribuído',
            ]);
        }

        return back()->with('success', 'Atribuição atualizada com sucesso.');
    }

    /**
     * Ação em lote para resolver alertas com comentário único
     */
    public function bulkResolve(Request $request)
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'comentario' => ['required', 'string', 'min:3'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:alerts,id'],
        ], [
            'comentario.required' => 'É obrigatório informar um comentário para resolver os alertas em lote.',
            'comentario.min' => 'O comentário deve conter pelo menos 3 caracteres.',
            'ids.required' => 'Selecione pelo menos um alerta para resolver em lote.',
        ]);

        $userId = auth()->id();
        $roots = collect($validated['ids'])
            ->map(function ($id) {
                $alert = Alert::find($id);
                return $alert ? $alert->groupRoot() : null;
            })
            ->filter()
            ->unique('id');

        if ($roots->isEmpty()) {
            return back()->with('error', 'Nenhum alerta pendente foi encontrado para resolver em lote.');
        }

        foreach ($roots as $root) {
            $root->resolveGroup(trim($validated['comentario']), $userId);
        }

        return back()->with('success', 'Resolvidos ' . $roots->count() . ' grupo(s) de alertas em lote com sucesso.');
    }
}
