<?php

namespace App\Console\Commands;

use App\Services\AlertIgnoreService;
use Illuminate\Console\Command;

class ApplyIgnoreRules extends Command
{
    protected $signature = 'alerts:apply-ignore-rules';

    protected $description = 'Ignora os alertas pendentes cuja mensagem contém um texto das regras para ignorar (ex.: inseridos direto via SQL).';

    public function handle(AlertIgnoreService $ignoreService): int
    {
        $ignored = $ignoreService->run()['ignorados'];

        $this->info("{$ignored} alerta(s) pendente(s) ignorado(s) pelas regras.");

        return self::SUCCESS;
    }
}
