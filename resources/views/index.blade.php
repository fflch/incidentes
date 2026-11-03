@extends('layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Dashboard de alertas</h1>
            <p class="text-muted mb-0">Visão geral dos incidentes em aberto e resolvidos.</p>
        </div>
        <a href="{{ route('alerts.create') }}" class="btn btn-primary">Novo alerta</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-uppercase small text-muted">Pendentes</div>
                    <div class="display-6 fw-bold mt-2">{{ $pendentes }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-uppercase small text-muted">Resolvidos</div>
                    <div class="display-6 fw-bold mt-2">{{ $resolvidos }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-uppercase small text-muted">Total</div>
                    <div class="display-6 fw-bold mt-2">{{ $pendentes + $resolvidos }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <strong>Alertas por mês</strong>
        </div>
        <div class="card-body">
            <canvas id="alertsChart" height="100"></canvas>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Últimos pendentes</strong>
            <a href="{{ route('alerts.index') }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
        </div>
        <div class="list-group list-group-flush">
            @forelse($alerts as $alert)
                <a href="{{ route('alerts.show', $alert) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="fw-semibold">{{ $alert->service_name ?? $alert->type }}</div>
                            <div class="small text-muted">{{ $alert->id_externo }} • {{ $alert->data_ocorrencia?->format('d/m/Y H:i') }}</div>
                        </div>
                        <span class="badge bg-warning text-dark">{{ ucfirst($alert->status_resolucao) }}</span>
                    </div>
                </a>
            @empty
                <div class="list-group-item text-muted">Nenhum alerta pendente no momento.</div>
            @endforelse
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = @json($chartLabels);
    const pendentes = @json($chartPendentes);
    const resolvidos = @json($chartResolvidos);

    new Chart(document.getElementById('alertsChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pendentes',
                    data: pendentes,
                    backgroundColor: '#f4b400'
                },
                {
                    label: 'Resolvidos',
                    data: resolvidos,
                    backgroundColor: '#198754'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>
@endsection