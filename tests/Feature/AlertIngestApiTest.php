<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\AssignmentRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AlertIngestApiTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'chave-de-teste';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.alerts_api.key' => self::KEY]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'type' => 'laravel',
            'id_externo' => 'abc123',
            'service_name' => 'portal.fflch.usp.br',
            'data_ocorrencia' => '2026-11-20 10:15:00',
            'mensagem' => "Erro de App (Laravel 500):\nSQLSTATE[HY000] Connection refused",
        ], $extra);
    }

    public function test_rejeita_sem_chave_ou_com_chave_errada(): void
    {
        $this->postJson('/api/alerts', $this->payload())->assertUnauthorized();
        $this->postJson('/api/alerts', $this->payload(), ['X-API-Key' => 'errada'])->assertUnauthorized();
        $this->assertSame(0, Alert::count());
    }

    public function test_api_fechada_quando_chave_nao_configurada(): void
    {
        config(['services.alerts_api.key' => null]);

        $this->postJson('/api/alerts', $this->payload(), ['X-API-Key' => ''])->assertUnauthorized();
        $this->assertSame(0, Alert::count());
    }

    public function test_cria_alerta_pendente_com_header_ou_bearer(): void
    {
        $this->postJson('/api/alerts', $this->payload(), ['X-API-Key' => self::KEY])
            ->assertCreated()
            ->assertJson(['created' => true]);

        $this->withToken(self::KEY)
            ->postJson('/api/alerts', $this->payload(['id_externo' => 'outro', 'mensagem' => 'Algo completamente diferente']))
            ->assertCreated();

        $alert = Alert::where('id_externo', 'abc123')->first();
        $this->assertSame('pendente', $alert->status_resolucao);
        $this->assertSame('2026-11-20 10:15:00', $alert->data_ocorrencia->format('Y-m-d H:i:s'));
        $this->assertSame(2, Alert::count());
    }

    public function test_reenviar_mesmo_alerta_nao_duplica(): void
    {
        $first = $this->postJson('/api/alerts', $this->payload(), ['X-API-Key' => self::KEY])->assertCreated();

        $this->postJson('/api/alerts', $this->payload(['mensagem' => 'mudou']), ['X-API-Key' => self::KEY])
            ->assertOk()
            ->assertJson(['created' => false, 'id' => $first->json('id')]);

        $this->assertSame(1, Alert::count());
        $this->assertStringContainsString('Connection refused', Alert::first()->mensagem);
    }

    public function test_data_com_fuso_e_convertida_para_horario_local(): void
    {
        $this->postJson('/api/alerts', $this->payload(['data_ocorrencia' => '2026-11-20T13:15:00+00:00']), ['X-API-Key' => self::KEY])
            ->assertCreated();

        $this->assertSame('2026-11-20 10:15:00', Alert::first()->data_ocorrencia->format('Y-m-d H:i:s'));
    }

    public function test_aplica_regra_de_atribuicao_e_agrupa_sem_enviar_email(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        AssignmentRule::create(['match_field' => 'type', 'pattern' => 'laravel', 'user_id' => $user->id]);

        $first = $this->postJson('/api/alerts', $this->payload(), ['X-API-Key' => self::KEY])->assertCreated();
        $this->postJson('/api/alerts', $this->payload(['id_externo' => 'def456', 'data_ocorrencia' => '2026-11-20 11:00:00']), ['X-API-Key' => self::KEY])
            ->assertCreated()
            ->assertJson(['parent_id' => $first->json('id'), 'atribuido' => $user->id]);

        $this->assertSame($user->id, Alert::find($first->json('id'))->atribuido);
        Mail::assertNothingSent();
    }

    public function test_valida_campos_obrigatorios(): void
    {
        $this->postJson('/api/alerts', ['type' => 'laravel'], ['X-API-Key' => self::KEY])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_externo', 'mensagem']);
    }
}
