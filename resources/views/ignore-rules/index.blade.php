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
            <h1 class="h3 mb-1"><i class="fas fa-eye-slash text-secondary me-2"></i>Regras para Ignorar Alertas</h1>
            <p class="text-muted mb-0">Alertas cuja mensagem contenha um destes textos são ignorados: ficam arquivados, sem responsável e sem e-mail para ninguém.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('alerts.ignored') }}" class="btn btn-outline-secondary">
                <i class="fas fa-archive me-1"></i> Alertas Ignorados
            </a>
            <a href="{{ route('ignore-rules.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Nova Regra
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="Filtrar pelo texto...">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    @if(request('search'))
                        <a href="{{ route('ignore-rules.index') }}" class="btn btn-outline-secondary">Limpar</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 55%;">Texto na mensagem</th>
                        <th style="width: 25%;">Alertas ignorados</th>
                        <th style="width: 20%;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                        <tr>
                            <td>
                                <pre class="mb-1 p-2 bg-light border rounded small text-dark text-break" style="white-space: pre-wrap; max-height: 9rem; overflow: auto;">{{ $rule->pattern }}</pre>
                                <div class="small text-muted">
                                    Criada em {{ $rule->created_at?->format('d/m/Y H:i') }}
                                    @if($rule->user) por {{ $rule->user->name }} @endif
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('alerts.ignored', ['regra' => $rule->id]) }}" class="text-decoration-none">
                                    <span class="badge bg-secondary-subtle text-secondary border">{{ $rule->alerts_count }}</span>
                                    <span class="small">ver alertas</span>
                                </a>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('ignore-rules.edit', $rule) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('ignore-rules.destroy', $rule) }}" class="d-inline" onsubmit="return confirm('Excluir esta regra? Novos alertas com esse texto voltarão a ser tratados normalmente; os já ignorados continuam arquivados.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                                <p class="mb-2">Nenhuma regra para ignorar cadastrada.</p>
                                <a href="{{ route('ignore-rules.create') }}" class="btn btn-sm btn-outline-primary">Cadastrar primeira regra</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
