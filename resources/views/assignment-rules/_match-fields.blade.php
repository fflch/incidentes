{{-- Critério (nome ou tipo do serviço) + valor comparado. Espera $matchFields, $suggestions e opcionalmente $rule. --}}
@php
    $currentField = old('match_field', $rule->match_field ?? \App\Models\AssignmentRule::FIELD_SERVICE_NAME);
@endphp

<div class="mb-4">
    <label class="form-label fw-bold d-block">Atribuir com base em <span class="text-danger">*</span></label>
    <div class="btn-group" role="group" aria-label="Critério da regra">
        @foreach($matchFields as $value => $label)
            <input type="radio" class="btn-check" name="match_field" id="match_field_{{ $value }}"
                   value="{{ $value }}" autocomplete="off" {{ $currentField === $value ? 'checked' : '' }}>
            <label class="btn btn-outline-primary" for="match_field_{{ $value }}">
                <i class="fas {{ $value === 'type' ? 'fa-tag' : 'fa-server' }} me-1"></i> {{ $label }}
            </label>
        @endforeach
    </div>
    <div class="form-text">
        Se um alerta casar com uma regra por nome do serviço e outra por tipo, vale a regra por nome do serviço.
    </div>
    @error('match_field')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-4">
    <label for="pattern" class="form-label fw-bold">
        <span data-label-for="service_name">Nome do Serviço</span><span data-label-for="type">Tipo do Serviço</span>
        <span class="text-danger">*</span>
    </label>
    <input type="text"
           id="pattern"
           name="pattern"
           list="suggestions-{{ $currentField }}"
           class="form-control form-control-lg @error('pattern') is-invalid @enderror"
           value="{{ old('pattern', $rule->pattern ?? '') }}"
           placeholder="Digite ou selecione um valor"
           autocomplete="off"
           required>
    @foreach($suggestions as $field => $values)
        <datalist id="suggestions-{{ $field }}">
            @foreach($values as $v)
                <option value="{{ $v }}">
            @endforeach
        </datalist>
    @endforeach
    <div class="form-text">
        Você pode digitar um valor exato ou um prefixo com a sintaxe <strong>portal-*</strong> para aplicar a regra a todos os valores que comecem com essa string.
    </div>
    @error('pattern')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<script>
    (function () {
        const input = document.getElementById('pattern');
        const sync = () => {
            const field = document.querySelector('input[name="match_field"]:checked')?.value || 'service_name';
            input.setAttribute('list', 'suggestions-' + field);
            document.querySelectorAll('[data-label-for]').forEach(el => {
                el.classList.toggle('d-none', el.dataset.labelFor !== field);
            });
        };
        document.querySelectorAll('input[name="match_field"]').forEach(r => r.addEventListener('change', sync));
        sync();
    })();
</script>
