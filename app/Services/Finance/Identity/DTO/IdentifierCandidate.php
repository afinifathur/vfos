<?php

namespace App\Services\Finance\Identity\DTO;

use App\Models\Account;
use DateTimeInterface;

/**
 * Framework-independent domain object representing an identifier candidate.
 */
class IdentifierCandidate
{
    public function __construct(
        public readonly string $uuid,
        public readonly int $accountId,
        public readonly string $type,
        public readonly string $value,
        public readonly string $originalValue,
        public readonly ?string $provider,
        public readonly int $priority,
        public readonly string $source,
        public readonly ?DateTimeInterface $archivedAt,
        public readonly ?Account $account = null
    ) {}
}
