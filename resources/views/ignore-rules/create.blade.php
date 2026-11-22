@extends('layout')

@section('content')
<div class="container py-4" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-eye-slash text-secondary me-2"></i>Nova Regra para Ignorar</h1>
            <p class="text-muted mb-0">Alertas cuja mensagem contenha o texto informado serão ignorados.</p>
        </div>
        <a href="{{ route('ignore-rules.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('ignore-rules.store') }}">
                @csrf
                @include('ignore-rules._form')

                <div class="d-flex justify-content-end gap-2 pt-2">
                    <a href="{{ route('ignore-rules.index') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> Salvar Regra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
