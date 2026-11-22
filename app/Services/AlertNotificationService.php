<?php

namespace App\Services;

use App\Mail\AlertCommentMail;
use App\Mail\AlertResolvedMail;
use App\Models\Alert;
use App\Models\AlertComment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertNotificationService
{
    /**
     * Envia e-mail quando alguém comenta em um alerta:
     *  - ao responsável (exceto se foi ele mesmo quem comentou);
     *  - à gestão, se o alerta ainda estiver aberto (pendente).
     */
    public static function notifyComment(Alert $alert, AlertComment $comment): void
    {
        $alert->loadMissing('atribuidoUser');
        $comment->loadMissing('user');

        $responsavel = $alert->atribuidoUser;
        $emailResponsavel = null;

        if ($responsavel && !empty($responsavel->email) && (int) $responsavel->id !== (int) $comment->user_id) {
            $emailResponsavel = $responsavel->email;

            try {
                Mail::to($emailResponsavel)->send(new AlertCommentMail($alert, $comment));
            } catch (\Throwable $e) {
                Log::error("Erro ao enviar e-mail de comentário para {$emailResponsavel}: " . $e->getMessage());
            }
        }

        if ($alert->status_resolucao !== 'pendente') {
            return;
        }

        // Não duplica o e-mail se o responsável também estiver na lista de gestão
        $gestao = array_values(array_filter(
            self::emailsGestao(),
            fn ($email) => strcasecmp($email, (string) $emailResponsavel) !== 0,
        ));

        if (empty($gestao)) {
            return;
        }

        try {
            Mail::to($gestao)->send(new AlertCommentMail($alert, $comment, paraGestao: true));
        } catch (\Throwable $e) {
            Log::error('Erro ao enviar e-mail de comentário para a gestão: ' . $e->getMessage());
        }
    }

    /**
     * Avisa a gestão (um único e-mail por grupo) que o alerta principal foi resolvido.
     */
    public static function notifyResolved(Alert $alert): void
    {
        $root = $alert->groupRoot();

        if ($root->status_resolucao !== 'resolvido') {
            return;
        }

        $gestao = self::emailsGestao();

        if (empty($gestao)) {
            return;
        }

        $root->loadMissing(['atribuidoUser', 'resolvidoPor']);

        try {
            Mail::to($gestao)->send(new AlertResolvedMail($root));
        } catch (\Throwable $e) {
            Log::error("Erro ao enviar e-mail de resolução do alerta #{$root->id} para a gestão: " . $e->getMessage());
        }
    }

    /**
     * @return string[]
     */
    private static function emailsGestao(): array
    {
        return array_values(array_unique(array_filter(
            (array) config('mail.emails_gestao', []),
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL),
        )));
    }
}
