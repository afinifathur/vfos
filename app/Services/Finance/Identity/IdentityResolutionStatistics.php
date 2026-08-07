<?php

namespace App\Services\Finance\Identity;

class IdentityResolutionStatistics
{
    private static array $stats = [
        'exact_match' => 0,
        'virtual_account_match' => 0,
        'phone' => 0,
        'alias' => 0,
        'display_name' => 0,
        'legacy_fallback' => 0,
        'no_match' => 0,
        'ambiguous' => 0,
    ];

    private static array $resolutionTimes = [];

    /**
     * Increment a metric counter.
     */
    public static function increment(string $key): void
    {
        if (array_key_exists($key, self::$stats)) {
            self::$stats[$key]++;
        }
    }

    /**
     * Record a resolution time in milliseconds.
     */
    public static function recordResolutionTime(float $elapsedMs): void
    {
        self::$resolutionTimes[] = $elapsedMs;
    }

    /**
     * Get a specific metric count.
     */
    public static function get(string $key): int
    {
        return self::$stats[$key] ?? 0;
    }

    /**
     * Get all raw stats counts.
     */
    public static function getCounts(): array
    {
        return self::$stats;
    }

    /**
     * Calculate and return performance metrics.
     */
    public static function getPerformanceMetrics(): array
    {
        $times = self::$resolutionTimes;
        $count = count($times);

        if ($count === 0) {
            return [
                'average' => 0.0,
                'median' => 0.0,
                'p95' => 0.0,
                'slowest' => 0.0,
                'count' => 0,
            ];
        }

        sort($times);

        $sum = array_sum($times);
        $average = $sum / $count;

        // Median
        if ($count % 2 === 0) {
            $median = ($times[($count / 2) - 1] + $times[$count / 2]) / 2.0;
        } else {
            $median = $times[floor($count / 2)];
        }

        // P95
        $p95Index = (int) ceil($count * 0.95) - 1;
        $p95 = $times[max(0, $p95Index)];

        // Slowest
        $slowest = $times[$count - 1];

        return [
            'average' => round($average, 4),
            'median' => round($median, 4),
            'p95' => round($p95, 4),
            'slowest' => round($slowest, 4),
            'count' => $count,
        ];
    }

    /**
     * Get comprehensive statistics report including performance.
     */
    public static function getAll(): array
    {
        return array_merge(self::$stats, [
            'performance' => self::getPerformanceMetrics()
        ]);
    }

    /**
     * Reset all statistics and recorded resolution times.
     */
    public static function reset(): void
    {
        foreach (self::$stats as $key => $val) {
            self::$stats[$key] = 0;
        }
        self::$resolutionTimes = [];
    }
}
