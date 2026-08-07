<?php

namespace App\Services\Finance\Diagnostics;

use Illuminate\Support\Facades\Session;

class ImportDiagnostics
{
    private const SESSION_KEY = 'vfos_import_diagnostics';

    public static function start(): void
    {
        Session::put(self::SESSION_KEY, [
            'imported' => 0,
            'duplicates' => 0,
            'skipped' => 0,
            'fallback' => 0,
            'ambiguous' => 0,
            'resolver_errors' => 0,
            'merchant_learned' => 0,
            'auto_categorized' => 0,
            'resolve_times' => [],
            'import_times' => [],
        ]);
    }

    public static function isActive(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    public static function recordImport(string $status, ?float $elapsedMs = null): void
    {
        if (!self::isActive()) return;

        $data = Session::get(self::SESSION_KEY);
        if ($status === 'SUCCESS') {
            $data['imported']++;
        } elseif ($status === 'DUPLICATE') {
            $data['duplicates']++;
        } else {
            $data['resolver_errors']++;
        }

        if ($elapsedMs !== null) {
            $data['import_times'][] = $elapsedMs;
        }

        Session::put(self::SESSION_KEY, $data);
    }

    public static function recordResolution(string $method, ?string $failureCode, float $elapsedMs): void
    {
        if (!self::isActive()) return;

        $data = Session::get(self::SESSION_KEY);
        $data['resolve_times'][] = $elapsedMs;

        if ($method === 'legacy_fallback') {
            $data['fallback']++;
        }
        if ($failureCode === 'AMBIGUOUS') {
            $data['ambiguous']++;
        } elseif ($failureCode !== null) {
            $data['resolver_errors']++;
        }

        Session::put(self::SESSION_KEY, $data);
    }

    public static function increment(string $key): void
    {
        if (!self::isActive()) return;

        $data = Session::get(self::SESSION_KEY);
        if (isset($data[$key])) {
            $data[$key]++;
        }
        Session::put(self::SESSION_KEY, $data);
    }

    public static function getReport(): array
    {
        if (!self::isActive()) {
            return [];
        }

        $data = Session::get(self::SESSION_KEY);

        $resolveTimes = $data['resolve_times'];
        $importTimes = $data['import_times'];

        $avgResolveTime = count($resolveTimes) > 0 ? array_sum($resolveTimes) / count($resolveTimes) : 0.0;
        $avgImportTime = count($importTimes) > 0 ? array_sum($importTimes) / count($importTimes) : 0.0;

        return [
            'imported' => $data['imported'],
            'duplicates' => $data['duplicates'],
            'skipped' => $data['skipped'],
            'fallback' => $data['fallback'],
            'ambiguous' => $data['ambiguous'],
            'resolver_errors' => $data['resolver_errors'],
            'merchant_learned' => $data['merchant_learned'],
            'auto_categorized' => $data['auto_categorized'],
            'avg_resolve_time_ms' => $avgResolveTime,
            'avg_import_time_ms' => $avgImportTime,
        ];
    }

    public static function stop(): array
    {
        $report = self::getReport();
        Session::forget(self::SESSION_KEY);
        return $report;
    }
}
