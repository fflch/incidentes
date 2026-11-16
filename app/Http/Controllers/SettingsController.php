<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\AlertGroupingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
    }

    public function index(AlertGroupingService $groupingService)
    {
        $lastRun = $groupingService->lastRun();
        if ($lastRun) {
            $lastRun['executado_em'] = Carbon::parse($lastRun['executado_em']);
        }

        return view('settings.index', [
            'lastRun' => $lastRun,
            'defaultThreshold' => AlertGroupingService::DEFAULT_SIMILARITY_THRESHOLD,
            'raizes' => Alert::whereNull('parent_id')->count(),
            'agrupados' => Alert::whereNotNull('parent_id')->count(),
        ]);
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
}
