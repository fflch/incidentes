<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\AssignmentRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AssignmentRuleTest extends TestCase
{
    use RefreshDatabase;

    private function alert(string $externo, array $extra = []): Alert
    {
        return Alert::create(array_merge([
            'type' => 'zabbix',
            'id_externo' => $externo,
            'service_name' => 'portal-api',
            'data_ocorrencia' => '2026-11-10 08:00:00',
            'mensagem' => 'Falha',
            'status_resolucao' => 'pendente',
        ], $extra));
    }

    public function test_regra_por_nome_do_servico_atribui_alerta(): void
    {
        $user = User::factory()->create();
        AssignmentRule::create(['match_field' => 'service_name', 'pattern' => 'portal-*', 'user_id' => $user->id]);

        $this->assertSame($user->id, $this->alert('1')->atribuido);
        $this->assertNull($this->alert('2', ['service_name' => 'outro'])->atribuido);
    }

    public function test_regra_por_tipo_atribui_alerta(): void
    {
        $user = User::factory()->create();
        AssignmentRule::create(['match_field' => 'type', 'pattern' => 'Zabbix', 'user_id' => $user->id]);

        $this->assertSame($user->id, $this->alert('1')->atribuido);
        $this->assertSame($user->id, $this->alert('2', ['service_name' => null])->atribuido);
        $this->assertNull($this->alert('3', ['type' => 'prometheus'])->atribuido);
    }

    public function test_regra_por_nome_tem_prioridade_sobre_tipo(): void
    {
        $porTipo = User::factory()->create();
        $porNome = User::factory()->create();
        AssignmentRule::create(['match_field' => 'type', 'pattern' => 'zabbix', 'user_id' => $porTipo->id]);
        AssignmentRule::create(['match_field' => 'service_name', 'pattern' => 'portal-*', 'user_id' => $porNome->id]);

        $this->assertSame($porNome->id, $this->alert('1')->atribuido);
        $this->assertSame($porTipo->id, $this->alert('2', ['service_name' => 'outro'])->atribuido);
    }

    public function test_mesmo_valor_pode_existir_em_campos_diferentes(): void
    {
        Gate::define('admin', fn () => true);
        Mail::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('assignment-rules.store'), [
            'match_field' => 'service_name', 'pattern' => 'zabbix', 'user_id' => $user->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('assignment-rules.store'), [
            'match_field' => 'type', 'pattern' => 'zabbix', 'user_id' => $user->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('assignment-rules.store'), [
            'match_field' => 'type', 'pattern' => 'zabbix', 'user_id' => $user->id,
        ])->assertSessionHasErrors('pattern');

        $this->actingAs($user)->post(route('assignment-rules.store'), [
            'match_field' => 'mensagem', 'pattern' => 'x', 'user_id' => $user->id,
        ])->assertSessionHasErrors('match_field');

        $this->assertSame(2, AssignmentRule::count());
    }

    public function test_regra_por_tipo_aplicada_aos_pendentes(): void
    {
        Gate::define('admin', fn () => true);
        Mail::fake();
        $user = User::factory()->create();

        $zabbix = $this->alert('1');
        $outro = $this->alert('2', ['type' => 'prometheus']);

        $this->actingAs($user)->post(route('assignment-rules.store'), [
            'match_field' => 'type', 'pattern' => 'zabbix', 'user_id' => $user->id, 'apply_to_pending' => 1,
        ])->assertRedirect(route('assignment-rules.index'));

        $this->assertSame($user->id, $zabbix->fresh()->atribuido);
        $this->assertNull($outro->fresh()->atribuido);
    }

    public function test_telas_de_regras_renderizam(): void
    {
        Gate::define('admin', fn () => true);
        $user = User::factory()->create();
        $rule = AssignmentRule::create(['match_field' => 'type', 'pattern' => 'zabbix', 'user_id' => $user->id]);

        $this->actingAs($user)->get(route('assignment-rules.index'))->assertOk()->assertSee('Tipo do serviço');
        $this->actingAs($user)->get(route('assignment-rules.create'))->assertOk()->assertSee('match_field');
        $this->actingAs($user)->get(route('assignment-rules.edit', $rule))->assertOk()->assertSee('zabbix');
    }
}
