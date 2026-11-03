<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use \Spatie\Permission\Traits\HasRoles;
    use \Uspdev\SenhaunicaSocialite\Traits\HasSenhaunica;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function alertasAtribuidos(): HasMany
    {
        return $this->hasMany(Alert::class, 'atribuido');
    }

    public function alertasResolvidos(): HasMany
    {
        return $this->hasMany(Alert::class, 'resolvido_por_id');
    }

    public function assignmentRules(): HasMany
    {
        return $this->hasMany(AssignmentRule::class, 'user_id');
    }

    /**
     * Verifica se o usuário é administrador
     */
    public function isAdmin(): bool
    {
        if ($this->codpes && in_array((string)$this->codpes, array_map('strval', config('senhaunica.admins', [])))) {
            return true;
        }

        try {
            return $this->hasPermissionTo('admin', 'senhaunica') || $this->hasRole('admin');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Retorna a lista de usuários administradores do sistema
     */
    public static function getAdmins()
    {
        $adminCodpes = array_filter(array_map('trim', config('senhaunica.admins', [])));

        $query = static::query()
            ->where(function ($q) use ($adminCodpes) {
                if (!empty($adminCodpes)) {
                    $q->whereIn('codpes', $adminCodpes);
                }
                $q->orWhereHas('permissions', function ($perm) {
                    $perm->where('name', 'admin');
                })->orWhereHas('roles', function ($role) {
                    $role->where('name', 'admin');
                });
            })
            ->orderBy('name');

        $admins = $query->get();

        // Se não houver usuários com admin formal cadastrado, retorna todos os usuários para não travar a aplicação
        if ($admins->isEmpty()) {
            return static::orderBy('name')->get();
        }

        return $admins;
    }
}
