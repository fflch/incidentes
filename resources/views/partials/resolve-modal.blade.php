<!-- Modal de Resolução com Comentário Obrigatório -->
<div class="modal fade" id="resolveAlertModal" tabindex="-1" aria-labelledby="resolveAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="resolveAlertModalLabel">
                    <i class="fas fa-check-circle me-1"></i> Marcar Alerta como Resolvido
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="resolveAlertForm" method="POST" action="">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-3">
                        <div class="small text-muted">Incidente:</div>
                        <strong id="modalAlertServiceName" class="fs-6 text-dark d-block"></strong>
                        <div id="modalAlertIdExterno" class="small text-muted"></div>
                    </div>

                    <div class="mb-3">
                        <label for="modalComentario" class="form-label fw-bold">
                            Comentário da Resolução <span class="text-danger">* (obrigatório)</span>
                        </label>
                        <textarea name="comentario"
                                  id="modalComentario"
                                  rows="4"
                                  class="form-control"
                                  placeholder="Descreva o que foi feito para resolver o problema, correções aplicadas, causa raiz, etc..."
                                  required></textarea>
                        <div class="invalid-feedback" id="modalComentarioFeedback">
                            O comentário é obrigatório para registrar a resolução do incidente.
                        </div>
                        <div class="form-text">
                            Descreva sucintamente a ação tomada. Este comentário ficará registrado no histórico do alerta.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4" id="btnConfirmResolve">
                        <i class="fas fa-check me-1"></i> Confirmar Resolução
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openResolveModal(alertId, serviceName, idExterno, actionUrl) {
        const form = document.getElementById('resolveAlertForm');
        form.action = actionUrl || `/alerts/${alertId}/resolve`;

        document.getElementById('modalAlertServiceName').textContent = serviceName || 'Alerta #' + alertId;
        document.getElementById('modalAlertIdExterno').textContent = idExterno ? 'ID Externo: ' + idExterno : '';

        const textarea = document.getElementById('modalComentario');
        textarea.value = '';
        textarea.classList.remove('is-invalid');

        const modalEl = document.getElementById('resolveAlertModal');

        if (window.bootstrap?.Modal) {
            const modal = typeof window.bootstrap.Modal.getOrCreateInstance === 'function'
                ? window.bootstrap.Modal.getOrCreateInstance(modalEl)
                : new window.bootstrap.Modal(modalEl);
            modal.show();
        } else if (window.jQuery?.fn.modal) {
            window.jQuery(modalEl).modal('show');
        } else {
            console.error('Bootstrap Modal não está disponível.');
        }

        setTimeout(() => textarea.focus(), 350);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('resolveAlertForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                const textarea = document.getElementById('modalComentario');
                if (!textarea.value.trim() || textarea.value.trim().length < 3) {
                    e.preventDefault();
                    textarea.classList.add('is-invalid');
                    textarea.focus();
                } else {
                    textarea.classList.remove('is-invalid');
                    const btn = document.getElementById('btnConfirmResolve');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
                }
            });
        }
    });
</script>

