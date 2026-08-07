<?php

namespace App\Services\Finance\Kernel;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Budget;
use App\Models\TransactionItem;
use App\Models\Investment;
use App\Models\Receivable;
use App\Models\Debt;
use App\Models\Asset;
use App\Services\Finance\Kernel\DTO\AccountSummary;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialKernelService
{
    /**
     * Records external income into an account.
     */
    public function createIncome(
        int $accountId,
        float $amount,
        string $date,
        ?string $description = null,
        array $metadata = []
    ): Transaction {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount for income must be positive.');
        }

        return DB::transaction(function () use ($accountId, $amount, $date, $description, $metadata) {
            $account = Account::findOrFail($accountId);
            
            $categoryId = $metadata['category_id'] ?? null;
            if (!$categoryId) {
                $category = Category::where('type', 'income')->first();
                $categoryId = $category ? $category->id : null;
            }

            if (!$categoryId) {
                throw new InvalidArgumentException('No suitable category found for income.');
            }

            $transaction = Transaction::create([
                'account_id'       => $accountId,
                'to_account_id'    => null,
                'type'             => 'income',
                'transaction_date' => $date,
                'total_amount'     => $amount,
                'notes'            => $description,
                'external_id'      => $metadata['external_id'] ?? null,
                'merchant_key'     => $metadata['merchant_key'] ?? null,
                'raw_merchant'     => $metadata['raw_merchant'] ?? null,
            ]);

            $transaction->transactionItems()->create([
                'category_id'    => $categoryId,
                'subcategory_id' => $metadata['subcategory_id'] ?? null,
                'description'    => $metadata['item_description'] ?? $description,
                'amount'         => $amount,
            ]);

            return $transaction;
        });
    }

    /**
     * Records external expense from an account.
     */
    public function createExpense(
        int $accountId,
        float $amount,
        string $date,
        ?string $description = null,
        array $metadata = []
    ): Transaction {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount for expense must be positive.');
        }

        return DB::transaction(function () use ($accountId, $amount, $date, $description, $metadata) {
            $account = Account::findOrFail($accountId);

            $categoryId = $metadata['category_id'] ?? null;
            if (!$categoryId) {
                $category = Category::where('type', 'expense')->first();
                $categoryId = $category ? $category->id : null;
            }

            if (!$categoryId) {
                throw new InvalidArgumentException('No suitable category found for expense.');
            }

            $transaction = Transaction::create([
                'account_id'       => $accountId,
                'to_account_id'    => null,
                'type'             => 'expense',
                'transaction_date' => $date,
                'total_amount'     => $amount,
                'notes'            => $description,
                'external_id'      => $metadata['external_id'] ?? null,
                'merchant_key'     => $metadata['merchant_key'] ?? null,
                'raw_merchant'     => $metadata['raw_merchant'] ?? null,
            ]);

            $transaction->transactionItems()->create([
                'category_id'    => $categoryId,
                'subcategory_id' => $metadata['subcategory_id'] ?? null,
                'description'    => $metadata['item_description'] ?? $description,
                'amount'         => $amount,
            ]);

            return $transaction;
        });
    }

    /**
     * Records internal transfer from one account to another.
     */
    public function createTransfer(
        int $fromAccountId,
        int $toAccountId,
        float $amount,
        string $date,
        ?string $description = null,
        array $metadata = []
    ): Transaction {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount for transfer must be positive.');
        }
        if ($fromAccountId === $toAccountId) {
            throw new InvalidArgumentException('Source and destination accounts must be different.');
        }

        return DB::transaction(function () use ($fromAccountId, $toAccountId, $amount, $date, $description, $metadata) {
            $fromAccount = Account::findOrFail($fromAccountId);
            $toAccount   = Account::findOrFail($toAccountId);

            $categoryId = $metadata['category_id'] ?? null;
            if (!$categoryId) {
                $category = Category::where('name', 'like', '%transfer%')->first()
                    ?? Category::where('type', 'expense')->first();
                $categoryId = $category ? $category->id : null;
            }

            if (!$categoryId) {
                throw new InvalidArgumentException('No suitable category found for transfer.');
            }

            $transaction = Transaction::create([
                'account_id'       => $fromAccountId,
                'to_account_id'    => $toAccountId,
                'type'             => 'transfer',
                'transaction_date' => $date,
                'total_amount'     => $amount,
                'notes'            => $description,
                'external_id'      => $metadata['external_id'] ?? null,
                'merchant_key'     => $metadata['merchant_key'] ?? null,
                'raw_merchant'     => $metadata['raw_merchant'] ?? null,
            ]);

            $transaction->transactionItems()->create([
                'category_id'    => $categoryId,
                'subcategory_id' => $metadata['subcategory_id'] ?? null,
                'description'    => $metadata['item_description'] ?? $description,
                'amount'         => $amount,
            ]);

            return $transaction;
        });
    }

    /**
     * Adjusts the balance of an account (valuation adjustment/correction).
     */
    public function createAdjustment(
        int $accountId,
        float $amount,
        string $date,
        ?string $description = null,
        array $metadata = []
    ): Transaction {
        if ($amount >= 0) {
            return $this->createIncome(
                $accountId,
                $amount,
                $date,
                $description ?? 'Balance adjustment (Credit)',
                $metadata
            );
        } else {
            return $this->createExpense(
                $accountId,
                abs($amount),
                $date,
                $description ?? 'Balance adjustment (Debit)',
                $metadata
            );
        }
    }

    /**
     * Stores a new transaction with one or more items.
     */
    public function recordTransaction(array $headerData, array $itemsData): Transaction
    {
        return DB::transaction(function () use ($headerData, $itemsData) {
            $totalAmount = collect($itemsData)->sum('amount');

            $accountId = $headerData['account_id'];
            $toAccountId = $headerData['to_account_id'] ?? null;
            $type = $headerData['type'];
            $date = $headerData['transaction_date'];
            $notes = $headerData['notes'] ?? null;

            Account::findOrFail($accountId);
            if ($type === 'transfer' && $toAccountId) {
                Account::findOrFail($toAccountId);
            }

            $transaction = Transaction::create([
                'account_id'       => $accountId,
                'to_account_id'    => $toAccountId,
                'type'             => $type,
                'transaction_date' => $date,
                'total_amount'     => $totalAmount,
                'notes'            => $notes,
            ]);

            foreach ($itemsData as $itemData) {
                $transaction->transactionItems()->create($itemData);
            }

            $transaction->refreshAccountBalances();

            return $transaction;
        });
    }

    /**
     * Updates an existing transaction and its items, handling category learning and balance updates.
     */
    public function reviseTransaction(int $transactionId, array $headerData, array $itemsData): Transaction
    {
        return DB::transaction(function () use ($transactionId, $headerData, $itemsData) {
            $transaction = Transaction::findOrFail($transactionId);

            $oldAccountId = $transaction->account_id;
            $oldToAccountId = $transaction->to_account_id;
            $oldType = $transaction->type;

            $totalAmount = collect($itemsData)->sum('amount');

            $primaryItem = $transaction->primaryItem;
            if (!$primaryItem) {
                $primaryItem = $transaction->transactionItems()->create([
                    'category_id' => $itemsData[0]['category_id'],
                    'subcategory_id' => $itemsData[0]['subcategory_id'] ?? null,
                    'amount' => $itemsData[0]['amount'],
                    'description' => $itemsData[0]['description'] ?? null,
                ]);
            }

            $newCategoryId = (int) $itemsData[0]['category_id'];
            $newSubcategoryId = isset($itemsData[0]['subcategory_id']) ? (int) $itemsData[0]['subcategory_id'] : null;

            app(\App\Services\Finance\TransactionCategoryService::class)->updateCategoryAndSubcategory(
                $primaryItem,
                $newCategoryId,
                $newSubcategoryId
            );

            $transaction->update(array_merge($headerData, [
                'total_amount' => $totalAmount,
            ]));

            $transaction->transactionItems()->where('id', '!=', $primaryItem->id)->delete();

            foreach ($itemsData as $index => $itemData) {
                if ($index === 0) {
                    $primaryItem->update([
                        'description' => $itemData['description'] ?? null,
                        'amount' => $itemData['amount'],
                    ]);
                } else {
                    $transaction->transactionItems()->create($itemData);
                }
            }

            $this->refreshAccountBalance($oldAccountId);
            if ($oldType === 'transfer' && $oldToAccountId) {
                $this->refreshAccountBalance($oldToAccountId);
            }

            $transaction->refresh();
            $transaction->refreshAccountBalances();

            return $transaction;
        });
    }

    /**
     * Updates only the category and subcategory of a transaction's primary item,
     * triggering merchant learning but bypassing other ledger revision logic.
     */
    public function updateCategory(int $transactionId, int $categoryId, ?int $subcategoryId): void
    {
        DB::transaction(function () use ($transactionId, $categoryId, $subcategoryId) {
            $transaction = Transaction::findOrFail($transactionId);

            $primaryItem = $transaction->primaryItem;
            if (!$primaryItem) {
                $primaryItem = $transaction->transactionItems()->create([
                    'category_id' => $categoryId,
                    'subcategory_id' => $subcategoryId,
                    'amount' => $transaction->total_amount,
                ]);
            }

            app(\App\Services\Finance\TransactionCategoryService::class)->updateCategoryAndSubcategory(
                $primaryItem,
                $categoryId,
                $subcategoryId
            );
        });
    }

    /**
     * Deletes a transaction and all its items, and updates the accounts' balances.
     */
    public function removeTransaction(int $transactionId): void
    {
        DB::transaction(function () use ($transactionId) {
            $transaction = Transaction::findOrFail($transactionId);
            
            $accountId = $transaction->account_id;
            $toAccountId = $transaction->to_account_id;
            $type = $transaction->type;

            $transaction->transactionItems()->delete();
            $transaction->delete();

            $this->refreshAccountBalance($accountId);
            if ($type === 'transfer' && $toAccountId) {
                $this->refreshAccountBalance($toAccountId);
            }
        });
    }

    /**
     * Forces balance recalculation and caching for a single account.
     */
    public function refreshAccountBalance(int $accountId): float
    {
        $account = Account::findOrFail($accountId);
        $balance = (float) $account->calculateBalance();
        $account->total_balance = $balance;
        $account->save();
        return $balance;
    }

    /**
     * Forces balance recalculation and caching for all accounts.
     */
    public function refreshAllBalances(): void
    {
        $accounts = Account::all();
        foreach ($accounts as $account) {
            $account->total_balance = (float) $account->calculateBalance();
            $account->save();
        }
    }

    /**
     * Retrieves the cached balance of a specific account.
     */
    public function getAccountBalance($account): float
    {
        if (is_numeric($account)) {
            $account = Account::findOrFail($account);
        }
        return (float) $account->total_balance;
    }

    /**
     * Retrieves all accounts scoped to a user.
     */
    public function getAccountBalances(?int $userId = null): \Illuminate\Support\Collection
    {
        $query = Account::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }
        return $query->orderBy('name', 'asc')->get();
    }

    /**
     * Computes the DTO containing all structured financial totals.
     */
    public function getAccountSummary(int $userId): AccountSummary
    {
        $accounts = Account::where('user_id', $userId)->get();

        $summary = new AccountSummary();
        $summary->cash = (float) $accounts->where('type', 'cash')->sum('total_balance');
        $summary->bank = (float) $accounts->where('type', 'bank')->sum('total_balance');
        $summary->ewallet = (float) $accounts->where('type', 'ewallet')->sum('total_balance');
        $summary->investment = (float) Investment::where('user_id', $userId)->get()->sum('market_value');
        $summary->receivable = (float) Receivable::where('user_id', $userId)->where('status', 'active')->sum('remaining_amount');
        $summary->payable = (float) Debt::where('user_id', $userId)->where('status', 'active')->sum('remaining_amount');

        $assets = Asset::where('user_id', $userId)->get();
        $totalAssetsVal = (float) $assets->sum('current_value');

        $summary->liquid_assets = $summary->cash + $summary->bank + $summary->ewallet;
        $summary->total_assets = $summary->liquid_assets + $summary->investment + $summary->receivable + $totalAssetsVal;
        $summary->total_liabilities = $summary->payable;
        $summary->net_worth = $summary->total_assets - $summary->total_liabilities;

        return $summary;
    }

    /**
     * Aggregates monthly income.
     */
    public function getMonthlyIncome(int $month, int $year, ?int $userId = null, ?string $space = null): float
    {
        $query = Transaction::where('type', 'income')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->whereDoesntHave('transactionItems.category', fn($q) => $q->where('is_ignored', true));

        if ($userId) {
            $query->whereHas('account', fn($q) => $q->where('user_id', $userId));
        }

        if ($space) {
            $query->whereHas('account', fn($q) => $q->where('owner', $space));
        }

        return (float) $query->sum('total_amount');
    }

    /**
     * Aggregates monthly expense.
     */
    public function getMonthlyExpense(int $month, int $year, ?int $userId = null, ?string $space = null): float
    {
        $query = Transaction::where('type', 'expense')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->whereDoesntHave('transactionItems.category', fn($q) => $q->where('is_ignored', true));

        if ($userId) {
            $query->whereHas('account', fn($q) => $q->where('user_id', $userId));
        }

        if ($space) {
            $query->whereHas('account', fn($q) => $q->where('owner', $space));
        }

        return (float) $query->sum('total_amount');
    }

    /**
     * Aggregates multi-month cashflow arrays.
     */
    public function getCashFlowSummary(int $userId, ?string $space = null, int $limitMonths = 12): array
    {
        $chartLabels  = [];
        $chartIncome  = [];
        $chartExpense = [];
        $chartSavings = [];

        for ($i = $limitMonths - 1; $i >= 0; $i--) {
            $date  = now()->startOfMonth()->subMonths($i);
            $m     = $date->month;
            $y     = $date->year;

            $chartLabels[] = $date->format('M Y');

            $inc = $this->getMonthlyIncome($m, $y, $userId, $space);
            $exp = $this->getMonthlyExpense($m, $y, $userId, $space);

            $chartIncome[]  = round($inc);
            $chartExpense[] = round($exp);
            $chartSavings[] = round($inc - $exp);
        }

        return compact('chartLabels', 'chartIncome', 'chartExpense', 'chartSavings');
    }

    /**
     * Calculates actual spent and remaining amounts for all budgets in a given period.
     */
    public function getBudgetSummary(int $month, int $year, int $userId): \Illuminate\Support\Collection
    {
        $budgets = Budget::with(['category', 'subcategory'])
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        foreach ($budgets as $budget) {
            $budget->spent = $this->getBudgetActual($budget);
            $budget->remaining = $this->getBudgetRemaining($budget);
        }

        return $budgets;
    }

    /**
     * Compiles a summary of actual and budgeted spending for all expense categories in a given period.
     */
    public function getExpenseCategoryBudgetSummary(int $month, int $year, int $userId): \Illuminate\Support\Collection
    {
        return Category::where('type', 'expense')
            ->where('user_id', $userId)
            ->get()
            ->map(function($category) use ($month, $year, $userId) {
                $actual = (float) TransactionItem::where('category_id', $category->id)
                    ->whereHas('transaction', function ($q) use ($month, $year, $userId) {
                        $q->whereMonth('transaction_date', $month)
                          ->whereYear('transaction_date', $year)
                          ->whereHas('account', fn($aq) => $aq->where('user_id', $userId));
                    })
                    ->sum('amount');

                $budget = (float) Budget::where('category_id', $category->id)
                    ->whereNull('subcategory_id')
                    ->where('month', $month)
                    ->where('year', $year)
                    ->value('allocated_amount') ?? 0.0;

                return (object) [
                    'name'    => $category->name,
                    'actual'  => $actual,
                    'budget'  => $budget,
                ];
            })
            ->filter(fn($b) => $b->budget > 0 || $b->actual > 0)
            ->sortByDesc('actual')
            ->values();
    }

    /**
     * Gets dynamic spend for a single budget.
     */
    public function getBudgetActual(Budget $budget): float
    {
        $query = TransactionItem::whereHas('transaction', function ($q) use ($budget) {
            $q->whereMonth('transaction_date', $budget->month)
              ->whereYear('transaction_date', $budget->year)
              ->whereHas('account', fn($aq) => $aq->where('user_id', $budget->user_id));
        });

        if ($budget->subcategory_id) {
            $query->where('subcategory_id', $budget->subcategory_id);
        } else {
            $query->where('category_id', $budget->category_id);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Gets remaining budget amount.
     */
    public function getBudgetRemaining(Budget $budget): float
    {
        return $budget->allocated_amount - $this->getBudgetActual($budget);
    }

    /**
     * Exposes complete Profit & Loss aggregates.
     */
    public function getProfitLoss(int $month, int $year, string $owner, int $userId): array
    {
        $query = TransactionItem::join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->join('categories', 'categories.id', '=', 'transaction_items.category_id')
            ->where('accounts.user_id', $userId)
            ->where('categories.is_ignored', false)
            ->where('transactions.type', '!=', 'transfer')
            ->whereMonth('transactions.transaction_date', $month)
            ->whereYear('transactions.transaction_date', $year);

        if ($owner !== 'all') {
            $query->where('accounts.owner', $owner);
        }

        $items = $query->select(
            'categories.name as category_name',
            'categories.type as category_type',
            DB::raw('SUM(transaction_items.amount) as total')
        )->groupBy('categories.id', 'categories.name', 'categories.type')->get();

        $incomeItems  = $items->where('category_type', 'income')->sortByDesc('total')->values();
        $expenseItems = $items->where('category_type', 'expense')->sortByDesc('total')->values();

        $totalIncome  = (float) $incomeItems->sum('total');
        $totalExpense = (float) $expenseItems->sum('total');
        $netProfit    = $totalIncome - $totalExpense;
        $profitMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        return compact('incomeItems', 'expenseItems', 'totalIncome', 'totalExpense', 'netProfit', 'profitMargin');
    }

    /**
     * Returns raw category sums.
     */
    public function getExpenseCategorySummary(int $month, int $year, string $space, int $userId): \Illuminate\Support\Collection
    {
        return TransactionItem::selectRaw('categories.name as label, sum(transaction_items.amount) as total')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('categories', 'categories.id', '=', 'transaction_items.category_id')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('transactions.type', 'expense')
            ->where('categories.is_ignored', false)
            ->where('accounts.owner', $space)
            ->where('accounts.user_id', $userId)
            ->whereMonth('transactions.transaction_date', $month)
            ->whereYear('transactions.transaction_date', $year)
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Returns raw subcategory sums.
     */
    public function getExpenseSubcategorySummary(int $month, int $year, string $space, int $userId): \Illuminate\Support\Collection
    {
        return TransactionItem::selectRaw("COALESCE(subcategories.name, 'Uncategorized') as label, sum(transaction_items.amount) as total")
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->leftJoin('subcategories', 'subcategories.id', '=', 'transaction_items.subcategory_id')
            ->join('categories', 'categories.id', '=', 'transaction_items.category_id')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('transactions.type', 'expense')
            ->where('categories.is_ignored', false)
            ->where('accounts.owner', $space)
            ->where('accounts.user_id', $userId)
            ->whereMonth('transactions.transaction_date', $month)
            ->whereYear('transactions.transaction_date', $year)
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Computes the Net Worth by summing cached liquid account balances.
     */
    public function calculateLiquidNetWorth(?int $userId = null): float
    {
        $query = Account::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }
        return (float) $query->sum('total_balance');
    }

    /**
     * Computes the Net Worth including investments, assets, debts, and receivables.
     */
    public function calculateFullNetWorth(?int $userId = null): float
    {
        if (!$userId) {
            $userId = auth()->id();
        }
        if (!$userId) {
            return (float) Account::sum('total_balance');
        }
        return $this->getAccountSummary($userId)->net_worth;
    }

    /**
     * Backward-compatible Net Worth calculation.
     */
    public function calculateNetWorth(): float
    {
        return $this->calculateLiquidNetWorth(auth()->id());
    }
}
