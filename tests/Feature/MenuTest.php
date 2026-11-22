<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_regras_ficam_agrupadas_em_submenu_para_admin(): void
    {
        Gate::define('admin', fn () => true);

        $this->actingAs(User::factory()->create())->get(route('settings.index'))
            ->assertOk()
            ->assertSeeInOrder(['dropdown-toggle', 'Regras', 'dropdown-menu', 'Regras de Atribuição', 'Regras para Ignorar'], false);
    }

    public function test_menu_de_regras_nao_aparece_para_quem_nao_e_admin(): void
    {
        Gate::define('admin', fn () => false);

        $this->actingAs(User::factory()->create())->get(route('alerts.my'))
            ->assertOk()
            ->assertDontSee('Regras para Ignorar')
            ->assertDontSee('Regras de Atribuição');
    }
}
