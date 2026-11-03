<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
    }

    public function index()
    {
        Gate::authorize('admin');

        $totalAlertas = Alert::count();
        $pendentes = Alert::where('status_resolucao', 'pendente')->count();
        $resolvidos = Alert::where('status_resolucao', 'resolvido')->count();
        $taxaGeralResolucao = $totalAlertas > 0 ? round(($resolvidos / $totalAlertas) * 100, 1) : 0;

        // Tempo médio de resolução (dos alertas resolvidos)
        $alertasResolvidos = Alert::where('status_resolucao', 'resolvido')->get();
        $temposMinutos = [];
        foreach ($alertasResolvidos as $alerta) {
            $minutos = $alerta->tempoResolucaoMinutos();
            if ($minutos !== null) {
                $temposMinutos[] = $minutos;
            }
        }

        $tempoMedioFormatado = '—';
        if (count($temposMinutos) > 0) {
            $mediaMinutos = (int) round(array_sum($temposMinutos) / count($temposMinutos));
            if ($mediaMinutos < 60) {
                $tempoMedioFormatado = "{$mediaMinutos} min";
            } elseif ($mediaMinutos < 1440) {
                $horas = intdiv($mediaMinutos, 60);
                $minRest = $mediaMinutos % 60;
                $tempoMedioFormatado = $minRest > 0 ? "{$horas}h {$minRest}m" : "{$horas}h";
            } else {
                $dias = intdiv($mediaMinutos, 1440);
                $horas = intdiv($mediaMinutos % 1440, 60);
                $tempoMedioFormatado = $horas > 0 ? "{$dias}d {$horas}h" : "{$dias}d";
            }
        }

        // Estatísticas de Não Atribuídos
        $naoAtribuidosPendentes = Alert::where('status_resolucao', 'pendente')->whereNull('atribuido')->count();
        $naoAtribuidosResolvidos = Alert::where('status_resolucao', 'resolvido')->whereNull('atribuido')->count();
        $naoAtribuidosTotal = $naoAtribuidosPendentes + $naoAtribuidosResolvidos;

        // Estatísticas por Pessoa Atribuída
        $users = User::getAdmins();
        $userStats = [];
        foreach ($users as $user) {
            $userPendentes = Alert::where('atribuido', $user->id)->where('status_resolucao', 'pendente')->count();
            $userResolvidos = Alert::where('atribuido', $user->id)->where('status_resolucao', 'resolvido')->count();
            $userTotal = $userPendentes + $userResolvidos;
            $userTaxa = $userTotal > 0 ? round(($userResolvidos / $userTotal) * 100, 1) : 0;

            // Tempo médio individual
            $userAlertasResolvidos = Alert::where('atribuido', $user->id)->where('status_resolucao', 'resolvido')->get();
            $userTempos = [];
            foreach ($userAlertasResolvidos as $al) {
                $m = $al->tempoResolucaoMinutos();
                if ($m !== null) {
                    $userTempos[] = $m;
                }
            }
            $userTempoMedio = '—';
            if (count($userTempos) > 0) {
                $uMedia = (int) round(array_sum($userTempos) / count($userTempos));
                if ($uMedia < 60) {
                    $userTempoMedio = "{$uMedia}m";
                } elseif ($uMedia < 1440) {
                    $userTempoMedio = intdiv($uMedia, 60) . 'h ' . ($uMedia % 60) . 'm';
                } else {
                    $userTempoMedio = intdiv($uMedia, 1440) . 'd ' . intdiv($uMedia % 1440, 60) . 'h';
                }
            }

            $userStats[] = [
                'user' => $user,
                'pendentes' => $userPendentes,
                'resolvidos' => $userResolvidos,
                'total' => $userTotal,
                'taxa' => $userTaxa,
                'tempo_medio' => $userTempoMedio,
            ];
        }

        // Ordena por pendentes decrescente e depois total
        usort($userStats, function ($a, $b) {
            return [$b['pendentes'], $b['total']] <=> [$a['pendentes'], $a['total']];
        });

        // Gráfico Mensal
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

        // Top 5 serviços com incidentes
        $topServices = Alert::query()
            ->selectRaw('COALESCE(service_name, type) as servico, count(*) as total, sum(case when status_resolucao = "pendente" then 1 else 0 end) as pendentes')
            ->groupBy('servico')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Dados para gráfico de responsáveis (apenas com pendentes)
        $chartUsersLabels = [];
        $chartUsersData = [];
        foreach ($userStats as $stat) {
            if ($stat['pendentes'] > 0) {
                $chartUsersLabels[] = $stat['user']->name;
                $chartUsersData[] = $stat['pendentes'];
            }
        }
        if ($naoAtribuidosPendentes > 0) {
            $chartUsersLabels[] = 'Não Atribuídos';
            $chartUsersData[] = $naoAtribuidosPendentes;
        }

        return view('dashboard.index', [
            'totalAlertas' => $totalAlertas,
            'pendentes' => $pendentes,
            'resolvidos' => $resolvidos,
            'taxaGeralResolucao' => $taxaGeralResolucao,
            'tempoMedioFormatado' => $tempoMedioFormatado,
            'naoAtribuidosPendentes' => $naoAtribuidosPendentes,
            'naoAtribuidosResolvidos' => $naoAtribuidosResolvidos,
            'naoAtribuidosTotal' => $naoAtribuidosTotal,
            'userStats' => $userStats,
            'chartLabels' => $chartLabels,
            'chartPendentes' => $chartPendentes,
            'chartResolvidos' => $chartResolvidos,
            'topServices' => $topServices,
            'chartUsersLabels' => $chartUsersLabels,
            'chartUsersData' => $chartUsersData,
        ]);
    }
}

