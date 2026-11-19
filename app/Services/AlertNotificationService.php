<?php

namespace App\Services;

use App\Mail\AlertCommentMail;
use App\Models\Alert;
use App\Models\AlertComment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertNotificationService
{
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
