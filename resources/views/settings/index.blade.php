@extends('layout')

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="mb-4">
        <h1 class="h3 mb-1"><i class="fas fa-cog text-primary me-2"></i>Configurações</h1>
        <p class="text-muted mb-0">Ferramentas de manutenção do sistema de alertas.</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-1"><i class="fas fa-eye-slash text-secondary me-2"></i>Regras para ignorar</h2>
                    <p class="text-muted small">
                        A cada 5 minutos, <strong>antes</strong> do agrupamento e da atribuição, os alertas pendentes cuja mensagem
                        contenha um texto das <a href="{{ route('ignore-rules.index') }}">regras para ignorar</a> são ignorados
                        (inclusive os inseridos direto via SQL). Alertas reativados manualmente não são ignorados de novo.
                        Use o botão abaixo para forçar a verificação agora.
                    </p>

                    <dl class="row small mb-3">
                        <dt class="col-sm-5 text-muted">Regras cadastradas</dt>
                        <dd class="col-sm-7">{{ $regrasIgnorar }}</dd>
                        <dt class="col-sm-5 text-muted">Alertas ignorados</dt>
                        <dd class="col-sm-7"><a href="{{ route('alerts.ignored') }}">{{ $ignorados }}</a></dd>
                        <dt class="col-sm-5 text-muted">Reativados (protegidos)</dt>
                        <dd class="col-sm-7">{{ $reativados }}</dd>
                        <dt class="col-sm-5 text-muted">Última execução</dt>
                        <dd class="col-sm-7">
                            @if($ignoreLastRun)
                                {{ $ignoreLastRun['executado_em']->format('d/m/Y H:i:s') }}
                                <span class="text-muted">({{ $ignoreLastRun['executado_em']->diffForHumans() }}, {{ $ignoreLastRun['origem'] }})</span>
                                <div class="text-muted">{{ $ignoreLastRun['ignorados'] }} alerta(s) ignorado(s)</div>
                            @else
                                <span class="text-muted">Sem registro</span>
                            @endif
                        </dd>
                    </dl>

                    <form method="POST" action="{{ route('settings.apply-ignore-rules') }}"
                          onsubmit="this.querySelector('button').disabled = true;">
                        @csrf
                        <button type="submit" class="btn btn-secondary w-100" @disabled($regrasIgnorar === 0)>
                            <i class="fas fa-play me-1"></i> Verificar pendentes agora
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-1"><i class="fas fa-layer-group text-primary me-2"></i>Agrupamento de alertas similares</h2>
                    <p class="text-muted small">
                        A varredura roda automaticamente a cada 5 minutos. Ela agrupa alertas pendentes (e, separadamente, os ignorados) do mesmo serviço
                        com mensagens parecidas sob o alerta mais antigo e corrige grupos cuja raiz não seja o mais antigo.
                        Alertas resolvidos não são considerados.
                        Use o botão abaixo para forçar a execução agora.
                    </p>

                    <dl class="row small mb-3">
                        <dt class="col-sm-5 text-muted">Alertas principais (raízes)</dt>
                        <dd class="col-sm-7">{{ $raizes }}</dd>
                        <dt class="col-sm-5 text-muted">Alertas agrupados</dt>
                        <dd class="col-sm-7">{{ $agrupados }}</dd>
                        <dt class="col-sm-5 text-muted">Última execução</dt>
                        <dd class="col-sm-7">
                            @if($lastRun)
                                {{ $lastRun['executado_em']->format('d/m/Y H:i:s') }}
                                <span class="text-muted">({{ $lastRun['executado_em']->diffForHumans() }}, {{ $lastRun['origem'] }})</span>
                                <div class="text-muted">
                                    {{ $lastRun['pendentes_agrupados'] }} pendente(s) e {{ $lastRun['ignorados_agrupados'] ?? 0 }} ignorado(s) agrupado(s),
                                    {{ $lastRun['raizes_corrigidas'] }} raiz(es) corrigida(s)
                                    — similaridade {{ $lastRun['threshold'] }}%
                                </div>
                            @else
                                <span class="text-muted">Sem registro</span>
                            @endif
                        </dd>
                    </dl>

                    <form method="POST" action="{{ route('settings.group-alerts') }}" class="row g-2 align-items-end"
                          onsubmit="this.querySelector('button').disabled = true;">
                        @csrf
                        <div class="col-sm-6">
                            <label for="threshold" class="form-label small mb-1">Similaridade mínima (%)</label>
                            <input type="number" id="threshold" name="threshold" min="1" max="100" step="1"
                                   value="{{ old('threshold', $defaultThreshold) }}" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sync-alt me-1"></i> Agrupar agora
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-1"><i class="fas fa-user-tag text-primary me-2"></i>Atribuição automática</h2>
                    <p class="text-muted small">
                        A cada 5 minutos as regras de atribuição são aplicadas aos alertas principais pendentes
                        sem responsável (ex.: inseridos direto via SQL). As repetições herdam o responsável do principal.
                        Use o botão abaixo para forçar a execução agora.
                    </p>

                    <dl class="row small mb-3">
                        <dt class="col-sm-5 text-muted">Regras cadastradas</dt>
                        <dd class="col-sm-7">{{ $regras }}</dd>
                        <dt class="col-sm-5 text-muted">Pendentes sem responsável</dt>
                        <dd class="col-sm-7">{{ $semResponsavel }}</dd>
                        <dt class="col-sm-5 text-muted">Última execução</dt>
                        <dd class="col-sm-7">
                            @if($assignmentLastRun)
                                {{ $assignmentLastRun['executado_em']->format('d/m/Y H:i:s') }}
                                <span class="text-muted">({{ $assignmentLastRun['executado_em']->diffForHumans() }}, {{ $assignmentLastRun['origem'] }})</span>
                                <div class="text-muted">{{ $assignmentLastRun['atribuidos'] }} alerta(s) atribuído(s)</div>
                            @else
                                <span class="text-muted">Sem registro</span>
                            @endif
                        </dd>
                    </dl>

                    <form method="POST" action="{{ route('settings.apply-assignment-rules') }}"
                          onsubmit="this.querySelector('button').disabled = true;">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100" @disabled($regras === 0)>
                            <i class="fas fa-play me-1"></i> Aplicar regras agora
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
