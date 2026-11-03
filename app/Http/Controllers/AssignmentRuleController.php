<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AssignmentRule;
use App\Models\User;
use App\Services\AlertNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AssignmentRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
    }

    public function index(Request $request)
    {
        Gate::authorize('admin');

        $rules = AssignmentRule::with('user')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where('service_name', 'like', $term)
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('name', 'like', $term)->orWhere('email', 'like', $term);
                  });
            })
            ->orderBy('service_name')
            ->get();

        return view('assignment-rules.index', [
            'rules' => $rules,
        ]);
    }

    public function create()
    {
        Gate::authorize('admin');

        // Sugere nomes de serviços já existentes nos alertas
        $suggestedServices = Alert::query()
            ->whereNotNull('service_name')
            ->where('service_name', '!=', '')
            ->distinct()
            ->orderBy('service_name')
            ->pluck('service_name');

        return view('assignment-rules.create', [
            'users' => User::getAdmins(),
            'suggestedServices' => $suggestedServices,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'service_name' => ['required', 'string', 'max:255', 'unique:assignment_rules,service_name'],
            'user_id' => ['required', 'exists:users,id'],
            'apply_to_pending' => ['nullable', 'boolean'],
        ], [
            'service_name.required' => 'O nome do serviço é obrigatório.',
            'service_name.unique' => 'Já existe uma regra cadastrada para este serviço.',
            'user_id.required' => 'Selecione um usuário responsável.',
            'user_id.exists' => 'Usuário selecionado inválido.',
        ]);

        $rule = AssignmentRule::create([
            'service_name' => trim($validated['service_name']),
            'user_id' => $validated['user_id'],
        ]);

        // Se solicitado, aplica retroativamente aos alertas pendentes sem atribuição deste serviço
        $appliedCount = 0;
        if ($request->boolean('apply_to_pending')) {
            $pendingAlerts = Alert::where('status_resolucao', 'pendente')
                ->whereNull('atribuido')
                ->where(function ($q) use ($rule) {
                    $q->where('service_name', $rule->service_name)
                      ->orWhereRaw('LOWER(service_name) = ?', [mb_strtolower($rule->service_name)]);
                })
                ->get();

            foreach ($pendingAlerts as $alert) {
                $alert->atribuido = $rule->user_id;
                $alert->save();
                AlertNotificationService::notifyAssignment($alert);
                $appliedCount++;
            }
        }

        $msg = 'Regra de atribuição cadastrada com sucesso!';
        if ($appliedCount > 0) {
            $msg .= " Aplicada retroativamente a {$appliedCount} alerta(s) pendente(s).";
        }

        return redirect()->route('assignment-rules.index')->with('success', $msg);
    }

    public function edit(AssignmentRule $assignmentRule)
    {
        Gate::authorize('admin');

        $suggestedServices = Alert::query()
            ->whereNotNull('service_name')
            ->where('service_name', '!=', '')
            ->distinct()
            ->orderBy('service_name')
            ->pluck('service_name');

        return view('assignment-rules.edit', [
            'rule' => $assignmentRule,
            'users' => User::getAdmins(),
            'suggestedServices' => $suggestedServices,
        ]);
    }

    public function update(Request $request, AssignmentRule $assignmentRule)
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'service_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assignment_rules', 'service_name')->ignore($assignmentRule->id),
            ],
            'user_id' => ['required', 'exists:users,id'],
        ], [
            'service_name.required' => 'O nome do serviço é obrigatório.',
            'service_name.unique' => 'Já existe uma regra cadastrada para este serviço.',
            'user_id.required' => 'Selecione um usuário responsável.',
        ]);

        $assignmentRule->update([
            'service_name' => trim($validated['service_name']),
            'user_id' => $validated['user_id'],
        ]);

        return redirect()->route('assignment-rules.index')->with('success', 'Regra de atribuição atualizada com sucesso.');
    }

    public function destroy(AssignmentRule $assignmentRule)
    {
        Gate::authorize('admin');

        $assignmentRule->delete();

        return redirect()->route('assignment-rules.index')->with('success', 'Regra de atribuição removida com sucesso.');
    }
}

