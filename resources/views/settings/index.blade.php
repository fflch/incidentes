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
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-1"><i class="fas fa-layer-group text-primary me-2"></i>Agrupamento de alertas similares</h2>
                    <p class="text-muted small">
                        A varredura roda automaticamente a cada 5 minutos. Ela agrupa alertas do mesmo serviço
                        com mensagens parecidas sob o alerta mais antigo e corrige grupos cuja raiz não seja o mais antigo.
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
                                    {{ $lastRun['pendentes_agrupados'] }} pendente(s),
                                    {{ $lastRun['resolvidos_agrupados'] }} resolvido(s) agrupados,
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
    </div>
</div>
@endsection
