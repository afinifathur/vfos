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

        // ── Net Worth ──────────────────────────────────────────────────────────
        $accounts = Account::where('user_id', $userId)->get();
        $netWorth = $accounts->sum(fn($a) => $a->calculateBalance());

        // ── This Month Income & Expense ───────────────────────────────────────
        $monthlyIncome = Transaction::whereHas('account', fn($q) => $q->where('user_id', $userId))
            ->where('type', 'income')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date',  $currentYear)
            ->sum('total_amount');

        $monthlyExpense = Transaction::whereHas('account', fn($q) => $q->where('user_id', $userId))
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date',  $currentYear)
            ->sum('total_amount');

        $savingRate = $monthlyIncome > 0
            ? (($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100
            : 0;

        // ── Recent Transactions ───────────────────────────────────────────────
        $recentTransactions = Transaction::with(['account', 'transactionItems.category'])
            ->whereHas('account', fn($q) => $q->where('user_id', $userId))
            ->latest('transaction_date')
            ->limit(5)
            ->get();

        // ── Budget vs Actual ─────────────────────────────────────────────────
        $budgets = Category::where('user_id', $userId)
            ->where('type', 'expense')
            ->with(['transactionItems' => function($q) use ($currentMonth, $currentYear, $userId) {
                $q->whereHas('transaction', function($query) use ($currentMonth, $currentYear, $userId) {
                    $query->whereMonth('transaction_date', $currentMonth)
                          ->whereYear('transaction_date',  $currentYear)
                          ->whereHas('account', fn($aq) => $aq->where('user_id', $userId));
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
            $date  = now()->subMonths($i);
            $m     = $date->month;
            $y     = $date->year;

            $chartLabels[] = $date->format('M Y');

            $inc = Transaction::whereHas('account', fn($q) => $q->where('user_id', $userId))
                ->where('type', 'income')
                ->whereMonth('transaction_date', $m)
                ->whereYear('transaction_date',  $y)
                ->sum('total_amount');

            $exp = Transaction::whereHas('account', fn($q) => $q->where('user_id', $userId))
                ->where('type', 'expense')
                ->whereMonth('transaction_date', $m)
                ->whereYear('transaction_date',  $y)
                ->sum('total_amount');

            $chartIncome[]  = round($inc);
            $chartExpense[] = round($exp);
            $chartSavings[] = round($inc - $exp);
        }

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
            'chartSavings'
        ));
    }
}
