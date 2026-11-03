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
            <h1 class="h3 mb-1"><i class="fas fa-info-circle text-primary me-2"></i>Detalhes do Alerta</h1>
            <p class="text-muted mb-0">{{ $alert->service_name ?? $alert->type }} (ID: {{ $alert->id }})</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('alerts.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Voltar
            </a>
            @can('admin')
                <a href="{{ route('alerts.edit', $alert) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Editar
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="mb-4 d-flex gap-2 flex-wrap align-items-center">
                        <span class="badge {{ $alert->status_resolucao === 'pendente' ? 'bg-warning text-dark' : 'bg-success text-white' }} text-uppercase px-3 py-2 fs-6">
                            <i class="fas {{ $alert->status_resolucao === 'pendente' ? 'fa-clock' : 'fa-check' }} me-1"></i>
                            {{ $alert->status_resolucao }}
                        </span>
                        <span class="badge bg-light text-dark border px-3 py-2 fs-6">{{ $alert->type }}</span>
                        @if($tempo = $alert->tempoResolucaoFormatado())
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 fs-6">
                                <i class="fas fa-stopwatch me-1"></i> Resolvido em {{ $tempo }}
                            </span>
                        @endif
                    </div>

                    <dl class="row mb-0 gy-3">
                        <dt class="col-sm-4 text-muted">ID Externo</dt>
                        <dd class="col-sm-8 fw-semibold text-dark">{{ $alert->id_externo }}</dd>

                        <dt class="col-sm-4 text-muted">Serviço</dt>
                        <dd class="col-sm-8 fw-semibold text-dark">{{ $alert->service_name ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Data da Ocorrência</dt>
                        <dd class="col-sm-8">{{ $alert->data_ocorrencia?->format('d/m/Y H:i:s') }} ({{ $alert->data_ocorrencia?->diffForHumans() }})</dd>

                        <dt class="col-sm-4 text-muted">Responsável Atribuído</dt>
                        <dd class="col-sm-8">
                            @if($alert->atribuidoUser)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    <i class="fas fa-user me-1"></i> {{ $alert->atribuidoUser->name }} ({{ $alert->atribuidoUser->email }})
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger px-2 py-1">
                                    <i class="fas fa-question-circle me-1"></i> Não atribuído
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Status da Resolução</dt>
                        <dd class="col-sm-8">
                            @if($alert->status_resolucao === 'resolvido')
                                <span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i> Resolvido</span>
                            @else
                                <span class="text-warning fw-semibold"><i class="fas fa-clock me-1"></i> Pendente</span>
                            @endif
                        </dd>

                        @if($alert->status_resolucao === 'resolvido')
                            <dt class="col-sm-4 text-muted">Data da Resolução</dt>
                            <dd class="col-sm-8 text-success fw-semibold">
                                {{ $alert->dataResolucao()?->format('d/m/Y H:i:s') ?? '—' }}
                            </dd>

                            <dt class="col-sm-4 text-muted">Resolvido por</dt>
                            <dd class="col-sm-8">{{ $alert->resolvidoPor?->name ?? 'Sistema' }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-3"><i class="fas fa-cogs text-secondary me-1"></i> Ações Rápidas</h5>

                    @if($alert->status_resolucao === 'pendente')
                        <!-- Botão com Popup Obrigatório de Comentário -->
                        <button type="button"
                                class="btn btn-success w-100 py-2 mb-3 shadow-sm"
                                onclick="openResolveModal('{{ $alert->id }}', '{{ addslashes($alert->service_name ?? $alert->type) }}', '{{ addslashes($alert->id_externo) }}')">
                            <i class="fas fa-check me-1"></i> Marcar como Resolvido
                        </button>
                    @else
                        @can('admin')
                            <form method="POST" action="{{ route('alerts.reopen', $alert) }}" class="mb-3" onsubmit="return confirm('Deseja reabrir este alerta?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning w-100 py-2">
                                    <i class="fas fa-undo me-1"></i> Reabrir Alerta
                                </button>
                            </form>
                        @endcan
                    @endif

                    @can('admin')
                        <div class="pt-3 border-top">
                            <label class="form-label fw-bold text-muted small"><i class="fas fa-user-tag me-1"></i> Atribuir / Reatribuir Responsável</label>
                            <form method="POST" action="{{ route('alerts.assign-user', $alert) }}">
                                @csrf
                                <select name="atribuido" class="form-select mb-2">
                                    <option value="">Sem atribuição</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $alert->atribuido == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-save me-1"></i> Salvar Atribuição
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Mensagem de Erro Completa -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold"><i class="fas fa-terminal text-danger me-2"></i>Mensagem do Erro / Stacktrace</h5>
        </div>
        <div class="card-body p-4">
            <pre class="mb-0 p-3 bg-dark text-light rounded font-monospace" style="white-space: pre-wrap; word-break: break-all; max-height: 400px; overflow-y: auto;">{{ $alert->mensagem }}</pre>
        </div>
    </div>

    <!-- Comentário do Atendimento -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold"><i class="fas fa-comment-dots text-primary me-2"></i>Comentário da Resolução</h5>
        </div>
        <div class="card-body p-4">
            @if($alert->comentario)
                <p class="mb-0 fs-6 text-dark">{{ $alert->comentario }}</p>
                <div class="small text-muted mt-2">
                    Registrado por <strong>{{ $alert->resolvidoPor?->name ?? 'Responsável' }}</strong>
                </div>
            @else
                <p class="mb-0 text-muted fst-italic">Nenhum comentário registrado até o momento.</p>
            @endif
        </div>
    </div>

    @can('admin')
        <div class="mt-4 pt-3 border-top text-end">
            <form method="POST" action="{{ route('alerts.destroy', $alert) }}" class="d-inline" onsubmit="return confirm('Deseja realmente excluir este alerta do sistema?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-trash-alt me-1"></i> Excluir Alerta
                </button>
            </form>
        </div>
    @endcan
</div>

@include('partials.resolve-modal')
@endsection
