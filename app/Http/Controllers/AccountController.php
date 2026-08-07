<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Storage;
use App\Services\Finance\Kernel\FinancialKernelService;

class AccountController extends Controller
{
    protected FinancialKernelService $kernel;

    public function __construct(FinancialKernelService $kernel)
    {
        $this->kernel = $kernel;
    }

    public function index()
    {
        $accounts = $this->kernel->getAccountBalances();
        return view('accounts.index', compact('accounts'));
    }

    public function create()
    {
        $goals = \App\Models\Goal::all();
        return view('accounts.create', compact('goals'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required',
            'type'            => 'required|in:bank,ewallet,cash,investment,other',
            'owner'           => 'required|in:afin,pacar,business',
            'is_active'       => 'boolean',
            'initial_balance' => 'nullable|numeric|min:0',
            'icon'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
            'goal_id'         => 'nullable|exists:goals,id',
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
        $goals = \App\Models\Goal::all();
        return view('accounts.edit', compact('account', 'goals'));
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'name'            => 'required',
            'type'            => 'required|in:bank,ewallet,cash,investment,other',
            'owner'           => 'required|in:afin,pacar,business',
            'is_active'       => 'boolean',
            'initial_balance' => 'nullable|numeric|min:0',
            'icon'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
            'goal_id'         => 'nullable|exists:goals,id',
        ]);

        if ($request->hasFile('icon')) {
            if ($account->icon_path) {
                Storage::disk('public')->delete($account->icon_path);
            }
            $path = $request->file('icon')->store('account_icons', 'public');
            $validated['icon_path'] = $path;
        }

        $account->update($validated);
        $this->kernel->refreshAccountBalance($account->id);

        return redirect()->route('accounts.index')->with('success', 'Account updated successfully.');
    }

    public function destroy(Account $account)
    {
        \App\Models\Transaction::where('account_id', $account->id)->update(['account_id' => null]);

        if ($account->icon_path) {
            Storage::disk('public')->delete($account->icon_path);
        }
        $account->delete();
        return redirect()->route('accounts.index')->with('success', 'Account deleted successfully.');
    }

    public function reconcile(Account $account)
    {
        $categories = \App\Models\Category::where('is_active', true)->get();
        return view('accounts.reconcile', compact('account', 'categories'));
    }

    public function processReconcile(Request $request, Account $account)
    {
        $validated = $request->validate([
            'actual_balance' => 'required|numeric',
            'category_id'    => 'required|exists:categories,id',
            'notes'          => 'nullable|string'
        ]);

        $currentBalance = $account->total_balance;
        $actualBalance  = $validated['actual_balance'];

        $difference = $actualBalance - $currentBalance;

        if (abs($difference) > 0.01) {
            $this->kernel->createAdjustment(
                $account->id,
                $difference,
                now()->toDateString(),
                $validated['notes'] ?: 'Account Reconciliation (Opname)',
                ['category_id' => $validated['category_id']]
            );
            
            return redirect()->route('accounts.index')->with('success', 'Account reconciled and transaction created.');
        }

        $account->touch();
        return redirect()->route('accounts.index')->with('success', 'Account is already balanced. Last checked timestamp updated.');
    }
}
