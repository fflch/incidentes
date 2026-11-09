<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_name',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Encontra uma regra correspondente para o nome do serviço.
     * Aceita:
     * - coincidência exata: "portal-api"
     * - prefixo com wildcard: "portal-*"
     */
    public static function findForService(?string $serviceName): ?self
    {
        if (empty($serviceName)) {
            return null;
        }

        $trimmed = trim($serviceName);

        $exact = static::where('service_name', $trimmed)
            ->orWhereRaw('LOWER(service_name) = ?', [mb_strtolower($trimmed)])
            ->first();

        if ($exact) {
            return $exact;
        }

        $rules = static::all();

        foreach ($rules as $rule) {
            if ($rule->matchesServiceName($trimmed)) {
                return $rule;
            }
        }

        return null;
    }

    public function matchesServiceName(?string $serviceName): bool
    {
        if (empty($serviceName)) {
            return false;
        }

        $candidate = trim($serviceName);
        $pattern = trim((string) $this->service_name);

        if ($pattern === '') {
            return false;
        }

        if (strtolower($pattern) === strtolower($candidate)) {
            return true;
        }

        $prefix = rtrim($pattern, '*');
        if ($prefix !== $pattern && $prefix !== '') {
            return strncasecmp($candidate, $prefix, strlen($prefix)) === 0;
        }

        return false;
    }
}

