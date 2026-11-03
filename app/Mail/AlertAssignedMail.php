<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Alert $alert,
        public string $tipoDestinatario = 'responsavel' // 'responsavel' ou 'gestao'
    ) {
    }

    public function envelope(): Envelope
    {
        $servico = $this->alert->service_name ?? $this->alert->type;
        $prefixo = $this->tipoDestinatario === 'responsavel' ? 'Alerta atribuído a você' : 'Novo alerta atribuído';

        return new Envelope(
            subject: "{$prefixo}: {$servico} (#{$this->alert->id})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alert-assigned',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

