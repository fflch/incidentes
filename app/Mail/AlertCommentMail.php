<?php

namespace App\Mail;

use App\Models\Alert;
use App\Models\AlertComment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertCommentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Alert $alert,
        public AlertComment $comment,
    ) {
    }

    public function envelope(): Envelope
    {
        $servico = $this->alert->service_name ?? $this->alert->type;

        return new Envelope(
            subject: "Novo comentário no alerta: {$servico} (#{$this->alert->id})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alert-comment',
        );
    }
}
