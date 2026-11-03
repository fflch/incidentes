<?php

namespace App\Services;

use App\Mail\AlertAssignedMail;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertNotificationService
{
    /**
     * Envia notificação imediata quando um alerta é atribuído a um responsável
     */
    public static function notifyAssignment(Alert $alert): void
    {
        $alert->loadMissing('atribuidoUser');

        if (!$alert->atribuidoUser || empty($alert->atribuidoUser->email)) {
            return;
        }

        // 1. Notifica o responsável atribuído
        try {
            Mail::to($alert->atribuidoUser->email)->send(new AlertAssignedMail($alert, 'responsavel'));
        } catch (\Throwable $e) {
            Log::error("Erro ao enviar e-mail de atribuição para o responsável {$alert->atribuidoUser->email}: " . $e->getMessage());
        }

        // 2. Notifica a gestão (EMAILS_GESTAO)
        $emailsGestao = config('mail.emails_gestao', []);
        foreach ($emailsGestao as $email) {
            $email = trim($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            try {
                Mail::to($email)->send(new AlertAssignedMail($alert, 'gestao'));
            } catch (\Throwable $e) {
                Log::error("Erro ao enviar e-mail de atribuição para gestão {$email}: " . $e->getMessage());
            }
        }
    }
}

