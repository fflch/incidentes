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
     * O texto aparece em qualquer parte da mensagem, sem diferenciar
     * maiúsculas e ignorando diferenças de espaços e quebras de linha
     * (\r\n, \n, tabs, indentação), já que o texto pode ser um trecho
     * grande e multilinha copiado do alerta.
     */
    public function matches(?string $mensagem): bool
    {
        $pattern = self::normalize((string) $this->pattern);

        return $pattern !== '' && mb_stripos(self::normalize((string) $mensagem), $pattern) !== false;
    }

    public static function normalize(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
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
