<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\Finance\Kernel\FinancialKernelService;

class DashboardController extends Controller
{
    protected FinancialKernelService $kernel;

    public function __construct(FinancialKernelService $kernel)
    {
        $this->kernel = $kernel;
    }

    public function index()
    {
        $userId = auth()->id();
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        $role = auth()->user()->role;
        $currentSpace = ($role === 'partner') ? 'pacar' : (($role === 'business') ? 'business' : 'afin');

        // ── Net Worth ──────────────────────────────────────────────────────────
        $netWorth = $this->kernel->calculateLiquidNetWorth($userId);

        // ── This Month Income & Expense ───────────────────────────────────────
        $monthlyIncome = $this->kernel->getMonthlyIncome($currentMonth, $currentYear, $userId);
        $monthlyExpense = $this->kernel->getMonthlyExpense($currentMonth, $currentYear, $userId);

        $savingRate = $monthlyIncome > 0
            ? (($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100
            : 0;

        // ── Recent Transactions ───────────────────────────────────────────────
        $recentTransactions = Transaction::with(['account', 'transactionItems.category'])
            ->latest('transaction_date')
            ->limit(5)
            ->get();

        // ── Budget vs Actual ─────────────────────────────────────────────────
        $budgets = $this->kernel->getExpenseCategoryBudgetSummary($currentMonth, $currentYear, $userId)
            ->map(function($budget) {
                return [
                    'name'    => $budget->name,
                    'actual'  => $budget->actual,
                    'budget'  => $budget->budget,
                    'percent' => $budget->budget > 0 ? min(100, ($budget->actual / $budget->budget) * 100) : ($budget->actual > 0 ? 100 : 0),
                ];
            });

        // ── Monthly Cashflow Chart (last 12 months) ───────────────────────────
        $cashFlow = $this->kernel->getCashFlowSummary($userId, null, 12);
        $chartLabels  = $cashFlow['chartLabels'];
        $chartIncome  = $cashFlow['chartIncome'];
        $chartExpense = $cashFlow['chartExpense'];
        $chartSavings = $cashFlow['chartSavings'];

        // ── Expenses by Category (Donut Chart) ────────────────────────────────
        $expenseByCategory = $this->kernel->getExpenseCategorySummary($currentMonth, $currentYear, $currentSpace, $userId);
        $donutCategoryLabels = $expenseByCategory->pluck('label')->toArray();
        $donutCategoryData   = $expenseByCategory->pluck('total')->toArray();

        // ── Expenses by Subcategory (Donut Chart) ─────────────────────────────
        $expenseBySubcategory = $this->kernel->getExpenseSubcategorySummary($currentMonth, $currentYear, $currentSpace, $userId);
        $donutSubcategoryLabels = $expenseBySubcategory->pluck('label')->toArray();
        $donutSubcategoryData   = $expenseBySubcategory->pluck('total')->toArray();

        return view('dashboard', compact(
            'netWorth',
            'monthlyIncome',
            'monthlyExpense',
            'savingRate',
            'recentTransactions',
            'budgets',
            'chartLabels',
            'chartIncome',
            'chartExpense',
            'chartSavings',
            'donutCategoryLabels',
            'donutCategoryData',
            'donutSubcategoryLabels',
            'donutSubcategoryData'
        ));
    }
}
