<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Debt;

class DebtController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $debts = Debt::where('user_id', $userId)->where('status', 'active')->latest()->get();

        $totalDebtAmount   = $debts->sum('remaining_amount');
        $totalOriginalAmount = $debts->sum('total_amount');
        $totalPrincipalPaid  = $totalOriginalAmount - $totalDebtAmount;

        $upcomingPayments = Debt::where('user_id', $userId)
            ->where('status', 'active')
            ->whereNotNull('due_date')
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        $nextPaymentDate = $upcomingPayments->first() ? $upcomingPayments->first()->due_date : null;

        return view('debts.index', compact(
            'debts',
            'totalDebtAmount',
            'totalPrincipalPaid',
            'upcomingPayments',
            'nextPaymentDate'
        ));
    }

    public function create()
    {
        return view('debts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required',
            'total_amount'     => 'required|numeric',
            'remaining_amount' => 'required|numeric',
            'due_date'         => 'nullable|date',
            'status'           => 'required|in:active,paid',
            'notes'            => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();
        Debt::create($validated);
        return redirect()->route('debts.index')->with('success', 'Debt recorded successfully.');
    }

    public function edit(Debt $debt)
    {
        abort_if($debt->user_id !== auth()->id(), 403);
        return view('debts.edit', compact('debt'));
    }

    public function update(Request $request, Debt $debt)
    {
        abort_if($debt->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'name'             => 'required',
            'total_amount'     => 'required|numeric',
            'remaining_amount' => 'required|numeric',
            'due_date'         => 'nullable|date',
            'status'           => 'required|in:active,paid',
            'notes'            => 'nullable|string',
        ]);

        $debt->update($validated);
        return redirect()->route('debts.index')->with('success', 'Debt updated successfully.');
    }

    public function destroy(Debt $debt)
    {
        abort_if($debt->user_id !== auth()->id(), 403);
        $debt->delete();
        return redirect()->route('debts.index')->with('success', 'Debt deleted successfully.');
    }
}
