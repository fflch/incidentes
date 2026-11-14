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
        'parent_id',
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

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function atribuidoUser()
    {
        return $this->belongsTo(User::class, 'atribuido');
    }

    public function resolvidoPor()
    {
        return $this->belongsTo(User::class, 'resolvido_por_id');
    }

    public function comments()
    {
        return $this->hasMany(AlertComment::class);
    }

    /**
     * IDs de todos os alertas do grupo (raiz + filhos).
     */
    public function groupIds(): array
    {
        $root = $this->groupRoot();

        return $root->children()->pluck('id')->push($root->id)->all();
    }

    /**
     * Comentários de todo o grupo, do mais antigo para o mais novo.
     */
    public function groupComments()
    {
        return AlertComment::query()
            ->with('user')
            ->whereIn('alert_id', $this->groupIds())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Pode ver detalhes / comentar: admin ou responsável pelo grupo.
     */
    public function canBeAccessedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->can('admin')) {
            return true;
        }

        $root = $this->groupRoot();

        return (int) $root->atribuido === (int) $user->id || (int) $this->atribuido === (int) $user->id;
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopePendente(Builder $query): Builder
    {
        return $query->where('status_resolucao', 'pendente')->whereNull('parent_id');
    }

    public function scopeResolvido(Builder $query): Builder
    {
        return $query->where('status_resolucao', 'resolvido')->whereNull('parent_id');
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function groupRoot(): self
    {
        $parent = $this->parent;

        if ($parent) {
            return $parent->groupRoot();
        }

        return $this;
    }

    public function groupSize(): int
    {
        $root = $this->groupRoot();

        return (int) $root->children()->count() + 1;
    }

    public function repetitionCount(): int
    {
        return max(0, $this->groupSize() - 1);
    }

    public function relatedAlerts()
    {
        $root = $this->groupRoot();

        if ($this->isRoot()) {
            return $root->children()->orderByDesc('data_ocorrencia')->get();
        }

        return $root->children()->whereKeyNot($this->id)->orderByDesc('data_ocorrencia')->get();
    }

    public function resolveGroup(string $comentario, int $userId): void
    {
        $root = $this->groupRoot();

        foreach ($root->children()->get()->merge([$root]) as $alert) {
            $alert->status_resolucao = 'resolvido';
            $alert->comentario = trim($comentario);
            $alert->resolvido_por_id = $userId;
            $alert->resolvido_em = now();
            $alert->atribuido = $alert->atribuido ?? $userId;
            $alert->save();
        }
    }

    public function reopenGroup(): void
    {
        $root = $this->groupRoot();

        foreach ($root->children()->get()->merge([$root]) as $alert) {
            $alert->status_resolucao = 'pendente';
            $alert->resolvido_por_id = null;
            $alert->resolvido_em = null;
            $alert->save();
        }
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
