@extends('layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-chart-line text-primary me-2"></i>Dashboard Gerencial de Alertas</h1>
            <p class="text-muted mb-0">Métricas de resolução, análise de carga por responsável e histórico de incidentes.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('alerts.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Novo Alerta
            </a>
            <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-list me-1"></i> Ver Todos os Alertas
            </a>
        </div>
    </div>

    <!-- Indicadores Principais (KPIs) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase small text-muted fw-bold">Pendências Abertas</div>
                            <div class="display-6 fw-bold text-dark mt-1">{{ $pendentes }}</div>
                        </div>
                        <span class="p-3 bg-warning-subtle text-warning rounded-3 fs-4">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                    </div>
                    <div class="small text-muted mt-2">
                        <i class="fas fa-clock me-1"></i> Aguardando atendimento
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase small text-muted fw-bold">Alertas Resolvidos</div>
                            <div class="display-6 fw-bold text-success mt-1">{{ $resolvidos }}</div>
                        </div>
                        <span class="p-3 bg-success-subtle text-success rounded-3 fs-4">
                            <i class="fas fa-check-double"></i>
                        </span>
                    </div>
                    <div class="small text-muted mt-2">
                        <span class="fw-semibold text-success">{{ $taxaGeralResolucao }}%</span> do total de {{ $totalAlertas }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase small text-muted fw-bold">Tempo Médio de Resolução</div>
                            <div class="display-6 fw-bold text-info mt-1">{{ $tempoMedioFormatado }}</div>
                        </div>
                        <span class="p-3 bg-info-subtle text-info rounded-3 fs-4">
                            <i class="fas fa-stopwatch"></i>
                        </span>
                    </div>
                    <div class="small text-muted mt-2">
                        <i class="fas fa-history me-1"></i> Do incidente à resolução
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase small text-muted fw-bold">Não Atribuídos</div>
                            <div class="display-6 fw-bold text-danger mt-1">{{ $naoAtribuidosPendentes }}</div>
                        </div>
                        <span class="p-3 bg-danger-subtle text-danger rounded-3 fs-4">
                            <i class="fas fa-user-slash"></i>
                        </span>
                    </div>
                    <div class="small text-muted mt-2">
                        Total sem responsável: {{ $naoAtribuidosTotal }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estatísticas de Alertas Atribuídos a Pessoas -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-0 fw-bold"><i class="fas fa-users-cog text-primary me-2"></i>Estatísticas por Responsável (Admins)</h5>
                <small class="text-muted">Distribuição de carga de atendimento e métricas individuais de resolução.</small>
            </div>
            <a href="{{ route('assignment-rules.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-magic me-1"></i> Regras de Atribuição
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30%;">Responsável</th>
                        <th class="text-center" style="width: 14%;">Pendentes</th>
                        <th class="text-center" style="width: 14%;">Resolvidos</th>
                        <th class="text-center" style="width: 14%;">Total Atribuído</th>
                        <th class="text-center" style="width: 14%;">Tempo Médio</th>
                        <th style="width: 14%;">Taxa de Resolução</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($userStats as $stat)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                                        {{ strtoupper(substr($stat['user']->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $stat['user']->name }}</div>
                                        <div class="small text-muted">{{ $stat['user']->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($stat['pendentes'] > 0)
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6">{{ $stat['pendentes'] }}</span>
                                @else
                                    <span class="badge bg-light text-muted px-2 py-1">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fs-6">{{ $stat['resolvidos'] }}</span>
                            </td>
                            <td class="text-center">
                                <strong class="text-dark">{{ $stat['total'] }}</strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $stat['tempo_medio'] }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $stat['taxa'] }}%" aria-valuenow="{{ $stat['taxa'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small fw-bold text-muted">{{ $stat['taxa'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum usuário cadastrado.</td>
                        </tr>
                    @endforelse

                    <!-- Linha para Não Atribuídos -->
                    <tr class="table-warning-subtle">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                                    <i class="fas fa-question"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold text-danger">Não Atribuídos (Sem responsável)</div>
                                    <div class="small text-muted">Alertas aguardando triagem ou atribuição de regras</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            @if($naoAtribuidosPendentes > 0)
                                <span class="badge bg-danger text-white px-3 py-2 rounded-pill fs-6">{{ $naoAtribuidosPendentes }}</span>
                            @else
                                <span class="badge bg-light text-muted px-2 py-1">0</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill fs-6">{{ $naoAtribuidosResolvidos }}</span>
                        </td>
                        <td class="text-center">
                            <strong class="text-dark">{{ $naoAtribuidosTotal }}</strong>
                        </td>
                        <td class="text-center">—</td>
                        <td>
                            @php
                                $taxaNaoAtrib = $naoAtribuidosTotal > 0 ? round(($naoAtribuidosResolvidos / $naoAtribuidosTotal) * 100, 1) : 0;
                            @endphp
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $taxaNaoAtrib }}%"></div>
                                </div>
                                <span class="small fw-bold text-muted">{{ $taxaNaoAtrib }}%</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Gráficos e Detalhes -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="fas fa-calendar-alt text-primary me-2"></i>Evolução de Alertas por Mês</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="fas fa-chart-pie text-primary me-2"></i>Pendências por Responsável</h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    @if(count($chartUsersData) > 0)
                        <canvas id="usersChart" height="220"></canvas>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                            <p class="mb-0">Nenhuma pendência em aberto no momento!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Top Serviços -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold"><i class="fas fa-server text-primary me-2"></i>Serviços com Mais Ocorrências</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome do Serviço</th>
                        <th class="text-center">Total de Ocorrências</th>
                        <th class="text-center">Pendentes Atualmente</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topServices as $service)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $service->servico }}</strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary px-2 py-1">{{ $service->total }}</span>
                            </td>
                            <td class="text-center">
                                @if($service->pendentes > 0)
                                    <span class="badge bg-warning text-dark px-2 py-1">{{ $service->pendentes }}</span>
                                @else
                                    <span class="badge bg-success-subtle text-success px-2 py-1">0</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('alerts.index', ['search' => $service->servico]) }}" class="btn btn-sm btn-outline-primary">
                                    Ver incidentes &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Nenhum serviço registrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Gráfico Mensal
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Pendentes',
                        data: @json($chartPendentes),
                        backgroundColor: '#ffc107',
                        borderRadius: 4
                    },
                    {
                        label: 'Resolvidos',
                        data: @json($chartResolvidos),
                        backgroundColor: '#198754',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    // Gráfico de Pendências por Responsável
    const usersCtx = document.getElementById('usersChart');
    if (usersCtx) {
        new Chart(usersCtx, {
            type: 'doughnut',
            data: {
                labels: @json($chartUsersLabels),
                datasets: [{
                    data: @json($chartUsersData),
                    backgroundColor: [
                        '#0d6efd', '#20c997', '#6f42c1', '#fd7e14', '#d63384', '#0dcaf0', '#dc3545', '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12 }
                    }
                }
            }
        });
    }
</script>
@endsection

