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
            <h1 class="h3 mb-1"><i class="fas fa-exclamation-triangle text-warning me-2"></i>{{ $title }}</h1>
            <p class="text-muted mb-0">Gerencie incidentes pendentes, atribua responsáveis e acompanhe a resolução.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('alerts.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Cadastrar Alerta
            </a>
            <a href="{{ route('alerts.resolved') }}" class="btn btn-outline-success">
                <i class="fas fa-check-circle me-1"></i> Ver Resolvidos
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-chart-line me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Filtro de Busca -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('alerts.index') }}" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="Buscar por ID externo, serviço ou mensagem de erro...">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Pesquisar</button>
                    @if(request('search'))
                        <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary">Limpar</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">
                    <strong>{{ $alerts->total() }}</strong> alerta(s) pendente(s) nesta página
                </div>
                @if($alerts->count() > 0)
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllPendingAlerts">
                            <i class="fas fa-check-square me-1"></i> Selecionar todos
                        </button>
                        <button type="button" class="btn btn-sm btn-success" id="bulkResolveButton" disabled>
                            <i class="fas fa-check-double me-1"></i> Resolver selecionados
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Lista de Alertas -->
    <div class="row g-3">
        @forelse($alerts as $alert)
            <div class="col-12">
                <div class="card border-0 shadow-sm border-start border-4 border-warning alert-card" data-alert-id="{{ $alert->id }}">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-3">
                                <input type="checkbox" class="form-check-input checkbox-alert-select" value="{{ $alert->id }}" aria-label="Selecionar alerta {{ $alert->id }}" style="width: 1.2rem; height: 1.2rem; margin-top: 0.25rem;">
                                <div>
                                    <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                                        <span class="badge bg-warning text-dark text-uppercase px-3 py-1">
                                            <i class="fas fa-clock me-1"></i> Pendente
                                        </span>
                                        <h4 class="mb-0 fw-bold text-dark">{{ $alert->service_name ?? $alert->type }}</h4>
                                        <span class="badge bg-light text-secondary border">{{ $alert->type }}</span>
                                    </div>

                                    <div class="text-muted small mb-3">
                                        <span class="me-3"><i class="fas fa-fingerprint me-1"></i> ID Externo: <strong>{{ $alert->id_externo }}</strong></span>
                                        <span class="me-3"><i class="fas fa-calendar-alt me-1"></i> Ocorrência: <strong>{{ $alert->data_ocorrencia?->format('d/m/Y H:i') }}</strong> ({{ $alert->data_ocorrencia?->diffForHumans() }})</span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <button type="button"
                                        class="btn btn-success shadow-sm"
                                        onclick="openResolveModal('{{ $alert->id }}', '{{ addslashes($alert->service_name ?? $alert->type) }}', '{{ addslashes($alert->id_externo) }}')">
                                    <i class="fas fa-check me-1"></i> Marcar como Resolvido
                                </button>

                                @if((int) $alert->atribuido !== (int) auth()->id())
                                    <form method="POST" action="{{ route('alerts.assign-me', $alert) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-hand-paper me-1"></i> Atribuir a mim
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('alerts.show', $alert) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-expand-alt me-1"></i> Detalhes
                                </a>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="font-monospace small text-dark" style="white-space: pre-wrap; word-break: break-all; max-height: 100px; overflow-y: auto;">{{ Str::limit($alert->mensagem, 350) }}</div>
                        </div>

                        <div class="row g-3 align-items-center pt-2 border-top">
                            <div class="col-md-7">
                                <form method="POST" action="{{ route('alerts.assign-user', $alert) }}" class="d-flex gap-2 align-items-center">
                                    @csrf
                                    <span class="small fw-bold text-muted text-nowrap"><i class="fas fa-user-tag me-1"></i> Responsável:</span>
                                    <select name="atribuido" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="">Sem atribuição</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ $alert->atribuido == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <noscript>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
                                    </noscript>
                                </form>
                            </div>
                            <div class="col-md-5 text-md-end small text-muted">
                                @if($alert->atribuidoUser)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                        <i class="fas fa-user me-1"></i> Atribuído a: {{ $alert->atribuidoUser->name }}
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger px-2 py-1">
                                        <i class="fas fa-exclamation-circle me-1"></i> Não atribuído
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm py-5 text-center text-muted">
                    <div class="py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5>Nenhum alerta pendente no momento</h5>
                        <p class="mb-0">Todos os incidentes foram resolvidos ou nenhum filtro correspondeu à busca.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $alerts->links() }}
    </div>
</div>

<div class="modal fade" id="bulkResolveModal" tabindex="-1" aria-labelledby="bulkResolveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="bulkResolveModalLabel"><i class="fas fa-check-double me-1"></i> Resolver alertas em lote</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST" action="{{ route('alerts.bulk-resolve') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-3">
                        <div class="small text-muted">Quantidade selecionada:</div>
                        <strong id="bulkResolveCount" class="fs-5 text-dark">0</strong>
                    </div>

                    <div class="mb-3">
                        <label for="bulkComentario" class="form-label fw-bold">Comentário da resolução <span class="text-danger">*</span></label>
                        <textarea name="comentario" id="bulkComentario" rows="4" class="form-control" placeholder="Descreva a ação tomada para resolver todos os alertas selecionados..." required minlength="3"></textarea>
                    </div>

                    <div id="bulkSelectedAlertsContainer"></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4"><i class="fas fa-check me-1"></i> Confirmar resolução em lote</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('partials.resolve-modal')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAllButton = document.getElementById('selectAllPendingAlerts');
        const bulkResolveButton = document.getElementById('bulkResolveButton');
        const bulkResolveModal = document.getElementById('bulkResolveModal');
        const checkboxAlerts = document.querySelectorAll('.checkbox-alert-select');
        const bulkSelectedAlertsContainer = document.getElementById('bulkSelectedAlertsContainer');
        const bulkResolveCount = document.getElementById('bulkResolveCount');

        function updateBulkSelectionState() {
            const checked = document.querySelectorAll('.checkbox-alert-select:checked');
            const hasSelection = checked.length > 0;
            if (bulkResolveButton) bulkResolveButton.disabled = !hasSelection;
            if (bulkResolveCount) bulkResolveCount.textContent = checked.length;

            let html = '';
            checked.forEach(item => {
                html += `<input type="hidden" name="ids[]" value="${item.value}">`;
            });
            if (bulkSelectedAlertsContainer) bulkSelectedAlertsContainer.innerHTML = html;
        }

        if (selectAllButton) {
            selectAllButton.addEventListener('click', function () {
                const allChecked = document.querySelectorAll('.checkbox-alert-select');
                const allSelected = document.querySelectorAll('.checkbox-alert-select:checked').length === allChecked.length;

                allChecked.forEach(item => {
                    item.checked = !allSelected;
                });

                updateBulkSelectionState();
            });
        }

        checkboxAlerts.forEach(item => {
            item.addEventListener('change', updateBulkSelectionState);
        });

        if (bulkResolveButton) {
            bulkResolveButton.addEventListener('click', function () {
                if (document.querySelectorAll('.checkbox-alert-select:checked').length === 0) {
                    return;
                }
                updateBulkSelectionState();
                if (bulkResolveModal && window.bootstrap && window.bootstrap.Modal) {
                    const modal = typeof window.bootstrap.Modal.getOrCreateInstance === 'function'
                        ? window.bootstrap.Modal.getOrCreateInstance(bulkResolveModal)
                        : new window.bootstrap.Modal(bulkResolveModal);
                    modal.show();
                }
            });
        }

        updateBulkSelectionState();
    });
</script>
@endsection
