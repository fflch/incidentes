{{-- Formulário de regra para ignorar. Espera opcionalmente $rule. --}}
<div class="mb-4">
    <label for="pattern" class="form-label fw-bold">Texto na mensagem do alerta <span class="text-danger">*</span></label>
    <input type="text"
           id="pattern"
           name="pattern"
           class="form-control form-control-lg @error('pattern') is-invalid @enderror"
           value="{{ old('pattern', $rule->pattern ?? '') }}"
           placeholder="Ex.: Certificate will expire in 30 days"
           autocomplete="off"
           required>
    <div class="form-text">
        Se esse texto aparecer em qualquer parte da mensagem de um alerta novo (sem diferenciar maiúsculas de minúsculas),
        o alerta é <strong>ignorado</strong>: fica arquivado em "Alertas Ignorados", sem responsável e sem e-mail para ninguém.
    </div>
    @error('pattern')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-4 p-3 bg-light rounded border">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="apply_to_pending" id="apply_to_pending" value="1" {{ old('apply_to_pending') ? 'checked' : '' }}>
        <label class="form-check-label fw-semibold" for="apply_to_pending">
            Ignorar também os alertas pendentes que já existem
        </label>
    </div>
    <div class="small text-muted mt-1">
        Se marcado, os alertas pendentes cuja mensagem do alerta principal contenha esse texto serão ignorados agora,
        junto com todas as suas repetições, e perdem o responsável.
    </div>
</div>
