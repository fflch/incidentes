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
        'ignore_rule_id',
    ];

    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_RESOLVIDO = 'resolvido';
    public const STATUS_IGNORADO = 'ignorado';

    /** Status cujos alertas passam pelo agrupamento de similares. */
    public const GROUPABLE_STATUSES = [self::STATUS_PENDENTE, self::STATUS_IGNORADO];

    protected $casts = [
        'data_ocorrencia' => 'datetime',
        'resolvido_em' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Alert $alert) {
            // Alerta novo e pendente cuja mensagem contém um texto cadastrado em
            // "Regras para ignorar" já nasce ignorado: fica arquivado e não é
            // atribuído nem notificado a ninguém.
            if (($alert->status_resolucao ?? self::STATUS_PENDENTE) === self::STATUS_PENDENTE && empty($alert->parent_id)) {
                $rule = IgnoreRule::findForMessage($alert->mensagem);
                if ($rule) {
                    $alert->status_resolucao = self::STATUS_IGNORADO;
                    $alert->ignore_rule_id = $rule->id;
                    $alert->resolvido_em = now();
                    $alert->resolvido_por_id = null;
                    $alert->atribuido = null;
                }
            }

            // Se não foi atribuído manualmente, aplica a regra de atribuição automática
            // (pelo nome do serviço ou, na falta de regra para ele, pelo tipo)
            if (empty($alert->atribuido) && $alert->status_resolucao !== self::STATUS_IGNORADO) {
                $rule = AssignmentRule::findForAlert($alert);
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

    public function ignoreRule()
    {
        return $this->belongsTo(IgnoreRule::class);
    }

    public function isIgnored(): bool
    {
        return $this->status_resolucao === self::STATUS_IGNORADO;
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

    public function scopeIgnorado(Builder $query): Builder
    {
        return $query->where('status_resolucao', self::STATUS_IGNORADO)->whereNull('parent_id');
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

    /**
     * Define o responsável do grupo inteiro: o alerta principal e todas as
     * repetições (filhos) passam a ter o mesmo responsável.
     */
    public function assignGroup(?int $userId): self
    {
        $root = $this->groupRoot();

        $root->atribuido = $userId;
        $root->save();

        $root->children()->update(['atribuido' => $userId]);

        return $root;
    }

    /**
     * Estatísticas de repetição do grupo: total de ocorrências (principal +
     * repetições), primeira e última data de ocorrência.
     *
     * @return array{total: int, first: ?Carbon, last: ?Carbon}
     */
    public function groupOccurrenceStats(): array
    {
        $root = $this->groupRoot();

        $stats = self::query()
            ->where(fn ($q) => $q->whereKey($root->id)->orWhere('parent_id', $root->id))
            ->selectRaw('COUNT(*) as total, MIN(data_ocorrencia) as first, MAX(data_ocorrencia) as last')
            ->first();

        return [
            'total' => (int) ($stats->total ?? 1),
            'first' => $stats?->first ? Carbon::parse($stats->first) : $root->data_ocorrencia,
            'last' => $stats?->last ? Carbon::parse($stats->last) : $root->data_ocorrencia,
        ];
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

    /**
     * Ignora o grupo inteiro (principal + repetições) por causa de uma regra.
     * Alerta ignorado não tem responsável.
     */
    public function ignoreGroup(?IgnoreRule $rule = null): void
    {
        $root = $this->groupRoot();

        foreach ($root->children()->get()->merge([$root]) as $alert) {
            $alert->status_resolucao = self::STATUS_IGNORADO;
            $alert->ignore_rule_id = $rule?->id;
            $alert->atribuido = null;
            $alert->resolvido_por_id = null;
            $alert->resolvido_em = now();
            $alert->save();
        }
    }

    /**
     * Volta o grupo para pendente (reabre um resolvido ou reativa um ignorado).
     */
    public function reopenGroup(): void
    {
        $root = $this->groupRoot();

        foreach ($root->children()->get()->merge([$root]) as $alert) {
            $alert->status_resolucao = 'pendente';
            $alert->resolvido_por_id = null;
            $alert->resolvido_em = null;
            $alert->ignore_rule_id = null;
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

        return self::formatarDuracao($minutos);
    }

    /**
     * Formata uma duração em minutos (ex: '2h 15m', '45 min', '3d 4h')
     */
    public static function formatarDuracao(int $minutos): string
    {
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
        return [self::STATUS_PENDENTE, self::STATUS_RESOLVIDO, self::STATUS_IGNORADO];
    }
}
