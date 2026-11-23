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
            <h1 class="h3 mb-1"><i class="fas fa-archive text-secondary me-2"></i>Alertas Ignorados</h1>
            <p class="text-muted mb-0">
                Alertas cuja mensagem contém um texto das <a href="{{ route('ignore-rules.index') }}">regras para ignorar</a>.
                Ficam arquivados, sem responsável e sem e-mail para ninguém. Repetições semelhantes continuam sendo agrupadas.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('ignore-rules.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-eye-slash me-1"></i> Regras para Ignorar
            </a>
            <a href="{{ route('home') }}" class="btn btn-outline-warning">
                <i class="fas fa-exclamation-triangle me-1"></i> Ver Pendentes
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('alerts.ignored') }}" class="row g-2 align-items-center">
                @if($regra)
                    <input type="hidden" name="regra" value="{{ $regra->id }}">
                @endif
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="Buscar por ID externo, serviço, tipo ou mensagem...">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Pesquisar</button>
                    @if(request('search') || $regra)
                        <a href="{{ route('alerts.ignored') }}" class="btn btn-outline-secondary">Limpar</a>
                    @endif
                </div>
            </form>
            <div class="small text-muted mt-2">
                {{ $totalIgnorados }} alerta(s) ignorado(s) no total.
                @if($regra)
                    Mostrando apenas os ignorados pela regra <code title="{{ $regra->pattern }}">{{ \Illuminate\Support\Str::limit(\App\Models\IgnoreRule::normalize($regra->pattern), 120) }}</code>.
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3">
        @forelse($alerts as $alert)
            <div class="col-12">
                <div class="card border-0 shadow-sm border-start border-4 border-secondary">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div class="min-w-0">
                                <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                                    <span class="badge bg-secondary text-white text-uppercase px-3 py-1">
                                        <i class="fas fa-eye-slash me-1"></i> Ignorado
                                    </span>
                                    <h4 class="mb-0 fw-bold text-dark">{{ $alert->service_name ?? $alert->type }}</h4>
                                    <span class="badge bg-light text-secondary border">{{ $alert->type }}</span>
                                    @if($alert->children_count > 0)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <i class="fas fa-layer-group me-1"></i> {{ $alert->children_count + 1 }} ocorrências
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
                                    @if($alert->children_max_data_ocorrencia)
                                        <div class="col-sm-auto me-3">
                                            <i class="fas fa-redo me-1"></i> Última repetição: <strong>{{ \Carbon\Carbon::parse($alert->children_max_data_ocorrencia)->format('d/m/Y H:i') }}</strong>
                                        </div>
                                    @endif
                                    <div class="col-sm-auto">
                                        <i class="fas fa-filter me-1"></i> Regra:
                                        @if($alert->ignoreRule)
                                            <code title="{{ $alert->ignoreRule->pattern }}">{{ \Illuminate\Support\Str::limit(\App\Models\IgnoreRule::normalize($alert->ignoreRule->pattern), 80) }}</code>
                                        @else
                                            <strong>manual / regra removida</strong>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <a href="{{ route('alerts.show', $alert) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-expand-alt me-1"></i> Detalhes
                                </a>
                                <form method="POST" action="{{ route('alerts.reopen', $alert) }}" onsubmit="return confirm('Reativar este alerta? Ele (e suas repetições) volta para os pendentes.');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-undo me-1"></i> Reativar
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-3 p-3 bg-light rounded border small text-dark text-break" style="white-space: pre-wrap; max-height: 8rem; overflow: auto;">{{ $alert->mensagem }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm py-5 text-center text-muted">
                    <div class="py-4">
                        <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                        <h5>Nenhum alerta ignorado encontrado</h5>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $alerts->links() }}
    </div>
</div>

<style>
    .min-w-0 { min-width: 0; }
</style>
@endsection
