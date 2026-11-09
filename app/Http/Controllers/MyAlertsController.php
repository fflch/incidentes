<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;

class MyAlertsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $userId = auth()->id();
        $statusFilter = $request->input('status', 'pendente');

        $query = Alert::query()
            ->with(['resolvidoPor', 'atribuidoUser'])
            ->whereNull('parent_id')
            ->where('atribuido', $userId);

        if ($statusFilter === 'pendente') {
            $query->where('status_resolucao', 'pendente');
        } elseif ($statusFilter === 'resolvido') {
            $query->where('status_resolucao', 'resolvido');
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('service_name', 'like', $search)
                  ->orWhere('id_externo', 'like', $search)
                  ->orWhere('mensagem', 'like', $search)
                  ->orWhere('comentario', 'like', $search);
            });
        }

        $alerts = $query->orderByDesc('data_ocorrencia')->paginate(15)->withQueryString();

        $countPendentes = Alert::whereNull('parent_id')->where('atribuido', $userId)->where('status_resolucao', 'pendente')->count();
        $countResolvidos = Alert::whereNull('parent_id')->where('atribuido', $userId)->where('status_resolucao', 'resolvido')->count();
        $countTotal = $countPendentes + $countResolvidos;

        return view('alerts.my', [
            'alerts' => $alerts,
            'statusFilter' => $statusFilter,
            'countPendentes' => $countPendentes,
            'countResolvidos' => $countResolvidos,
            'countTotal' => $countTotal,
        ]);
    }
}

