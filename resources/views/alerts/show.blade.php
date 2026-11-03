@extends('layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Detalhes do alerta</h1>
            <p class="text-muted mb-0">{{ $alert->service_name ?? $alert->type }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary">Voltar</a>
            <a href="{{ route('alerts.edit', $alert) }}" class="btn btn-primary">Editar</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
                        <span class="badge bg-{{ $alert->status_resolucao === 'pendente' ? 'warning text-dark' : 'success' }} text-uppercase">{{ $alert->status_resolucao }}</span>
                        <span class="badge bg-light text-dark">{{ $alert->type }}</span>
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-sm-4">ID externo</dt>
                        <dd class="col-sm-8">{{ $alert->id_externo }}</dd>

                        <dt class="col-sm-4">Serviço</dt>
                        <dd class="col-sm-8">{{ $alert->service_name ?? '—' }}</dd>

                        <dt class="col-sm-4">Data da ocorrência</dt>
                        <dd class="col-sm-8">{{ $alert->data_ocorrencia?->format('d/m/Y H:i') }}</dd>

                        <dt class="col-sm-4">Responsável</dt>
                        <dd class="col-sm-8">{{ $alert->atribuidoUser?->name ?? 'Não atribuído' }}</dd>

                        <dt class="col-sm-4">Resolvido por</dt>
                        <dd class="col-sm-8">{{ $alert->resolvidoPor?->name ?? 'Ainda não resolvido' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Ações</h5>
                    <form method="POST" action="{{ route('alerts.toggle-status', $alert) }}" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-{{ $alert->status_resolucao === 'pendente' ? 'success' : 'warning' }} w-100">
                            {{ $alert->status_resolucao === 'pendente' ? 'Marcar como resolvido' : 'Reabrir alerta' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('alerts.assign-user', $alert) }}">
                        @csrf
                        <label class="form-label">Atribuir</label>
                        <select name="atribuido" class="form-select mb-2">
                            <option value="">Sem atribuição</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $alert->atribuido == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-outline-primary w-100">Salvar atribuição</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white">
            <strong>Mensagem do erro</strong>
        </div>
        <div class="card-body">
            <pre class="mb-0 white-space-pre-wrap">{{ $alert->mensagem }}</pre>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white">
            <strong>Comentário</strong>
        </div>
        <div class="card-body">
            <p class="mb-0">{{ $alert->comentario ?? 'Sem comentário registrado.' }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('alerts.destroy', $alert) }}" class="mt-4" onsubmit="return confirm('Deseja realmente excluir este alerta?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Excluir alerta</button>
    </form>
</div>
@endsection