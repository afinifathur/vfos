<?php

namespace App\Services\Finance;

use App\Models\Transaction;
use App\Models\MerchantAlias;
use App\Services\Finance\MerchantNormalizer;

class MerchantLearningService
{
    public function __construct(
        private readonly MerchantNormalizer $normalizer,
    ) {}

    /**
     * Learn from transaction category edits.
     */
    public function learnFromEdit(Transaction $transaction, int $newCategoryId, ?int $newSubcategoryId): void
    {
        $firstItem = $transaction->transactionItems()->first();
        $oldCategoryId = $firstItem?->category_id;
        $oldSubcategoryId = $firstItem?->subcategory_id;

        // If category/subcategory did not change, nothing to learn
        if ($oldCategoryId === $newCategoryId && $oldSubcategoryId === $newSubcategoryId) {
            return;
        }

        // If transaction has no merchant_key, we cannot learn
        if (empty($transaction->merchant_key)) {
            return;
        }

        $key = $transaction->merchant_key;
        $rawMerchant = $transaction->raw_merchant ?? $key;

        $alias = MerchantAlias::where('merchant_key', $key)->first();
        if ($alias) {
            $alias->update([
                'category_id'    => $newCategoryId,
                'subcategory_id' => $newSubcategoryId,
                'is_locked'      => true, // Lock the categorization based on manual user decision
            ]);
        } else {
            MerchantAlias::create([
                'merchant_key'   => $key,
                'raw_merchant'   => $rawMerchant,
                'display_name'   => $this->normalizer->normalizeMerchant($rawMerchant),
                'category_id'    => $newCategoryId,
                'subcategory_id' => $newSubcategoryId,
                'is_locked'      => true,
                'usage_count'    => 1,
                'confidence'     => 1.0,
                'first_seen_at'  => now(),
                'last_seen_at'   => now(),
            ]);
        }
    }
}
