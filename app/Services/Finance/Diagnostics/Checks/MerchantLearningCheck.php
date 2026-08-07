<?php

namespace App\Services\Finance\Diagnostics\Checks;

use App\Services\Finance\Diagnostics\Contracts\IntegrityCheckInterface;
use App\Services\Finance\Diagnostics\DTO\IntegrityFinding;
use App\Services\Finance\Diagnostics\DTO\IntegrityFindingCollection;
use App\Models\MerchantAlias;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;

class MerchantLearningCheck implements IntegrityCheckInterface
{
    public function run(?int $userId = null): IntegrityFindingCollection
    {
        $findings = new IntegrityFindingCollection();

        $aliases = MerchantAlias::with(['category', 'subcategory']);

        if ($userId) {
            // Scope by categories belonging to the user
            $aliases->where(function ($query) use ($userId) {
                $query->whereHas('category', fn($q) => $q->where('user_id', $userId))
                    ->orWhereNull('category_id');
            });
        }

        $aliasRecords = $aliases->get();

        foreach ($aliasRecords as $alias) {
            // 1. Alias with no Category
            if (is_null($alias->category_id)) {
                $findings->add(new IntegrityFinding(
                    category: 'Merchant Learning',
                    severity: 'WARNING',
                    issue: 'Duplicate/Uncategorized Merchant Alias',
                    affectedObject: "Merchant Key: '{$alias->merchant_key}'",
                    currentValue: 'NULL',
                    expectedValue: 'Valid Category ID',
                    difference: null,
                    rootCause: 'Merchant learned during import but category was not automatically or manually assigned.',
                    recommendation: 'Assign a category to this merchant alias to enable auto-categorization for future imports.'
                ));
                continue;
            }

            // 2. Broken Category Reference
            if (!$alias->category) {
                $findings->add(new IntegrityFinding(
                    category: 'Merchant Learning',
                    severity: 'ERROR',
                    issue: 'Broken Category Reference',
                    affectedObject: "Merchant Key: '{$alias->merchant_key}'",
                    currentValue: $alias->category_id,
                    expectedValue: 'Valid Category ID',
                    difference: null,
                    rootCause: 'The associated Category was deleted from the database.',
                    recommendation: 'Re-assign this alias to an existing category.'
                ));
                continue;
            }

            // 3. Broken Subcategory Reference
            if ($alias->subcategory_id && !$alias->subcategory) {
                $findings->add(new IntegrityFinding(
                    category: 'Merchant Learning',
                    severity: 'ERROR',
                    issue: 'Broken Subcategory Reference',
                    affectedObject: "Merchant Key: '{$alias->merchant_key}'",
                    currentValue: $alias->subcategory_id,
                    expectedValue: 'Valid Subcategory ID',
                    difference: null,
                    rootCause: 'The associated Subcategory was deleted from the database.',
                    recommendation: 'Re-assign or clear this alias subcategory.'
                ));
                continue;
            }

            // 4. Subcategory category parent mismatch
            if ($alias->subcategory && $alias->subcategory->category_id !== $alias->category_id) {
                $findings->add(new IntegrityFinding(
                    category: 'Merchant Learning',
                    severity: 'ERROR',
                    issue: 'Subcategory Parent Mismatch',
                    affectedObject: "Merchant Key: '{$alias->merchant_key}'",
                    currentValue: "Subcategory belongs to category ID {$alias->subcategory->category_id}",
                    expectedValue: "Should belong to category ID {$alias->category_id}",
                    difference: null,
                    rootCause: 'Category configuration changed or inconsistent manual DB modification.',
                    recommendation: 'Align the subcategory mapping or clear subcategory.'
                ));
            }
        }

        return $findings;
    }
}
