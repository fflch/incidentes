@extends('layout')

@section('content')
<div class="container py-4" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-magic text-primary me-2"></i>Nova Regra de Atribuição</h1>
            <p class="text-muted mb-0">Quando chegar um alerta com o nome ou o tipo do serviço informado, ele será atribuído ao usuário selecionado.</p>
        </div>
        <a href="{{ route('assignment-rules.index') }}" class="btn btn-outline-secondary">
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
            <form method="POST" action="{{ route('assignment-rules.store') }}">
                @csrf

                @include('assignment-rules._match-fields')

                <div class="mb-4">
                    <label for="user_id" class="form-label fw-bold">Usuário Responsável (Admin) <span class="text-danger">*</span></label>
                    <select id="user_id"
                            name="user_id"
                            class="form-select form-select-lg @error('user_id') is-invalid @enderror"
                            required>
                        <option value="">Selecione o usuário administrador...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        Apenas usuários administradores cadastrados no sistema são exibidos para atribuição de alertas.
                    </div>
                    @error('user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 p-3 bg-light rounded border">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="apply_to_pending" id="apply_to_pending" value="1" {{ old('apply_to_pending') ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="apply_to_pending">
                            Aplicar agora aos alertas pendentes existentes
                        </label>
                    </div>
                    <div class="small text-muted mt-1">
                        Se marcado, todos os alertas que casem com esta regra e estejam atualmente com status "pendente" e sem responsável serão automaticamente atribuídos a este usuário. Eles aparecerão no resumo diário de pendências enviado por e-mail.
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2">
                    <a href="{{ route('assignment-rules.index') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> Salvar Regra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

