@extends('layout')

@section('content')
@php
    $rootAlert = $diag['root'];
    $isPending = $rootAlert->status_resolucao === 'pendente';
    $sigIndex = $diag['signatures']->pluck('signature')->flip();
    $sigLetter = fn ($sig) => chr(65 + min(25, (int) ($sigIndex[$sig] ?? 0)));
    $human = [\App\Services\AlertDiagnosticsService::class, 'humanMinutes'];
    $trendDiff = $diag['last_24h'] - $diag['prev_24h'];
@endphp

<div class="container-fluid py-4 ad-wrap">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @if(!$alert->isRoot())
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2">
            <i class="fas fa-layer-group"></i>
            <div>
                Você está vendo a ocorrência <strong>#{{ $alert->id }}</strong>, que é uma repetição do alerta principal
                <a href="{{ route('alerts.show', $rootAlert) }}" class="fw-bold">#{{ $rootAlert->id }}</a>.
                O panorama abaixo considera todo o grupo; esta ocorrência está destacada na tabela.
            </div>
        </div>
    @endif

    <!-- Cabeçalho -->
    <section class="ad-hero {{ $isPending ? 'is-pending' : 'is-resolved' }} mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <span class="ad-status">
                        <i class="fas {{ $isPending ? 'fa-exclamation-triangle' : 'fa-check' }} me-1"></i>
                        {{ $isPending ? 'Pendente' : 'Resolvido' }}
                    </span>
                    <span class="ad-chip">{{ $alert->type }}</span>
                    <span class="ad-chip">Alerta #{{ $rootAlert->id }}</span>
                    @if($tempo = $rootAlert->tempoResolucaoFormatado())
                        <span class="ad-chip"><i class="fas fa-stopwatch me-1"></i> Resolvido em {{ $tempo }}</span>
                    @endif
                </div>
                <h1 class="ad-title">{{ $alert->service_name ?? $alert->type }}</h1>
                <div class="ad-sub">
                    @if($rootAlert->atribuidoUser)
                        <i class="fas fa-user me-1"></i> Responsável: <strong>{{ $rootAlert->atribuidoUser->name }}</strong>
                    @else
                        <i class="fas fa-user-slash me-1"></i> <strong>Sem responsável</strong>
                    @endif
                    <span class="mx-2 opacity-50">·</span>
                    {{ $diag['total'] }} {{ $diag['total'] === 1 ? 'ocorrência' : 'ocorrências' }}
                    @if($diag['first'])
                        desde {{ $diag['first']->format('d/m/Y H:i') }}
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('alerts.index') }}" class="btn btn-sm ad-btn-ghost">
                    <i class="fas fa-arrow-left me-1"></i> Voltar
                </a>
                @can('admin')
                    <a href="{{ route('alerts.edit', $alert) }}" class="btn btn-sm btn-light fw-semibold">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                @endcan
            </div>
        </div>
    </section>

    <!-- Indicadores -->
    <section class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ad-kpi">
                <div class="ad-kpi-label">Ocorrências</div>
                <div class="ad-kpi-value">{{ $diag['total'] }}</div>
                <div class="ad-kpi-foot">{{ max(0, $diag['total'] - 1) }} repetições agrupadas</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ad-kpi">
                <div class="ad-kpi-label">Primeira</div>
                <div class="ad-kpi-value ad-kpi-sm">{{ $diag['first']?->format('d/m H:i') ?? '—' }}</div>
                <div class="ad-kpi-foot">{{ $diag['first']?->diffForHumans() }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ad-kpi">
                <div class="ad-kpi-label">Última</div>
                <div class="ad-kpi-value ad-kpi-sm">{{ $diag['last']?->format('d/m H:i') ?? '—' }}</div>
                <div class="ad-kpi-foot">{{ $diag['last']?->diffForHumans() }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ad-kpi">
                <div class="ad-kpi-label">Intervalo típico</div>
                <div class="ad-kpi-value ad-kpi-sm">{{ $human($diag['median_interval']) }}</div>
                <div class="ad-kpi-foot">média {{ $human($diag['avg_interval']) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ad-kpi">
                <div class="ad-kpi-label">Últimas 24h</div>
                <div class="ad-kpi-value">{{ $diag['last_24h'] }}</div>
                <div class="ad-kpi-foot">
                    @if($trendDiff > 0)
                        <span class="text-danger"><i class="fas fa-arrow-up"></i> +{{ $trendDiff }}</span>
                    @elseif($trendDiff < 0)
                        <span class="text-success"><i class="fas fa-arrow-down"></i> {{ $trendDiff }}</span>
                    @else
                        <span>=</span>
                    @endif
                    vs. 24h anteriores
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ad-kpi">
                @if($isPending)
                    <div class="ad-kpi-label">Em aberto há</div>
                    <div class="ad-kpi-value ad-kpi-sm">{{ $human($diag['open_minutes']) }}</div>
                    <div class="ad-kpi-foot">{{ $diag['last_1h'] }} na última hora</div>
                @else
                    <div class="ad-kpi-label">Resolvido</div>
                    <div class="ad-kpi-value ad-kpi-sm">{{ $rootAlert->dataResolucao()?->format('d/m H:i') ?? '—' }}</div>
                    <div class="ad-kpi-foot">por {{ $rootAlert->resolvidoPor?->name ?? 'Sistema' }}</div>
                @endif
            </div>
        </div>
    </section>

    <div class="row g-4">
        <!-- Coluna principal: panorama -->
        <div class="col-xl-8">
            <!-- Diagnóstico automático -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="fas fa-stethoscope text-primary me-2"></i>Diagnóstico</h5>
                </div>
                <div class="card-body p-4">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        @foreach($diag['insights'] as $insight)
                            <li class="ad-insight tone-{{ $insight['tone'] }}">
                                <i class="fas {{ $insight['icon'] }}"></i>
                                <span>{{ $insight['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Ocorrências por dia -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0 fw-bold"><i class="fas fa-chart-bar text-primary me-2"></i>Ocorrências por dia</h5>
                    <small class="text-muted">
                        {{ $diag['daily']['truncated'] ? 'Últimos ' . \App\Services\AlertDiagnosticsService::DAILY_CHART_MAX_DAYS . ' dias' : 'Desde a primeira ocorrência' }}
                    </small>
                </div>
                <div class="card-body p-4">
                    @php($dayCount = count($diag['daily']['days']))
                    @php($labelEvery = max(1, (int) ceil($dayCount / 15)))
                    <div class="ad-bars" role="img" aria-label="Gráfico de ocorrências por dia">
                        @foreach($diag['daily']['days'] as $i => $day)
                            @php($h = $diag['daily']['max'] > 0 ? max($day['count'] > 0 ? 4 : 0, round(100 * $day['count'] / $diag['daily']['max'])) : 0)
                            <div class="ad-bar-col {{ $day['is_weekend'] ? 'is-weekend' : '' }}"
                                 title="{{ $day['weekday'] }} {{ $day['date']->format('d/m/Y') }}: {{ $day['count'] }} ocorrência(s)">
                                <div class="ad-bar-value">{{ $day['count'] ?: '' }}</div>
                                <div class="ad-bar-track">
                                    <div class="ad-bar" style="height: {{ $h }}%"></div>
                                </div>
                                <div class="ad-bar-label">{{ ($i % $labelEvery === 0 || $i === $dayCount - 1) ? $day['label'] : '' }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="small text-muted mt-2"><span class="ad-legend-weekend"></span> fim de semana</div>
                </div>
            </div>

            <!-- Mapa de calor -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0 fw-bold"><i class="fas fa-th text-primary me-2"></i>Horários das ocorrências</h5>
                    <small class="text-muted">dia da semana × hora · todas as ocorrências</small>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="ad-heat">
                            <thead>
                                <tr>
                                    <th></th>
                                    @for($h = 0; $h < 24; $h++)
                                        <th>{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</th>
                                    @endfor
                                    <th class="ad-heat-total">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($diag['weekdays'] as $d => $label)
                                    <tr>
                                        <th class="text-start">{{ $label }}</th>
                                        @foreach($diag['heatmap']['grid'][$d] as $h => $c)
                                            @php($alpha = $diag['heatmap']['max'] > 0 && $c > 0 ? 0.15 + 0.85 * $c / $diag['heatmap']['max'] : 0)
                                            <td title="{{ $label }} {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}h: {{ $c }} ocorrência(s)"
                                                style="{{ $c > 0 ? "background: rgba(217, 45, 32, {$alpha});" : '' }} {{ $alpha > 0.55 ? 'color: #fff;' : '' }}">
                                                {{ $c ?: '' }}
                                            </td>
                                        @endforeach
                                        <td class="ad-heat-total">{{ $diag['heatmap']['day_totals'][$d] }}</td>
                                    </tr>
                                @endforeach
                                <tr class="ad-heat-hours">
                                    <th class="text-start">Hora</th>
                                    @foreach($diag['heatmap']['hour_totals'] as $h => $c)
                                        <td title="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}h: {{ $c }} ocorrência(s)">
                                            <div class="ad-mini-track">
                                                <div class="ad-mini-bar" style="height: {{ $diag['heatmap']['max_hour_total'] > 0 ? round(100 * $c / $diag['heatmap']['max_hour_total']) : 0 }}%"></div>
                                            </div>
                                        </td>
                                    @endforeach
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna lateral: ações, dados e conversa -->
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-3"><i class="fas fa-bolt text-warning me-1"></i> Ações</h5>

                    @if((int) $rootAlert->atribuido !== (int) auth()->id())
                        <form method="POST" action="{{ route('alerts.assign-me', $alert) }}" class="mb-2"
                              @if($rootAlert->atribuido) onsubmit="return confirm({{ Js::from('Este alerta está atribuído a ' . ($rootAlert->atribuidoUser?->name ?? 'outra pessoa') . '. Deseja assumi-lo?') }});" @endif>
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm">
                                <i class="fas fa-hand-paper me-1"></i> Atribuir a mim
                            </button>
                        </form>
                    @else
                        <div class="alert alert-primary py-2 small mb-2">
                            <i class="fas fa-user-check me-1"></i> Este alerta está atribuído a você.
                        </div>
                    @endif

                    @if($isPending)
                        <button type="button"
                                class="btn btn-success w-100 py-2 mb-2 shadow-sm"
                                onclick="openResolveModal('{{ $alert->id }}', '{{ addslashes($alert->service_name ?? $alert->type) }}', '{{ addslashes($alert->id_externo) }}')">
                            <i class="fas fa-check me-1"></i> Marcar como resolvido
                        </button>
                    @else
                        @can('admin')
                            <form method="POST" action="{{ route('alerts.reopen', $alert) }}" class="mb-2" onsubmit="return confirm('Deseja reabrir este alerta?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning w-100 py-2">
                                    <i class="fas fa-undo me-1"></i> Reabrir alerta
                                </button>
                            </form>
                        @endcan
                    @endif

                    @can('admin')
                        <div class="pt-3 mt-2 border-top">
                            <label class="form-label fw-bold text-muted small"><i class="fas fa-user-tag me-1"></i> Atribuir / reatribuir responsável</label>
                            <form method="POST" action="{{ route('alerts.assign-user', $rootAlert) }}" class="d-flex gap-2">
                                @csrf
                                <select name="atribuido" class="form-select form-select-sm">
                                    <option value="">Sem atribuição</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $rootAlert->atribuido == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap"><i class="fas fa-save"></i></button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-3"><i class="fas fa-info-circle text-secondary me-1"></i> Dados {{ $alert->isRoot() ? 'do alerta' : 'desta ocorrência' }}</h5>
                    <dl class="row mb-0 small gy-2">
                        <dt class="col-5 text-muted fw-normal">ID externo</dt>
                        <dd class="col-7 fw-semibold text-break mb-0">{{ $alert->id_externo }}</dd>

                        <dt class="col-5 text-muted fw-normal">Serviço</dt>
                        <dd class="col-7 fw-semibold mb-0">{{ $alert->service_name ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Tipo</dt>
                        <dd class="col-7 mb-0">{{ $alert->type }}</dd>

                        <dt class="col-5 text-muted fw-normal">Ocorrência</dt>
                        <dd class="col-7 mb-0">{{ $alert->data_ocorrencia?->format('d/m/Y H:i:s') }}</dd>

                        <dt class="col-5 text-muted fw-normal">Responsável</dt>
                        <dd class="col-7 mb-0">{{ $rootAlert->atribuidoUser?->name ?? 'Não atribuído' }}</dd>

                        @unless($isPending)
                            <dt class="col-5 text-muted fw-normal">Resolvido em</dt>
                            <dd class="col-7 mb-0 text-success fw-semibold">{{ $rootAlert->dataResolucao()?->format('d/m/Y H:i') ?? '—' }}</dd>
                            <dt class="col-5 text-muted fw-normal">Resolvido por</dt>
                            <dd class="col-7 mb-0">{{ $rootAlert->resolvidoPor?->name ?? 'Sistema' }}</dd>
                        @endunless
                    </dl>

                    @if($rootAlert->comentario)
                        <div class="mt-3 p-3 rounded bg-success-subtle border border-success-subtle small">
                            <div class="fw-bold text-success mb-1"><i class="fas fa-clipboard-check me-1"></i> Comentário da resolução</div>
                            <div class="text-dark" style="white-space: pre-wrap;">{{ $rootAlert->comentario }}</div>
                            <div class="text-muted mt-1">por {{ $rootAlert->resolvidoPor?->name ?? 'Responsável' }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Comentários -->
            <div class="card border-0 shadow-sm mb-4" id="comentarios">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold"><i class="fas fa-comments text-primary me-2"></i>Comentários</h5>
                    <span class="badge rounded-pill bg-primary-subtle text-primary">{{ $comments->count() }}</span>
                </div>
                <div class="card-body p-4">
                    <div class="ad-comments">
                        @forelse($comments as $comment)
                            <div class="d-flex gap-2 mb-3">
                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 34px; height: 34px;">
                                    {{ mb_strtoupper(mb_substr($comment->user?->name ?? '?', 0, 1)) }}
                                </div>
                                <div class="flex-grow-1 bg-light border rounded-3 px-3 py-2 min-w-0">
                                    <div class="mb-1">
                                        <span class="fw-bold text-primary">{{ $comment->user?->name ?? 'Usuário removido' }}</span>
                                        @if($comment->user_id && (int) $comment->user_id === (int) $rootAlert->atribuido)
                                            <span class="badge bg-primary-subtle text-primary ms-1">responsável</span>
                                        @endif
                                        <span class="text-muted small ms-1">comentou</span>
                                        <div class="text-muted small" title="{{ $comment->created_at?->format('d/m/Y H:i:s') }}">
                                            {{ $comment->created_at?->format('d/m/Y H:i') }} · {{ $comment->created_at?->diffForHumans() }}
                                        </div>
                                    </div>
                                    <div class="text-dark" style="white-space: pre-wrap;">{{ $comment->comentario }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted fst-italic small mb-3">Nenhum comentário ainda. Registre o andamento aqui sem resolver o alerta.</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('alerts.comments.store', $alert) }}" class="pt-3 border-top">
                        @csrf
                        <textarea name="comentario" id="novoComentario" rows="3" class="form-control @error('comentario') is-invalid @enderror"
                                  placeholder="Ex.: investigando, aguardando fornecedor, causa provável..." required>{{ old('comentario') }}</textarea>
                        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                            <small class="text-muted">
                                @if($rootAlert->atribuidoUser && (int) $rootAlert->atribuido !== (int) auth()->id())
                                    <i class="fas fa-envelope me-1"></i> {{ $rootAlert->atribuidoUser->name }} será avisado(a).
                                @endif
                            </small>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-paper-plane me-1"></i> Comentar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico detalhado -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white pt-3 pb-0 border-bottom-0">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-log" type="button" role="tab">
                        <i class="fas fa-stream me-1"></i> Mensagens em sequência <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $diag['total'] }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-dias" type="button" role="tab">
                        <i class="fas fa-calendar-alt me-1"></i> Por dia <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $diag['by_day']->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ocorrencias" type="button" role="tab">
                        <i class="fas fa-list-ol me-1"></i> Todas as ocorrências <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $diag['total'] }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-variacoes" type="button" role="tab">
                        <i class="fas fa-code-branch me-1"></i> Variações da mensagem <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $diag['signatures']->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-mensagem" type="button" role="tab">
                        <i class="fas fa-terminal me-1"></i> Mensagem completa
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content">
                <!-- Mensagens em sequência (log) -->
                <div class="tab-pane fade show active" id="tab-log" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-4 py-3 border-bottom bg-light">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="logToggleOrder">
                                <i class="fas fa-sort-amount-down me-1"></i> <span>Mais recentes primeiro</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="logToggleWrap" title="Mostrar mensagens inteiras ou compactas">
                                <i class="fas fa-compress-alt me-1"></i> <span>Compactar</span>
                            </button>
                            @if($diag['signatures']->count() > 1)
                                <select class="form-select form-select-sm" id="logFilterSig" style="width: auto;">
                                    <option value="">Todas as variações</option>
                                    @foreach($diag['signatures'] as $i => $sig)
                                        <option value="{{ $i }}">Variação {{ $sigLetter($sig['signature']) }} ({{ $sig['count'] }})</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="input-group input-group-sm" style="max-width: 320px;">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="search" class="form-control" id="logSearch" placeholder="Buscar no texto das mensagens">
                        </div>
                    </div>
                    @if($diag['rows_truncated'])
                        <div class="small text-muted px-4 pt-2">Exibindo as {{ \App\Services\AlertDiagnosticsService::TABLE_LIMIT }} ocorrências mais recentes de {{ $diag['total'] }}.</div>
                    @endif
                    <div class="ad-log" id="adLog">
                        @foreach($diag['rows'] as $row)
                            <div class="ad-log-row {{ $row['is_current'] ? 'is-current' : '' }}" data-sig="{{ $sigIndex[$row['signature']] }}">
                                <div class="ad-log-when">
                                    <a href="{{ route('alerts.show', $row['id']) }}" class="ad-log-time" title="Abrir ocorrência #{{ $row['id'] }}">{{ $row['date']->format('d/m/Y H:i:s') }}</a>
                                    <div class="ad-log-meta">
                                        {{ $row['weekday'] }}
                                        @if($row['since_previous'] !== null)
                                            · +{{ $human($row['since_previous']) }}
                                        @endif
                                    </div>
                                    <div class="d-flex gap-1 align-items-center mt-1 flex-wrap">
                                        <span class="ad-sig sig-{{ $sigIndex[$row['signature']] % 6 }}" title="Variação da mensagem">{{ $sigLetter($row['signature']) }}</span>
                                        @if($row['is_root'])
                                            <span class="badge bg-dark-subtle text-dark">principal</span>
                                        @endif
                                        @if($row['is_current'])
                                            <span class="badge bg-primary text-white">esta</span>
                                        @endif
                                    </div>
                                    <div class="ad-log-meta text-break">{{ $row['id_externo'] }}</div>
                                </div>
                                <pre class="ad-log-msg">{{ $row['message'] }}</pre>
                            </div>
                        @endforeach
                        <div class="text-center text-muted py-4 d-none" id="logEmpty">Nenhuma mensagem corresponde ao filtro.</div>
                    </div>
                </div>

                <!-- Por dia -->
                <div class="tab-pane fade" id="tab-dias" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 ad-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Dia</th>
                                    <th class="text-center">Qtd.</th>
                                    <th>Primeira</th>
                                    <th>Última</th>
                                    <th>Horários</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($diag['by_day'] as $day)
                                    <tr>
                                        <td class="text-nowrap">
                                            <strong>{{ $day['date']->format('d/m/Y') }}</strong>
                                            <span class="text-muted small ms-1">{{ $day['weekday'] }}</span>
                                        </td>
                                        <td class="text-center"><span class="ad-count">{{ $day['count'] }}</span></td>
                                        <td class="text-nowrap">{{ $day['first']->format('H:i') }}</td>
                                        <td class="text-nowrap">{{ $day['last']->format('H:i') }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($day['times']->take(40) as $t)
                                                    <a href="{{ route('alerts.show', $t['id']) }}" class="ad-time {{ $t['id'] === $alert->id ? 'is-current' : '' }}" title="Ocorrência #{{ $t['id'] }}">{{ $t['time'] }}</a>
                                                @endforeach
                                                @if($day['times']->count() > 40)
                                                    <span class="text-muted small align-self-center">+{{ $day['times']->count() - 40 }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Todas as ocorrências -->
                <div class="tab-pane fade" id="tab-ocorrencias" role="tabpanel">
                    @if($diag['rows_truncated'])
                        <div class="small text-muted px-4 pt-3">Exibindo as {{ \App\Services\AlertDiagnosticsService::TABLE_LIMIT }} ocorrências mais recentes de {{ $diag['total'] }}.</div>
                    @endif
                    <div class="table-responsive" style="max-height: 560px;">
                        <table class="table table-hover align-middle mb-0 ad-table">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-end">#</th>
                                    <th>Data / hora</th>
                                    <th>Desde a anterior</th>
                                    <th>ID externo</th>
                                    <th>Variação</th>
                                    <th>Mensagem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($diag['rows'] as $row)
                                    <tr class="{{ $row['is_current'] ? 'table-primary' : '' }}">
                                        <td class="text-end text-muted small">{{ $row['n'] }}</td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('alerts.show', $row['id']) }}" class="fw-semibold text-decoration-none">{{ $row['date']->format('d/m/Y H:i:s') }}</a>
                                            <span class="text-muted small ms-1">{{ $row['weekday'] }}</span>
                                            @if($row['is_root'])
                                                <span class="badge bg-dark-subtle text-dark ms-1">principal</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap small">{{ $row['since_previous'] === null ? '—' : $human($row['since_previous']) }}</td>
                                        <td class="small text-break">{{ $row['id_externo'] }}</td>
                                        <td><span class="ad-sig sig-{{ $sigIndex[$row['signature']] % 6 }}">{{ $sigLetter($row['signature']) }}</span></td>
                                        <td class="small font-monospace text-muted ad-msg" title="{{ Str::limit($row['message'], 500) }}">{{ Str::limit($row['message'], 90) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Variações -->
                <div class="tab-pane fade" id="tab-variacoes" role="tabpanel">
                    <div class="p-4">
                        <p class="small text-muted">
                            Mensagens que diferem apenas em números, IPs, IDs ou hashes são tratadas como a mesma variação.
                        </p>
                        @foreach($diag['signatures'] as $i => $sig)
                            <div class="border rounded-3 p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="ad-sig sig-{{ $i % 6 }}">{{ $sigLetter($sig['signature']) }}</span>
                                        <strong>{{ $sig['count'] }} {{ $sig['count'] === 1 ? 'ocorrência' : 'ocorrências' }}</strong>
                                        <span class="text-muted small">({{ round(100 * $sig['count'] / max(1, $diag['total'])) }}%)</span>
                                    </div>
                                    <span class="small text-muted">
                                        {{ $sig['first']->format('d/m/Y H:i') }} → {{ $sig['last']->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                <pre class="mb-0 p-3 bg-dark text-light rounded font-monospace small" style="white-space: pre-wrap; word-break: break-all; max-height: 200px; overflow-y: auto;">{{ $sig['example'] }}</pre>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Mensagem completa -->
                <div class="tab-pane fade" id="tab-mensagem" role="tabpanel">
                    <div class="p-4">
                        <div class="small text-muted mb-2">Ocorrência #{{ $alert->id }} · {{ $alert->data_ocorrencia?->format('d/m/Y H:i:s') }}</div>
                        <pre class="mb-0 p-3 bg-dark text-light rounded font-monospace" style="white-space: pre-wrap; word-break: break-all; max-height: 500px; overflow-y: auto;">{{ $alert->mensagem }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('admin')
        <div class="text-end">
            <form method="POST" action="{{ route('alerts.destroy', $alert) }}" class="d-inline" onsubmit="return confirm('Deseja realmente excluir este alerta do sistema?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash-alt me-1"></i> Excluir {{ $alert->isRoot() ? 'alerta' : 'esta ocorrência' }}
                </button>
            </form>
        </div>
    @endcan
</div>

<style>
    .min-w-0 { min-width: 0; }
    .ad-wrap { max-width: 1600px; margin: 0 auto; }

    .ad-hero { border-radius: 18px; padding: 26px 30px; color: #fff; box-shadow: 0 10px 30px rgba(15, 42, 74, .16); }
    .ad-hero.is-pending  { background: linear-gradient(135deg, #3b0d14 0%, #6b1622 55%, #a3283a 100%); }
    .ad-hero.is-resolved { background: linear-gradient(135deg, #0b3b2a 0%, #11613f 55%, #198754 100%); }
    .ad-title { font-size: 1.9rem; font-weight: 700; color: #fff; margin: 0 0 4px; word-break: break-word; }
    .ad-sub { opacity: .88; }
    .ad-status { background: rgba(255,255,255,.95); color: #101828; font-weight: 700; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; padding: 5px 12px; border-radius: 999px; }
    .is-pending .ad-status { color: #b42318; }
    .is-resolved .ad-status { color: #067647; }
    .ad-chip { background: rgba(255,255,255,.15); font-size: .78rem; padding: 4px 10px; border-radius: 999px; }
    .ad-btn-ghost { color: #fff; border: 1px solid rgba(255,255,255,.35); background: rgba(255,255,255,.08); }
    .ad-btn-ghost:hover { color: #fff; background: rgba(255,255,255,.2); }

    .ad-kpi { background: #fff; border: 1px solid #edf0f4; border-radius: 14px; padding: 16px 18px; height: 100%; box-shadow: 0 2px 8px rgba(16, 24, 40, .04); }
    .ad-kpi-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #667085; font-weight: 700; }
    .ad-kpi-value { font-size: 1.8rem; font-weight: 700; color: #101828; line-height: 1.2; font-variant-numeric: tabular-nums; }
    .ad-kpi-sm { font-size: 1.2rem; padding: 5px 0 3px; }
    .ad-kpi-foot { font-size: .78rem; color: #667085; }

    .ad-insight { display: flex; gap: 12px; align-items: flex-start; padding: 10px 14px; border-radius: 10px; background: #f8f9fb; border-left: 4px solid #98a2b3; }
    .ad-insight i { margin-top: 3px; width: 16px; text-align: center; }
    .ad-insight.tone-danger  { background: #fef3f2; border-color: #d92d20; } .ad-insight.tone-danger i  { color: #d92d20; }
    .ad-insight.tone-warning { background: #fffaeb; border-color: #f79009; } .ad-insight.tone-warning i { color: #dc6803; }
    .ad-insight.tone-success { background: #ecfdf3; border-color: #12b76a; } .ad-insight.tone-success i { color: #039855; }
    .ad-insight.tone-primary { background: #eff8ff; border-color: #2e90fa; } .ad-insight.tone-primary i { color: #1570ef; }

    .ad-bars { display: flex; align-items: flex-end; gap: 3px; height: 190px; }
    .ad-bar-col { flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; align-items: center; height: 100%; border-radius: 4px; }
    .ad-bar-col.is-weekend { background: #f8f9fb; }
    .ad-bar-value { font-size: .68rem; color: #475467; height: 16px; font-variant-numeric: tabular-nums; }
    .ad-bar-track { flex: 1; width: 100%; display: flex; align-items: flex-end; justify-content: center; }
    .ad-bar { width: 72%; max-width: 28px; background: #d92d20; border-radius: 4px 4px 0 0; transition: opacity .15s; }
    .ad-bar-col:hover .ad-bar { opacity: .75; }
    .ad-bar-label { font-size: .66rem; color: #667085; height: 18px; padding-top: 4px; white-space: nowrap; }
    .ad-legend-weekend { display: inline-block; width: 12px; height: 12px; background: #eef0f4; border-radius: 3px; vertical-align: middle; margin-right: 4px; }

    .ad-heat { border-collapse: separate; border-spacing: 3px; width: 100%; font-size: .72rem; }
    .ad-heat th { color: #667085; font-weight: 600; text-align: center; padding: 0 2px; }
    .ad-heat td { background: #f2f4f7; height: 30px; min-width: 26px; text-align: center; border-radius: 4px; font-weight: 600; color: #344054; font-variant-numeric: tabular-nums; }
    .ad-heat .ad-heat-total { background: transparent; color: #101828; font-weight: 700; padding-left: 8px; }
    .ad-heat-hours td { background: transparent; height: 34px; vertical-align: bottom; }
    .ad-mini-track { height: 30px; display: flex; align-items: flex-end; justify-content: center; }
    .ad-mini-bar { width: 70%; background: #98a2b3; border-radius: 2px 2px 0 0; }

    .ad-comments { max-height: 420px; overflow-y: auto; }

    .ad-table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; color: #667085; }
    .ad-table td:first-child, .ad-table th:first-child { padding-left: 1.5rem; }
    .ad-count { display: inline-block; min-width: 32px; padding: 2px 8px; border-radius: 999px; background: #fef3f2; color: #b42318; font-weight: 700; }
    .ad-time { font-size: .75rem; font-variant-numeric: tabular-nums; padding: 2px 7px; border-radius: 6px; background: #f2f4f7; color: #344054; text-decoration: none; }
    .ad-time:hover { background: #e4e7ec; color: #101828; }
    .ad-time.is-current { background: #1570ef; color: #fff; }
    .ad-msg { max-width: 420px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .ad-log { max-height: 720px; overflow-y: auto; }
    .ad-log-row { display: grid; grid-template-columns: 190px minmax(0, 1fr); gap: 16px; padding: 12px 24px; border-bottom: 1px solid #eef0f3; }
    .ad-log-row:nth-child(even) { background: #fafbfc; }
    .ad-log-row.is-current { background: #eff8ff; box-shadow: inset 4px 0 0 #1570ef; }
    .ad-log-time { font-weight: 700; color: #101828; font-variant-numeric: tabular-nums; text-decoration: none; }
    .ad-log-time:hover { color: #1570ef; }
    .ad-log-meta { font-size: .75rem; color: #667085; }
    .ad-log-msg { align-self: start; margin: 0; padding: 10px 12px; background: #101828; color: #e4e7ec; border-radius: 8px; font-size: .8rem; white-space: pre-wrap; word-break: break-word; max-height: 260px; overflow-y: auto; }
    .ad-log.is-compact .ad-log-msg { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-height: none; }
    @media (max-width: 768px) { .ad-log-row { grid-template-columns: 1fr; gap: 6px; padding: 12px 16px; } }

    .ad-sig { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 6px; font-weight: 700; font-size: .75rem; }
    .sig-0 { background: #eff8ff; color: #175cd3; }
    .sig-1 { background: #fdf2fa; color: #c11574; }
    .sig-2 { background: #fffaeb; color: #b54708; }
    .sig-3 { background: #ecfdf3; color: #067647; }
    .sig-4 { background: #f4f3ff; color: #5925dc; }
    .sig-5 { background: #f2f4f7; color: #344054; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const log = document.getElementById('adLog');
        if (!log) return;

        const rows = () => Array.from(log.querySelectorAll('.ad-log-row'));
        const empty = document.getElementById('logEmpty');
        const search = document.getElementById('logSearch');
        const sigFilter = document.getElementById('logFilterSig');

        function applyFilters() {
            const term = (search.value || '').trim().toLowerCase();
            const sig = sigFilter ? sigFilter.value : '';
            let visible = 0;
            rows().forEach(row => {
                const okSig = !sig || row.dataset.sig === sig;
                const okTerm = !term || row.textContent.toLowerCase().includes(term);
                row.classList.toggle('d-none', !(okSig && okTerm));
                if (okSig && okTerm) visible++;
            });
            empty.classList.toggle('d-none', visible > 0);
        }

        search.addEventListener('input', applyFilters);
        if (sigFilter) sigFilter.addEventListener('change', applyFilters);

        let newestFirst = true;
        document.getElementById('logToggleOrder').addEventListener('click', function () {
            newestFirst = !newestFirst;
            rows().reverse().forEach(row => log.insertBefore(row, empty));
            this.querySelector('span').textContent = newestFirst ? 'Mais recentes primeiro' : 'Mais antigas primeiro';
            this.querySelector('svg, i')?.classList.toggle('fa-flip-vertical');
        });

        document.getElementById('logToggleWrap').addEventListener('click', function () {
            const compact = log.classList.toggle('is-compact');
            this.querySelector('span').textContent = compact ? 'Expandir' : 'Compactar';
        });
    });
</script>

@include('partials.resolve-modal')
@endsection
