<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\AssignmentRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplyAssignmentRulesTest extends TestCase
{
    use RefreshDatabase;

    /** Simula um alerta inserido direto via SQL (sem evento "creating"). */
    private function insertViaSql(string $externo, array $extra = []): Alert
    {
        $id = DB::table('alerts')->insertGetId(array_merge([
            'type' => 'zabbix',
            'id_externo' => $externo,
            'service_name' => 'portal-api',
            'data_ocorrencia' => '2026-11-10 08:00:00',
            'mensagem' => 'Falha',
            'status_resolucao' => 'pendente',
            'created_at' => now(),
            'updated_at' => now(),
        ], $extra));

        return Alert::findOrFail($id);
    }

    public function test_atribui_pendentes_sem_responsavel_inseridos_via_sql(): void
    {
        $porNome = User::factory()->create();
        $porTipo = User::factory()->create();
        $manual = User::factory()->create();
        AssignmentRule::create(['match_field' => 'service_name', 'pattern' => 'portal-*', 'user_id' => $porNome->id]);
        AssignmentRule::create(['match_field' => 'type', 'pattern' => 'zabbix', 'user_id' => $porTipo->id]);

        $pendente = $this->insertViaSql('1');
        $repeticao = $this->insertViaSql('2', ['parent_id' => $pendente->id]);
        $porTipoAlert = $this->insertViaSql('3', ['service_name' => 'outro']);
        $semRegra = $this->insertViaSql('4', ['service_name' => 'outro', 'type' => 'prometheus']);
        $jaAtribuido = $this->insertViaSql('5', ['atribuido' => $manual->id]);
        $resolvido = $this->insertViaSql('6', ['status_resolucao' => 'resolvido']);

        $this->artisan('alerts:apply-assignment-rules')
            ->expectsOutputToContain('2 alerta(s)')
            ->assertSuccessful();

        $this->assertSame($porNome->id, $pendente->fresh()->atribuido);
        $this->assertSame($porNome->id, $repeticao->fresh()->atribuido);
        $this->assertSame($porTipo->id, $porTipoAlert->fresh()->atribuido);
        $this->assertNull($semRegra->fresh()->atribuido);
        $this->assertSame($manual->id, $jaAtribuido->fresh()->atribuido);
        $this->assertNull($resolvido->fresh()->atribuido);
    }
}
