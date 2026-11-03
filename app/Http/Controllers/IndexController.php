<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Carbon\Carbon;

use Illuminate\Support\Facades\Gate;

class IndexController extends Controller
{

    public function index()
    {
        Gate::authorize('admin');
        $pendentes = Alert::where('status_resolucao', 'pendente')->count();
        $resolvidos = Alert::where('status_resolucao', 'resolvido')->count();

        $monthly = Alert::query()
            ->select(['data_ocorrencia', 'status_resolucao'])
            ->get()
            ->groupBy(function ($alert) {
                return Carbon::parse($alert->data_ocorrencia)->format('Y-m');
            });

        $chartLabels = [];
        $chartPendentes = [];
        $chartResolvidos = [];

        foreach ($monthly as $mes => $items) {
            $chartLabels[] = Carbon::createFromFormat('Y-m', $mes)->translatedFormat('M/Y');
            $chartPendentes[] = $items->filter(fn ($item) => $item->status_resolucao === 'pendente')->count();
            $chartResolvidos[] = $items->filter(fn ($item) => $item->status_resolucao === 'resolvido')->count();
        }

        $alerts = Alert::with(['atribuidoUser', 'resolvidoPor'])
            ->where('status_resolucao', 'pendente')
            ->orderByDesc('data_ocorrencia')
            ->limit(8)
            ->get();

        return view('index', [
            'pendentes' => $pendentes,
            'resolvidos' => $resolvidos,
            'alerts' => $alerts,
            'chartLabels' => json_encode($chartLabels),
            'chartPendentes' => json_encode($chartPendentes),
            'chartResolvidos' => json_encode($chartResolvidos),
        ]);
    }
}