{{-- Erros de validação e mensagens de falha (o flash do tema está desligado em config/laravel-usp-theme.php) --}}
@if($errors->any() || session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mt-3" role="alert">
        <div class="d-flex gap-2">
            <i class="fas fa-exclamation-circle mt-1"></i>
            <div>
                @if(session('error'))
                    <div>{{ session('error') }}</div>
                @endif
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif
