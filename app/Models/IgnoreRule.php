<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Texto que, se aparecer na mensagem de um alerta, faz o alerta ser ignorado
 * (arquivado sem notificar ninguém).
 */
class IgnoreRule extends Model
{
    protected $fillable = [
        'pattern',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * O texto aparece em qualquer parte da mensagem (sem diferenciar maiúsculas).
     */
    public function matches(?string $mensagem): bool
    {
        $pattern = trim((string) $this->pattern);

        return $pattern !== '' && mb_stripos((string) $mensagem, $pattern) !== false;
    }

    /**
     * Ignora os alertas pendentes que já existem e cuja mensagem do alerta
     * principal contém o texto da regra. O grupo inteiro (principal +
     * repetições) é ignorado. Retorna quantos grupos foram ignorados.
     */
    public function applyToPending(): int
    {
        $like = '%' . addcslashes(trim($this->pattern), '%_\\') . '%';
        $count = 0;

        Alert::query()
            ->whereNull('parent_id')
            ->where('status_resolucao', Alert::STATUS_PENDENTE)
            ->where('mensagem', 'like', $like)
            ->orderBy('id')
            ->get()
            ->filter(fn (Alert $alert) => $this->matches($alert->mensagem))
            ->each(function (Alert $alert) use (&$count) {
                $alert->ignoreGroup($this);
                $count++;
            });

        return $count;
    }

    /**
     * Primeira regra (a mais antiga) cujo texto aparece na mensagem.
     */
    public static function findForMessage(?string $mensagem): ?self
    {
        if (trim((string) $mensagem) === '') {
            return null;
        }

        return static::query()->orderBy('id')->get()->first(fn (self $rule) => $rule->matches($mensagem));
    }
}
