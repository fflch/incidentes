@extends('layout')

@section('content')
@php
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
    $tabs = [
        'pendente' => ['label' => 'Pendentes', 'icon' => 'fa-clock', 'count' => $countPendentes],
        'resolvido' => ['label' => 'Resolvidos', 'icon' => 'fa-check-circle', 'count' => $countResolvidos],
        'todos' => ['label' => 'Todos', 'icon' => 'fa-layer-group', 'count' => $countTotal],
    ];
@endphp

<div class="ma-wrap py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <!-- Cabeçalho -->
    <section class="ma-hero mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <div class="ma-hero-avatar">{{ $initials }}</div>
                <div class="min-w-0">
                    <div class="ma-eyebrow">Meus alertas</div>
                    <h1 class="ma-title mb-1">Olá, {{ \Illuminate\Support\Str::of($user->name)->explode(' ')->first() }}</h1>
                    <div class="ma-subtitle">
                        @if($countPendentes > 0)
                            Você tem <strong>{{ $countPendentes }}</strong> {{ $countPendentes === 1 ? 'incidente pendente' : 'incidentes pendentes' }} sob sua responsabilidade.
                        @else
                            Nenhum incidente pendente com você. Bom trabalho!
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('home') }}" class="btn btn-sm ma-btn-ghost"><i class="fas fa-tv me-1"></i> Painel</a>
                @can('admin')
                    <a href="{{ route('dashboard') }}" class="btn btn-sm ma-btn-ghost"><i class="fas fa-chart-line me-1"></i> Dashboard</a>
                @endcan
            </div>
        </div>

        <div class="ma-hero-stats">
            <div class="ma-hero-stat">
                <span class="ma-hero-stat-value text-warning-soft">{{ $countPendentes }}</span>
                <span class="ma-hero-stat-label">Pendentes</span>
            </div>
            <div class="ma-hero-stat">
                <span class="ma-hero-stat-value text-success-soft">{{ $countResolvidos }}</span>
                <span class="ma-hero-stat-label">Resolvidos</span>
            </div>
            <div class="ma-hero-stat">
                <span class="ma-hero-stat-value">{{ $countTotal }}</span>
                <span class="ma-hero-stat-label">Total atribuído</span>
            </div>
        </div>
    </section>

    <!-- Filtros e busca -->
    <section class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <nav class="ma-tabs" aria-label="Filtrar por status">
            @foreach($tabs as $key => $tab)
                <a href="{{ route('alerts.my', array_filter(['status' => $key, 'search' => request('search')])) }}"
                   class="ma-tab {{ $statusFilter === $key ? 'active' : '' }}">
                    <i class="fas {{ $tab['icon'] }} me-1"></i> {{ $tab['label'] }}
                    <span class="ma-tab-count">{{ $tab['count'] }}</span>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('alerts.my') }}" class="ma-search">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <i class="fas fa-search ma-search-icon"></i>
            <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                   placeholder="Buscar serviço, ID externo, mensagem…" aria-label="Buscar">
            @if(request('search'))
                <a href="{{ route('alerts.my', ['status' => $statusFilter]) }}" class="ma-search-clear" title="Limpar busca"><i class="fas fa-times"></i></a>
            @endif
        </form>
    </section>

    @if(request('search'))
        <div class="small text-muted mb-3">
            {{ $alerts->total() }} {{ $alerts->total() === 1 ? 'resultado' : 'resultados' }} para <strong>“{{ request('search') }}”</strong>
        </div>
    @endif

    <!-- Lista -->
    <section class="row g-3">
        @forelse($alerts as $alert)
            @php
                $pendente = $alert->status_resolucao === 'pendente';
                $idadeMin = $alert->data_ocorrencia ? (int) $alert->data_ocorrencia->diffInMinutes(now()) : 0;
                if (!$pendente) {
                    $sev = ['cls' => 'is-done', 'label' => 'Resolvido', 'icon' => 'fa-check'];
                } elseif ($idadeMin >= 24 * 60) {
                    $sev = ['cls' => 'is-crit', 'label' => 'Crítico', 'icon' => 'fa-fire'];
                } elseif ($idadeMin >= 60) {
                    $sev = ['cls' => 'is-warn', 'label' => 'Atenção', 'icon' => 'fa-exclamation'];
                } else {
                    $sev = ['cls' => 'is-new', 'label' => 'Recente', 'icon' => 'fa-bolt'];
                }
                $repeticoes = (int) $alert->children_count;
                $ultima = $alert->children_max_data_ocorrencia ? \Carbon\Carbon::parse($alert->children_max_data_ocorrencia) : null;
            @endphp
            <div class="col-12 col-xl-6">
                <article class="ma-card {{ $sev['cls'] }}">
                    <header class="d-flex justify-content-between align-items-start gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('alerts.show', $alert) }}" class="ma-card-title">{{ $alert->service_name ?? $alert->type }}</a>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @if($alert->type && $alert->type !== $alert->service_name)
                                    <span class="ma-chip">{{ $alert->type }}</span>
                                @endif
                                <span class="ma-chip ma-chip-muted">#{{ $alert->id }}</span>
                                @if($alert->id_externo)
                                    <span class="ma-chip ma-chip-muted text-truncate" style="max-width: 220px;" title="ID externo">
                                        <i class="fas fa-fingerprint me-1"></i>{{ $alert->id_externo }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <span class="ma-pill flex-shrink-0"><i class="fas {{ $sev['icon'] }} me-1"></i>{{ $sev['label'] }}</span>
                    </header>

                    <div class="ma-meta">
                        <span title="{{ $alert->data_ocorrencia?->format('d/m/Y H:i:s') }}">
                            <i class="far fa-clock me-1"></i>{{ $alert->data_ocorrencia?->diffForHumans() }}
                        </span>
                        <span><i class="far fa-calendar me-1"></i>{{ $alert->data_ocorrencia?->format('d/m/Y H:i') }}</span>
                        @if($repeticoes > 0)
                            <span class="ma-repeat" title="Última ocorrência {{ $ultima?->format('d/m/Y H:i') }}">
                                <i class="fas fa-redo-alt me-1"></i>{{ $repeticoes + 1 }} ocorrências
                                @if($ultima) · última {{ $ultima->diffForHumans() }} @endif
                            </span>
                        @endif
                    </div>

                    <div class="ma-log">{{ \Illuminate\Support\Str::limit($alert->mensagem, 400) }}</div>

                    @if(!$pendente)
                        <div class="ma-resolution">
                            <div class="d-flex justify-content-between flex-wrap gap-1 mb-1">
                                <span class="fw-semibold"><i class="fas fa-check-double me-1"></i> Resolvido por {{ $alert->resolvidoPor?->name ?? 'responsável' }}</span>
                                <span class="small">
                                    {{ $alert->dataResolucao()?->format('d/m/Y H:i') }}
                                    @if($tempo = $alert->tempoResolucaoFormatado())
                                        · em {{ $tempo }}
                                    @endif
                                </span>
                            </div>
                            @if($alert->comentario)
                                <div class="ma-resolution-text">{{ $alert->comentario }}</div>
                            @endif
                        </div>
                    @endif

                    <footer class="ma-card-footer">
                        <a href="{{ route('alerts.show', $alert) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye me-1"></i> Ver
                        </a>
                        @if($pendente)
                            <button type="button" class="btn btn-sm btn-success"
                                    onclick="openResolveModal({{ Js::from((string) $alert->id) }}, {{ Js::from($alert->service_name ?? $alert->type) }}, {{ Js::from((string) $alert->id_externo) }})">
                                <i class="fas fa-check me-1"></i> Resolver
                            </button>
                        @else
                            @can('admin')
                                <form method="POST" action="{{ route('alerts.reopen', $alert) }}" class="d-inline" onsubmit="return confirm('Deseja reabrir este alerta?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-undo me-1"></i> Reabrir
                                    </button>
                                </form>
                            @endcan
                        @endif
                    </footer>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="ma-empty">
                    <div class="ma-empty-icon"><i class="fas {{ request('search') ? 'fa-search' : 'fa-clipboard-check' }}"></i></div>
                    <h3 class="h5 fw-bold mb-1">
                        @if(request('search'))
                            Nada encontrado
                        @elseif($statusFilter === 'pendente')
                            Tudo em dia!
                        @else
                            Nenhum alerta por aqui
                        @endif
                    </h3>
                    <p class="text-muted mb-0">
                        @if(request('search'))
                            Nenhum alerta corresponde à busca “{{ request('search') }}”.
                        @elseif($statusFilter === 'pendente')
                            Você não possui nenhum alerta pendente atribuído no momento.
                        @elseif($statusFilter === 'resolvido')
                            Nenhum alerta resolvido atribuído a você ainda.
                        @else
                            Você não possui nenhum alerta atribuído.
                        @endif
                    </p>
                </div>
            </div>
        @endforelse
    </section>

    @if($alerts->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $alerts->links() }}
        </div>
    @endif
</div>

@include('partials.resolve-modal')

<style>
    .ma-wrap { max-width: 1400px; margin: 0 auto; padding-left: 16px; padding-right: 16px; }

    .ma-hero {
        border-radius: 18px; padding: 26px 30px 0; color: #fff; overflow: hidden;
        background: linear-gradient(135deg, #0f2a4a 0%, #163d6b 55%, #1d5a96 100%);
        box-shadow: 0 10px 30px rgba(15, 42, 74, .18);
    }
    .ma-hero-avatar {
        width: 58px; height: 58px; border-radius: 16px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; font-weight: 700; letter-spacing: .03em;
        background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.25);
    }
    .ma-eyebrow { text-transform: uppercase; letter-spacing: .12em; font-size: .72rem; opacity: .75; font-weight: 600; }
    .ma-title { font-size: 1.6rem; font-weight: 700; color: #fff; }
    .ma-subtitle { opacity: .85; }
    .ma-btn-ghost { color: #fff; border: 1px solid rgba(255,255,255,.35); background: rgba(255,255,255,.08); }
    .ma-btn-ghost:hover { color: #fff; background: rgba(255,255,255,.2); }

    .ma-hero-stats {
        display: grid; grid-template-columns: repeat(3, 1fr);
        margin: 22px -30px 0; border-top: 1px solid rgba(255,255,255,.14);
        background: rgba(0,0,0,.12);
    }
    .ma-hero-stat { padding: 14px 30px; display: flex; flex-direction: column; }
    .ma-hero-stat + .ma-hero-stat { border-left: 1px solid rgba(255,255,255,.12); }
    .ma-hero-stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .ma-hero-stat-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; opacity: .7; font-weight: 600; }
    .text-warning-soft { color: #fec84b; }
    .text-success-soft { color: #6ce9a6; }

    .ma-tabs { display: inline-flex; background: #fff; border: 1px solid #e4e7ec; border-radius: 12px; padding: 4px; gap: 2px; flex-wrap: wrap; }
    .ma-tab { padding: 6px 14px; border-radius: 9px; font-weight: 600; font-size: .875rem; color: #475467; text-decoration: none !important; display: inline-flex; align-items: center; }
    .ma-tab:hover { background: #f2f4f7; color: #101828; }
    .ma-tab.active { background: #163d6b; color: #fff; }
    .ma-tab-count { margin-left: 6px; font-size: .72rem; padding: 1px 8px; border-radius: 999px; background: #f2f4f7; color: #344054; }
    .ma-tab.active .ma-tab-count { background: rgba(255,255,255,.2); color: #fff; }

    .ma-search { position: relative; width: 340px; max-width: 100%; margin: 0; }
    .ma-search .form-control { padding-left: 34px; padding-right: 32px; border-radius: 10px; height: 38px; border-color: #d0d5dd; }
    .ma-search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #98a2b3; font-size: .85rem; }
    .ma-search-clear { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #98a2b3; }
    .ma-search-clear:hover { color: #475467; }

    .ma-card {
        --accent: #2e90fa; --accent-bg: #eff8ff; --accent-fg: #175cd3;
        position: relative; height: 100%;
        background: #fff; border-radius: 14px; border: 1px solid #edf0f4;
        box-shadow: 0 2px 8px rgba(16, 24, 40, .05);
        padding: 18px 20px 14px 24px; overflow: hidden;
        display: flex; flex-direction: column; gap: 12px;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .ma-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16, 24, 40, .09); }
    .ma-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: var(--accent); }
    .ma-card.is-new  { --accent: #2e90fa; --accent-bg: #eff8ff; --accent-fg: #175cd3; }
    .ma-card.is-warn { --accent: #f79009; --accent-bg: #fffaeb; --accent-fg: #b54708; }
    .ma-card.is-crit { --accent: #d92d20; --accent-bg: #fef3f2; --accent-fg: #b42318; }
    .ma-card.is-done { --accent: #12b76a; --accent-bg: #ecfdf3; --accent-fg: #027a48; }

    .ma-card-title { font-size: 1.1rem; font-weight: 700; color: #101828; word-break: break-word; text-decoration: none !important; }
    .ma-card-title:hover { color: #163d6b; }
    .ma-chip { font-size: .72rem; font-weight: 600; padding: 2px 8px; border-radius: 6px; background: #eef4ff; color: #3538cd; display: inline-block; }
    .ma-chip-muted { background: #f2f4f7; color: #475467; }
    .ma-pill { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; padding: 4px 10px; border-radius: 999px; background: var(--accent-bg); color: var(--accent-fg); }

    .ma-meta { font-size: .83rem; color: #667085; display: flex; flex-wrap: wrap; gap: 4px 14px; align-items: center; }
    .ma-repeat { background: #f4f3ff; color: #5925dc; font-weight: 600; font-size: .75rem; padding: 3px 9px; border-radius: 999px; }

    .ma-log {
        font-family: SFMono-Regular, Menlo, Consolas, monospace; font-size: .78rem; line-height: 1.5;
        background: #0f172a; color: #e2e8f0; border-radius: 10px; padding: 10px 12px;
        white-space: pre-wrap; word-break: break-all;
        max-height: 7.2em; overflow: hidden; position: relative;
    }
    .ma-log::after {
        content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 1.8em;
        background: linear-gradient(transparent, #0f172a);
    }

    .ma-resolution { background: #ecfdf3; border: 1px solid #abefc6; color: #05603a; border-radius: 10px; padding: 10px 12px; font-size: .85rem; }
    .ma-resolution-text { color: #101828; white-space: pre-line; }

    .ma-card-footer { margin-top: auto; padding-top: 12px; border-top: 1px dashed #eaecf0; display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }

    .ma-empty { background: #fff; border-radius: 18px; border: 1px dashed #d0d5dd; padding: 56px 24px; text-align: center; }
    .ma-empty-icon { width: 76px; height: 76px; border-radius: 50%; background: #ecfdf3; color: #039855; display: inline-flex; align-items: center; justify-content: center; font-size: 30px; margin-bottom: 14px; }

    @media (max-width: 575.98px) {
        .ma-hero { padding: 20px 18px 0; }
        .ma-hero-stats { margin: 18px -18px 0; }
        .ma-hero-stat { padding: 12px 14px; }
        .ma-title { font-size: 1.3rem; }
        .ma-search { width: 100%; }
    }
</style>
@endsection
