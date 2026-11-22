<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertResolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $repeticoes;
    public int $totalOcorrencias;
    public ?string $tempoAberto;
    public $primeiraOcorrencia;
    public $ultimaOcorrencia;

    /**
     * @param Alert $alert alerta principal (raiz) do grupo
     */
    public function __construct(public Alert $alert)
    {
        $stats = $alert->groupOccurrenceStats();

        $this->totalOcorrencias = $stats['total'];
        $this->repeticoes = max(0, $stats['total'] - 1);
        $this->primeiraOcorrencia = $stats['first'];
        $this->ultimaOcorrencia = $stats['last'];

        $resolvidoEm = $alert->dataResolucao();
        $this->tempoAberto = ($stats['first'] && $resolvidoEm)
            ? Alert::formatarDuracao(max(0, (int) $stats['first']->diffInMinutes($resolvidoEm, false)))
            : null;
    }

    public function envelope(): Envelope
    {
        $servico = $this->alert->service_name ?? $this->alert->type;

        return new Envelope(
            subject: "Alerta resolvido: {$servico} (#{$this->alert->id})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alert-resolved',
        );
    }
}
