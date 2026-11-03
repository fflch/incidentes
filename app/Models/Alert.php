<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
        'resolvido_em',
    ];

    protected $casts = [
        'data_ocorrencia' => 'datetime',
        'resolvido_em' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Alert $alert) {
            // Se não foi atribuído manualmente, aplica a regra de atribuição automática pelo nome do serviço
            if (empty($alert->atribuido) && !empty($alert->service_name)) {
                $rule = AssignmentRule::findForService($alert->service_name);
                if ($rule) {
                    $alert->atribuido = $rule->user_id;
                }
            }

            // Se criado já resolvido sem resolvido_em, define agora
            if ($alert->status_resolucao === 'resolvido' && empty($alert->resolvido_em)) {
                $alert->resolvido_em = now();
            }
        });
    }

    public function atribuidoUser()
    {
        return $this->belongsTo(User::class, 'atribuido');
    }

    public function resolvidoPor()
    {
        return $this->belongsTo(User::class, 'resolvido_por_id');
    }

    public function scopePendente(Builder $query): Builder
    {
        return $query->where('status_resolucao', 'pendente');
    }

    public function scopeResolvido(Builder $query): Builder
    {
        return $query->where('status_resolucao', 'resolvido');
    }

    public function dataResolucao(): ?Carbon
    {
        if ($this->status_resolucao !== 'resolvido') {
            return null;
        }

        return $this->resolvido_em ?? $this->updated_at;
    }

    /**
     * Retorna o tempo de resolução em minutos ou null se não resolvido
     */
    public function tempoResolucaoMinutos(): ?int
    {
        $resolvidoEm = $this->dataResolucao();
        if (!$resolvidoEm || !$this->data_ocorrencia) {
            return null;
        }

        $minutos = $this->data_ocorrencia->diffInMinutes($resolvidoEm, false);
        return max(0, (int) $minutos);
    }

    /**
     * Retorna o tempo de resolução formatado humanizado (ex: '2h 15m' ou '45m')
     */
    public function tempoResolucaoFormatado(): ?string
    {
        $minutos = $this->tempoResolucaoMinutos();
        if ($minutos === null) {
            return null;
        }

        if ($minutos < 1) {
            return 'Menos de 1 min';
        }

        if ($minutos < 60) {
            return "{$minutos} min";
        }

        $horas = intdiv($minutos, 60);
        $minRestantes = $minutos % 60;

        if ($horas < 24) {
            return $minRestantes > 0 ? "{$horas}h {$minRestantes}m" : "{$horas}h";
        }

        $dias = intdiv($horas, 24);
        $horasRestantes = $horas % 24;

        return $horasRestantes > 0 ? "{$dias}d {$horasRestantes}h" : "{$dias}d";
    }

    public static function statusOptions(): array
    {
        return ['pendente', 'resolvido'];
    }
}
