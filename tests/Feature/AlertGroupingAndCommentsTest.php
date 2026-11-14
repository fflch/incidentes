<?php

namespace Tests\Feature;

use App\Mail\AlertCommentMail;
use App\Models\Alert;
use App\Models\User;
use App\Services\AlertGroupingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AlertGroupingAndCommentsTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_grupo_tem_como_raiz_o_alerta_mais_antigo(): void
    {
        $novo = $this->alert('3', '2026-11-10 12:00:00');
        $velho = $this->alert('1', '2026-11-10 08:00:00');
        $meio = $this->alert('2', '2026-11-10 10:00:00');
        $outro = $this->alert('4', '2026-11-10 09:00:00', ['service_name' => 'Outro']);

        app(AlertGroupingService::class)->groupPendingAlerts();

        $this->assertNull($velho->fresh()->parent_id);
        $this->assertSame($velho->id, $meio->fresh()->parent_id);
        $this->assertSame($velho->id, $novo->fresh()->parent_id);
        $this->assertNull($outro->fresh()->parent_id);
    }

    public function test_alerta_novo_e_agregado_ao_grupo_existente(): void
    {
        $velho = $this->alert('1', '2026-11-10 08:00:00');
        $this->alert('2', '2026-11-10 09:00:00');
        app(AlertGroupingService::class)->groupPendingAlerts();

        $chegou = $this->alert('3', '2026-11-11 08:00:00');
        app(AlertGroupingService::class)->groupPendingAlerts();

        $this->assertSame($velho->id, $chegou->fresh()->parent_id);
        $this->assertSame(2, $velho->children()->count());
    }

    public function test_corrige_grupos_antigos_com_raiz_mais_nova(): void
    {
        $user = User::factory()->create();
        $novo = $this->alert('2', '2026-11-10 12:00:00', ['atribuido' => $user->id]);
        $velho = $this->alert('1', '2026-11-10 08:00:00', ['parent_id' => $novo->id]);
        $neto = $this->alert('3', '2026-11-10 13:00:00', ['parent_id' => $velho->id]);

        $this->artisan('alerts:group-similar')->assertSuccessful();

        $velho->refresh();
        $this->assertNull($velho->parent_id);
        $this->assertSame($user->id, $velho->atribuido, 'responsável deve ser transferido para a nova raiz');
        $this->assertSame($velho->id, $novo->fresh()->parent_id);
        $this->assertSame($velho->id, $neto->fresh()->parent_id);
    }

    public function test_group_alert_unico_promove_alerta_mais_antigo(): void
    {
        $root = $this->alert('2', '2026-11-10 12:00:00');
        $inserido = $this->alert('1', '2026-11-10 06:00:00');

        $result = app(AlertGroupingService::class)->groupAlert($inserido);

        $this->assertSame($inserido->id, $result->id);
        $this->assertSame($inserido->id, $root->fresh()->parent_id);
    }

    public function test_atribuir_a_mim(): void
    {
        $user = User::factory()->create();
        $root = $this->alert('1', '2026-11-10 08:00:00');
        $child = $this->alert('2', '2026-11-10 09:00:00', ['parent_id' => $root->id]);

        $this->actingAs($user)
            ->postJson(route('alerts.assign-me', $child))
            ->assertOk()
            ->assertJson(['atribuido_id' => $user->id]);

        $this->assertSame($user->id, $root->fresh()->atribuido);

        // Não-admin pode ver o alerta atribuído a ele, mas não os outros
        $outroAlerta = $this->alert('9', '2026-11-10 08:00:00', ['service_name' => 'X']);
        $this->actingAs($user)->get(route('alerts.show', $root))->assertOk();
        $this->actingAs($user)->get(route('alerts.show', $outroAlerta))->assertForbidden();
        $this->actingAs($user)->get('/alerts/create')->assertForbidden();
    }

    public function test_comentario_envia_email_ao_responsavel_mas_nao_a_quem_comentou(): void
    {
        Mail::fake();
        Gate::define('admin', fn () => true);

        $responsavel = User::factory()->create();
        $outro = User::factory()->create();
        $alert = $this->alert('1', '2026-11-10 08:00:00', ['atribuido' => $responsavel->id]);

        $this->actingAs($outro)
            ->post(route('alerts.comments.store', $alert), ['comentario' => 'Investigando'])
            ->assertRedirect();

        Mail::assertSent(AlertCommentMail::class, fn ($m) => $m->hasTo($responsavel->email));
        $this->assertSame('Investigando', $alert->comments()->first()->comentario);
        $this->assertSame('pendente', $alert->fresh()->status_resolucao);

        Mail::fake();
        $this->actingAs($responsavel)
            ->post(route('alerts.comments.store', $alert), ['comentario' => 'Eu mesmo'])
            ->assertRedirect();

        Mail::assertNothingSent();
        $this->assertSame(2, $alert->comments()->count());

        $this->actingAs($outro)->get(route('alerts.show', $alert))
            ->assertOk()
            ->assertSee('Investigando')
            ->assertSee('Eu mesmo')
            ->assertSee('Atribuir a mim');
    }

    public function test_home_e_json_publico(): void
    {
        $root = $this->alert('1', '2026-11-10 08:00:00');
        $this->alert('2', '2026-11-10 09:00:00', ['parent_id' => $root->id]);

        $this->get('/')->assertOk()->assertSee('Central de Incidentes');

        Gate::define('admin', fn () => true);
        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertSee('bulkResolveHomeModal')
            ->assertSee('resolveAlertModal');
        $this->getJson('/api/public-alerts')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('ocorrencias', 2)
            ->assertJsonPath('alerts.0.repeticoes', 1)
            ->assertJsonMissingPath('alerts.0.mensagem');
    }

    public function test_tela_de_detalhes_mostra_panorama_do_grupo(): void
    {
        Gate::define('admin', fn () => true);
        $user = User::factory()->create();

        $root = $this->alert('0', now()->subDays(3)->setTime(2, 0)->toDateTimeString(), ['mensagem' => 'Timeout após 30s no host 10.0.0.1']);
        $child = null;
        for ($i = 1; $i <= 12; $i++) {
            $child = $this->alert((string) $i, now()->subDays(3)->setTime(2, 0)->addHours(6 * $i)->toDateTimeString(), [
                'parent_id' => $root->id,
                'mensagem' => $i % 4 === 0 ? 'Disk full on /var' : "Timeout após {$i}s no host 10.0.0.{$i}",
            ]);
        }

        $this->actingAs($user)->get(route('alerts.show', $root))
            ->assertOk()
            ->assertSee('Diagnóstico')
            ->assertSee('Ocorrências por dia')
            ->assertSee('Horários das ocorrências')
            ->assertSee('Padrão periódico')
            ->assertSee('2 variações diferentes')
            ->assertSee('Mensagens em sequência')
            ->assertSeeInOrder(['Disk full on /var', 'Timeout após 11s no host 10.0.0.11', 'Timeout após 10s no host 10.0.0.10']);

        $this->actingAs($user)->get(route('alerts.show', $child))
            ->assertOk()
            ->assertSee('é uma repetição do alerta principal', false);
    }

    public function test_resolver_em_lote_pela_home(): void
    {
        Gate::define('admin', fn () => true);
        $user = User::factory()->create();
        $a = $this->alert('1', '2026-11-10 08:00:00');
        $b = $this->alert('2', '2026-11-10 09:00:00', ['service_name' => 'Outro']);
        $filho = $this->alert('3', '2026-11-10 10:00:00', ['parent_id' => $a->id]);
        $c = $this->alert('4', '2026-11-10 11:00:00', ['service_name' => 'Terceiro']);

        $this->actingAs($user)->from('/')
            ->post(route('alerts.bulk-resolve'), ['comentario' => 'Reiniciado o banco', 'ids' => [$a->id, $b->id]])
            ->assertRedirect('/');

        $this->assertSame('resolvido', $a->fresh()->status_resolucao);
        $this->assertSame('resolvido', $filho->fresh()->status_resolucao);
        $this->assertSame('resolvido', $b->fresh()->status_resolucao);
        $this->assertSame('pendente', $c->fresh()->status_resolucao);
    }

    public function test_comentario_curto_mostra_erro_na_home_e_nao_resolve(): void
    {
        Gate::define('admin', fn () => true);
        $user = User::factory()->create();
        $a = $this->alert('1', '2026-11-10 08:00:00');

        $this->actingAs($user)->from('/')
            ->post(route('alerts.bulk-resolve'), ['comentario' => 'ok', 'ids' => [$a->id]])
            ->assertRedirect('/');

        $this->assertSame('pendente', $a->fresh()->status_resolucao);
        $this->actingAs($user)->get('/')->assertSee('O comentário deve conter pelo menos 3 caracteres.');
    }
}
