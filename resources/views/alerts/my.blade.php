@extends('layout')

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-user-check text-primary me-2"></i>Meus Alertas</h1>
            <p class="text-muted mb-0">Incidentes e tarefas de monitoramento atribuídos diretamente a você ({{ auth()->user()->name }}).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                <i class="fas fa-tv me-1"></i> Painel TV
            </a>
            @can('admin')
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                    <i class="fas fa-chart-line me-1"></i> Dashboard
                </a>
            @endcan
        </div>
    </div>

    <!-- Navegação de Filtro por Status e Busca -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-7">
                    <ul class="nav nav-pills">
                        <li class="nav-item">
                            <a class="nav-link {{ $statusFilter === 'pendente' ? 'active' : '' }}" href="{{ route('alerts.my', ['status' => 'pendente', 'search' => request('search')]) }}">
                                <i class="fas fa-clock me-1"></i> Pendentes
                                <span class="badge {{ $statusFilter === 'pendente' ? 'bg-white text-primary' : 'bg-warning text-dark' }} ms-1">
                                    {{ $countPendentes }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $statusFilter === 'resolvido' ? 'active' : '' }}" href="{{ route('alerts.my', ['status' => 'resolvido', 'search' => request('search')]) }}">
                                <i class="fas fa-check-circle me-1"></i> Resolvidos
                                <span class="badge {{ $statusFilter === 'resolvido' ? 'bg-white text-primary' : 'bg-success text-white' }} ms-1">
                                    {{ $countResolvidos }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $statusFilter === 'todos' ? 'active' : '' }}" href="{{ route('alerts.my', ['status' => 'todos', 'search' => request('search')]) }}">
                                Todos
                                <span class="badge {{ $statusFilter === 'todos' ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">
                                    {{ $countTotal }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="col-md-5">
                    <form method="GET" action="{{ route('alerts.my') }}" class="d-flex gap-2">
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="Buscar por serviço, ID externo, mensagem...">
                        </div>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        @if(request('search'))
                            <a href="{{ route('alerts.my', ['status' => $statusFilter]) }}" class="btn btn-outline-secondary">Limpar</a>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Alertas -->
    <div class="row g-3">
        @forelse($alerts as $alert)
            <div class="col-12">
                <div class="card border-0 shadow-sm {{ $alert->status_resolucao === 'pendente' ? 'border-start border-4 border-warning' : 'border-start border-4 border-success' }}">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                                    <span class="badge {{ $alert->status_resolucao === 'pendente' ? 'bg-warning text-dark' : 'bg-success text-white' }} text-uppercase px-3 py-1">
                                        <i class="fas {{ $alert->status_resolucao === 'pendente' ? 'fa-clock' : 'fa-check' }} me-1"></i>
                                        {{ $alert->status_resolucao }}
                                    </span>
                                    <h4 class="mb-0 fw-bold text-dark">{{ $alert->service_name ?? $alert->type }}</h4>
                                    <span class="badge bg-light text-secondary border">{{ $alert->type }}</span>
                                </div>

                                <div class="text-muted small mb-3">
                                    <span class="me-3"><i class="fas fa-fingerprint me-1"></i> ID Externo: <strong>{{ $alert->id_externo }}</strong></span>
                                    <span class="me-3"><i class="fas fa-calendar-alt me-1"></i> Ocorrência: <strong>{{ $alert->data_ocorrencia?->format('d/m/Y H:i') }}</strong> ({{ $alert->data_ocorrencia?->diffForHumans() }})</span>
                                    @if($alert->status_resolucao === 'resolvido')
                                        <span class="text-success"><i class="fas fa-check-double me-1"></i> Resolvido em: <strong>{{ $alert->resolvido_em?->format('d/m/Y H:i') ?? $alert->updated_at?->format('d/m/Y H:i') }}</strong></span>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                @if($alert->status_resolucao === 'pendente')
                                    <!-- Botão com Popup Obrigatório de Comentário -->
                                    <button type="button"
                                            class="btn btn-success shadow-sm"
                                            onclick="openResolveModal('{{ $alert->id }}', '{{ addslashes($alert->service_name ?? $alert->type) }}', '{{ addslashes($alert->id_externo) }}')">
                                        <i class="fas fa-check me-1"></i> Resolver Incidente
                                    </button>
                                @else
                                    @can('admin')
                                        <form method="POST" action="{{ route('alerts.reopen', $alert) }}" onsubmit="return confirm('Deseja reabrir este alerta?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-undo me-1"></i> Reabrir Alerta
                                            </button>
                                        </form>
                                    @endcan
                                @endif

                                <a href="{{ route('alerts.show', $alert) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-expand-alt me-1"></i> Detalhes
                                </a>
                            </div>
                        </div>

                        <!-- Snippet da Mensagem de Erro -->
                        <div class="mt-2 p-3 bg-light rounded border">
                            <div class="small fw-bold text-muted mb-1"><i class="fas fa-terminal me-1"></i> Resumo do Erro:</div>
                            <div class="font-monospace small text-dark" style="white-space: pre-wrap; word-break: break-all; max-height: 120px; overflow-y: auto;">{{ Str::limit($alert->mensagem, 400) }}</div>
                        </div>

                        <!-- Comentário registrado se já resolvido -->
                        @if($alert->status_resolucao === 'resolvido' && $alert->comentario)
                            <div class="mt-3 p-3 bg-success-subtle rounded border border-success-subtle">
                                <div class="small fw-bold text-success mb-1">
                                    <i class="fas fa-comment-dots me-1"></i> Comentário da Resolução (por {{ $alert->resolvidoPor?->name ?? 'Responsável' }}):
                                </div>
                                <div class="text-dark">{{ $alert->comentario }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm py-5 text-center text-muted">
                    <div class="py-4">
                        <i class="fas fa-clipboard-check fa-3x mb-3 text-secondary opacity-50"></i>
                        <h5>Nenhum alerta encontrado</h5>
                        <p class="mb-0">
                            @if($statusFilter === 'pendente')
                                Parabéns! Você não possui nenhum alerta pendente atribuído no momento.
                            @elseif($statusFilter === 'resolvido')
                                Nenhum alerta resolvido atribuído a você ainda.
                            @else
                                Você não possui nenhum alerta atribuído.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Paginação -->
    <div class="mt-4">
        {{ $alerts->links() }}
    </div>
</div>

@include('partials.resolve-modal')
@endsection

