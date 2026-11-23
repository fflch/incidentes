{{-- Formulário de regra para ignorar. Espera opcionalmente $rule. --}}
<div class="mb-4">
    <label for="pattern" class="form-label fw-bold">Texto na mensagem do alerta <span class="text-danger">*</span></label>
    <textarea id="pattern"
              name="pattern"
              rows="8"
              maxlength="20000"
              class="form-control font-monospace @error('pattern') is-invalid @enderror"
              placeholder="Ex.: Certificate will expire in 30 days"
              required>{{ old('pattern', $rule->pattern ?? '') }}</textarea>
    <div class="form-text">
        Pode ser um trecho grande, com várias linhas, copiado da mensagem do alerta. Diferenças de maiúsculas/minúsculas,
        espaços e quebras de linha são desconsideradas. Se esse texto aparecer em qualquer parte da mensagem de um alerta,
        o alerta é <strong>ignorado</strong>: fica arquivado em "Alertas Ignorados", sem responsável e sem e-mail para ninguém.
    </div>
    @error('pattern')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-4 p-3 bg-light rounded border small text-muted">
    <i class="fas fa-info-circle me-1"></i>
    Ao salvar, a regra também é aplicada aos <strong>alertas pendentes que já existem</strong>: os que tiverem esse texto
    na mensagem do alerta principal são ignorados junto com as repetições e perdem o responsável.
    A cada 5 minutos a verificação roda de novo (pegando inclusive alertas inseridos direto via SQL),
    exceto para alertas que você reativou manualmente.
</div>
