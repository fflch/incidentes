@extends('layout')

@section('content')
<div class="container-fluid py-4" id="tvPanelContainer">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <!-- Barra de Administração (Exibida somente para admins logados) -->
    @if($isAdmin)
        <div class="alert alert-primary border-0 shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 py-2 px-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary text-white"><i class="fas fa-user-shield me-1"></i> Modo Administrador</span>
                <span class="small text-muted d-none d-md-inline">Você tem acesso rápido para atribuir responsáveis e marcar incidentes como resolvidos.</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-primary bg-white">
                    <i class="fas fa-chart-line me-1"></i> Dashboard
                </a>
                <a href="{{ route('alerts.index') }}" class="btn btn-sm btn-outline-primary bg-white">
                    <i class="fas fa-list me-1"></i> Gestão de Alertas
                </a>
                <a href="{{ route('assignment-rules.index') }}" class="btn btn-sm btn-outline-primary bg-white">
                    <i class="fas fa-magic me-1"></i> Regras de Atribuição
                </a>
                <a href="{{ route('alerts.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> Novo Alerta
                </a>
            </div>
        </div>
    @endif

    <!-- Cabeçalho do Painel TV -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden" id="statusHeaderCard">
        <div class="card-body p-4 {{ $alerts->count() > 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }}" id="statusHeaderBg">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div id="statusIconBox" class="p-3 rounded-circle d-flex align-items-center justify-content-center shadow-sm {{ $alerts->count() > 0 ? 'bg-danger text-white' : 'bg-success text-white' }}" style="width: 56px; height: 56px; font-size: 24px;">
                        <i class="fas {{ $alerts->count() > 0 ? 'fa-exclamation-triangle' : 'fa-check' }}"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="h4 mb-0 fw-bold" id="statusTitle">
                                {{ $alerts->count() > 0 ? 'Painel de Incidentes em Aberto' : 'Todos os Sistemas Operando Normalmente' }}
                            </h2>
                            <span class="badge rounded-pill {{ $alerts->count() > 0 ? 'bg-danger' : 'bg-success' }} fs-6 px-3" id="alertsCountBadge">
                                {{ $alerts->count() }} {{ $alerts->count() === 1 ? 'pendência' : 'pendências' }}
                            </span>
                        </div>
                        <p class="mb-0 text-muted small mt-1" id="statusSubtitle">
                            {{ $alerts->count() > 0 ? 'Exibição pública para acompanhamento operacional em tempo real.' : 'Nenhum alerta ou pendência crítica no momento.' }}
                        </p>
                    </div>
                </div>

                <!-- Indicador Ao Vivo com Auto-refresh -->
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <div class="d-flex align-items-center gap-2 justify-content-end">
                            <span class="live-dot"></span>
                            <span class="fw-bold small text-uppercase" style="letter-spacing: 0.5px;">Painel Ao Vivo</span>
                        </div>
                        <div class="small text-muted">
                            Última atualização: <strong id="lastUpdatedTime">{{ now()->format('H:i:s') }}</strong>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm" id="btnToggleFullscreen" title="Alternar Tela Cheia">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela / Cards de Alertas Pendentes (Público) -->
    <div class="card border-0 shadow-sm" id="alertsTableCard">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">
                <i class="fas fa-bell text-warning me-2"></i>Lista de Pendências
            </h5>
            <div class="small text-muted">
                <i class="fas fa-sync-alt fa-spin me-1 text-primary" id="syncSpinner" style="display: none;"></i>
                Atualização automática a cada 15 segundos
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="publicAlertsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%;">Data da Ocorrência</th>
                        <th style="width: 40%;">Nome do Serviço</th>
                        <th style="width: 20%;">Responsável Atribuído</th>
                        @if($isAdmin)
                            <th style="width: 15%;" class="text-end">Ações Admin</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="alertsTableBody">
                    @forelse($alerts as $alert)
                        <tr id="alert-row-{{ $alert->id }}" class="alert-row">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning text-dark p-2 rounded">
                                        <i class="fas fa-clock"></i>
                                    </span>
                                    <div>
                                        <strong class="text-dark d-block fs-6">{{ $alert->data_ocorrencia?->format('d/m/Y H:i') }}</strong>
                                        <small class="text-muted">{{ $alert->data_ocorrencia?->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-dark border p-2">
                                        <i class="fas fa-server"></i>
                                    </span>
                                    <div>
                                        <strong class="fs-5 text-dark">{{ $alert->service_name ?? $alert->type }}</strong>
                                        @if($alert->service_name && $alert->type)
                                            <span class="badge bg-secondary-subtle text-secondary ms-1 small">{{ $alert->type }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($alert->atribuidoUser)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 13px;">
                                            {{ strtoupper(substr($alert->atribuidoUser->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-dark">{{ $alert->atribuidoUser->name }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold">
                                        <i class="fas fa-question-circle me-1"></i> Não atribuído
                                    </span>
                                @endif
                            </td>
                            @if($isAdmin)
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center">
                                        <!-- Atribuição rápida inline -->
                                        <select class="form-select form-select-sm select-quick-assign" style="max-width: 170px;" data-alert-id="{{ $alert->id }}" title="Trocar responsável">
                                            <option value="">Sem atribuição</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ $alert->atribuido == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <!-- Botão Resolver com Popup -->
                                        <button type="button" class="btn btn-sm btn-success" title="Resolver Incidente" onclick="openResolveModal('{{ $alert->id }}', '{{ addslashes($alert->service_name ?? $alert->type) }}', '{{ addslashes($alert->id_externo) }}')">
                                            <i class="fas fa-check"></i>
                                        </button>

                                        <!-- Ver detalhes -->
                                        <a href="{{ route('alerts.show', $alert) }}" class="btn btn-sm btn-outline-primary" title="Ver detalhes completos">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr id="emptyRow">
                            <td colspan="{{ $isAdmin ? 4 : 3 }}" class="text-center py-5">
                                <div class="py-4">
                                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                                        <i class="fas fa-check-double fa-2x"></i>
                                    </div>
                                    <h4 class="text-success fw-bold">Tudo sob controle!</h4>
                                    <p class="text-muted mb-0">Não há nenhum alerta pendente registrado no sistema.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($isAdmin)
    @include('partials.resolve-modal')
@endif

<style>
    .live-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #198754;
        animation: pulse-green 2s infinite;
    }

    @keyframes pulse-green {
        0% {
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7);
        }
        70% {
            box-shadow: 0 0 0 8px rgba(25, 135, 84, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
        }
    }

    .alert-row {
        transition: background-color 0.3s ease;
    }
    .alert-row:hover {
        background-color: #fcfcfd;
    }
    .new-item-highlight {
        animation: highlight-row 1.5s ease-out;
    }
    @keyframes highlight-row {
        0% { background-color: #fff3cd; }
        100% { background-color: transparent; }
    }
</style>

<script>
    const isAdmin = @json($isAdmin);
    const usersList = @json($users);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    // Auto-refresh via polling seguro a cada 15 segundos
    async function fetchPublicAlerts() {
        const spinner = document.getElementById('syncSpinner');
        if (spinner) spinner.style.display = 'inline-block';

        try {
            const response = await fetch('/api/public-alerts', {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) throw new Error('Falha na resposta');

            const data = await response.json();
            updatePublicPanel(data);

            const lastUpdatedEl = document.getElementById('lastUpdatedTime');
            if (lastUpdatedEl) lastUpdatedEl.textContent = data.updated_at;
        } catch (error) {
            console.warn('Erro ao atualizar painel de TV:', error);
        } finally {
            if (spinner) spinner.style.display = 'none';
        }
    }

    function updatePublicPanel(data) {
        const tbody = document.getElementById('alertsTableBody');
        const countBadge = document.getElementById('alertsCountBadge');
        const statusHeaderBg = document.getElementById('statusHeaderBg');
        const statusIconBox = document.getElementById('statusIconBox');
        const statusTitle = document.getElementById('statusTitle');
        const statusSubtitle = document.getElementById('statusSubtitle');

        const total = data.total;
        countBadge.textContent = `${total} ${total === 1 ? 'pendência' : 'pendências'}`;

        if (total === 0) {
            statusHeaderBg.className = 'card-body p-4 bg-success-subtle';
            statusIconBox.className = 'p-3 rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-success text-white';
            statusIconBox.innerHTML = '<i class="fas fa-check"></i>';
            statusTitle.textContent = 'Todos os Sistemas Operando Normalmente';
            statusSubtitle.textContent = 'Nenhum alerta ou pendência crítica no momento.';
            countBadge.className = 'badge rounded-pill bg-success fs-6 px-3';

            tbody.innerHTML = `
                <tr id="emptyRow">
                    <td colspan="${isAdmin ? 4 : 3}" class="text-center py-5">
                        <div class="py-4">
                            <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                                <i class="fas fa-check-double fa-2x"></i>
                            </div>
                            <h4 class="text-success fw-bold">Tudo sob controle!</h4>
                            <p class="text-muted mb-0">Não há nenhum alerta pendente registrado no sistema.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        statusHeaderBg.className = 'card-body p-4 bg-danger-subtle';
        statusIconBox.className = 'p-3 rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-danger text-white';
        statusIconBox.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        statusTitle.textContent = 'Painel de Incidentes em Aberto';
        statusSubtitle.textContent = 'Exibição pública para acompanhamento operacional em tempo real.';
        countBadge.className = 'badge rounded-pill bg-danger fs-6 px-3';

        let html = '';
        data.alerts.forEach(alert => {
            const initial = alert.atribuido_nome && alert.atribuido_nome !== 'Não atribuído'
                ? alert.atribuido_nome.charAt(0).toUpperCase()
                : '?';

            const userBadge = (alert.atribuido_nome && alert.atribuido_nome !== 'Não atribuído')
                ? `<div class="d-flex align-items-center gap-2">
                     <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 13px;">${initial}</div>
                     <span class="fw-semibold text-dark">${escapeHtml(alert.atribuido_nome)}</span>
                   </div>`
                : `<span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold">
                     <i class="fas fa-question-circle me-1"></i> Não atribuído
                   </span>`;

            let adminActions = '';
            if (isAdmin) {
                let options = `<option value="">Sem atribuição</option>`;
                usersList.forEach(u => {
                    const sel = (alert.atribuido_id == u.id) ? 'selected' : '';
                    options += `<option value="${u.id}" ${sel}>${escapeHtml(u.name)}</option>`;
                });

                adminActions = `
                    <td class="text-end">
                        <div class="d-inline-flex gap-2 align-items-center">
                            <select class="form-select form-select-sm select-quick-assign" style="max-width: 170px;" data-alert-id="${alert.id}">
                                ${options}
                            </select>
                            <button type="button" class="btn btn-sm btn-success" title="Resolver Incidente" onclick="openResolveModal('${alert.id}', '${escapeHtml(alert.service_name)}', '')">
                                <i class="fas fa-check"></i>
                            </button>
                            <a href="/alerts/${alert.id}" class="btn btn-sm btn-outline-primary" title="Ver detalhes completos">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                `;
            }

            html += `
                <tr id="alert-row-${alert.id}" class="alert-row">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark p-2 rounded"><i class="fas fa-clock"></i></span>
                            <div>
                                <strong class="text-dark d-block fs-6">${alert.data_ocorrencia_formatada}</strong>
                                <small class="text-muted">${alert.data_ocorrencia_relativa || ''}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border p-2"><i class="fas fa-server"></i></span>
                            <div>
                                <strong class="fs-5 text-dark">${escapeHtml(alert.service_name)}</strong>
                            </div>
                        </div>
                    </td>
                    <td>${userBadge}</td>
                    ${adminActions}
                </tr>
            `;
        });

        tbody.innerHTML = html;
        bindQuickAssignEvents();
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    function bindQuickAssignEvents() {
        document.querySelectorAll('.select-quick-assign').forEach(select => {
            select.onchange = async function () {
                const alertId = this.dataset.alertId;
                const userId = this.value;
                this.disabled = true;

                try {
                    const res = await fetch(`/alerts/${alertId}/assign-user`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ atribuido: userId })
                    });
                    if (res.ok) {
                        fetchPublicAlerts();
                    } else {
                        alert('Erro ao atualizar atribuição');
                    }
                } catch (e) {
                    alert('Erro de comunicação com o servidor');
                } finally {
                    this.disabled = false;
                }
            };
        });
    }

    // Alternar Tela Cheia (Modo TV)
    document.getElementById('btnToggleFullscreen')?.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(err => console.log(err));
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    });

    // Inicia polling a cada 15s
    setInterval(fetchPublicAlerts, 15000);
    bindQuickAssignEvents();
</script>
@endsection

