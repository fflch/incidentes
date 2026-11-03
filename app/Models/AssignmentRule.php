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
     * Encontra uma regra correspondente para o nome do serviço
     */
    public static function findForService(?string $serviceName): ?self
    {
        if (empty($serviceName)) {
            return null;
        }

        $trimmed = trim($serviceName);

        // Busca exata ou insensível a maiúsculas/minúsculas
        return static::where('service_name', $trimmed)
            ->orWhereRaw('LOWER(service_name) = ?', [mb_strtolower($trimmed)])
            ->first();
    }
}

