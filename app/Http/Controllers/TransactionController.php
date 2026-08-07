<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\TransactionItem;
use App\Services\Finance\TransactionCategoryService;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function __construct(
        private readonly \App\Services\Finance\Kernel\FinancialKernelService $kernelService
    ) {}

    public function index(Request $request)
    {
        $query = Transaction::with([
            'account',
            'primaryItem.category',
            'primaryItem.subcategory',
            'transactionItems.category',
            'transactionItems.subcategory'
        ]);

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

        $categories = Category::where('is_active', true)->with('subcategories')->get();
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
        Account::findOrFail($validated['account_id']);

        $headerData = [
            'account_id'       => $validated['account_id'],
            'to_account_id'    => $validated['to_account_id'] ?? null,
            'type'             => $validated['type'],
            'transaction_date' => $validated['transaction_date'],
            'notes'            => null,
        ];

        $this->kernelService->recordTransaction($headerData, $validated['items']);

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

        $headerData = [
            'account_id'       => $validated['account_id'],
            'to_account_id'    => $validated['to_account_id'] ?? null,
            'type'             => $validated['type'],
            'transaction_date' => $validated['transaction_date'],
            'notes'            => $transaction->notes, // preserve existing notes
        ];

        $this->kernelService->reviseTransaction($transaction->id, $headerData, $validated['items']);

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    public function inlineCategory(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'category_id'    => 'required|integer',
            'subcategory_id' => 'nullable|integer',
        ]);

        $this->kernelService->updateCategory(
            $transaction->id,
            (int) $validated['category_id'],
            $validated['subcategory_id'] ? (int) $validated['subcategory_id'] : null
        );

        $primaryItem = $transaction->fresh()->primaryItem;

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'category_id' => $primaryItem->category_id,
            'subcategory_id' => $primaryItem->subcategory_id,
        ]);
    }

    public function destroy(Transaction $transaction)
    {
        $this->kernelService->removeTransaction($transaction->id);
        return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
    }
}

