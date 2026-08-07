<?php

namespace App\Services\Finance\Identity;

use Illuminate\Support\Facades\Log;

class ImportResolutionTrace
{
    private static array $traces = [];

    /**
     * Add a diagnostic trace for an import.
     */
    public static function add(
        ?string $googleMessageId,
        string $originalIdentifier,
        string $normalizedIdentifier,
        ?string $provider,
        ?int $matchedAccountId,
        float $confidence,
        string $resolutionMethod,
        bool $fallbackUsed,
        float $elapsedMs,
        array $resolverTrace = [],
        ?string $failureCode = null
    ): void {
        // Determine severity level based on final outcome
        if ($failureCode === 'AMBIGUOUS') {
            $level = 'ERROR';
        } elseif ($matchedAccountId === null) {
            $level = 'CRITICAL';
        } elseif ($fallbackUsed) {
            $level = 'WARNING';
        } else {
            $level = 'INFO';
        }

        $traceEntry = [
            'level' => $level,
            'google_message_id' => $googleMessageId,
            'original_identifier' => $originalIdentifier,
            'normalized_identifier' => $normalizedIdentifier,
            'provider' => $provider,
            'matched_account_id' => $matchedAccountId,
            'confidence' => $confidence,
            'resolution_method' => $resolutionMethod,
            'fallback_used' => $fallbackUsed,
            'elapsed_ms' => $elapsedMs,
            'resolver_trace' => $resolverTrace,
            'failure_code' => $failureCode,
        ];

        self::$traces[] = $traceEntry;

        // Log to application logger for runtime observability
        $logMessage = sprintf(
            "[IdentityResolutionTrace] [%s] Message: %s | Input: %s | Resolved Account ID: %s | Method: %s | Fallback: %s | Time: %.2fms | Failure: %s",
            $level,
            $googleMessageId ?? 'N/A',
            $originalIdentifier,
            $matchedAccountId ?? 'None',
            $resolutionMethod,
            $fallbackUsed ? 'YES' : 'NO',
            $elapsedMs,
            $failureCode ?? 'None'
        );

        switch ($level) {
            case 'INFO':
                Log::info($logMessage);
                break;
            case 'WARNING':
                Log::warning($logMessage);
                break;
            case 'ERROR':
                Log::error($logMessage);
                break;
            case 'CRITICAL':
                Log::critical($logMessage);
                break;
        }
    }

    /**
     * Get all traces.
     */
    public static function getTraces(): array
    {
        return self::$traces;
    }

    /**
     * Get the last trace recorded.
     */
    public static function getLastTrace(): ?array
    {
        return empty(self::$traces) ? null : self::$traces[count(self::$traces) - 1];
    }

    /**
     * Reset recorded traces in memory.
     */
    public static function reset(): void
    {
        self::$traces = [];
    }
}
