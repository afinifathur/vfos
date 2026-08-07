<?php

namespace App\Repositories\Finance\Identity;

use App\Services\Finance\Identity\DTO\IdentifierCandidate;
use Illuminate\Support\Collection;

interface AccountIdentifierRepositoryInterface
{
    /**
     * Find active identifier candidates by a normalized value and user ID.
     *
     * @param string $normalizedValue
     * @param int $userId
     * @return Collection<IdentifierCandidate>
     */
    public function findByIdentifier(string $normalizedValue, int $userId): Collection;

    /**
     * Find active identifier candidates by multiple normalized values and user ID.
     *
     * @param array<string> $normalizedValues
     * @param int $userId
     * @return Collection<IdentifierCandidate>
     */
    public function findByIdentifiers(array $normalizedValues, int $userId): Collection;

    /**
     * Register a new identifier.
     *
     * @param array $attributes
     * @return IdentifierCandidate
     */
    public function create(array $attributes): IdentifierCandidate;

    /**
     * Soft-deactivate (archive) an identifier by UUID.
     *
     * @param string $uuid
     * @return bool
     */
    public function archive(string $uuid): bool;
}
