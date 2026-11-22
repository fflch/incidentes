<?php

namespace Tests\Feature;

use App\Mail\AlertCommentMail;
use App\Models\Alert;
use App\Models\AssignmentRule;
use App\Models\IgnoreRule;
use App\Models\User;
use App\Services\AlertGroupingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IgnoreRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.alerts_api.key' => 'chave', 'mail.emails_gestao' => ['gestao@example.com']]);
        Gate::define('admin', fn () => true);
        $this->admin = User::factory()->create();
    }

    private function alert(string $externo, string $quando, array $extra = []): Alert
    {
        return Alert::create(array_merge([
            'type' => 'zabbix',
            'id_externo' => $externo,
            'service_name' => 'Portal',
            'data_ocorrencia' => $quando,
            'mensagem' => 'Connection timeout to database server db01',
            'status_resolucao' => 'pendente',
        ], $extra));
    }

    private function ingest(string $id, string $mensagem): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/alerts', [
            'type' => 'laravel',
            'id_externo' => $id,
            'service_name' => 'portal',
            'data_ocorrencia' => '2026-11-20 10:00:00',
            'mensagem' => $mensagem,
        ], ['X-API-Key' => 'chave']);
    }

    public function test_alerta_com_texto_cadastrado_chega_ignorado_sem_responsavel_e_agrupado(): void
    {
        $responsavel = User::factory()->create();
        AssignmentRule::create(['match_field' => 'service_name', 'pattern' => 'portal', 'user_id' => $responsavel->id]);
        $regra = IgnoreRule::create(['pattern' => 'CERTIFICATE will expire']);

        $this->ingest('1', 'SSL: Certificate will expire in 30 days')
            ->assertCreated()
            ->assertJsonPath('status', 'ignorado')
            ->assertJsonPath('atribuido', null);

        $primeiro = Alert::where('id_externo', '1')->first();
        $this->assertSame($regra->id, $primeiro->ignore_rule_id);
        $this->assertNotNull($primeiro->resolvido_em);

        // Repetição semelhante também é ignorada e agrupada ao ignorado
        $this->ingest('2', 'SSL: Certificate will expire in 29 days')->assertCreated();
        $this->assertSame($primeiro->id, Alert::where('id_externo', '2')->first()->parent_id);

        // Alerta normal continua pendente e atribuído
        $this->ingest('3', 'Connection refused')->assertCreated()
            ->assertJsonPath('status', 'pendente')
            ->assertJsonPath('atribuido', $responsavel->id);

        // Fora da home / painel público
        $this->getJson('/api/public-alerts')->assertJsonPath('total', 1);
    }

    public function test_varredura_agrupa_ignorados_entre_si_sem_misturar_com_pendentes(): void
    {
        $ignA = $this->alert('1', '2026-11-10 08:00:00', ['status_resolucao' => 'ignorado']);
        $ignB = $this->alert('2', '2026-11-10 09:00:00', ['status_resolucao' => 'ignorado']);
        $pend = $this->alert('3', '2026-11-10 10:00:00');

        $result = app(AlertGroupingService::class)->run();

        $this->assertSame(1, $result['ignorados_agrupados']);
        $this->assertSame($ignA->id, $ignB->fresh()->parent_id);
        $this->assertNull($pend->fresh()->parent_id);
    }

    public function test_cadastro_de_regra_ignora_pendentes_existentes_e_tira_responsavel(): void
    {
        Mail::fake();
        $resp = User::factory()->create();
        $root = $this->alert('1', '2026-11-10 08:00:00', ['mensagem' => 'Backup job FINISHED with warnings', 'atribuido' => $resp->id]);
        $filho = $this->alert('2', '2026-11-10 09:00:00', ['mensagem' => 'Backup job finished with warnings', 'parent_id' => $root->id, 'atribuido' => $resp->id]);
        $outro = $this->alert('3', '2026-11-10 10:00:00', ['mensagem' => 'Disk full']);

        $this->actingAs($this->admin)
            ->post(route('ignore-rules.store'), ['pattern' => '  finished with warnings '])
            ->assertRedirect(route('ignore-rules.index'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, '1 alerta(s) pendente(s) foram ignorados'));

        $regra = IgnoreRule::first();
        $this->assertSame('finished with warnings', $regra->pattern);
        $this->assertSame($this->admin->id, $regra->user_id);

        foreach ([$root, $filho] as $a) {
            $a->refresh();
            $this->assertSame('ignorado', $a->status_resolucao);
            $this->assertNull($a->atribuido);
            $this->assertSame($regra->id, $a->ignore_rule_id);
        }
        $this->assertSame('pendente', $outro->fresh()->status_resolucao);
        Mail::assertNothingSent();

        $this->actingAs($this->admin)->post(route('ignore-rules.store'), ['pattern' => 'Disk full'])->assertRedirect();
        $this->assertSame('ignorado', $outro->fresh()->status_resolucao);

        // Validações
        $this->actingAs($this->admin)->post(route('ignore-rules.store'), ['pattern' => 'ab'])->assertSessionHasErrors('pattern');
        $this->actingAs($this->admin)->post(route('ignore-rules.store'), ['pattern' => 'Disk full'])->assertSessionHasErrors('pattern');
    }

    public function test_telas_de_regras_e_de_alertas_ignorados(): void
    {
        $regra = IgnoreRule::create(['pattern' => 'heartbeat ok']);
        $ign = $this->alert('1', '2026-11-10 08:00:00', ['mensagem' => 'Heartbeat OK from host x']);
        $this->alert('2', '2026-11-10 09:00:00', ['mensagem' => 'Heartbeat OK from host y', 'parent_id' => $ign->id]);
        $this->alert('3', '2026-11-10 09:00:00', ['mensagem' => 'Problema real', 'service_name' => 'Outro']);

        $this->assertTrue($ign->fresh()->isIgnored());

        $this->actingAs($this->admin)->get(route('ignore-rules.index'))
            ->assertOk()->assertSee('heartbeat ok');
        $this->actingAs($this->admin)->get(route('ignore-rules.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('ignore-rules.edit', $regra))->assertOk()->assertSee('heartbeat ok');

        $this->actingAs($this->admin)->get(route('alerts.ignored'))
            ->assertOk()
            ->assertSee('Heartbeat OK from host x')
            ->assertSee('2 ocorrências')
            ->assertDontSee('Problema real');

        $this->actingAs($this->admin)->get(route('alerts.ignored', ['regra' => $regra->id]))->assertOk()->assertSee('heartbeat ok');

        $this->actingAs($this->admin)->get(route('alerts.show', $ign))
            ->assertOk()
            ->assertSee('Ignorado pela regra', false)
            ->assertSee('Reativar alerta')
            ->assertDontSee('Atribuir a mim')
            ->assertDontSee('Marcar como resolvido');

        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('alerts.my', ['status' => 'todos']))->assertOk();

        // Excluir a regra mantém os alertas ignorados
        $this->actingAs($this->admin)->delete(route('ignore-rules.destroy', $regra))->assertRedirect();
        $this->assertTrue($ign->fresh()->isIgnored());
        $this->assertNull($ign->fresh()->ignore_rule_id);
    }

    public function test_reativar_volta_para_pendente_e_ignorado_nao_aceita_responsavel_nem_avisa_gestao(): void
    {
        Mail::fake();
        IgnoreRule::create(['pattern' => 'heartbeat']);
        $ign = $this->alert('1', '2026-11-10 08:00:00', ['mensagem' => 'heartbeat']);
        $filho = $this->alert('2', '2026-11-10 09:00:00', ['mensagem' => 'heartbeat', 'parent_id' => $ign->id, 'status_resolucao' => 'ignorado']);

        $this->actingAs($this->admin)->post(route('alerts.assign-me', $ign))->assertStatus(422);
        $this->actingAs($this->admin)->post(route('alerts.assign-user', $ign), ['atribuido' => $this->admin->id])->assertStatus(422);
        $this->assertNull($ign->fresh()->atribuido);

        $this->actingAs($this->admin)->post(route('alerts.comments.store', $ign), ['comentario' => 'anotação'])->assertRedirect();
        Mail::assertNotSent(AlertCommentMail::class);

        $this->actingAs($this->admin)->post(route('alerts.reopen', $ign))
            ->assertSessionHas('success', 'Alerta reativado: voltou para os pendentes.');

        $this->assertSame('pendente', $ign->fresh()->status_resolucao);
        $this->assertNull($ign->fresh()->ignore_rule_id);
        $this->assertSame('pendente', $filho->fresh()->status_resolucao);
    }

    public function test_editar_principal_para_ignorado_ignora_grupo_e_tira_responsavel(): void
    {
        $root = $this->alert('1', '2026-11-10 08:00:00', ['atribuido' => $this->admin->id]);
        $filho = $this->alert('2', '2026-11-10 09:00:00', ['parent_id' => $root->id, 'atribuido' => $this->admin->id]);

        $this->actingAs($this->admin)->patch(route('alerts.update', $root), [
            'type' => 'zabbix',
            'id_externo' => '1',
            'service_name' => 'Portal',
            'data_ocorrencia' => '2026-11-10 08:00:00',
            'mensagem' => $root->mensagem,
            'status_resolucao' => 'ignorado',
            'atribuido' => $this->admin->id,
        ])->assertRedirect();

        $this->assertSame('ignorado', $root->fresh()->status_resolucao);
        $this->assertNull($root->fresh()->atribuido);
        $this->assertSame('ignorado', $filho->fresh()->status_resolucao);
        $this->assertNull($filho->fresh()->atribuido);
    }

    public function test_home_mostra_ultimo_alerta_pendente_recebido(): void
    {
        $root = $this->alert('1', now()->subDays(3)->toDateTimeString());
        $this->alert('2', now()->subMinutes(10)->toDateTimeString(), ['parent_id' => $root->id]);
        $this->alert('3', now()->subHour()->toDateTimeString(), ['service_name' => 'Outro', 'mensagem' => 'x']);
        // Ignorado mais recente não conta
        $this->alert('4', now()->toDateTimeString(), ['service_name' => 'Z', 'status_resolucao' => 'ignorado']);

        $this->getJson('/api/public-alerts')
            ->assertOk()
            ->assertJsonPath('ultimo_recebido_relativo', now()->subMinutes(10)->diffForHumans())
            ->assertJsonPath('ultimo_recebido_formatado', now()->subMinutes(10)->format('d/m/Y H:i'))
            ->assertJsonMissingPath('alerts.0.ultima_ocorrencia_obj');

        $this->get('/')->assertOk()->assertSee('Último alerta recebido');
    }

    private function insertViaSql(string $id, string $mensagem, array $extra = []): int
    {
        return DB::table('alerts')->insertGetId(array_merge([
            'type' => 'zabbix',
            'id_externo' => $id,
            'service_name' => 'Portal',
            'data_ocorrencia' => '2026-11-10 08:00:00',
            'mensagem' => $mensagem,
            'status_resolucao' => 'pendente',
        ], $extra));
    }

    public function test_verificacao_periodica_ignora_alertas_inseridos_via_sql_antes_da_atribuicao(): void
    {
        $responsavel = User::factory()->create();
        AssignmentRule::create(['match_field' => 'service_name', 'pattern' => 'Portal', 'user_id' => $responsavel->id]);
        IgnoreRule::create(['pattern' => 'heartbeat']);

        $ruido = $this->insertViaSql('1', 'Heartbeat ok');
        $filho = $this->insertViaSql('2', 'Heartbeat ok again', ['parent_id' => $ruido]);
        $real = $this->insertViaSql('3', 'Disk full');

        // Mesma ordem do agendamento
        $this->artisan('alerts:apply-ignore-rules')->expectsOutputToContain('1 alerta(s)')->assertSuccessful();
        $this->artisan('alerts:group-similar')->assertSuccessful();
        $this->artisan('alerts:apply-assignment-rules')->assertSuccessful();

        $this->assertSame('ignorado', Alert::find($ruido)->status_resolucao);
        $this->assertSame('ignorado', Alert::find($filho)->status_resolucao);
        $this->assertNull(Alert::find($ruido)->atribuido);
        $this->assertSame('pendente', Alert::find($real)->status_resolucao);
        $this->assertSame($responsavel->id, Alert::find($real)->atribuido);
    }

    public function test_agendamento_roda_ignorar_antes_de_agrupar_e_atribuir(): void
    {
        $commands = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command)->implode("\n");

        $ignorar = strpos($commands, 'alerts:apply-ignore-rules');
        $agrupar = strpos($commands, 'alerts:group-similar');
        $atribuir = strpos($commands, 'alerts:apply-assignment-rules');

        $this->assertNotFalse($ignorar);
        $this->assertLessThan($agrupar, $ignorar);
        $this->assertLessThan($atribuir, $ignorar);
    }

    public function test_alerta_reativado_nao_e_ignorado_de_novo(): void
    {
        IgnoreRule::create(['pattern' => 'heartbeat']);
        $id = $this->insertViaSql('1', 'heartbeat');
        $this->artisan('alerts:apply-ignore-rules')->assertSuccessful();
        $alert = Alert::find($id);
        $this->assertTrue($alert->isIgnored());

        $this->actingAs($this->admin)->post(route('alerts.reopen', $alert))->assertRedirect();
        $this->assertTrue($alert->fresh()->nao_ignorar);

        $this->artisan('alerts:apply-ignore-rules')->expectsOutputToContain('0 alerta(s)')->assertSuccessful();
        $this->actingAs($this->admin)->post(route('ignore-rules.store'), ['pattern' => 'heartbe'])->assertRedirect();
        $this->assertSame('pendente', $alert->fresh()->status_resolucao);

        $this->actingAs($this->admin)->get(route('alerts.show', $alert))->assertOk()->assertSee('Reativado manualmente');

        // Resolver e reabrir um alerta comum não marca como protegido
        $comum = $this->alert('9', '2026-11-11 08:00:00', ['service_name' => 'X', 'mensagem' => 'Erro']);
        $comum->resolveGroup('ok', $this->admin->id);
        $comum->reopenGroup();
        $this->assertFalse($comum->fresh()->nao_ignorar);
    }

    public function test_admin_forca_verificacao_pela_tela_de_configuracoes(): void
    {
        $id = $this->insertViaSql('1', 'Heartbeat OK');
        DB::table('ignore_rules')->insert(['pattern' => 'heartbeat', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin)->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Regras para ignorar')
            ->assertSee('Verificar pendentes agora');

        $this->actingAs($this->admin)
            ->post(route('settings.apply-ignore-rules'))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, '1 alerta(s) pendente(s) ignorado(s)'));

        $this->assertTrue(Alert::find($id)->isIgnored());
        $this->actingAs($this->admin)->get(route('settings.index'))->assertOk()->assertSee('1 alerta(s) ignorado(s)');

        Gate::define('admin', fn () => false);
        $this->actingAs(User::factory()->create())->post(route('settings.apply-ignore-rules'))->assertForbidden();
    }
}
