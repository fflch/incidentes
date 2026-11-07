<?php

namespace App\Console\Commands;

use App\Mail\DailyAlertsSummaryMail;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyAlertsSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:daily-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia resumo diário de alertas pendentes para os responsáveis e e-mails de gestão';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando envio do resumo diário de alertas pendentes...');

        $pendingAlerts = Alert::with('atribuidoUser')
            ->where('status_resolucao', 'pendente')
            ->orderByDesc('data_ocorrencia')
            ->get();

        $totalGeral = $pendingAlerts->count();
        $totalSemAtribuicao = $pendingAlerts->whereNull('atribuido')->count();

        $this->line("Total de alertas pendentes: {$totalGeral} (sem atribuição: {$totalSemAtribuicao})");

        if ($totalGeral === 0) {
            $this->info('Nenhum alerta pendente. Nenhum e-mail será enviado.');
            return Command::SUCCESS;
        }

        // 1. Enviar para cada usuário com alertas atribuídos
        $alertsByUser = $pendingAlerts->whereNotNull('atribuido')->groupBy('atribuido');

        foreach ($alertsByUser as $userId => $userAlerts) {
            $user = User::find($userId);
            if ($user && !empty($user->email)) {
                try {
                    Mail::to($user->email)->send(new DailyAlertsSummaryMail(
                        $userAlerts,
                        $user,
                        $totalGeral,
                        $totalSemAtribuicao
                    ));
                    $this->info("Resumo enviado para {$user->name} ({$user->email}) - {$userAlerts->count()} pendências");
                } catch (\Throwable $e) {
                    $this->error("Falha ao enviar resumo para {$user->email}: " . $e->getMessage());
                }
            }
        }

        // 2. Enviar para a lista de e-mails de gestão
        $emailsGestao = config('mail.emails_gestao', []);

        if (!empty($emailsGestao)) {
            foreach ($emailsGestao as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                try {
                    Mail::to($email)->send(new DailyAlertsSummaryMail(
                        $pendingAlerts,
                        null,
                        $totalGeral,
                        $totalSemAtribuicao
                    ));
                    $this->info("Resumo geral de gestão enviado para: {$email}");
                } catch (\Throwable $e) {
                    $this->error("Falha ao enviar para gestão {$email}: " . $e->getMessage());
                }
            }
        } else {
            $this->warn('Nenhum e-mail de gestão configurado em EMAILS_GESTAO.');
        }

        $this->info('Processo de envio do resumo diário concluído com sucesso.');
        return Command::SUCCESS;
    }
}

