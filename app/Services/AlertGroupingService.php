<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Support\Facades\DB;

class AlertGroupingService
{
    public const DEFAULT_SIMILARITY_THRESHOLD = 80;

    public function groupPendingAlerts(?float $threshold = null): int
    {
        return $this->groupByStatus('pendente', $threshold ?? self::DEFAULT_SIMILARITY_THRESHOLD);
    }

    public function groupResolvedAlerts(?float $threshold = null): int
    {
        return $this->groupByStatus('resolvido', $threshold ?? self::DEFAULT_SIMILARITY_THRESHOLD);
    }

    /**
     * Agrupa um único alerta (ex.: recém-cadastrado pela interface) a um grupo
     * existente similar, com o mesmo status. Retorna a raiz do grupo, ou null se
     * não encontrou nenhum similar.
     */
    public function groupAlert(Alert $alert, ?float $threshold = null): ?Alert
    {
        $threshold = $threshold ?? self::DEFAULT_SIMILARITY_THRESHOLD;

        if (!$alert->isRoot()) {
            return $alert->groupRoot();
        }

        $roots = Alert::query()
            ->whereNull('parent_id')
            ->whereKeyNot($alert->id)
            ->where('status_resolucao', $alert->status_resolucao)
            ->orderBy('data_ocorrencia')
            ->orderBy('id')
            ->get();

        foreach ($roots as $root) {
            if ($this->sameService($alert, $root) && $this->similarity($alert, $root) >= $threshold) {
                $this->attachToParent($root, $alert);

                // Se o alerta novo for mais antigo que a raiz atual, ele vira a raiz.
                return $this->ensureOldestIsRoot($root->fresh());
            }
        }

        return null;
    }

    /**
     * Corrige grupos existentes:
     *  - achata hierarquias aninhadas (neto -> raiz);
     *  - garante que a raiz de cada grupo seja sempre o alerta mais antigo.
     * Retorna quantos grupos tiveram a raiz trocada.
     */
    public function normalizeGroups(): int
    {
        $this->flattenNestedGroups();

        $fixed = 0;

        Alert::query()
            ->whereNull('parent_id')
            ->whereHas('children')
            ->orderBy('id')
            ->get()
            ->each(function (Alert $root) use (&$fixed) {
                if ($this->ensureOldestIsRoot($root)->id !== $root->id) {
                    $fixed++;
                }
            });

        return $fixed;
    }

    /**
     * Percorre as raízes em ordem cronológica (mais antiga primeiro). Cada raiz
     * é comparada apenas com as raízes mais antigas que já foram mantidas; ao
     * encontrar uma similar, é agregada à mais antiga delas. Assim, o alerta
     * principal de um grupo é sempre o mais velho.
     */
    protected function groupByStatus(string $status, float $threshold): int
    {
        $roots = Alert::query()
            ->whereNull('parent_id')
            ->where('status_resolucao', $status)
            ->orderBy('data_ocorrencia')
            ->orderBy('id')
            ->get();

        /** @var Alert[] $kept */
        $kept = [];
        $grouped = 0;

        foreach ($roots as $candidate) {
            $parent = null;

            foreach ($kept as $existing) {
                if ($this->sameService($candidate, $existing) && $this->similarity($candidate, $existing) >= $threshold) {
                    $parent = $existing;
                    break; // $kept está em ordem cronológica: o primeiro similar é o mais antigo
                }
            }

            if ($parent) {
                $this->attachToParent($parent, $candidate);
                $grouped++;
            } else {
                $kept[] = $candidate;
            }
        }

        return $grouped;
    }

    /**
     * Agrega $child (e todos os seus filhos) ao grupo de $parent.
     */
    protected function attachToParent(Alert $parent, Alert $child): void
    {
        DB::transaction(function () use ($parent, $child) {
            Alert::query()
                ->where('parent_id', $child->id)
                ->update(['parent_id' => $parent->id]);

            $child->parent_id = $parent->id;
            $child->save();
        });
    }

    /**
     * Se algum filho for mais antigo que a raiz, promove o mais antigo a raiz,
     * transferindo para ele o estado do grupo (responsável, status, resolução).
     */
    protected function ensureOldestIsRoot(Alert $root): Alert
    {
        $members = $root->children()->get()->push($root);

        $oldest = $members
            ->sort(function (Alert $a, Alert $b) {
                return [$a->data_ocorrencia?->getTimestamp() ?? PHP_INT_MAX, $a->id]
                    <=> [$b->data_ocorrencia?->getTimestamp() ?? PHP_INT_MAX, $b->id];
            })
            ->first();

        if ($oldest->id === $root->id) {
            return $root;
        }

        DB::transaction(function () use ($root, $oldest) {
            $oldest->parent_id = null;
            $oldest->atribuido = $root->atribuido ?? $oldest->atribuido;
            $oldest->status_resolucao = $root->status_resolucao;
            $oldest->comentario = $root->comentario ?? $oldest->comentario;
            $oldest->resolvido_por_id = $root->resolvido_por_id;
            $oldest->resolvido_em = $root->resolvido_em;
            $oldest->save();

            Alert::query()
                ->where(function ($q) use ($root) {
                    $q->where('parent_id', $root->id)->orWhere('id', $root->id);
                })
                ->whereKeyNot($oldest->id)
                ->update(['parent_id' => $oldest->id]);
        });

        return $oldest->fresh();
    }

    /**
     * Grupos antigos podiam ter netos (filho de um alerta que depois virou filho).
     * Reaponta todos para a raiz real.
     */
    protected function flattenNestedGroups(): void
    {
        do {
            $nested = Alert::query()
                ->whereNotNull('parent_id')
                ->whereHas('parent', fn ($q) => $q->whereNotNull('parent_id'))
                ->with('parent')
                ->get();

            foreach ($nested as $alert) {
                $alert->parent_id = $alert->parent->parent_id;
                $alert->save();
            }
        } while ($nested->isNotEmpty());
    }

    protected function sameService(Alert $a, Alert $b): bool
    {
        $serviceA = trim((string) ($a->service_name ?? $a->type ?? ''));
        $serviceB = trim((string) ($b->service_name ?? $b->type ?? ''));

        return $serviceA !== '' && strtolower($serviceA) === strtolower($serviceB);
    }

    protected function similarity(Alert $a, Alert $b): float
    {
        $textA = $this->normalizeText($a->mensagem ?? '');
        $textB = $this->normalizeText($b->mensagem ?? '');

        if ($textA === '' && $textB === '') {
            return 100.0;
        }

        if ($textA === '' || $textB === '') {
            return 0.0;
        }

        if (strtolower($textA) === strtolower($textB)) {
            return 100.0;
        }

        similar_text($textA, $textB, $percent);
        $percent = (float) $percent;

        $commonWordsA = $this->wordOverlapScore($textA, $textB);
        $commonWordsB = $this->wordOverlapScore($textB, $textA);
        $wordOverlap = max($commonWordsA, $commonWordsB);

        return max($percent, $wordOverlap * 100);
    }

    protected function normalizeText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s\-_:.]+/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', trim((string) $text));

        return trim((string) $text);
    }

    protected function wordOverlapScore(string $textA, string $textB): float
    {
        $wordsA = array_filter(array_unique(preg_split('/\s+/', $textA) ?: []));
        $wordsB = array_filter(array_unique(preg_split('/\s+/', $textB) ?: []));

        if ($wordsA === [] || $wordsB === []) {
            return 0.0;
        }

        $matches = 0;
        foreach ($wordsA as $wordA) {
            foreach ($wordsB as $wordB) {
                if ($wordA === $wordB) {
                    $matches++;
                    break;
                }
            }
        }

        $total = count($wordsA);
        return $total > 0 ? min(1.0, $matches / $total) : 0.0;
    }
}
