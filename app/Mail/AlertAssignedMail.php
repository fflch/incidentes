<?php

namespace App\Mail;

use App\Models\Alert;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $ocorrencias;

    public ?Carbon $primeiraOcorrencia;

    public ?Carbon $ultimaOcorrencia;

    public function __construct(
        public Alert $alert,
        public string $tipoDestinatario = 'responsavel' // 'responsavel' ou 'gestao'
    ) {
        $stats = $alert->groupOccurrenceStats();
        $this->ocorrencias = $stats['total'];
        $this->primeiraOcorrencia = $stats['first'];
        $this->ultimaOcorrencia = $stats['last'];
    }

    public function envelope(): Envelope
    {
        $servico = $this->alert->service_name ?? $this->alert->type;
        $prefixo = $this->tipoDestinatario === 'responsavel' ? 'Alerta atribuído a você' : 'Novo alerta atribuído';
        $repeticao = $this->ocorrencias > 1 ? " — {$this->ocorrencias} ocorrências" : '';

        return new Envelope(
            subject: "{$prefixo}: {$servico} (#{$this->alert->id}){$repeticao}",
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

