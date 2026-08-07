<?php

namespace App\Repositories\Finance\Identity;

use App\Models\AccountIdentifier;
use App\Services\Finance\Identity\DTO\IdentifierCandidate;
use Illuminate\Support\Collection;

class EloquentAccountIdentifierRepository implements AccountIdentifierRepositoryInterface
{
    public function findByIdentifier(string $normalizedValue, int $userId): Collection
    {
        $models = AccountIdentifier::active()
            ->whereHas('account', function ($query) use ($userId) {
                $query->withoutGlobalScopes()->where('user_id', $userId);
            })
            ->with(['account' => function ($query) {
                $query->withoutGlobalScopes();
            }])
            ->where(function ($query) use ($normalizedValue) {
                $query->where('value', $normalizedValue);
                
                if (strlen($normalizedValue) >= 4) {
                    $query->orWhereRaw('CHAR_LENGTH(value) >= 4 AND value = RIGHT(?, CHAR_LENGTH(value))', [$normalizedValue])
                          ->orWhereRaw('CHAR_LENGTH(?) >= 4 AND RIGHT(value, CHAR_LENGTH(?)) = ?', [$normalizedValue, $normalizedValue, $normalizedValue]);
                }
            })
            ->get();

        return $models->map(fn ($m) => $this->toCandidate($m));
    }

    /**
     * Find active identifier candidates by multiple normalized values and user ID.
     */
    public function findByIdentifiers(array $normalizedValues, int $userId): Collection
    {
        if (empty($normalizedValues)) {
            return collect();
        }

        $models = AccountIdentifier::active()
            ->whereIn('value', $normalizedValues)
            ->whereHas('account', function ($query) use ($userId) {
                $query->withoutGlobalScopes()->where('user_id', $userId);
            })
            ->with(['account' => function ($query) {
                $query->withoutGlobalScopes();
            }])
            ->get();

        return $models->map(fn ($m) => $this->toCandidate($m));
    }

    /**
     * Register a new identifier.
     */
    public function create(array $attributes): IdentifierCandidate
    {
        $model = AccountIdentifier::create($attributes);
        
        // Eager load the account relation without global scopes
        $model->load(['account' => function ($query) {
            $query->withoutGlobalScopes();
        }]);

        return $this->toCandidate($model);
    }

    /**
     * Soft-deactivate (archive) an identifier by UUID.
     */
    public function archive(string $uuid): bool
    {
        $model = AccountIdentifier::where('uuid', $uuid)->first();
        if ($model) {
            $model->archived_at = now();
            return $model->save();
        }
        return false;
    }

    /**
     * Map Eloquent model to Domain DTO candidate.
     */
    private function toCandidate(AccountIdentifier $model): IdentifierCandidate
    {
        return new IdentifierCandidate(
            uuid: $model->uuid,
            accountId: $model->account_id,
            type: $model->type,
            value: $model->value,
            originalValue: $model->original_value,
            provider: $model->provider,
            priority: $model->priority,
            source: $model->source,
            archivedAt: $model->archived_at,
            account: $model->account
        );
    }
}
