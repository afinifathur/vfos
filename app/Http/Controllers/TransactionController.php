<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        $query = Transaction::with(['account', 'transactionItems.category'])
            ->whereHas('account', fn($q) => $q->where('user_id', $userId));

        if ($request->filled('search')) {
            $query->where('notes', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('category')) {
            $query->whereHas('transactionItems', function($q) use ($request) {
                $q->where('category_id', $request->category);
            });
        }

        $transactions = $query->latest('transaction_date')->paginate(15)->appends($request->all());

        $currentMonth = now()->month;
        $currentYear  = now()->year;

        $baseSummaryQuery = Transaction::whereHas('account', fn($q) => $q->where('user_id', $userId))
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear);

        if ($request->filled('account_id')) {
            $baseSummaryQuery->where('account_id', $request->account_id);
        }

        $monthlyIncome = (clone $baseSummaryQuery)->where('type', 'income')->sum('total_amount');
        $monthlyExpense = (clone $baseSummaryQuery)->where('type', 'expense')->sum('total_amount');

        $netSavings = $monthlyIncome - $monthlyExpense;

        $categories = Category::where('user_id', $userId)->get();
        $accounts = Account::where('user_id', $userId)->get();

        return view('transactions.index', compact(
            'transactions',
            'monthlyIncome',
            'monthlyExpense',
            'netSavings',
            'categories',
            'accounts'
        ));
    }

    public function create()
    {
        $userId    = auth()->id();
        $accounts  = Account::where('user_id', $userId)->where('is_active', true)->get();
        $categories = Category::where('user_id', $userId)->where('is_active', true)->with('subcategories')->get();
        return view('transactions.create', compact('accounts', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id'               => 'required|exists:accounts,id',
            'to_account_id'            => 'required_if:type,transfer|nullable|exists:accounts,id|different:account_id',
            'type'                     => 'required|in:income,expense,transfer,withdrawal',
            'transaction_date'         => 'required|date',
            'total_amount'             => 'required|numeric',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.category_id'      => 'required|exists:categories,id',
            'items.*.subcategory_id'   => 'nullable|exists:subcategories,id',
            'items.*.description'      => 'nullable|string',
            'items.*.amount'           => 'required|numeric',
        ]);

        // Ensure account belongs to auth user
        $account = Account::where('id', $validated['account_id'])->where('user_id', auth()->id())->firstOrFail();

        $totalItemsAmount = collect($request->items)->sum('amount');
        if (abs($request->total_amount - $totalItemsAmount) > 0.01) {
            return back()->withErrors(['total_amount' => 'The total amount must equal the sum of transaction items.'])->withInput();
        }

        DB::transaction(function () use ($validated, $request) {
            $transaction = Transaction::create($validated);
            foreach ($request->items as $item) {
                $transaction->transactionItems()->create($item);
            }
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    public function edit(Transaction $transaction)
    {
        abort_if($transaction->account->user_id !== auth()->id(), 403);
        $transaction->load('transactionItems');
        $userId    = auth()->id();
        $accounts  = Account::where('user_id', $userId)->where('is_active', true)->get();
        $categories = Category::where('user_id', $userId)->where('is_active', true)->with('subcategories')->get();
        return view('transactions.edit', compact('transaction', 'accounts', 'categories'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        abort_if($transaction->account->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'account_id'               => 'required|exists:accounts,id',
            'to_account_id'            => 'required_if:type,transfer|nullable|exists:accounts,id|different:account_id',
            'type'                     => 'required|in:income,expense,transfer,withdrawal',
            'transaction_date'         => 'required|date',
            'total_amount'             => 'required|numeric',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.category_id'      => 'required|exists:categories,id',
            'items.*.subcategory_id'   => 'nullable|exists:subcategories,id',
            'items.*.description'      => 'nullable|string',
            'items.*.amount'           => 'required|numeric',
        ]);

        $totalItemsAmount = collect($request->items)->sum('amount');
        if (abs($request->total_amount - $totalItemsAmount) > 0.01) {
            return back()->withErrors(['total_amount' => 'The total amount must equal the sum of transaction items.'])->withInput();
        }

        DB::transaction(function () use ($validated, $request, $transaction) {
            $transaction->update($validated);
            $transaction->transactionItems()->delete();
            foreach ($request->items as $item) {
                $transaction->transactionItems()->create($item);
            }
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction)
    {
        abort_if($transaction->account->user_id !== auth()->id(), 403);
        $transaction->delete();
        return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
    }
}
