<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Budget;
use App\Models\Category;
use App\Models\TransactionItem;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $month  = (int) $request->input('month', now()->month);
        $year   = (int) $request->input('year', now()->year);

        $budgets = Budget::with(['category', 'subcategory'])
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $totalBudgeted = 0;
        $totalSpent    = 0;

        foreach ($budgets as $budget) {
            $query = TransactionItem::whereHas('transaction', function ($q) use ($month, $year, $userId) {
                $q->whereMonth('transaction_date', $month)
                  ->whereYear('transaction_date', $year)
                  ->whereHas('account', fn($aq) => $aq->where('user_id', $userId));
            });

            if ($budget->subcategory_id) {
                $query->where('subcategory_id', $budget->subcategory_id);
            } else {
                $query->where('category_id', $budget->category_id);
            }

            $spent = $query->sum('amount');
            $budget->spent = $spent;
            $totalBudgeted += $budget->allocated_amount;
            $totalSpent    += $spent;
        }

        $remainingBudget = $totalBudgeted - $totalSpent;

        $availablePeriods = Budget::where('user_id', $userId)
            ->select('month', 'year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($b) {
                return (object) [
                    'value' => "{$b->month}-{$b->year}",
                    'month' => (int) $b->month,
                    'year'  => (int) $b->year,
                    'label' => Carbon::createFromDate($b->year, $b->month, 1)->format('F Y'),
                ];
            });

        $currentPeriodExists = $availablePeriods->contains(fn($p) => $p->month == $month && $p->year == $year);

        if (!$currentPeriodExists) {
            $availablePeriods->prepend((object) [
                'value' => "{$month}-{$year}",
                'month' => $month,
                'year'  => $year,
                'label' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
            ]);
        }

        return view('budgets.index', compact('budgets', 'totalBudgeted', 'totalSpent', 'remainingBudget', 'month', 'year', 'availablePeriods'));
    }

    public function create()
    {
        $userId     = auth()->id();
        $categories = Category::with('subcategories')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'expense')
            ->get();
        return view('budgets.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'      => 'required|exists:categories,id',
            'subcategory_id'   => 'nullable|exists:subcategories,id',
            'month'            => 'required|integer|min:1|max:12',
            'year'             => 'required|integer|min:2000|max:2100',
            'allocated_amount' => 'required|numeric|min:0',
        ]);

        $validated['user_id'] = auth()->id();
        Budget::create($validated);
        return redirect()->route('budgets.index')->with('success', 'Budget set successfully.');
    }

    public function edit(Budget $budget)
    {
        abort_if($budget->user_id !== auth()->id(), 403);
        $userId     = auth()->id();
        $categories = Category::with('subcategories')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'expense')
            ->get();
        return view('budgets.edit', compact('budget', 'categories'));
    }

    public function update(Request $request, Budget $budget)
    {
        abort_if($budget->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'category_id'      => 'required|exists:categories,id',
            'subcategory_id'   => 'nullable|exists:subcategories,id',
            'month'            => 'required|integer|min:1|max:12',
            'year'             => 'required|integer|min:2000|max:2100',
            'allocated_amount' => 'required|numeric|min:0',
        ]);

        $budget->update($validated);
        return redirect()->route('budgets.index')->with('success', 'Budget updated successfully.');
    }

    public function destroy(Budget $budget)
    {
        abort_if($budget->user_id !== auth()->id(), 403);
        $budget->delete();
        return redirect()->route('budgets.index')->with('success', 'Budget deleted successfully.');
    }
}
