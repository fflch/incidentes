@extends('layout')

@section('content')
<div class="container py-4" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-plus-circle text-primary me-2"></i>Cadastrar Alerta</h1>
            <p class="text-muted mb-0">Preencha os dados do incidente para registrar o alerta.</p>
        </div>
        <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('alerts.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipo <span class="text-danger">*</span></label>
                        <input type="text" name="type" value="{{ old('type') }}" class="form-control" placeholder="docker, laravel, drupal..." required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">ID Externo <span class="text-danger">*</span></label>
                        <input type="text" name="id_externo" value="{{ old('id_externo') }}" class="form-control" placeholder="Ex: container_123, job_456" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nome do Serviço</label>
                        <input type="text"
                               name="service_name"
                               id="service_name"
                               list="services-list"
                               value="{{ old('service_name') }}"
                               class="form-control"
                               placeholder="drupal-prod_init, portal-api..."
                               autocomplete="off">
                        <datalist id="services-list">
                            @foreach($suggestedServices as $service)
                                <option value="{{ $service }}">
                            @endforeach
                        </datalist>
                        <div class="form-text small">Se houver regra cadastrada, o responsável será atribuído automaticamente.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Data da Ocorrência <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="data_ocorrencia" value="{{ old('data_ocorrencia', now()->format('Y-m-d\TH:i')) }}" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status Inicial</label>
                        <select name="status_resolucao" class="form-select">
                            <option value="pendente" {{ old('status_resolucao', 'pendente') === 'pendente' ? 'selected' : '' }}>Pendente</option>
                            <option value="resolvido" {{ old('status_resolucao') === 'resolvido' ? 'selected' : '' }}>Resolvido</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Responsável</label>
                        <select name="atribuido" class="form-select">
                            <option value="">Atribuição automática ou sem responsável</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('atribuido') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Alerta relacionado</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Nenhum (alerta principal)</option>
                            @foreach($relatedAlerts as $relatedAlert)
                                <option value="{{ $relatedAlert->id }}" {{ old('parent_id') == $relatedAlert->id ? 'selected' : '' }}>
                                    #{{ $relatedAlert->id }} · {{ $relatedAlert->service_name ?? $relatedAlert->type }} · {{ $relatedAlert->status_resolucao }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small">Se este alerta for uma repetição do mesmo problema, selecione o registro pai.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Mensagem do Erro / Logs <span class="text-danger">*</span></label>
                        <textarea name="mensagem" rows="6" class="form-control font-monospace" placeholder="Cole a mensagem de erro ou logs relevantes..." required>{{ old('mensagem') }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Comentário Adicional</label>
                        <textarea name="comentario" rows="3" class="form-control" placeholder="Observações preliminares ou comentário de encerramento...">{{ old('comentario') }}</textarea>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i class="fas fa-save me-1"></i> Salvar Alerta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
