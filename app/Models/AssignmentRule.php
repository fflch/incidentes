<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentRule extends Model
{
    use HasFactory;

    public const FIELD_SERVICE_NAME = 'service_name';
    public const FIELD_TYPE = 'type';

    /** Campos do alerta que uma regra pode usar, com o rótulo exibido. */
    public const MATCH_FIELDS = [
        self::FIELD_SERVICE_NAME => 'Nome do serviço',
        self::FIELD_TYPE => 'Tipo do serviço',
    ];

    protected $fillable = [
        'match_field',
        'pattern',
        'user_id',
    ];

    protected $attributes = [
        'match_field' => self::FIELD_SERVICE_NAME,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function matchFieldLabel(): string
    {
        return self::MATCH_FIELDS[$this->match_field] ?? $this->match_field;
    }

    /**
     * Encontra a regra aplicável a um alerta.
     *
     * Regras por nome do serviço têm prioridade sobre regras por tipo, por
     * serem mais específicas. Em cada campo, coincidência exata vence prefixo.
     */
    public static function findForAlert(Alert $alert): ?self
    {
        return static::findFor(self::FIELD_SERVICE_NAME, $alert->service_name)
            ?? static::findFor(self::FIELD_TYPE, $alert->type);
    }

    /**
     * Encontra uma regra do campo informado que case com o valor.
     * Aceita:
     * - coincidência exata (sem diferenciar maiúsculas): "portal-api"
     * - prefixo com wildcard: "portal-*"
     */
    public static function findFor(string $field, ?string $value): ?self
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $rules = static::where('match_field', $field)->get();

        return $rules->first(fn (self $rule) => strcasecmp(trim($rule->pattern), $value) === 0)
            ?? $rules->first(fn (self $rule) => $rule->matchesValue($value));
    }

    public function matchesAlert(Alert $alert): bool
    {
        return $this->matchesValue($alert->{$this->match_field});
    }

    public function matchesValue(?string $value): bool
    {
        $candidate = trim((string) $value);
        $pattern = trim((string) $this->pattern);

        if ($candidate === '' || $pattern === '') {
            return false;
        }

        if (strcasecmp($pattern, $candidate) === 0) {
            return true;
        }

        $prefix = rtrim($pattern, '*');
        if ($prefix !== $pattern && $prefix !== '') {
            return strncasecmp($candidate, $prefix, strlen($prefix)) === 0;
        }

        return false;
    }
}
