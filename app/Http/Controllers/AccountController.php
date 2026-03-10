<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Account;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::where('user_id', auth()->id())->orderBy('name', 'asc')->get();
        return view('accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required',
            'type'            => 'required|in:bank,ewallet,cash,investment,other',
            'owner'           => 'required|in:afin,pacar',
            'is_active'       => 'boolean',
            'initial_balance' => 'nullable|numeric|min:0',
            'icon'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
        ]);

        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('account_icons', 'public');
            $validated['icon_path'] = $path;
        }

        $validated['user_id']       = auth()->id();
        $validated['total_balance'] = $validated['initial_balance'] ?? 0;
        Account::create($validated);
        return redirect()->route('accounts.index')->with('success', 'Account created successfully.');
    }

    public function edit(Account $account)
    {
        abort_if($account->user_id !== auth()->id(), 403);
        return view('accounts.edit', compact('account'));
    }

    public function update(Request $request, Account $account)
    {
        abort_if($account->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'name'            => 'required',
            'type'            => 'required|in:bank,ewallet,cash,investment,other',
            'owner'           => 'required|in:afin,pacar',
            'is_active'       => 'boolean',
            'initial_balance' => 'nullable|numeric|min:0',
            'icon'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
        ]);

        if ($request->hasFile('icon')) {
            if ($account->icon_path) {
                Storage::disk('public')->delete($account->icon_path);
            }
            $path = $request->file('icon')->store('account_icons', 'public');
            $validated['icon_path'] = $path;
        }

        $account->update($validated);
        $account->total_balance = $account->calculateBalance();
        $account->save();

        return redirect()->route('accounts.index')->with('success', 'Account updated successfully.');
    }

    public function destroy(Account $account)
    {
        abort_if($account->user_id !== auth()->id(), 403);

        \App\Models\Transaction::where('account_id', $account->id)->update(['account_id' => null]);

        if ($account->icon_path) {
            Storage::disk('public')->delete($account->icon_path);
        }
        $account->delete();
        return redirect()->route('accounts.index')->with('success', 'Account deleted successfully.');
    }

    public function reconcile(Account $account)
    {
        abort_if($account->user_id !== auth()->id(), 403);
        $categories = \App\Models\Category::where('user_id', auth()->id())->where('is_active', true)->get();
        return view('accounts.reconcile', compact('account', 'categories'));
    }

    public function processReconcile(Request $request, Account $account)
    {
        abort_if($account->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'actual_balance' => 'required|numeric',
            'category_id'    => 'required|exists:categories,id',
            'notes'          => 'nullable|string'
        ]);

        $currentBalance = $account->total_balance;
        $actualBalance  = $validated['actual_balance'];

        $difference = $actualBalance - $currentBalance;

        if (abs($difference) > 0.01) {
            $type = $difference > 0 ? 'income' : 'expense';
            $amount = abs($difference);

            \Illuminate\Support\Facades\DB::transaction(function () use ($account, $type, $amount, $validated) {
                $transaction = \App\Models\Transaction::create([
                    'account_id'       => $account->id,
                    'type'             => $type,
                    'transaction_date' => now()->toDateString(),
                    'total_amount'     => $amount,
                    'notes'            => $validated['notes'] ?: 'Account Reconciliation (Opname)',
                ]);

                $transaction->transactionItems()->create([
                    'category_id' => $validated['category_id'],
                    'amount'      => $amount,
                    'description' => 'System Balance Adjustment',
                ]);

                $account->update(['total_balance' => $account->total_balance + ($type === 'income' ? $amount : -$amount)]);
            });
            
            return redirect()->route('accounts.index')->with('success', 'Account reconciled and transaction created.');
        }

        $account->touch(); // Update `updated_at` only if no difference
        return redirect()->route('accounts.index')->with('success', 'Account is already balanced. Last checked timestamp updated.');
    }
}
