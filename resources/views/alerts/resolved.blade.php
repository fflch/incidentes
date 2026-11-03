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
            <h1 class="h3 mb-1"><i class="fas fa-check-circle text-success me-2"></i>Histórico de Alertas Resolvidos</h1>
            <p class="text-muted mb-0">Consulte os incidentes finalizados, comentários de encerramento, tempo de resolução e responsáveis.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('alerts.index') }}" class="btn btn-outline-warning">
                <i class="fas fa-exclamation-triangle me-1"></i> Ver Pendentes
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                <i class="fas fa-chart-line me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Barra de Pesquisa -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('alerts.resolved') }}" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="Buscar por ID externo, serviço, mensagem ou comentário da resolução...">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Pesquisar</button>
                    @if(request('search'))
                        <a href="{{ route('alerts.resolved') }}" class="btn btn-outline-secondary">Limpar</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Alertas Resolvidos -->
    <div class="row g-3">
        @forelse($alerts as $alert)
            <div class="col-12">
                <div class="card border-0 shadow-sm border-start border-4 border-success">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                                    <span class="badge bg-success text-white text-uppercase px-3 py-1">
                                        <i class="fas fa-check-double me-1"></i> Resolvido
                                    </span>
                                    <h4 class="mb-0 fw-bold text-dark">{{ $alert->service_name ?? $alert->type }}</h4>
                                    <span class="badge bg-light text-secondary border">{{ $alert->type }}</span>
                                    @if($tempo = $alert->tempoResolucaoFormatado())
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            <i class="fas fa-stopwatch me-1"></i> Tempo de Resolução: {{ $tempo }}
                                        </span>
                                    @endif
                                </div>

                                <div class="row g-2 text-muted small mt-1">
                                    <div class="col-sm-auto me-3">
                                        <i class="fas fa-fingerprint me-1"></i> ID Externo: <strong>{{ $alert->id_externo }}</strong>
                                    </div>
                                    <div class="col-sm-auto me-3">
                                        <i class="fas fa-calendar-alt me-1"></i> Ocorrência: <strong>{{ $alert->data_ocorrencia?->format('d/m/Y H:i') }}</strong>
                                    </div>
                                    <div class="col-sm-auto me-3 text-success">
                                        <i class="fas fa-calendar-check me-1"></i> Resolução: <strong>{{ $alert->dataResolucao()?->format('d/m/Y H:i') ?? '—' }}</strong>
                                    </div>
                                    <div class="col-sm-auto me-3">
                                        <i class="fas fa-user me-1"></i> Atribuído a: <strong>{{ $alert->atribuidoUser?->name ?? 'Não atribuído' }}</strong>
                                    </div>
                                    <div class="col-sm-auto">
                                        <i class="fas fa-user-check me-1"></i> Resolvido por: <strong>{{ $alert->resolvidoPor?->name ?? 'Sistema' }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <a href="{{ route('alerts.show', $alert) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-expand-alt me-1"></i> Detalhes
                                </a>

                                <form method="POST" action="{{ route('alerts.reopen', $alert) }}" onsubmit="return confirm('Tem certeza que deseja reabrir este alerta?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-undo me-1"></i> Reabrir Alerta
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Comentário Obrigatório de Resolução Registrado -->
                        <div class="mt-3 p-3 bg-success-subtle rounded border border-success-subtle">
                            <div class="small fw-bold text-success mb-1">
                                <i class="fas fa-comment-alt me-1"></i> Comentário do Atendimento / Resolução:
                            </div>
                            <div class="text-dark">
                                {{ $alert->comentario ?: 'Nenhum comentário registrado no fechamento.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm py-5 text-center text-muted">
                    <div class="py-4">
                        <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                        <h5>Nenhum alerta resolvido encontrado</h5>
                        <p class="mb-0">Não foram encontrados incidentes resolvidos correspondentes à sua pesquisa.</p>
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
@endsection

