@extends('layout')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Editar alerta</h1>
            <p class="text-muted mb-0">Atualize as informações do incidente ou do atendimento.</p>
        </div>
        <a href="{{ route('alerts.show', $alert) }}" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('alerts.update', $alert) }}">
                @csrf
                @method('PATCH')

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipo</label>
                        <input type="text" name="type" value="{{ old('type', $alert->type) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ID externo</label>
                        <input type="text" name="id_externo" value="{{ old('id_externo', $alert->id_externo) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nome do serviço</label>
                        <input type="text" name="service_name" value="{{ old('service_name', $alert->service_name) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data da ocorrência</label>
                        <input type="datetime-local" name="data_ocorrencia" value="{{ old('data_ocorrencia', $alert->data_ocorrencia?->format('Y-m-d\TH:i')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status_resolucao" class="form-select">
                            <option value="pendente" {{ old('status_resolucao', $alert->status_resolucao) === 'pendente' ? 'selected' : '' }}>Pendente</option>
                            <option value="resolvido" {{ old('status_resolucao', $alert->status_resolucao) === 'resolvido' ? 'selected' : '' }}>Resolvido</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Responsável</label>
                        <select name="atribuido" class="form-select">
                            <option value="">Sem atribuição</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('atribuido', $alert->atribuido) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mensagem</label>
                        <textarea name="mensagem" rows="8" class="form-control" required>{{ old('mensagem', $alert->mensagem) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Comentário</label>
                        <textarea name="comentario" rows="4" class="form-control">{{ old('comentario', $alert->comentario) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                    <a href="{{ route('alerts.show', $alert) }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection