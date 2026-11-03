<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (\App::environment('production')) {
            \URL::forceScheme('https');
        }

        Gate::before(function ($user, $ability) {
            if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                if (in_array($ability, ['admin', 'boss', 'manager', 'poweruser', 'user'])) {
                    return true;
                }
            }
        });

        if (!Gate::has('user')) {
            Gate::define('user', function ($user) {
                return $user !== null;
            });
        }

        try {
            if (Schema::hasTable('permissions')) {
                Permission::findOrCreate('admin', 'senhaunica');
                Permission::findOrCreate('user', 'senhaunica');
            }
        } catch (\Throwable $e) {
            // Em caso de falha de conexão inicial ou migrações, não interrompe o boot
        }
    }
}
