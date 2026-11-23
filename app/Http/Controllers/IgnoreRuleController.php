<?php

namespace App\Http\Controllers;

use App\Models\IgnoreRule;
use App\Services\AlertIgnoreService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IgnoreRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
    }

    public function index(Request $request)
    {
        $rules = IgnoreRule::with('user')
            ->withCount(['alerts' => fn ($q) => $q->whereNull('parent_id')])
            ->when($request->filled('search'), fn ($q) => $q->where('pattern', 'like', '%' . $request->search . '%'))
            ->orderBy('pattern')
            ->get();

        return view('ignore-rules.index', ['rules' => $rules]);
    }

    public function create()
    {
        return view('ignore-rules.create');
    }

    public function store(Request $request, AlertIgnoreService $ignoreService)
    {
        $validated = $this->validateRule($request);

        $rule = IgnoreRule::create([
            'pattern' => trim($validated['pattern']),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('ignore-rules.index')
            ->with('success', $this->savedMessage('Regra cadastrada com sucesso.', $rule, $ignoreService));
    }

    public function edit(IgnoreRule $ignoreRule)
    {
        return view('ignore-rules.edit', ['rule' => $ignoreRule]);
    }

    public function update(Request $request, IgnoreRule $ignoreRule, AlertIgnoreService $ignoreService)
    {
        $validated = $this->validateRule($request, $ignoreRule);

        $ignoreRule->update(['pattern' => trim($validated['pattern'])]);

        return redirect()->route('ignore-rules.index')
            ->with('success', $this->savedMessage('Regra atualizada com sucesso.', $ignoreRule, $ignoreService));
    }

    public function destroy(IgnoreRule $ignoreRule)
    {
        // Os alertas já ignorados continuam ignorados (apenas perdem o vínculo com a regra)
        $ignoreRule->delete();

        return redirect()->route('ignore-rules.index')->with('success', 'Regra removida. Os alertas já ignorados continuam arquivados.');
    }

    /**
     * A regra vale na hora também para os pendentes que já existem (a
     * verificação periódica faria isso de qualquer forma em até 5 minutos).
     */
    private function savedMessage(string $msg, IgnoreRule $rule, AlertIgnoreService $ignoreService): string
    {
        $count = $ignoreService->ignorePending($rule);

        return $msg . ($count > 0
            ? " {$count} alerta(s) pendente(s) foram ignorados."
            : ' Nenhum alerta pendente continha esse texto.');
    }

    private function validateRule(Request $request, ?IgnoreRule $ignore = null): array
    {
        $unique = Rule::unique('ignore_rules', 'pattern');
        if ($ignore) {
            $unique->ignore($ignore->id);
        }

        $request->merge(['pattern' => trim((string) $request->input('pattern'))]);

        return $request->validate([
            'pattern' => ['required', 'string', 'min:3', 'max:20000', $unique],
        ], [
            'pattern.required' => 'Informe o texto que, se aparecer na mensagem, fará o alerta ser ignorado.',
            'pattern.min' => 'O texto deve ter pelo menos 3 caracteres, para não ignorar alertas demais.',
            'pattern.max' => 'O texto pode ter no máximo 20000 caracteres.',
            'pattern.unique' => 'Já existe uma regra com esse texto.',
        ]);
    }
}
