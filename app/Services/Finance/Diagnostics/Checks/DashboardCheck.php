<?php

namespace App\Services\Finance\Diagnostics\Checks;

use App\Services\Finance\Diagnostics\Contracts\IntegrityCheckInterface;
use App\Services\Finance\Diagnostics\DTO\IntegrityFinding;
use App\Services\Finance\Diagnostics\DTO\IntegrityFindingCollection;
use App\Services\Finance\Kernel\FinancialKernelService;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Budget;
use Carbon\Carbon;

class DashboardCheck implements IntegrityCheckInterface
{
    public function __construct(protected FinancialKernelService $kernelService) {}

    public function run(?int $userId = null): IntegrityFindingCollection
    {
        $findings = new IntegrityFindingCollection();

        if (!$userId) {
            // For global run, let's look at all users in the system
            $users = Account::select('user_id')->distinct()->pluck('user_id');
        } else {
            $users = collect([$userId]);
        }

        foreach ($users as $uId) {
            if (!$uId) continue;

            // 1. Liquid Net Worth vs Sum of Accounts Check
            $liquidNetWorth = (float) $this->kernelService->calculateLiquidNetWorth($uId);
            $sumBalances = (float) Account::where('user_id', $uId)->sum('total_balance');

            if (abs($liquidNetWorth - $sumBalances) > 0.01) {
                $findings->add(new IntegrityFinding(
                    category: 'Dashboard',
                    severity: 'CRITICAL',
                    issue: 'Liquid Net Worth Mismatch',
                    affectedObject: "User ID {$uId} Net Worth",
                    currentValue: $liquidNetWorth,
                    expectedValue: $sumBalances,
                    difference: abs($liquidNetWorth - $sumBalances),
                    rootCause: 'Discrepancy in account sum calculation or caching failure.',
                    recommendation: 'Re-evaluate net worth computation and refresh balances.'
                ));
            }

            // 2. Monthly Income & Expense Consistency (last 12 months)
            for ($i = 0; $i < 12; $i++) {
                $date = Carbon::now()->startOfMonth()->subMonths($i);
                $month = $date->month;
                $year = $date->year;
                $monthLabel = $date->format('M Y');

                // Get values from Kernel Service APIs (used by dashboard)
                $serviceIncome = (float) $this->kernelService->getMonthlyIncome($month, $year, $uId);
                $serviceExpense = (float) $this->kernelService->getMonthlyExpense($month, $year, $uId);

                // Compute directly from database
                $dbIncome = (float) Transaction::where('type', 'income')
                    ->whereMonth('transaction_date', $month)
                    ->whereYear('transaction_date', $year)
                    ->whereHas('account', fn($q) => $q->where('user_id', $uId))
                    ->whereDoesntHave('transactionItems.category', fn($q) => $q->where('is_ignored', true))
                    ->sum('total_amount');

                $dbExpense = (float) Transaction::where('type', 'expense')
                    ->whereMonth('transaction_date', $month)
                    ->whereYear('transaction_date', $year)
                    ->whereHas('account', fn($q) => $q->where('user_id', $uId))
                    ->whereDoesntHave('transactionItems.category', fn($q) => $q->where('is_ignored', true))
                    ->sum('total_amount');

                if (abs($serviceIncome - $dbIncome) > 0.01) {
                    $findings->add(new IntegrityFinding(
                        category: 'Dashboard',
                        severity: 'ERROR',
                        issue: 'Dashboard Income Discrepancy',
                        affectedObject: "User ID {$uId} - {$monthLabel} Income",
                        currentValue: $serviceIncome,
                        expectedValue: $dbIncome,
                        difference: abs($serviceIncome - $dbIncome),
                        rootCause: 'Aggregation mismatch or caching discrepancies.',
                        recommendation: 'Clear dashboard cache and rebuild ledger balances.'
                    ));
                }

                if (abs($serviceExpense - $dbExpense) > 0.01) {
                    $findings->add(new IntegrityFinding(
                        category: 'Dashboard',
                        severity: 'ERROR',
                        issue: 'Dashboard Expense Discrepancy',
                        affectedObject: "User ID {$uId} - {$monthLabel} Expense",
                        currentValue: $serviceExpense,
                        expectedValue: $dbExpense,
                        difference: abs($serviceExpense - $dbExpense),
                        rootCause: 'Aggregation mismatch or caching discrepancies.',
                        recommendation: 'Clear dashboard cache and rebuild ledger balances.'
                    ));
                }
            }

            // 3. Budgets Verification
            $budgets = Budget::where('user_id', $uId)->with(['category', 'subcategory'])->get();
            foreach ($budgets as $budget) {
                if (is_null($budget->category_id)) {
                    $findings->add(new IntegrityFinding(
                        category: 'Budgets',
                        severity: 'CRITICAL',
                        issue: 'Orphan Budget Category Reference',
                        affectedObject: "Budget ID {$budget->id}",
                        currentValue: 'NULL',
                        expectedValue: 'Valid Category ID',
                        difference: null,
                        rootCause: 'Budget category was deleted or incorrectly seeded.',
                        recommendation: 'Assign a valid category to the budget or remove it.'
                    ));
                    continue;
                }

                if (!$budget->category) {
                    $findings->add(new IntegrityFinding(
                        category: 'Budgets',
                        severity: 'CRITICAL',
                        issue: 'Broken Category Reference',
                        affectedObject: "Budget ID {$budget->id}",
                        currentValue: $budget->category_id,
                        expectedValue: 'Valid Category ID',
                        difference: null,
                        rootCause: 'Associated Category was deleted from database.',
                        recommendation: 'Reassign this budget to an active category.'
                    ));
                }

                if ($budget->subcategory_id && !$budget->subcategory) {
                    $findings->add(new IntegrityFinding(
                        category: 'Budgets',
                        severity: 'CRITICAL',
                        issue: 'Broken Subcategory Reference',
                        affectedObject: "Budget ID {$budget->id}",
                        currentValue: $budget->subcategory_id,
                        expectedValue: 'Valid Subcategory ID',
                        difference: null,
                        rootCause: 'Associated Subcategory was deleted from database.',
                        recommendation: 'Reassign or clear the subcategory on this budget.'
                    ));
                }

                if ($budget->allocated_amount < 0) {
                    $findings->add(new IntegrityFinding(
                        category: 'Budgets',
                        severity: 'WARNING',
                        issue: 'Negative Budget Allocation',
                        affectedObject: "Budget ID {$budget->id}",
                        currentValue: $budget->allocated_amount,
                        expectedValue: '>= 0.0',
                        difference: abs($budget->allocated_amount),
                        rootCause: 'Invalid manual database seeding or input entry.',
                        recommendation: 'Update allocated amount to be positive.'
                    ));
                }
            }
        }

        return $findings;
    }
}
