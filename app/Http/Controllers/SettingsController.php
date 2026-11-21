<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AssignmentRule;
use App\Services\AlertAssignmentService;
use App\Services\AlertGroupingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
    }

    public function index(AlertGroupingService $groupingService, AlertAssignmentService $assignmentService)
    {
        $lastRun = $this->parseRun($groupingService->lastRun());
        $assignmentLastRun = $this->parseRun($assignmentService->lastRun());

        return view('settings.index', [
            'lastRun' => $lastRun,
            'defaultThreshold' => AlertGroupingService::DEFAULT_SIMILARITY_THRESHOLD,
            'raizes' => Alert::whereNull('parent_id')->count(),
            'agrupados' => Alert::whereNotNull('parent_id')->count(),
            'assignmentLastRun' => $assignmentLastRun,
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
                'Varredura concluída: %d pendente(s) e %d resolvido(s) agrupados; %d grupo(s) com raiz corrigida.',
                $result['pendentes_agrupados'],
                $result['resolvidos_agrupados'],
                $result['raizes_corrigidas'],
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
