<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlertRequest;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AlertController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
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
            ->get();

        return view('alerts.index', [
            'alerts' => $alerts,
            'title' => 'Alertas pendentes',
            'statusFilter' => 'pendente',
            'users' => User::orderBy('name')->get(),
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
                        ->orWhere('mensagem', 'like', '%' . $request->search . '%');
                });
            })
            ->where('status_resolucao', 'resolvido')
            ->orderByDesc('data_ocorrencia')
            ->get();

        return view('alerts.index', [
            'alerts' => $alerts,
            'title' => 'Alertas resolvidos',
            'statusFilter' => 'resolvido',
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        Gate::authorize('admin');

        return view('alerts.create', [
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(AlertRequest $request)
    {
        $payload = $request->validated();
        $payload['status_resolucao'] = $payload['status_resolucao'] ?? 'pendente';
        $payload['comentario'] = $payload['comentario'] ?? null;
        $payload['service_name'] = $payload['service_name'] ?? null;
        $payload['atribuido'] = $payload['atribuido'] ?? null;
        $payload['resolvido_por_id'] = null;

        $alert = Alert::create($payload);

        return redirect()->route('alerts.index')->with('success', 'Alerta cadastrado com sucesso.');
    }

    public function show(Alert $alert)
    {
        return view('alerts.show', [
            'alert' => $alert->load(['atribuidoUser', 'resolvidoPor']),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function edit(Alert $alert)
    {
        return view('alerts.edit', [
            'alert' => $alert->load(['atribuidoUser', 'resolvidoPor']),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(AlertRequest $request, Alert $alert)
    {
        $data = $request->validated();

        if ($request->filled('status_resolucao')) {
            $alert->status_resolucao = $request->status_resolucao;
            if ($request->status_resolucao === 'resolvido' && empty($alert->resolvido_por_id)) {
                $alert->resolvido_por_id = auth()->id();
            }

            if ($request->status_resolucao === 'pendente') {
                $alert->resolvido_por_id = null;
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

        return redirect()->route('alerts.show', ['alert' => $alert])->with('success', 'Alerta atualizado.');
    }

    public function destroy(Alert $alert)
    {
        $alert->delete();

        return redirect()->route('alerts.index')->with('success', 'Alerta removido.');
    }

    public function toggleStatus(Alert $alert)
    {
        $alert->status_resolucao = $alert->status_resolucao === 'pendente' ? 'resolvido' : 'pendente';
        if ($alert->status_resolucao === 'resolvido') {
            $alert->resolvido_por_id = auth()->id();
        } else {
            $alert->resolvido_por_id = null;
        }
        $alert->save();

        return back()->with('success', 'Status do alerta atualizado.');
    }

    public function assignUser(Request $request, Alert $alert)
    {
        $request->validate([
            'atribuido' => ['nullable', 'exists:users,id'],
        ]);

        $alert->atribuido = $request->input('atribuido');
        $alert->save();

        return back()->with('success', 'Atribuição atualizada.');
    }
}