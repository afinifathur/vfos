<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionItem;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        
        $month = $request->query('month', now()->month);
        $year  = $request->query('year', now()->year);
        $owner = $request->query('owner', 'all');

        $query = TransactionItem::join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->join('categories', 'categories.id', '=', 'transaction_items.category_id')
            ->where('accounts.user_id', $userId)
            ->where('categories.is_ignored', false)
            ->where('transactions.type', '!=', 'transfer') // Exclude internal transfers
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

        $totalIncome  = $incomeItems->sum('total');
        $totalExpense = $expenseItems->sum('total');
        $netProfit    = $totalIncome - $totalExpense;
        $profitMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        // Generate months for dropdown
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = date('F', mktime(0, 0, 0, $m, 1));
        }

        $years = range(now()->year - 3, now()->year + 1);

        return view('profit-loss.index', compact(
            'month', 'year', 'owner', 'months', 'years',
            'incomeItems', 'expenseItems',
            'totalIncome', 'totalExpense',
            'netProfit', 'profitMargin'
        ));
    }

    public function pdf(Request $request)
    {
        $userId = auth()->id();
        
        $month = $request->query('month', now()->month);
        $year  = $request->query('year', now()->year);
        $owner = $request->query('owner', 'all');

        $query = TransactionItem::join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->join('categories', 'categories.id', '=', 'transaction_items.category_id')
            ->where('accounts.user_id', $userId)
            ->where('categories.is_ignored', false)
            ->where('transactions.type', '!=', 'transfer') // Exclude internal transfers
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

        $totalIncome  = $incomeItems->sum('total');
        $totalExpense = $expenseItems->sum('total');
        $netProfit    = $totalIncome - $totalExpense;
        $profitMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = date('F', mktime(0, 0, 0, $m, 1));
        }

        $user = auth()->user();

        return view('profit-loss-pdf', compact(
            'month', 'year', 'owner', 'months', 'user',
            'incomeItems', 'expenseItems',
            'totalIncome', 'totalExpense',
            'netProfit', 'profitMargin'
        ));
    }
}
