@extends('layout')

@section('content')
<div class="container py-4" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-edit text-primary me-2"></i>Editar Regra de Atribuição</h1>
            <p class="text-muted mb-0">Atualize o serviço ou o responsável associado.</p>
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
            <form method="POST" action="{{ route('assignment-rules.update', $rule) }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="service_name" class="form-label fw-bold">Nome do Serviço <span class="text-danger">*</span></label>
                    <input type="text"
                           id="service_name"
                           name="service_name"
                           list="services-list"
                           class="form-control form-control-lg @error('service_name') is-invalid @enderror"
                           value="{{ old('service_name', $rule->service_name) }}"
                           placeholder="Digite ou selecione o serviço"
                           autocomplete="off"
                           required>
                    <datalist id="services-list">
                        @foreach($suggestedServices as $service)
                            <option value="{{ $service }}">
                        @endforeach
                    </datalist>
                    @error('service_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="user_id" class="form-label fw-bold">Usuário Responsável (Admin) <span class="text-danger">*</span></label>
                    <select id="user_id"
                            name="user_id"
                            class="form-select form-select-lg @error('user_id') is-invalid @enderror"
                            required>
                        <option value="">Selecione o usuário administrador...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id', $rule->user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2">
                    <a href="{{ route('assignment-rules.index') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> Atualizar Regra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

