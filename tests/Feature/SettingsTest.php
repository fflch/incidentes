<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function alert(string $externo, string $quando): Alert
    {
        return Alert::create([
            'type' => 'zabbix',
            'id_externo' => $externo,
            'service_name' => 'Portal',
            'data_ocorrencia' => $quando,
            'mensagem' => 'Connection timeout to database server db01',
            'status_resolucao' => 'pendente',
        ]);
    }

    public function test_admin_forca_agrupamento_pela_interface(): void
    {
        Gate::define('admin', fn () => true);
        $user = User::factory()->create();

        $velho = $this->alert('1', '2026-11-10 08:00:00');
        $novo = $this->alert('2', '2026-11-10 09:00:00');

        $this->actingAs($user)->get(route('settings.index'))->assertOk()->assertSee('Sem registro');

        $this->actingAs($user)
            ->post(route('settings.group-alerts'), ['threshold' => 80])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success');

        $this->assertSame($velho->id, $novo->fresh()->parent_id);
        $this->actingAs($user)->get(route('settings.index'))->assertOk()->assertSee('manual');
    }

    public function test_nao_admin_nao_acessa_configuracoes(): void
    {
        Gate::define('admin', fn () => false);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($user)->post(route('settings.group-alerts'), ['threshold' => 80])->assertForbidden();
    }
}
