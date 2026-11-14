<?php

namespace App\Services;

use App\Models\Alert;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Monta um panorama de todas as ocorrências de um grupo de alertas
 * (raiz + repetições) para ajudar no diagnóstico do problema.
 */
class AlertDiagnosticsService
{
    public const DAILY_CHART_MAX_DAYS = 30;
    public const TABLE_LIMIT = 300;

    private const WEEKDAYS = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];
    private const WEEKDAYS_FULL = [1 => 'segunda-feira', 2 => 'terça-feira', 3 => 'quarta-feira', 4 => 'quinta-feira', 5 => 'sexta-feira', 6 => 'sábado', 7 => 'domingo'];

    public function forGroup(Alert $alert): array
    {
        $root = $alert->groupRoot();
        $now = now();

        $occurrences = Alert::query()
            ->where(fn ($q) => $q->where('id', $root->id)->orWhere('parent_id', $root->id))
            ->whereNotNull('data_ocorrencia')
            ->orderBy('data_ocorrencia')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'type', 'id_externo', 'data_ocorrencia', 'mensagem']);

        $times = $occurrences->pluck('data_ocorrencia');
        $first = $times->first();
        $last = $times->last();
        $total = $occurrences->count();

        $intervals = $this->intervalsInMinutes($times);
        $avgInterval = $intervals->isNotEmpty() ? $intervals->avg() : null;
        $medianInterval = $intervals->isNotEmpty() ? $intervals->median() : null;

        $last24h = $times->filter(fn ($t) => $t->gte($now->copy()->subDay()))->count();
        $prev24h = $times->filter(fn ($t) => $t->lt($now->copy()->subDay()) && $t->gte($now->copy()->subDays(2)))->count();
        $last1h = $times->filter(fn ($t) => $t->gte($now->copy()->subHour()))->count();

        $heatmap = $this->heatmap($times);
        $daily = $this->dailySeries($times, $now);
        $byDay = $this->byDay($occurrences);
        $signatures = $this->messageSignatures($occurrences);
        $burst = $this->biggestBurst($times, 60);

        $rows = $this->occurrenceRows($occurrences, $alert->id);

        return [
            'root' => $root,
            'total' => $total,
            'first' => $first,
            'last' => $last,
            'span_minutes' => $first && $last ? (int) $first->diffInMinutes($last) : 0,
            'open_minutes' => $first && $root->status_resolucao === 'pendente' ? (int) $first->diffInMinutes($now) : null,
            'avg_interval' => $avgInterval,
            'median_interval' => $medianInterval,
            'last_1h' => $last1h,
            'last_24h' => $last24h,
            'prev_24h' => $prev24h,
            'heatmap' => $heatmap,
            'weekdays' => self::WEEKDAYS,
            'daily' => $daily,
            'by_day' => $byDay,
            'signatures' => $signatures,
            'burst' => $burst,
            'rows' => $rows->take(-self::TABLE_LIMIT)->reverse()->values(),
            'rows_truncated' => $rows->count() > self::TABLE_LIMIT,
            'insights' => $this->insights(
                $root, $total, $first, $last, $now, $medianInterval, $intervals,
                $last24h, $prev24h, $heatmap, $signatures, $burst
            ),
        ];
    }

    public static function humanMinutes(?float $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        $minutes = (int) round($minutes);

        if ($minutes < 1) {
            return 'menos de 1 min';
        }
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        $restMin = $minutes % 60;
        if ($hours < 24) {
            return $restMin ? "{$hours}h {$restMin}min" : "{$hours}h";
        }

        $days = intdiv($hours, 24);
        $restH = $hours % 24;

        return $restH ? "{$days}d {$restH}h" : "{$days}d";
    }

    protected function intervalsInMinutes(Collection $times): Collection
    {
        $intervals = collect();
        $prev = null;

        foreach ($times as $t) {
            if ($prev) {
                $intervals->push($prev->diffInSeconds($t) / 60);
            }
            $prev = $t;
        }

        return $intervals;
    }

    /**
     * Matriz [dia da semana ISO 1..7][hora 0..23] => quantidade.
     */
    protected function heatmap(Collection $times): array
    {
        $grid = [];
        foreach (array_keys(self::WEEKDAYS) as $d) {
            $grid[$d] = array_fill(0, 24, 0);
        }

        foreach ($times as $t) {
            $grid[$t->dayOfWeekIso][$t->hour]++;
        }

        $hourTotals = array_fill(0, 24, 0);
        $dayTotals = [];
        foreach ($grid as $d => $hours) {
            $dayTotals[$d] = array_sum($hours);
            foreach ($hours as $h => $c) {
                $hourTotals[$h] += $c;
            }
        }

        $max = 0;
        foreach ($grid as $hours) {
            $max = max($max, ...$hours);
        }

        return [
            'grid' => $grid,
            'hour_totals' => $hourTotals,
            'day_totals' => $dayTotals,
            'max' => $max,
            'max_hour_total' => max($hourTotals) ?: 0,
        ];
    }

    /**
     * Série diária com zeros, do primeiro dia até hoje (no máximo os últimos N dias).
     */
    protected function dailySeries(Collection $times, CarbonInterface $now): array
    {
        if ($times->isEmpty()) {
            return ['days' => [], 'max' => 0, 'truncated' => false];
        }

        $counts = $times->countBy(fn ($t) => $t->format('Y-m-d'));

        $end = $now->copy()->startOfDay();
        $start = $times->first()->copy()->startOfDay();
        $truncated = false;

        if ($start->diffInDays($end) >= self::DAILY_CHART_MAX_DAYS) {
            $start = $end->copy()->subDays(self::DAILY_CHART_MAX_DAYS - 1);
            $truncated = true;
        }

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $days[] = [
                'date' => $d->copy(),
                'label' => $d->format('d/m'),
                'weekday' => self::WEEKDAYS[$d->dayOfWeekIso],
                'count' => (int) ($counts[$key] ?? 0),
                'is_weekend' => $d->dayOfWeekIso >= 6,
            ];
        }

        return [
            'days' => $days,
            'max' => max(array_column($days, 'count')) ?: 0,
            'truncated' => $truncated,
        ];
    }

    /**
     * Agrupamento por dia, do mais recente ao mais antigo, com todos os horários.
     */
    protected function byDay(Collection $occurrences): Collection
    {
        return $occurrences
            ->groupBy(fn ($o) => $o->data_ocorrencia->format('Y-m-d'))
            ->map(function (Collection $items) {
                $first = $items->first()->data_ocorrencia;

                return [
                    'date' => $first->copy()->startOfDay(),
                    'weekday' => self::WEEKDAYS[$first->dayOfWeekIso],
                    'count' => $items->count(),
                    'first' => $first,
                    'last' => $items->last()->data_ocorrencia,
                    'times' => $items->map(fn ($o) => ['id' => $o->id, 'time' => $o->data_ocorrencia->format('H:i')])->values(),
                ];
            })
            ->sortByDesc(fn ($d) => $d['date']->getTimestamp())
            ->values();
    }

    /**
     * Agrupa mensagens que só diferem em números, IDs, IPs, hashes etc.
     */
    protected function messageSignatures(Collection $occurrences): Collection
    {
        return $occurrences
            ->groupBy(fn ($o) => $this->signature((string) $o->mensagem))
            ->map(fn (Collection $items, $sig) => [
                'signature' => $sig,
                'example' => (string) $items->last()->mensagem,
                'count' => $items->count(),
                'first' => $items->first()->data_ocorrencia,
                'last' => $items->last()->data_ocorrencia,
            ])
            ->sortByDesc('count')
            ->values();
    }

    protected function signature(string $message): string
    {
        $s = mb_strtolower(trim($message));
        $s = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', '<uuid>', $s);
        $s = preg_replace('/\b\d{1,3}(\.\d{1,3}){3}(:\d+)?\b/', '<ip>', $s);
        $s = preg_replace('/\b[0-9a-f]{12,}\b/', '<hash>', $s);
        $s = preg_replace('/\d+/', '<n>', $s);
        $s = preg_replace('/\s+/', ' ', $s);

        return mb_substr($s, 0, 300);
    }

    /**
     * Maior número de ocorrências dentro de uma janela de $windowMinutes.
     */
    protected function biggestBurst(Collection $times, int $windowMinutes): ?array
    {
        $ts = $times->values();
        $stamps = $ts->map(fn ($t) => $t->getTimestamp())->all();
        $window = $windowMinutes * 60;
        $best = null;
        $j = 0;

        for ($i = 0; $i < count($stamps); $i++) {
            while ($stamps[$i] - $stamps[$j] > $window) {
                $j++;
            }
            $count = $i - $j + 1;
            if (!$best || $count > $best['count']) {
                $best = ['count' => $count, 'start' => $ts[$j], 'end' => $ts[$i]];
            }
        }

        return $best && $best['count'] > 1 ? $best : null;
    }

    protected function occurrenceRows(Collection $occurrences, int $currentId): Collection
    {
        $prev = null;

        return $occurrences->values()->map(function (Alert $o, int $index) use (&$prev, $currentId) {
            $row = [
                'n' => $index + 1,
                'id' => $o->id,
                'is_root' => is_null($o->parent_id),
                'is_current' => $o->id === $currentId,
                'id_externo' => $o->id_externo,
                'type' => $o->type,
                'date' => $o->data_ocorrencia,
                'weekday' => self::WEEKDAYS[$o->data_ocorrencia->dayOfWeekIso],
                'since_previous' => $prev ? $prev->diffInSeconds($o->data_ocorrencia) / 60 : null,
                'signature' => $this->signature((string) $o->mensagem),
                'message' => (string) $o->mensagem,
            ];
            $prev = $o->data_ocorrencia;

            return $row;
        });
    }

    protected function insights(
        Alert $root,
        int $total,
        ?CarbonInterface $first,
        ?CarbonInterface $last,
        CarbonInterface $now,
        ?float $median,
        Collection $intervals,
        int $last24h,
        int $prev24h,
        array $heatmap,
        Collection $signatures,
        ?array $burst,
    ): array {
        $out = [];

        if ($total <= 1) {
            $out[] = ['icon' => 'fa-info-circle', 'tone' => 'secondary', 'text' => 'Ocorrência única até agora — ainda não há repetições para identificar um padrão.'];

            return $out;
        }

        // Situação atual: ainda está acontecendo?
        $sinceLast = $last->diffInMinutes($now);
        if ($root->status_resolucao === 'pendente' && $median !== null) {
            if ($sinceLast <= max(2 * $median, 15)) {
                $out[] = ['icon' => 'fa-fire', 'tone' => 'danger', 'text' => 'Ainda ocorrendo: a última ocorrência foi há ' . self::humanMinutes($sinceLast) . ', dentro do intervalo típico de ' . self::humanMinutes($median) . '.'];
            } else {
                $out[] = ['icon' => 'fa-pause-circle', 'tone' => 'success', 'text' => 'Sem novas ocorrências há ' . self::humanMinutes($sinceLast) . ' (o intervalo típico é ' . self::humanMinutes($median) . '). Pode ter parado sozinho.'];
            }
        }

        // Periodicidade (intervalos muito regulares sugerem rotina agendada)
        if ($intervals->count() >= 3 && $median > 0) {
            $mean = $intervals->avg();
            $std = sqrt($intervals->map(fn ($i) => ($i - $mean) ** 2)->avg());
            if ($mean > 0 && $std / $mean < 0.25) {
                $out[] = ['icon' => 'fa-sync-alt', 'tone' => 'primary', 'text' => 'Padrão periódico: ocorre a cada ~' . self::humanMinutes($median) . ' com bastante regularidade — verifique rotinas agendadas (cron, backup, jobs).'];
            }
        }

        // Tendência 24h
        if ($last24h > 0 || $prev24h > 0) {
            if ($prev24h === 0) {
                $out[] = ['icon' => 'fa-arrow-up', 'tone' => 'warning', 'text' => "{$last24h} ocorrência(s) nas últimas 24h, nenhuma nas 24h anteriores — problema novo ou que voltou."];
            } elseif ($last24h >= $prev24h * 1.5) {
                $out[] = ['icon' => 'fa-arrow-up', 'tone' => 'danger', 'text' => "Piorando: {$last24h} ocorrências nas últimas 24h contra {$prev24h} nas 24h anteriores."];
            } elseif ($last24h <= $prev24h * 0.5) {
                $out[] = ['icon' => 'fa-arrow-down', 'tone' => 'success', 'text' => "Melhorando: {$last24h} ocorrências nas últimas 24h contra {$prev24h} nas 24h anteriores."];
            }
        }

        // Concentração por horário (melhor janela de 3h consecutivas)
        if ($total >= 5) {
            $hours = $heatmap['hour_totals'];
            $bestStart = 0;
            $bestSum = -1;
            for ($h = 0; $h < 24; $h++) {
                $sum = $hours[$h] + $hours[($h + 1) % 24] + $hours[($h + 2) % 24];
                if ($sum > $bestSum) {
                    $bestSum = $sum;
                    $bestStart = $h;
                }
            }
            $pct = (int) round(100 * $bestSum / $total);
            if ($pct >= 50) {
                $end = ($bestStart + 3) % 24;
                $out[] = ['icon' => 'fa-clock', 'tone' => 'primary', 'text' => sprintf('%d%% das ocorrências acontecem entre %02dh e %02dh — compare com o que roda nesse horário (carga, backups, deploys).', $pct, $bestStart, $end)];
            }
        }

        // Concentração por dia da semana
        if ($total >= 7) {
            $days = $heatmap['day_totals'];
            arsort($days);
            $topDay = array_key_first($days);
            $pct = (int) round(100 * $days[$topDay] / $total);
            if ($pct >= 40) {
                $out[] = ['icon' => 'fa-calendar-day', 'tone' => 'primary', 'text' => "{$pct}% das ocorrências caem em " . self::WEEKDAYS_FULL[$topDay] . '.'];
            }

            $weekend = ($heatmap['day_totals'][6] ?? 0) + ($heatmap['day_totals'][7] ?? 0);
            if ($weekend === 0) {
                $out[] = ['icon' => 'fa-briefcase', 'tone' => 'secondary', 'text' => 'Nunca ocorreu em fim de semana — provavelmente ligado a uso/carga em dias úteis.'];
            }
        }

        if ($burst && $burst['count'] >= 3) {
            $out[] = ['icon' => 'fa-bolt', 'tone' => 'warning', 'text' => "Maior rajada: {$burst['count']} ocorrências em 1 hora, em " . $burst['start']->format('d/m/Y \à\s H:i') . '.'];
        }

        if ($signatures->count() > 1) {
            $out[] = ['icon' => 'fa-code-branch', 'tone' => 'secondary', 'text' => $signatures->count() . ' variações diferentes da mensagem de erro neste grupo — veja a seção "Variações da mensagem".'];
        }

        return $out;
    }
}
