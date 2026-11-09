<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Support\Collection;

class AlertGroupingService
{
    public const DEFAULT_SIMILARITY_THRESHOLD = 90;

    public function groupPendingAlerts(?float $threshold = null): int
    {
        $threshold = $threshold ?? self::DEFAULT_SIMILARITY_THRESHOLD;

        $alerts = Alert::query()
            ->whereNull('parent_id')
            ->where('status_resolucao', 'pendente')
            ->orderBy('data_ocorrencia')
            ->get();

        return $this->groupAlerts($alerts, $threshold);
    }

    public function groupResolvedAlerts(?float $threshold = null): int
    {
        $threshold = $threshold ?? self::DEFAULT_SIMILARITY_THRESHOLD;

        $alerts = Alert::query()
            ->whereNull('parent_id')
            ->where('status_resolucao', 'resolvido')
            ->orderBy('data_ocorrencia')
            ->get();

        return $this->groupAlerts($alerts, $threshold);
    }

    protected function groupAlerts(Collection $alerts, float $threshold): int
    {
        $grouped = 0;

        foreach ($alerts as $candidate) {
            if (!is_null($candidate->parent_id)) {
                continue;
            }

            $parent = $this->findSimilarParent($candidate, $alerts, $threshold);
            if ($parent && $parent->id !== $candidate->id) {
                $this->attachToParent($parent, $candidate);
                $grouped++;
            }
        }

        return $grouped;
    }

    protected function findSimilarParent(Alert $candidate, Collection $alerts, float $threshold): ?Alert
    {
        $bestMatch = null;

        foreach ($alerts as $other) {
            if ($other->id === $candidate->id || !is_null($other->parent_id)) {
                continue;
            }

            if ($other->data_ocorrencia && $candidate->data_ocorrencia && $other->data_ocorrencia->lt($candidate->data_ocorrencia)) {
                continue;
            }

            if (!$this->sameService($candidate, $other)) {
                continue;
            }

            if ($this->similarity($candidate, $other) >= $threshold) {
                if (!$bestMatch || $other->data_ocorrencia && $candidate->data_ocorrencia && $other->data_ocorrencia->lt($bestMatch->data_ocorrencia)) {
                    $bestMatch = $other;
                }
            }
        }

        return $bestMatch;
    }

    protected function attachToParent(Alert $parent, Alert $child): void
    {
        $child->parent_id = $parent->id;
        $child->save();
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

        similar_text($textA, $textB, $percent);

        return (float) $percent;
    }

    protected function normalizeText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s\-_:./\\]+/u', ' ', $text);

        return trim((string) $text);
    }
}
