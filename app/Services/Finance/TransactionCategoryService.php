<?php

namespace App\Services\Finance;

use App\Models\TransactionItem;
use App\Models\Category;
use App\Models\Subcategory;
use App\Services\Finance\MerchantLearningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionCategoryService
{
    public function __construct(
        private readonly MerchantLearningService $merchantLearningService
    ) {}

    /**
     * Update the category and subcategory of a transaction item.
     *
     * @param TransactionItem $item
     * @param int $categoryId
     * @param int|null $subcategoryId
     * @throws ValidationException
     */
    public function updateCategoryAndSubcategory(TransactionItem $item, int $categoryId, ?int $subcategoryId): void
    {
        // 1. Validate Category exists and is active
        $category = Category::where('id', $categoryId)->where('is_active', true)->first();
        if (!$category) {
            throw ValidationException::withMessages([
                'category_id' => ['The selected category is invalid or inactive.'],
            ]);
        }

        // 2. Validate Subcategory (if provided) belongs to Category and is active
        if ($subcategoryId !== null) {
            $subcategory = Subcategory::where('id', $subcategoryId)
                ->where('category_id', $categoryId)
                ->where('is_active', true)
                ->first();
            if (!$subcategory) {
                throw ValidationException::withMessages([
                    'subcategory_id' => ['The selected subcategory is invalid or does not belong to the selected category.'],
                ]);
            }
        }

        // 3. Perform database operations atomically
        DB::transaction(function () use ($item, $categoryId, $subcategoryId) {
            $transaction = $item->transaction;

            // Trigger Merchant Learning BEFORE updating the item, so it compares the original state
            if ($transaction) {
                $this->merchantLearningService->learnFromEdit($transaction, $categoryId, $subcategoryId);
            }

            // Update TransactionItem category and subcategory
            $item->update([
                'category_id' => $categoryId,
                'subcategory_id' => $subcategoryId,
            ]);
        });
    }
}
