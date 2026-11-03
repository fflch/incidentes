<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyAlertsSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $alerts,
        public ?User $destinatario = null, // null significa e-mail geral de gestão
        public int $totalGeral = 0,
        public int $totalSemAtribuicao = 0
    ) {
    }

    public function envelope(): Envelope
    {
        $hoje = now()->format('d/m/Y');
        $assunto = $this->destinatario
            ? "Resumo diário: suas pendências em aberto ({$this->alerts->count()}) - {$hoje}"
            : "Resumo diário de alertas pendentes ({$this->totalGeral}) - {$hoje}";

        return new Envelope(
            subject: $assunto,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-summary',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

