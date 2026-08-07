<?php

namespace App\Services\Finance\Identity;

use App\Models\Account;
use App\Models\AccountIdentifier;
use App\Repositories\Finance\Identity\AccountIdentifierRepositoryInterface;
use App\Services\Finance\Identity\DTO\IdentifierCandidate;
use App\Services\Finance\Identity\DTO\ResolutionResult;
use App\Services\Finance\Identity\Policies\ConfidencePolicy;
use App\Services\Finance\Identity\IdentityResolutionStatistics;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AccountIdentityResolver implements AccountIdentityResolverInterface
{
    public function __construct(
        protected AccountIdentifierRepositoryInterface $repository
    ) {}

    /**
     * Resolves a query identifier to a concrete account.
     */
    public function resolve(string $identifier, ?string $provider, int $userId, string $mode = 'STRICT'): ResolutionResult
    {
        $startTime = microtime(true);
        $trace = [];
        $trace[] = "Input: \"{$identifier}\"";
        
        $normAlpha = $this->normalizeAlphanumeric($identifier);
        $normPhone = $this->normalizePhone($identifier);
        $normEmail = $this->normalizeEmail($identifier);

        $trace[] = "Normalized: Alphanumeric=\"{$normAlpha}\", Phone=\"{$normPhone}\", Email=\"{$normEmail}\"";

        if ($normAlpha === '') {
            $trace[] = "Normalized identifier is empty.";
            $elapsedMs = (microtime(true) - $startTime) * 1000.0;
            IdentityResolutionStatistics::recordResolutionTime($elapsedMs);
            return new ResolutionResult(
                account: null,
                originalIdentifier: $identifier,
                normalizedIdentifier: '',
                identifierType: null,
                matchedBy: null,
                confidence: 0.0,
                provider: $provider,
                resolutionMethod: 'no_match',
                trace: $trace,
                failureCode: 'NO_IDENTIFIER'
            );
        }

        // Query candidates matching any normalized forms
        $candidates = $this->repository->findByIdentifier($normAlpha, $userId);

        if ($candidates->isEmpty()) {
            // Check if there is an archived identifier matching the value for diagnostic purposes
            $hasArchived = AccountIdentifier::where('value', $normAlpha)
                ->whereHas('account', function ($q) use ($userId) {
                    $q->withoutGlobalScopes()->where('user_id', $userId);
                })
                ->whereNotNull('archived_at')
                ->exists();

            $failureCode = $hasArchived ? 'ARCHIVED_IDENTIFIER' : 'NO_MATCH';
            $trace[] = $hasArchived 
                ? "Matching active candidate not found, but archived identifier matches value."
                : "No matching identifier candidates found in repository.";

            $elapsedMs = (microtime(true) - $startTime) * 1000.0;
            IdentityResolutionStatistics::recordResolutionTime($elapsedMs);

            return new ResolutionResult(
                account: null,
                originalIdentifier: $identifier,
                normalizedIdentifier: $normAlpha,
                identifierType: null,
                matchedBy: null,
                confidence: 0.0,
                provider: $provider,
                resolutionMethod: 'no_match',
                trace: $trace,
                failureCode: $failureCode
            );
        }

        $scoredCandidates = [];
        $providerMismatchesCount = 0;

        foreach ($candidates as $candidate) {
            // Provider isolation check
            if ($provider !== null && $candidate->provider !== null && strcasecmp($candidate->provider, $provider) !== 0) {
                $trace[] = "Candidate [UUID: {$candidate->uuid}, Type: {$candidate->type}, Value: {$candidate->value}] ignored due to provider mismatch (Candidate: {$candidate->provider}, Query: {$provider})";
                $providerMismatchesCount++;
                continue;
            }

            // Determine matching score and method
            $score = 0.0;
            $method = 'no_match';
            $matchedBy = null;

            // 1. Exact matches
            if ($candidate->value === $normAlpha || 
                ($candidate->type === 'phone' && $candidate->value === $normPhone) ||
                ($candidate->type === 'email' && $candidate->value === $normEmail)) {
                
                $score = ConfidencePolicy::getConfidence($candidate->type);
                $method = 'exact_match';
                $matchedBy = $candidate->value;
            } 
            // 2. Suffix matches (for account numbers / virtual accounts)
            else if (in_array($candidate->type, ['account_number', 'virtual_account'], true)) {
                $candidateVal = $candidate->value;
                $candidateLen = strlen($candidateVal);
                $queryLen = strlen($normAlpha);

                // Case A: Stored value is suffix of query (query is longer, e.g. Virtual Account matches Base Account Suffix)
                if ($candidateLen >= 4 && str_ends_with($normAlpha, $candidateVal)) {
                    $score = ConfidencePolicy::CONFIDENCE_VIRTUAL_ACCOUNT_SUFFIX;
                    $method = 'virtual_account_match';
                    $matchedBy = $candidateVal;
                }
                // Case B: Query is suffix of stored value (query is shorter, e.g. partial account number lookup)
                else if ($queryLen >= 4 && str_ends_with($candidateVal, $normAlpha)) {
                    $score = ConfidencePolicy::CONFIDENCE_PARTIAL_SUFFIX;
                    $method = 'alias_match'; // Suffix-partial match treated as alias/partial confidence
                    $matchedBy = $candidateVal;
                }
            }

            if ($score > 0.0) {
                $scoredCandidates[] = [
                    'candidate' => $candidate,
                    'score' => $score,
                    'method' => $method,
                    'matchedBy' => $matchedBy,
                ];
                $trace[] = "Candidate matched: UUID={$candidate->uuid}, Type={$candidate->type}, Value={$candidate->value}, Score={$score}, Method={$method}";
            }
        }

        if (empty($scoredCandidates)) {
            $failureCode = ($providerMismatchesCount > 0) ? 'PROVIDER_MISMATCH' : 'NO_MATCH';
            $trace[] = "No candidates passed matching and validation criteria.";

            $elapsedMs = (microtime(true) - $startTime) * 1000.0;
            IdentityResolutionStatistics::recordResolutionTime($elapsedMs);

            return new ResolutionResult(
                account: null,
                originalIdentifier: $identifier,
                normalizedIdentifier: $normAlpha,
                identifierType: null,
                matchedBy: null,
                confidence: 0.0,
                provider: $provider,
                resolutionMethod: 'no_match',
                trace: $trace,
                failureCode: $failureCode
            );
        }

        // Sort candidates: highest confidence first, then highest priority first
        usort($scoredCandidates, function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score']; // descending confidence
            }
            if ($a['candidate']->priority !== $b['candidate']->priority) {
                return $b['candidate']->priority <=> $a['candidate']->priority; // descending priority
            }
            return strcmp($a['candidate']->uuid, $b['candidate']->uuid); // deterministic fallback
        });

        $best = $scoredCandidates[0];
        
        // Check for ambiguity (only if they point to different accounts)
        if (count($scoredCandidates) > 1) {
            $second = $scoredCandidates[1];
            if ($best['score'] === $second['score'] && 
                $best['candidate']->priority === $second['candidate']->priority &&
                $best['candidate']->accountId !== $second['candidate']->accountId) {
                
                $trace[] = "Ambiguity detected: Multiple candidates match with same confidence ({$best['score']}) and priority ({$best['candidate']->priority}) pointing to different accounts (IDs: {$best['candidate']->accountId} vs {$second['candidate']->accountId}). Resolving to null.";
                
                $elapsedMs = (microtime(true) - $startTime) * 1000.0;
                IdentityResolutionStatistics::recordResolutionTime($elapsedMs);

                return new ResolutionResult(
                    account: null,
                    originalIdentifier: $identifier,
                    normalizedIdentifier: $normAlpha,
                    identifierType: null,
                    matchedBy: null,
                    confidence: 0.0,
                    provider: $provider,
                    resolutionMethod: 'ambiguous',
                    trace: $trace,
                    failureCode: 'AMBIGUOUS'
                );
            }
        }

        // Validate mode thresholds
        $finalScore = $best['score'];
        if ($mode === 'STRICT' && $finalScore < ConfidencePolicy::THRESHOLD_STRICT) {
            $trace[] = "Rejected best candidate (Confidence {$finalScore}) under STRICT mode (threshold: " . ConfidencePolicy::THRESHOLD_STRICT . ").";
            
            $elapsedMs = (microtime(true) - $startTime) * 1000.0;
            IdentityResolutionStatistics::recordResolutionTime($elapsedMs);

            return new ResolutionResult(
                account: null,
                originalIdentifier: $identifier,
                normalizedIdentifier: $normAlpha,
                identifierType: $best['candidate']->type,
                matchedBy: $best['matchedBy'],
                confidence: 0.0,
                provider: $provider,
                resolutionMethod: 'low_confidence',
                trace: $trace,
                failureCode: 'LOW_CONFIDENCE'
            );
        }

        $trace[] = "Resolved Account ID: {$best['candidate']->accountId} (\"{$best['candidate']->account->name}\") with confidence {$finalScore} via {$best['method']}.";

        $elapsedMs = (microtime(true) - $startTime) * 1000.0;
        IdentityResolutionStatistics::recordResolutionTime($elapsedMs);

        return new ResolutionResult(
            account: $best['candidate']->account,
            originalIdentifier: $identifier,
            normalizedIdentifier: $normAlpha,
            identifierType: $best['candidate']->type,
            matchedBy: $best['matchedBy'],
            confidence: $finalScore,
            provider: $best['candidate']->provider,
            resolutionMethod: $best['method'],
            trace: $trace,
            failureCode: null
        );
    }

    /**
     * Resolves multiple query identifiers at once.
     */
    public function resolveMany(array $identifiers, ?string $provider, int $userId, string $mode = 'STRICT'): array
    {
        $results = [];
        foreach ($identifiers as $identifier) {
            $results[$identifier] = $this->resolve($identifier, $provider, $userId, $mode);
        }
        return $results;
    }

    /**
     * Helper to register a new account identifier in the system.
     */
    public function registerIdentifier(
        int $accountId,
        string $type,
        string $value,
        ?string $provider,
        int $priority = 0,
        string $source = 'manual'
    ): IdentifierCandidate {
        
        $norm = $this->normalizeForType($type, $value);

        return $this->repository->create([
            'account_id' => $accountId,
            'type' => $type,
            'value' => $norm,
            'original_value' => $value,
            'provider' => $provider,
            'priority' => $priority,
            'source' => $source,
        ]);
    }

    /**
     * Helper to archive an account identifier by UUID.
     */
    public function archiveIdentifier(string $uuid): bool
    {
        return $this->repository->archive($uuid);
    }

    /**
     * Helper to validate a proposed identifier value for a given type.
     */
    public function validateIdentifier(string $type, string $value): bool
    {
        if (trim($value) === '') {
            return false;
        }

        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'phone':
                return preg_match('/^\+?[0-9\-\s]{5,20}$/', $value) === 1;
            case 'account_number':
            case 'virtual_account':
                return preg_match('/^[a-zA-Z0-9\-\s]{4,30}$/', $value) === 1;
            case 'alias':
            case 'display_name':
                return strlen(trim($value)) >= 2;
            default:
                return false;
        }
    }

    // --- Helper Normalizations ---

    private function normalizeAlphanumeric(string $value): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $value));
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        return $digits;
    }

    private function normalizeEmail(string $value): string
    {
        return strtolower(trim($value));
    }

    private function normalizeForType(string $type, string $value): string
    {
        switch ($type) {
            case 'email':
                return $this->normalizeEmail($value);
            case 'phone':
                return $this->normalizePhone($value);
            default:
                return $this->normalizeAlphanumeric($value);
        }
    }
}
