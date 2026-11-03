@extends('layout')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Cadastrar alerta</h1>
            <p class="text-muted mb-0">Preencha os dados do incidente para registrar o alerta.</p>
        </div>
        <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('alerts.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipo</label>
                        <input type="text" name="type" value="{{ old('type') }}" class="form-control" placeholder="docker, laravel, drupal, ..." required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ID externo</label>
                        <input type="text" name="id_externo" value="{{ old('id_externo') }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nome do serviço</label>
                        <input type="text" name="service_name" value="{{ old('service_name') }}" class="form-control" placeholder="drupal-prod_init, app-api ...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data da ocorrência</label>
                        <input type="datetime-local" name="data_ocorrencia" value="{{ old('data_ocorrencia') }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status_resolucao" class="form-select">
                            <option value="pendente" {{ old('status_resolucao', 'pendente') === 'pendente' ? 'selected' : '' }}>Pendente</option>
                            <option value="resolvido" {{ old('status_resolucao') === 'resolvido' ? 'selected' : '' }}>Resolvido</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Responsável</label>
                        <select name="atribuido" class="form-select">
                            <option value="">Sem atribuição</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('atribuido') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mensagem</label>
                        <textarea name="mensagem" rows="8" class="form-control" required>{{ old('mensagem') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Comentário</label>
                        <textarea name="comentario" rows="4" class="form-control">{{ old('comentario') }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Salvar alerta</button>
                    <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection