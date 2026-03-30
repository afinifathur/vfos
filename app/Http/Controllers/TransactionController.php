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
        $query = Transaction::with(['account', 'transactionItems.category']);

        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        $query->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', $search)
                  ->orWhereHas('transactionItems', function ($qi) use ($search) {
                      $qi->where('description', 'like', $search);
                  });
            });
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

        $baseSummaryQuery = Transaction::whereBetween('transaction_date', [$startDate, $endDate]);

        if ($request->filled('account_id')) {
            $baseSummaryQuery->where('account_id', $request->account_id);
        }

        $monthlyIncome = (clone $baseSummaryQuery)->where('type', 'income')->sum('total_amount');
        $monthlyExpense = (clone $baseSummaryQuery)->where('type', 'expense')->sum('total_amount');

        $netSavings = $monthlyIncome - $monthlyExpense;

        $categories = Category::all();
        $accounts = Account::all();

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
        $accounts  = Account::where('is_active', true)->get();
        $categories = Category::where('is_active', true)->with('subcategories')->get();
        return view('transactions.create', compact('accounts', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id'               => 'required|exists:accounts,id',
            'to_account_id'            => 'required_if:type,transfer|nullable|exists:accounts,id|different:account_id',
            'type'                     => 'required|in:income,expense,transfer,withdrawal',
            'transaction_date'         => 'required|date',
            'items'                    => 'required|array|min:1',
            'items.*.category_id'      => 'required|exists:categories,id',
            'items.*.subcategory_id'   => 'nullable|exists:subcategories,id',
            'items.*.description'      => 'nullable|string',
            'items.*.amount'           => 'required|numeric|min:0',
        ]);

        // Ensure account is accessible
        $account = Account::findOrFail($validated['account_id']);

        // Compute total from items (server-side, ignore any user-submitted value)
        $totalAmount = collect($request->items)->sum('amount');

        DB::transaction(function () use ($validated, $request, $totalAmount) {
            $transaction = Transaction::create(array_merge($validated, [
                'total_amount' => $totalAmount,
                'notes'        => null,
            ]));
            foreach ($request->items as $item) {
                $transaction->transactionItems()->create($item);
            }
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    public function edit(Transaction $transaction)
    {
        $transaction->load('transactionItems');
        $accounts  = Account::where('is_active', true)->get();
        $categories = Category::where('is_active', true)->with('subcategories')->get();
        return view('transactions.edit', compact('transaction', 'accounts', 'categories'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'account_id'               => 'required|exists:accounts,id',
            'to_account_id'            => 'required_if:type,transfer|nullable|exists:accounts,id|different:account_id',
            'type'                     => 'required|in:income,expense,transfer,withdrawal',
            'transaction_date'         => 'required|date',
            'items'                    => 'required|array|min:1',
            'items.*.category_id'      => 'required|exists:categories,id',
            'items.*.subcategory_id'   => 'nullable|exists:subcategories,id',
            'items.*.description'      => 'nullable|string',
            'items.*.amount'           => 'required|numeric|min:0',
        ]);

        // Compute total from items server-side
        $totalAmount = collect($request->items)->sum('amount');

        DB::transaction(function () use ($validated, $request, $transaction, $totalAmount) {
            $transaction->update(array_merge($validated, [
                'total_amount' => $totalAmount,
                'notes'        => $transaction->notes, // preserve existing notes
            ]));
            $transaction->transactionItems()->delete();
            foreach ($request->items as $item) {
                $transaction->transactionItems()->create($item);
            }
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
    }
}
