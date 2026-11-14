<?php

namespace App\Services;

use App\Mail\AlertAssignedMail;
use App\Mail\AlertCommentMail;
use App\Models\Alert;
use App\Models\AlertComment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertNotificationService
{
    /**
     * Envia notificação imediata quando um alerta é atribuído a um responsável
     */
    public static function notifyAssignment(Alert $alert, bool $notifyAssignee = true): void
    {
        $alert->loadMissing('atribuidoUser');

        if (!$alert->atribuidoUser || empty($alert->atribuidoUser->email)) {
            return;
        }

        // 1. Notifica o responsável atribuído (não é necessário quando ele mesmo se atribuiu)
        if ($notifyAssignee) {
            try {
                Mail::to($alert->atribuidoUser->email)->send(new AlertAssignedMail($alert, 'responsavel'));
            } catch (\Throwable $e) {
                Log::error("Erro ao enviar e-mail de atribuição para o responsável {$alert->atribuidoUser->email}: " . $e->getMessage());
            }
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

    /**
     * Envia e-mail ao responsável pelo alerta quando alguém comenta nele.
     * Não envia nada se quem comentou é o próprio responsável.
     */
    public static function notifyComment(Alert $alert, AlertComment $comment): void
    {
        $alert->loadMissing('atribuidoUser');
        $comment->loadMissing('user');

        $responsavel = $alert->atribuidoUser;

        if (!$responsavel || empty($responsavel->email)) {
            return;
        }

        if ((int) $responsavel->id === (int) $comment->user_id) {
            return;
        }

        try {
            Mail::to($responsavel->email)->send(new AlertCommentMail($alert, $comment));
        } catch (\Throwable $e) {
            Log::error("Erro ao enviar e-mail de comentário para {$responsavel->email}: " . $e->getMessage());
        }
    }
}
