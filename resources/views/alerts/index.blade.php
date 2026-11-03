@extends('layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">{{ $title }}</h1>
            <p class="text-muted mb-0">Gerencie os alertas de infraestrutura e aplicações.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('alerts.create') }}" class="btn btn-primary">Cadastrar alerta</a>
            <a href="{{ route('alerts.resolved') }}" class="btn btn-outline-secondary">Resolvidos</a>
        </div>
    </div>

    <form method="GET" class="row g-2 mb-4 align-items-center">
        <div class="col-md-8">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por ID externo, serviço ou mensagem">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Pesquisar</button>
            @if(request('search'))
                <a href="{{ route('alerts.index', ['status' => $statusFilter]) }}" class="btn btn-outline-secondary">Limpar</a>
            @endif
        </div>
    </form>

    <div class="row g-3">
        @forelse($alerts as $alert)
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                                    <span class="badge bg-{{ $alert->status_resolucao === 'pendente' ? 'warning text-dark' : 'success' }} text-uppercase">{{ $alert->status_resolucao }}</span>
                                    <strong class="fs-5">{{ $alert->service_name ?? $alert->type }}</strong>
                                    <small class="text-muted">{{ $alert->type }}</small>
                                </div>
                                <div><strong>ID externo:</strong> {{ $alert->id_externo }}</div>
                                <div><strong>Ocorrência:</strong> {{ $alert->data_ocorrencia?->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <form method="POST" action="{{ route('alerts.toggle-status', $alert) }}">@csrf
                                    <button type="submit" class="btn btn-sm {{ $alert->status_resolucao === 'pendente' ? 'btn-success' : 'btn-warning' }}">
                                        {{ $alert->status_resolucao === 'pendente' ? 'Marcar como resolvido' : 'Reabrir alerta' }}
                                    </button>
                                </form>
                                <a href="{{ route('alerts.show', $alert) }}" class="btn btn-sm btn-outline-info">Expandir</a>
                            </div>
                        </div>

                        <div class="row mt-3 g-3 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Atribuir responsável</label>
                                <form method="POST" action="{{ route('alerts.assign-user', $alert) }}" class="d-flex gap-2 align-items-center">
                                    @csrf
                                    <select name="atribuido" class="form-select">
                                        <option value="">Sem atribuição</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ $alert->atribuido == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Responsável: {{ $alert->atribuidoUser?->name ?? 'Não atribuído' }}</div>
                                <div class="small text-muted">Resolvido por: {{ $alert->resolvidoPor?->name ?? 'Ainda não resolvido' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-light border">Nenhum alerta encontrado.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection