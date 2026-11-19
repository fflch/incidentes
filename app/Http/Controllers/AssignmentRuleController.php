<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AssignmentRule;
use App\Models\User;
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
                $q->where(function ($q) use ($term) {
                    $q->where('pattern', 'like', $term)
                      ->orWhereHas('user', function ($uq) use ($term) {
                          $uq->where('name', 'like', $term)->orWhere('email', 'like', $term);
                      });
                });
            })
            ->orderBy('match_field')
            ->orderBy('pattern')
            ->get();

        return view('assignment-rules.index', [
            'rules' => $rules,
        ]);
    }

    public function create()
    {
        Gate::authorize('admin');

        return view('assignment-rules.create', [
            'users' => User::getAdmins(),
            'matchFields' => AssignmentRule::MATCH_FIELDS,
            'suggestions' => $this->suggestions(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('admin');

        $validated = $this->validateRule($request);

        $rule = AssignmentRule::create([
            'match_field' => $validated['match_field'],
            'pattern' => trim($validated['pattern']),
            'user_id' => $validated['user_id'],
        ]);

        // Se solicitado, aplica retroativamente aos alertas pendentes sem atribuição
        $appliedCount = 0;
        if ($request->boolean('apply_to_pending')) {
            // Somente alertas principais: as repetições (parent_id não nulo) herdam
            // o responsável do principal e não geram e-mail próprio.
            // Um alerta só recebe esta regra se ela for a que ele receberia ao
            // chegar agora (respeita a prioridade nome do serviço > tipo).
            $pendingAlerts = Alert::query()
                ->whereNull('parent_id')
                ->where('status_resolucao', 'pendente')
                ->whereNull('atribuido')
                ->get()
                ->filter(fn (Alert $alert) => $rule->matchesAlert($alert)
                    && AssignmentRule::findForAlert($alert)?->is($rule));

            foreach ($pendingAlerts as $alert) {
                $alert->assignGroup($rule->user_id);
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

        return view('assignment-rules.edit', [
            'rule' => $assignmentRule,
            'users' => User::getAdmins(),
            'matchFields' => AssignmentRule::MATCH_FIELDS,
            'suggestions' => $this->suggestions(),
        ]);
    }

    public function update(Request $request, AssignmentRule $assignmentRule)
    {
        Gate::authorize('admin');

        $validated = $this->validateRule($request, $assignmentRule);

        $assignmentRule->update([
            'match_field' => $validated['match_field'],
            'pattern' => trim($validated['pattern']),
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

    private function validateRule(Request $request, ?AssignmentRule $ignore = null): array
    {
        $unique = Rule::unique('assignment_rules', 'pattern')
            ->where('match_field', $request->input('match_field'));
        if ($ignore) {
            $unique->ignore($ignore->id);
        }

        return $request->validate([
            'match_field' => ['required', Rule::in(array_keys(AssignmentRule::MATCH_FIELDS))],
            'pattern' => ['required', 'string', 'max:255', $unique],
            'user_id' => ['required', 'exists:users,id'],
            'apply_to_pending' => ['nullable', 'boolean'],
        ], [
            'match_field.required' => 'Escolha se a regra usa o nome ou o tipo do serviço.',
            'match_field.in' => 'Critério de correspondência inválido.',
            'pattern.required' => 'Informe o valor a ser comparado.',
            'pattern.unique' => 'Já existe uma regra cadastrada para este valor.',
            'user_id.required' => 'Selecione um usuário responsável.',
            'user_id.exists' => 'Usuário selecionado inválido.',
        ]);
    }

    /**
     * Valores já existentes nos alertas, por campo, para sugerir no formulário.
     */
    private function suggestions(): array
    {
        $distinct = fn (string $column) => Alert::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column);

        return [
            AssignmentRule::FIELD_SERVICE_NAME => $distinct('service_name'),
            AssignmentRule::FIELD_TYPE => $distinct('type'),
        ];
    }
}
