<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'id_externo' => ['required', 'string', 'max:100'],
            'service_name' => ['nullable', 'string', 'max:100'],
            'data_ocorrencia' => ['required', 'date'],
            'mensagem' => ['required', 'string'],
            'status_resolucao' => ['nullable', 'in:pendente,resolvido'],
            'comentario' => ['nullable', 'string', 'required_if:status_resolucao,resolvido'],
            'atribuido' => ['nullable', 'exists:users,id'],
            'parent_id' => ['nullable', 'exists:alerts,id'],
            'resolvido_por_id' => ['nullable', 'exists:users,id'],
        ];
    }
}