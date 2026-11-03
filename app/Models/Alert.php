<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'type',
        'id_externo',
        'service_name',
        'data_ocorrencia',
        'mensagem',
        'status_resolucao',
        'comentario',
        'atribuido',
        'resolvido_por_id',
    ];

    protected $casts = [
        'data_ocorrencia' => 'datetime',
    ];

    public function atribuidoUser()
    {
        return $this->belongsTo(User::class, 'atribuido');
    }

    public function resolvidoPor()
    {
        return $this->belongsTo(User::class, 'resolvido_por_id');
    }

    public static function statusOptions(): array
    {
        return ['pendente', 'resolvido'];
    }
}