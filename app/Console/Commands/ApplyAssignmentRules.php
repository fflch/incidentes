<?php

namespace App\Console\Commands;

use App\Services\AlertAssignmentService;
use Illuminate\Console\Command;

class ApplyAssignmentRules extends Command
{
    protected $signature = 'alerts:apply-assignment-rules';

    protected $description = 'Aplica as regras de atribuição aos alertas pendentes sem responsável (ex.: inseridos direto via SQL).';

    public function handle(AlertAssignmentService $assignmentService): int
    {
        $assigned = $assignmentService->run()['atribuidos'];

        $this->info("{$assigned} alerta(s) pendente(s) atribuído(s) pelas regras.");

        return self::SUCCESS;
    }
}
