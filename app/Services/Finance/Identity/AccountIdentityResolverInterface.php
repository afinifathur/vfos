<?php

namespace App\Services\Finance\Identity;

use App\Services\Finance\Identity\DTO\ResolutionResult;
use App\Services\Finance\Identity\DTO\IdentifierCandidate;

interface AccountIdentityResolverInterface
{
    /**
     * Resolves a query identifier to a concrete account.
     *
     * @param string $identifier The raw input identifier
     * @param string|null $provider The provider to isolate lookups (e.g. BCA, Mandiri)
     * @param int $userId The ID of the owner user
     * @param string $mode Operating mode: 'STRICT' or 'BEST_EFFORT'
     * @return ResolutionResult
     */
    public function resolve(string $identifier, ?string $provider, int $userId, string $mode = 'STRICT'): ResolutionResult;

    /**
     * Resolves multiple query identifiers at once.
     *
     * @param array<string> $identifiers
     * @param string|null $provider
     * @param int $userId
     * @param string $mode
     * @return array<string, ResolutionResult>
     */
    public function resolveMany(array $identifiers, ?string $provider, int $userId, string $mode = 'STRICT'): array;

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
    ): IdentifierCandidate;

    /**
     * Helper to archive an account identifier by UUID.
     */
    public function archiveIdentifier(string $uuid): bool;

    /**
     * Helper to validate a proposed identifier value for a given type.
     */
    public function validateIdentifier(string $type, string $value): bool;
}
