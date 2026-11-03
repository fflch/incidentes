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
            <h1 class="h3 mb-1"><i class="fas fa-magic text-primary me-2"></i>Regras de Atribuição Automática</h1>
            <p class="text-muted mb-0">Associe o nome de um serviço a um usuário admin para que novos alertas sejam atribuídos automaticamente.</p>
        </div>
        <div>
            <a href="{{ route('assignment-rules.create') }}" class="btn btn-primary shadow-sm">
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
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="Filtrar por nome de serviço ou responsável...">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    @if(request('search'))
                        <a href="{{ route('assignment-rules.index') }}" class="btn btn-outline-secondary">Limpar</a>
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
                        <th style="width: 45%;">Nome do Serviço</th>
                        <th style="width: 35%;">Responsável Atribuído (Admin)</th>
                        <th style="width: 20%;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle p-2">
                                        <i class="fas fa-server"></i>
                                    </span>
                                    <div>
                                        <strong class="text-dark">{{ $rule->service_name }}</strong>
                                        <div class="small text-muted">Criada em {{ $rule->created_at?->format('d/m/Y H:i') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: 600;">
                                        {{ strtoupper(substr($rule->user?->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $rule->user?->name ?? 'Usuário não encontrado' }}</div>
                                        <div class="small text-muted">{{ $rule->user?->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('assignment-rules.edit', $rule) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('assignment-rules.destroy', $rule) }}" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta regra de atribuição?');">
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
                                <p class="mb-2">Nenhuma regra de atribuição cadastrada.</p>
                                <a href="{{ route('assignment-rules.create') }}" class="btn btn-sm btn-outline-primary">
                                    Cadastrar primeira regra
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

