@extends('layout')

@section('content')
<div class="container py-4" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-edit text-primary me-2"></i>Editar Alerta</h1>
            <p class="text-muted mb-0">Atualize as informações do incidente ou atribuições de responsabilidade.</p>
        </div>
        <a href="{{ route('alerts.show', $alert) }}" class="btn btn-outline-secondary">
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
            <form method="POST" action="{{ route('alerts.update', $alert) }}">
                @csrf
                @method('PATCH')

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipo <span class="text-danger">*</span></label>
                        <input type="text" name="type" value="{{ old('type', $alert->type) }}" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">ID Externo <span class="text-danger">*</span></label>
                        <input type="text" name="id_externo" value="{{ old('id_externo', $alert->id_externo) }}" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nome do Serviço</label>
                        <input type="text"
                               name="service_name"
                               id="service_name"
                               list="services-list"
                               value="{{ old('service_name', $alert->service_name) }}"
                               class="form-control"
                               autocomplete="off">
                        <datalist id="services-list">
                            @foreach($suggestedServices as $service)
                                <option value="{{ $service }}">
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Data da Ocorrência <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="data_ocorrencia" value="{{ old('data_ocorrencia', $alert->data_ocorrencia?->format('Y-m-d\TH:i')) }}" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status_resolucao" class="form-select">
                            <option value="pendente" {{ old('status_resolucao', $alert->status_resolucao) === 'pendente' ? 'selected' : '' }}>Pendente</option>
                            <option value="resolvido" {{ old('status_resolucao', $alert->status_resolucao) === 'resolvido' ? 'selected' : '' }}>Resolvido</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Responsável</label>
                        <select name="atribuido" class="form-select">
                            <option value="">Sem atribuição</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('atribuido', $alert->atribuido) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Mensagem do Erro <span class="text-danger">*</span></label>
                        <textarea name="mensagem" rows="6" class="form-control font-monospace" required>{{ old('mensagem', $alert->mensagem) }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Comentário da Resolução / Observações</label>
                        <textarea name="comentario" rows="3" class="form-control" placeholder="Descreva os procedimentos efetuados...">{{ old('comentario', $alert->comentario) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="{{ route('alerts.show', $alert) }}" class="btn btn-outline-secondary px-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i class="fas fa-save me-1"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
