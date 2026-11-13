<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Support;

/**
 * Una durata in millisecondi detta come la direbbe una persona: 850 ms,
 * 4,2 s, 3 min 10 s, 2 h 5 min.
 */
final class Duration
{
    /**
     * Accetta anche stringhe: AVG e SUM su MySQL e MariaDB restituiscono
     * decimali come testo.
     */
    public static function format(int|float|string|null $milliseconds): string
    {
        if ($milliseconds === null || $milliseconds === '') {
            return '—';
        }

        $ms = (int) round((float) $milliseconds);

        return match (true) {
            $ms < 1000 => $ms.' ms',
            $ms < 60_000 => number_format($ms / 1000, 1, ',', '.').' s',
            $ms < 3_600_000 => intdiv($ms, 60_000).' min '.intdiv($ms % 60_000, 1000).' s',
            default => intdiv($ms, 3_600_000).' h '.intdiv($ms % 3_600_000, 60_000).' min',
        };
    }
}
