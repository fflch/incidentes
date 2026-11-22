<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AssignmentRule;
use App\Models\IgnoreRule;
use App\Services\AlertAssignmentService;
use App\Services\AlertGroupingService;
use App\Services\AlertIgnoreService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
    }

    public function index(AlertGroupingService $groupingService, AlertAssignmentService $assignmentService, AlertIgnoreService $ignoreService)
    {
        $lastRun = $this->parseRun($groupingService->lastRun());
        $assignmentLastRun = $this->parseRun($assignmentService->lastRun());
        $ignoreLastRun = $this->parseRun($ignoreService->lastRun());

        return view('settings.index', [
            'lastRun' => $lastRun,
            'defaultThreshold' => AlertGroupingService::DEFAULT_SIMILARITY_THRESHOLD,
            'raizes' => Alert::whereNull('parent_id')->count(),
            'agrupados' => Alert::whereNotNull('parent_id')->count(),
            'assignmentLastRun' => $assignmentLastRun,
            'ignoreLastRun' => $ignoreLastRun,
            'regrasIgnorar' => IgnoreRule::count(),
            'ignorados' => Alert::ignorado()->count(),
            'reativados' => Alert::whereNull('parent_id')->where('status_resolucao', Alert::STATUS_PENDENTE)->where('nao_ignorar', true)->count(),
            'regras' => AssignmentRule::count(),
            'semResponsavel' => Alert::whereNull('parent_id')
                ->where('status_resolucao', 'pendente')
                ->whereNull('atribuido')
                ->count(),
        ]);
    }

    private function parseRun(?array $run): ?array
    {
        if ($run) {
            $run['executado_em'] = Carbon::parse($run['executado_em']);
        }

        return $run;
    }

    public function groupAlerts(Request $request, AlertGroupingService $groupingService)
    {
        $data = $request->validate([
            'threshold' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        $result = $groupingService->run((float) $data['threshold'], 'manual (' . $request->user()->name . ')');

        return redirect()
            ->route('settings.index')
            ->with('success', sprintf(
                'Varredura concluída: %d pendente(s) e %d ignorado(s) agrupado(s); %d grupo(s) com raiz corrigida.',
                $result['pendentes_agrupados'],
                $result['ignorados_agrupados'],
                $result['raizes_corrigidas'],
            ));
    }

    public function applyIgnoreRules(Request $request, AlertIgnoreService $ignoreService)
    {
        $result = $ignoreService->run('manual (' . $request->user()->name . ')');

        return redirect()
            ->route('settings.index')
            ->with('success', sprintf(
                'Regras para ignorar aplicadas: %d alerta(s) pendente(s) ignorado(s).',
                $result['ignorados'],
            ));
    }

    public function applyAssignmentRules(Request $request, AlertAssignmentService $assignmentService)
    {
        $result = $assignmentService->run('manual (' . $request->user()->name . ')');

        return redirect()
            ->route('settings.index')
            ->with('success', sprintf(
                'Regras de atribuição aplicadas: %d alerta(s) pendente(s) atribuído(s).',
                $result['atribuidos'],
            ));
    }
}
