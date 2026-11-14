@extends('layout')

@section('content')
<div class="ip-wrap py-4" id="tvPanelContainer">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <!-- Cabeçalho -->
    <section class="ip-hero mb-4" id="ipHero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="ip-hero-icon" id="ipHeroIcon"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <div class="ip-eyebrow">Central de Incidentes</div>
                    <h1 class="ip-title mb-1" id="statusTitle">Carregando…</h1>
                    <div class="ip-subtitle" id="statusSubtitle"></div>
                </div>
            </div>

            <div class="text-end">
                <div class="ip-clock" id="ipClock">--:--</div>
                <div class="ip-live">
                    <span class="live-dot"></span> Ao vivo · atualizado às <strong id="lastUpdatedTime">{{ $panel['updated_at'] }}</strong>
                    <i class="fas fa-sync-alt fa-spin ms-1" id="syncSpinner" style="display: none;"></i>
                </div>
                <button type="button" class="btn btn-sm ip-btn-ghost mt-2" id="btnToggleFullscreen" title="Alternar tela cheia">
                    <i class="fas fa-expand me-1"></i> Tela cheia
                </button>
            </div>
        </div>

        @if($isAdmin)
            <div class="ip-admin-links mt-4">
                <a href="{{ route('dashboard') }}" class="btn btn-sm ip-btn-ghost"><i class="fas fa-chart-line me-1"></i> Dashboard</a>
                <a href="{{ route('alerts.index') }}" class="btn btn-sm ip-btn-ghost"><i class="fas fa-list me-1"></i> Gestão de alertas</a>
                <a href="{{ route('assignment-rules.index') }}" class="btn btn-sm ip-btn-ghost"><i class="fas fa-magic me-1"></i> Regras de atribuição</a>
                <a href="{{ route('alerts.create') }}" class="btn btn-sm btn-light fw-semibold"><i class="fas fa-plus me-1"></i> Novo alerta</a>
            </div>
        @endif
    </section>

    <!-- Indicadores -->
    <section class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="ip-stat">
                <div class="ip-stat-icon bg-danger-subtle text-danger"><i class="fas fa-fire"></i></div>
                <div>
                    <div class="ip-stat-value" id="statTotal">0</div>
                    <div class="ip-stat-label">Incidentes em aberto</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ip-stat">
                <div class="ip-stat-icon bg-warning-subtle text-warning-emphasis"><i class="fas fa-user-slash"></i></div>
                <div>
                    <div class="ip-stat-value" id="statUnassigned">0</div>
                    <div class="ip-stat-label">Sem responsável</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ip-stat">
                <div class="ip-stat-icon bg-primary-subtle text-primary"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="ip-stat-value" id="statOccurrences">0</div>
                    <div class="ip-stat-label">Ocorrências agrupadas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ip-stat">
                <div class="ip-stat-icon bg-secondary-subtle text-secondary"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <div class="ip-stat-value ip-stat-value-sm" id="statOldest">—</div>
                    <div class="ip-stat-label">Incidente mais antigo</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filtros -->
    <section class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="btn-group ip-filters" role="group" aria-label="Filtrar incidentes">
            <button type="button" class="btn btn-sm active" data-filter="todos">Todos <span class="badge" id="countTodos">0</span></button>
            <button type="button" class="btn btn-sm" data-filter="sem">Sem responsável <span class="badge" id="countSem">0</span></button>
            @auth
                <button type="button" class="btn btn-sm" data-filter="meus">Meus <span class="badge" id="countMeus">0</span></button>
            @endauth
        </div>

        <div class="d-flex gap-2 align-items-center flex-wrap">
            <div class="input-group input-group-sm ip-search">
                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                <input type="search" class="form-control" id="ipSearch" placeholder="Filtrar por serviço ou responsável">
            </div>
            @if($isAdmin)
                <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllHomeAlerts">
                    <i class="fas fa-check-square me-1"></i> Selecionar visíveis
                </button>
                <button type="button" class="btn btn-sm btn-success" id="bulkResolveHomeButton" disabled>
                    <i class="fas fa-check-double me-1"></i> Resolver <span id="selectedCountLabel">0</span>
                </button>
            @endif
        </div>
    </section>

    <!-- Incidentes -->
    <section class="row g-3" id="alertsGrid" aria-live="polite"></section>
</div>

@if($isAdmin)
    <div class="modal fade" id="bulkResolveHomeModal" tabindex="-1" aria-labelledby="bulkResolveHomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="bulkResolveHomeModalLabel"><i class="fas fa-check-double me-1"></i> Resolver alertas em lote</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="POST" action="{{ route('alerts.bulk-resolve') }}">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-light border mb-3">
                            <div class="small text-muted">Quantidade selecionada:</div>
                            <strong id="bulkHomeResolveCount" class="fs-5 text-dark">0</strong>
                            <div class="small text-muted mt-1">As repetições agrupadas em cada alerta também serão resolvidas.</div>
                        </div>
                        <div class="mb-3">
                            <label for="bulkHomeComentario" class="form-label fw-bold">Comentário da resolução <span class="text-danger">*</span> <small class="text-muted fw-normal">(mínimo 3 caracteres)</small></label>
                            <textarea name="comentario" id="bulkHomeComentario" rows="4" class="form-control" placeholder="Descreva a ação tomada para resolver todos os alertas selecionados..." required minlength="3"></textarea>
                        </div>
                        <div id="bulkHomeSelectedAlertsContainer"></div>
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
@endif

<style>
    .min-w-0 { min-width: 0; }
    .ip-wrap { max-width: 1600px; margin: 0 auto; padding-left: 16px; padding-right: 16px; }

    .ip-hero {
        border-radius: 18px;
        padding: 28px 32px;
        color: #fff;
        background: linear-gradient(135deg, #0f2a4a 0%, #163d6b 55%, #1d5a96 100%);
        box-shadow: 0 10px 30px rgba(15, 42, 74, .18);
        transition: background .6s ease;
    }
    .ip-hero.is-alert { background: linear-gradient(135deg, #3b0d14 0%, #6b1622 55%, #a3283a 100%); box-shadow: 0 10px 30px rgba(107, 22, 34, .22); }
    .ip-hero.is-ok    { background: linear-gradient(135deg, #0b3b2a 0%, #11613f 55%, #198754 100%); box-shadow: 0 10px 30px rgba(17, 97, 63, .22); }
    .ip-hero-icon {
        width: 60px; height: 60px; border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; background: rgba(255,255,255,.14); backdrop-filter: blur(4px);
    }
    .ip-eyebrow { text-transform: uppercase; letter-spacing: .12em; font-size: .75rem; opacity: .75; font-weight: 600; }
    .ip-title { font-size: 1.75rem; font-weight: 700; color: #fff; }
    .ip-subtitle { opacity: .8; }
    .ip-clock { font-size: 2.25rem; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1; }
    .ip-live { font-size: .85rem; opacity: .85; margin-top: 6px; }
    .ip-btn-ghost { color: #fff; border: 1px solid rgba(255,255,255,.35); background: rgba(255,255,255,.08); }
    .ip-btn-ghost:hover { color: #fff; background: rgba(255,255,255,.2); }
    .ip-admin-links { display: flex; gap: 8px; flex-wrap: wrap; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.15); }

    .live-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; background: #3ddc84; animation: ip-pulse 2s infinite; vertical-align: middle; }
    @keyframes ip-pulse {
        0% { box-shadow: 0 0 0 0 rgba(61, 220, 132, .7); }
        70% { box-shadow: 0 0 0 8px rgba(61, 220, 132, 0); }
        100% { box-shadow: 0 0 0 0 rgba(61, 220, 132, 0); }
    }

    .ip-stat {
        display: flex; align-items: center; gap: 14px; height: 100%;
        background: #fff; border-radius: 14px; padding: 18px 20px;
        border: 1px solid #edf0f4; box-shadow: 0 2px 8px rgba(16, 24, 40, .04);
    }
    .ip-stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 19px; flex-shrink: 0; }
    .ip-stat-value { font-size: 1.9rem; font-weight: 700; line-height: 1.1; color: #101828; font-variant-numeric: tabular-nums; }
    .ip-stat-value-sm { font-size: 1.15rem; }
    .ip-stat-label { font-size: .8rem; color: #667085; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; }

    .ip-filters .btn { border: 1px solid #d0d5dd; background: #fff; color: #344054; font-weight: 600; }
    .ip-filters .btn .badge { background: #f2f4f7; color: #344054; margin-left: 4px; }
    .ip-filters .btn.active { background: #163d6b; border-color: #163d6b; color: #fff; }
    .ip-filters .btn.active .badge { background: rgba(255,255,255,.2); color: #fff; }
    .ip-search { width: 280px; max-width: 100%; }

    .ip-card {
        position: relative; height: 100%;
        background: #fff; border-radius: 14px; border: 1px solid #edf0f4;
        box-shadow: 0 2px 8px rgba(16, 24, 40, .05);
        padding: 18px 20px 16px 24px; overflow: hidden;
        display: flex; flex-direction: column; gap: 12px;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .ip-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16, 24, 40, .09); }
    .ip-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: var(--sev, #f79009); }
    .ip-card.sev-new  { --sev: #2e90fa; }
    .ip-card.sev-warn { --sev: #f79009; }
    .ip-card.sev-crit { --sev: #d92d20; }
    .ip-card.is-selected { outline: 2px solid #163d6b; outline-offset: -2px; }
    .ip-card.is-new { animation: ip-flash 1.6s ease-out; }
    @keyframes ip-flash { 0% { background: #fff6e0; } 100% { background: #fff; } }

    .ip-card-service { font-size: 1.15rem; font-weight: 700; color: #101828; word-break: break-word; }
    .ip-sev-pill { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; padding: 3px 9px; border-radius: 999px; }
    .sev-new  .ip-sev-pill { background: #eff8ff; color: #175cd3; }
    .sev-warn .ip-sev-pill { background: #fffaeb; color: #b54708; }
    .sev-crit .ip-sev-pill { background: #fef3f2; color: #b42318; }
    .ip-meta { font-size: .85rem; color: #667085; display: flex; flex-wrap: wrap; gap: 4px 14px; }
    .ip-repeat { background: #f4f3ff; color: #5925dc; font-weight: 600; font-size: .75rem; padding: 3px 9px; border-radius: 999px; }
    .ip-avatar { width: 30px; height: 30px; border-radius: 50%; background: #e0eaff; color: #3538cd; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .8rem; flex-shrink: 0; }
    .ip-unassigned { background: #fef3f2; color: #b42318; font-weight: 600; font-size: .8rem; padding: 4px 10px; border-radius: 999px; }
    .ip-card-footer { margin-top: auto; padding-top: 12px; border-top: 1px dashed #eaecf0; display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }

    .ip-empty { background: #fff; border-radius: 18px; border: 1px dashed #c7d7cd; padding: 64px 24px; text-align: center; }
    .ip-empty-icon { width: 84px; height: 84px; border-radius: 50%; background: #ecfdf3; color: #039855; display: inline-flex; align-items: center; justify-content: center; font-size: 34px; margin-bottom: 16px; }

    #tvPanelContainer:fullscreen { background: #f5f7fa; overflow-y: auto; max-width: none; padding: 32px; }
</style>

<script>
    const isAdmin = @json($isAdmin);
    const isLoggedIn = @json(auth()->check());
    const currentUserId = @json(auth()->id());
    const usersList = @json($users);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());

    let panelData = @json($panel);
    let currentFilter = 'todos';
    let knownIds = new Set(panelData.alerts.map(a => a.id));
    const selectedIds = new Set();

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    function severity(alert) {
        if (alert.idade_minutos >= 24 * 60) return { cls: 'sev-crit', label: 'Crítico' };
        if (alert.idade_minutos >= 60) return { cls: 'sev-warn', label: 'Atenção' };
        return { cls: 'sev-new', label: 'Recente' };
    }

    function isMine(alert) {
        return isLoggedIn && alert.atribuido_id !== null && Number(alert.atribuido_id) === Number(currentUserId);
    }

    function visibleAlerts() {
        const term = (document.getElementById('ipSearch').value || '').trim().toLowerCase();
        return panelData.alerts.filter(a => {
            if (currentFilter === 'sem' && a.atribuido_id) return false;
            if (currentFilter === 'meus' && !isMine(a)) return false;
            if (!term) return true;
            return [a.service_name, a.type, a.atribuido_nome].some(v => (v || '').toLowerCase().includes(term));
        });
    }

    function renderHeader() {
        const total = panelData.total;
        const hero = document.getElementById('ipHero');
        const icon = document.getElementById('ipHeroIcon');

        hero.classList.toggle('is-alert', total > 0);
        hero.classList.toggle('is-ok', total === 0);
        icon.innerHTML = total > 0 ? '<i class="fas fa-exclamation-triangle"></i>' : '<i class="fas fa-check"></i>';

        document.getElementById('statusTitle').textContent = total > 0
            ? `${total} ${total === 1 ? 'incidente em aberto' : 'incidentes em aberto'}`
            : 'Todos os sistemas operando normalmente';
        document.getElementById('statusSubtitle').textContent = total > 0
            ? (panelData.nao_atribuidos > 0
                ? `${panelData.nao_atribuidos} ainda sem responsável — assuma um se puder ajudar.`
                : 'Todos os incidentes já têm um responsável.')
            : 'Nenhum alerta pendente no momento.';

        document.getElementById('statTotal').textContent = total;
        document.getElementById('statUnassigned').textContent = panelData.nao_atribuidos;
        document.getElementById('statOccurrences').textContent = panelData.ocorrencias;
        document.getElementById('statOldest').textContent = panelData.mais_antigo_relativo || '—';
        document.getElementById('lastUpdatedTime').textContent = panelData.updated_at;

        document.getElementById('countTodos').textContent = total;
        document.getElementById('countSem').textContent = panelData.nao_atribuidos;
        const countMeus = document.getElementById('countMeus');
        if (countMeus) countMeus.textContent = panelData.alerts.filter(isMine).length;
    }

    function renderCard(alert, isNew) {
        const sev = severity(alert);
        const mine = isMine(alert);
        const assigned = !!alert.atribuido_id;

        const assignee = assigned
            ? `<div class="d-flex align-items-center gap-2 min-w-0">
                   <div class="ip-avatar">${escapeHtml(alert.atribuido_nome.charAt(0).toUpperCase())}</div>
                   <span class="fw-semibold text-dark text-truncate">${escapeHtml(alert.atribuido_nome)}${mine ? ' <span class="text-primary small">(você)</span>' : ''}</span>
               </div>`
            : `<span class="ip-unassigned"><i class="fas fa-user-slash me-1"></i> Sem responsável</span>`;

        let actions = '';
        if (isLoggedIn && !mine) {
            actions += `<button type="button" class="btn btn-sm btn-primary btn-assign-me" data-alert-id="${alert.id}" data-assigned="${assigned ? escapeHtml(alert.atribuido_nome) : ''}" title="Atribuir este incidente a mim">
                            <i class="fas fa-hand-paper me-1"></i> Atribuir a mim
                        </button>`;
        }
        if (isAdmin) {
            actions += `<button type="button" class="btn btn-sm btn-success btn-resolve" data-alert-id="${alert.id}" data-service="${escapeHtml(alert.service_name)}" title="Resolver incidente">
                            <i class="fas fa-check"></i>
                        </button>`;
        }
        if (isAdmin || mine) {
            actions += `<a href="/alerts/${alert.id}" class="btn btn-sm btn-outline-secondary" title="Ver detalhes e comentários"><i class="fas fa-arrow-right"></i></a>`;
        }

        const adminAssign = isAdmin
            ? `<select class="form-select form-select-sm select-quick-assign" data-alert-id="${alert.id}" title="Trocar responsável" style="max-width: 180px;">
                   <option value="">Sem atribuição</option>
                   ${usersList.map(u => `<option value="${u.id}" ${Number(alert.atribuido_id) === Number(u.id) ? 'selected' : ''}>${escapeHtml(u.name)}</option>`).join('')}
               </select>`
            : '';

        const checkbox = isAdmin
            ? `<input type="checkbox" class="form-check-input checkbox-home-alert-select mt-0" value="${alert.id}" ${selectedIds.has(alert.id) ? 'checked' : ''} aria-label="Selecionar alerta ${alert.id}">`
            : '';

        return `
            <div class="col-12 col-md-6 col-xxl-4">
                <article class="ip-card ${sev.cls} ${selectedIds.has(alert.id) ? 'is-selected' : ''} ${isNew ? 'is-new' : ''}" id="alert-row-${alert.id}">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="d-flex align-items-start gap-2 min-w-0">
                            ${checkbox}
                            <div class="min-w-0">
                                <div class="ip-card-service">${escapeHtml(alert.service_name)}</div>
                                ${alert.type && alert.type !== alert.service_name ? `<div class="small text-muted text-truncate">${escapeHtml(alert.type)}</div>` : ''}
                            </div>
                        </div>
                        <span class="ip-sev-pill flex-shrink-0">${sev.label}</span>
                    </div>

                    <div class="ip-meta">
                        <span title="${escapeHtml(alert.data_ocorrencia_formatada)}"><i class="far fa-clock me-1"></i>${escapeHtml(alert.data_ocorrencia_relativa || '')}</span>
                        <span><i class="far fa-calendar me-1"></i>${escapeHtml(alert.data_ocorrencia_formatada || '')}</span>
                        ${alert.repeticoes > 0
                            ? `<span class="ip-repeat" title="Última ocorrência ${escapeHtml(alert.ultima_ocorrencia_relativa || '')}"><i class="fas fa-redo-alt me-1"></i>+${alert.repeticoes} ${alert.repeticoes === 1 ? 'repetição' : 'repetições'}</span>`
                            : ''}
                    </div>

                    <div class="ip-card-footer">
                        ${assignee}
                        <div class="d-flex gap-2 align-items-center flex-wrap">${adminAssign}${actions}</div>
                    </div>
                </article>
            </div>`;
    }

    function renderGrid() {
        const grid = document.getElementById('alertsGrid');
        const list = visibleAlerts();

        if (panelData.total === 0) {
            grid.innerHTML = `
                <div class="col-12">
                    <div class="ip-empty">
                        <div class="ip-empty-icon"><i class="fas fa-check-double"></i></div>
                        <h3 class="fw-bold text-success mb-1">Tudo sob controle!</h3>
                        <p class="text-muted mb-0">Não há nenhum alerta pendente registrado no sistema.</p>
                    </div>
                </div>`;
        } else if (list.length === 0) {
            grid.innerHTML = `
                <div class="col-12">
                    <div class="ip-empty" style="border-color: #d0d5dd;">
                        <i class="fas fa-filter fa-2x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Nenhum incidente corresponde ao filtro atual.</p>
                    </div>
                </div>`;
        } else {
            grid.innerHTML = list.map(a => renderCard(a, !knownIds.has(a.id))).join('');
        }

        knownIds = new Set(panelData.alerts.map(a => a.id));
        bindCardEvents();
        syncSelection();
    }

    function render() {
        renderHeader();
        renderGrid();
    }

    function bindCardEvents() {
        document.querySelectorAll('.btn-assign-me').forEach(btn => {
            btn.onclick = async function () {
                const current = this.dataset.assigned;
                if (current && !confirm(`Este incidente está atribuído a ${current}. Deseja assumi-lo?`)) return;

                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                try {
                    const res = await fetch(`/alerts/${this.dataset.alertId}/assign-me`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    });
                    if (!res.ok) throw new Error();
                    await fetchPublicAlerts(true);
                } catch (e) {
                    alert('Não foi possível atribuir o alerta. Tente novamente.');
                    this.disabled = false;
                }
            };
        });

        document.querySelectorAll('.btn-resolve').forEach(btn => {
            btn.onclick = function () {
                openResolveModal(this.dataset.alertId, this.dataset.service, '');
            };
        });

        document.querySelectorAll('.select-quick-assign').forEach(select => {
            select.onchange = async function () {
                this.disabled = true;
                try {
                    const res = await fetch(`/alerts/${this.dataset.alertId}/assign-user`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ atribuido: this.value })
                    });
                    if (!res.ok) throw new Error();
                    await fetchPublicAlerts(true);
                } catch (e) {
                    alert('Erro ao atualizar atribuição');
                    this.disabled = false;
                }
            };
        });

        document.querySelectorAll('.checkbox-home-alert-select').forEach(cb => {
            cb.onchange = function () {
                const id = Number(this.value);
                this.checked ? selectedIds.add(id) : selectedIds.delete(id);
                this.closest('.ip-card')?.classList.toggle('is-selected', this.checked);
                syncSelection();
            };
        });
    }

    function syncSelection() {
        if (!isAdmin) return;
        // Remove da seleção alertas que já não estão pendentes
        const existing = new Set(panelData.alerts.map(a => a.id));
        [...selectedIds].forEach(id => { if (!existing.has(id)) selectedIds.delete(id); });

        document.getElementById('selectedCountLabel').textContent = selectedIds.size;
        document.getElementById('bulkResolveHomeButton').disabled = selectedIds.size === 0;
        document.getElementById('bulkHomeResolveCount').textContent = selectedIds.size;
        document.getElementById('bulkHomeSelectedAlertsContainer').innerHTML = [...selectedIds]
            .map(id => `<input type="hidden" name="ids[]" value="${id}">`).join('');
    }

    function userIsBusy() {
        const active = document.activeElement;
        return !!document.querySelector('.modal.show')
            || (active && active.tagName === 'SELECT');
    }

    async function fetchPublicAlerts(force = false) {
        if (!force && userIsBusy()) return;

        const spinner = document.getElementById('syncSpinner');
        if (spinner) spinner.style.display = 'inline-block';

        try {
            const response = await fetch('/api/public-alerts', { headers: { 'Accept': 'application/json' } });
            if (!response.ok) throw new Error('Falha na resposta');
            panelData = await response.json();
            render();
        } catch (error) {
            console.warn('Erro ao atualizar painel:', error);
        } finally {
            if (spinner) spinner.style.display = 'none';
        }
    }

    function tickClock() {
        const now = new Date();
        document.getElementById('ipClock').textContent = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    document.addEventListener('DOMContentLoaded', function () {
        knownIds = new Set(panelData.alerts.map(a => a.id)); // nada é "novo" no primeiro carregamento
        render();
        tickClock();
        setInterval(tickClock, 10000);
        setInterval(fetchPublicAlerts, 15000);

        document.querySelectorAll('.ip-filters [data-filter]').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.ip-filters [data-filter]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.filter;
                renderGrid();
            });
        });

        document.getElementById('ipSearch').addEventListener('input', renderGrid);

        document.getElementById('btnToggleFullscreen').addEventListener('click', function () {
            const el = document.getElementById('tvPanelContainer');
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else if (el.requestFullscreen) {
                el.requestFullscreen();
            }
        });

        if (isAdmin) {
            document.getElementById('selectAllHomeAlerts').addEventListener('click', function () {
                const visible = visibleAlerts().map(a => a.id);
                const allSelected = visible.length > 0 && visible.every(id => selectedIds.has(id));
                visible.forEach(id => allSelected ? selectedIds.delete(id) : selectedIds.add(id));
                renderGrid();
            });

            document.getElementById('bulkResolveHomeButton').addEventListener('click', function () {
                if (selectedIds.size === 0) return;
                syncSelection();
                const modalEl = document.getElementById('bulkResolveHomeModal');
                // O tema usa Bootstrap 4 (jQuery); getOrCreateInstance só existe no Bootstrap 5
                if (window.jQuery?.fn.modal) {
                    window.jQuery(modalEl).modal('show');
                } else if (typeof window.bootstrap?.Modal?.getOrCreateInstance === 'function') {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                } else if (window.bootstrap?.Modal) {
                    new window.bootstrap.Modal(modalEl).show();
                }
            });
        }
    });
</script>
@endsection
