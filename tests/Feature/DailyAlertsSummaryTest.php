<?php

namespace Tests\Feature;

use App\Mail\DailyAlertsSummaryMail;
use App\Models\Alert;
use App\Models\AssignmentRule;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DailyAlertsSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function alert(string $externo, array $extra = []): Alert
    {
        return Alert::create(array_merge([
            'type' => 'zabbix',
            'id_externo' => $externo,
            'service_name' => 'portal',
            'data_ocorrencia' => '2026-11-10 08:00:00',
            'mensagem' => "Falha {$externo}",
            'status_resolucao' => 'pendente',
        ], $extra));
    }

    public function test_criar_alerta_atribuido_por_regra_nao_envia_email(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        AssignmentRule::create(['match_field' => 'service_name', 'pattern' => 'portal', 'user_id' => $user->id]);

        $alert = $this->alert('1');

        $this->assertSame($user->id, $alert->atribuido);
        Mail::assertNothingSent();
    }

    public function test_um_email_por_responsavel_com_pendencias(): void
    {
        Mail::fake();
        config(['mail.emails_gestao' => []]);

        $comDuas = User::factory()->create();
        $comUma = User::factory()->create();
        $soResolvido = User::factory()->create();
        User::factory()->create(); // sem nenhum alerta

        $root = $this->alert('1', ['atribuido' => $comDuas->id]);
        $this->alert('2', ['atribuido' => $comDuas->id, 'parent_id' => $root->id]); // repetição
        $this->alert('3', ['atribuido' => $comDuas->id]);
        $this->alert('4', ['atribuido' => $comUma->id]);
        $this->alert('5', ['atribuido' => $soResolvido->id, 'status_resolucao' => 'resolvido']);

        $this->artisan('alerts:daily-summary')->assertSuccessful();

        Mail::assertSent(DailyAlertsSummaryMail::class, 2);
        $this->assertCount(1, Mail::sent(DailyAlertsSummaryMail::class, fn ($m) => $m->hasTo($comDuas->email)));
        Mail::assertSent(DailyAlertsSummaryMail::class, fn ($m) => $m->hasTo($comDuas->email)
            && $m->alerts->pluck('id_externo')->sort()->values()->all() === ['1', '3']);
        Mail::assertSent(DailyAlertsSummaryMail::class, fn ($m) => $m->hasTo($comUma->email) && $m->alerts->count() === 1);
        Mail::assertNotSent(DailyAlertsSummaryMail::class, fn ($m) => $m->hasTo($soResolvido->email));

        $mail = new DailyAlertsSummaryMail(
            Alert::withCount('children')->whereNull('parent_id')->where('atribuido', $comDuas->id)->get(),
            $comDuas,
        );
        $html = $mail->render();
        $this->assertStringContainsString('2x', $html);
        $this->assertStringContainsString('Falha 1', $html);
    }

    public function test_sem_pendencias_ninguem_recebe(): void
    {
        Mail::fake();
        config(['mail.emails_gestao' => ['gestao@example.com']]);
        $user = User::factory()->create();
        $this->alert('1', ['atribuido' => $user->id, 'status_resolucao' => 'resolvido']);

        $this->artisan('alerts:daily-summary')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_resumo_agendado_ao_meio_dia(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command ?? '', 'alerts:daily-summary'));

        $this->assertNotNull($event);
        $this->assertSame('0 12 * * *', $event->expression);
    }
}
