<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        $role = auth()->user()->role;
        $currentSpace = ($role === 'partner') ? 'pacar' : (($role === 'business') ? 'business' : 'afin');

        // ── Net Worth ──────────────────────────────────────────────────────────
        $accounts = Account::all();
        $netWorth = $accounts->sum(fn($a) => $a->calculateBalance());

        // ── This Month Income & Expense ───────────────────────────────────────
        $monthlyIncome = Transaction::where('type', 'income')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date',  $currentYear)
            ->whereDoesntHave('transactionItems.category', fn($q) => $q->where('is_ignored', true))
            ->sum('total_amount');

        $monthlyExpense = Transaction::where('type', 'expense')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date',  $currentYear)
            ->whereDoesntHave('transactionItems.category', fn($q) => $q->where('is_ignored', true))
            ->sum('total_amount');

        $savingRate = $monthlyIncome > 0
            ? (($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100
            : 0;

        // ── Recent Transactions ───────────────────────────────────────────────
        $recentTransactions = Transaction::with(['account', 'transactionItems.category'])
            ->latest('transaction_date')
            ->limit(5)
            ->get();

        // ── Budget vs Actual ─────────────────────────────────────────────────
        // ── Budget vs Actual ─────────────────────────────────────────────────
        $budgets = Category::where('type', 'expense')
            ->with(['transactionItems' => function($q) use ($currentMonth, $currentYear) {
                $q->whereHas('transaction', function($query) use ($currentMonth, $currentYear) {
                    $query->whereMonth('transaction_date', $currentMonth)
                          ->whereYear('transaction_date',  $currentYear);
                });
            }])
            ->get()
            ->map(function($category) use ($currentMonth, $currentYear) {
                $actual = $category->transactionItems->sum('amount');
                $budget = DB::table('budgets')
                    ->where('category_id', $category->id)
                    ->where('month', $currentMonth)
                    ->where('year',  $currentYear)
                    ->value('allocated_amount') ?? 0;

                return [
                    'name'    => $category->name,
                    'actual'  => $actual,
                    'budget'  => $budget,
                    'percent' => $budget > 0 ? min(100, ($actual / $budget) * 100) : ($actual > 0 ? 100 : 0),
                ];
            })
            ->filter(fn($b) => $b['budget'] > 0 || $b['actual'] > 0) // hide empty 0/0 rows
            ->sortByDesc('actual')
            ->values();

        // ── Monthly Cashflow Chart (last 12 months) ───────────────────────────
        $chartLabels  = [];
        $chartIncome  = [];
        $chartExpense = [];
        $chartSavings = [];

        for ($i = 11; $i >= 0; $i--) {
            $date  = now()->startOfMonth()->subMonths($i);
            $m     = $date->month;
            $y     = $date->year;

            $chartLabels[] = $date->format('M Y');

            $inc = Transaction::where('type', 'income')
                ->whereMonth('transaction_date', $m)
                ->whereYear('transaction_date',  $y)
                ->whereDoesntHave('transactionItems.category', fn($q) => $q->where('is_ignored', true))
                ->sum('total_amount');

            $exp = Transaction::where('type', 'expense')
                ->whereMonth('transaction_date', $m)
                ->whereYear('transaction_date',  $y)
                ->whereDoesntHave('transactionItems.category', fn($q) => $q->where('is_ignored', true))
                ->sum('total_amount');

            $chartIncome[]  = round($inc);
            $chartExpense[] = round($exp);
            $chartSavings[] = round($inc - $exp);
        }

        // ── Expenses by Category (Donut Chart) ────────────────────────────────
        $expenseByCategory = TransactionItem::selectRaw('categories.name as label, sum(transaction_items.amount) as total')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('categories', 'categories.id', '=', 'transaction_items.category_id')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('transactions.type', 'expense')
            ->where('categories.is_ignored', false)
            ->where('accounts.owner', $currentSpace)
            ->whereMonth('transactions.transaction_date', $currentMonth)
            ->whereYear('transactions.transaction_date', $currentYear)
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();

        $donutCategoryLabels = $expenseByCategory->pluck('label')->toArray();
        $donutCategoryData   = $expenseByCategory->pluck('total')->toArray();

        // ── Expenses by Subcategory (Donut Chart) ─────────────────────────────
        $expenseBySubcategory = TransactionItem::selectRaw("COALESCE(subcategories.name, 'Uncategorized') as label, sum(transaction_items.amount) as total")
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->leftJoin('subcategories', 'subcategories.id', '=', 'transaction_items.subcategory_id')
            ->join('categories', 'categories.id', '=', 'transaction_items.category_id')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('transactions.type', 'expense')
            ->where('categories.is_ignored', false)
            ->where('accounts.owner', $currentSpace)
            ->whereMonth('transactions.transaction_date', $currentMonth)
            ->whereYear('transactions.transaction_date', $currentYear)
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

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
