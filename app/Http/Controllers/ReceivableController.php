<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Receivable;
use Carbon\Carbon;

class ReceivableController extends Controller
{
    public function index()
    {
        $receivables = Receivable::where('status', 'active')->latest()->get();
        $allReceivables = Receivable::all();

        $totalOutstanding = $receivables->sum('remaining_amount');

        $overdueAmount = $receivables->filter(function($r) {
            return $r->due_date && Carbon::parse($r->due_date)->isPast();
        })->sum('remaining_amount');

        $expectedThisMonth = $receivables->filter(function($r) {
            return $r->due_date && Carbon::parse($r->due_date)->isCurrentMonth();
        })->sum('remaining_amount');

        $totalCollected = $allReceivables->sum(function($r) {
            return max(0, $r->total_amount - $r->remaining_amount);
        });

        $currentOverdueAmount = $receivables->filter(function($r) {
            if (!$r->due_date) return false;
            $days = Carbon::parse($r->due_date)->diffInDays(now(), false);
            return $days > 0 && $days <= 30;
        })->sum('remaining_amount');

        $thirtyToSixtyOverdue = $receivables->filter(function($r) {
            if (!$r->due_date) return false;
            $days = Carbon::parse($r->due_date)->diffInDays(now(), false);
            return $days > 30 && $days <= 60;
        })->sum('remaining_amount');

        $overSixtyOverdue = $receivables->filter(function($r) {
            if (!$r->due_date) return false;
            $days = Carbon::parse($r->due_date)->diffInDays(now(), false);
            return $days > 60;
        })->sum('remaining_amount');

        return view('receivables.index', compact(
            'receivables',
            'totalOutstanding',
            'overdueAmount',
            'expectedThisMonth',
            'totalCollected',
            'currentOverdueAmount',
            'thirtyToSixtyOverdue',
            'overSixtyOverdue'
        ));
    }

    public function create()
    {
        return view('receivables.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required',
            'owner'            => 'required|in:afin,pacar,business',
            'total_amount'     => 'required|numeric',
            'remaining_amount' => 'required|numeric',
            'due_date'         => 'nullable|date',
            'status'           => 'required|in:active,paid',
            'notes'            => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();
        Receivable::create($validated);
        return redirect()->route('receivables.index')->with('success', 'Receivable recorded successfully.');
    }

    public function edit(Receivable $receivable)
    {
        return view('receivables.edit', compact('receivable'));
    }

    public function update(Request $request, Receivable $receivable)
    {

        $validated = $request->validate([
            'name'             => 'required',
            'owner'            => 'required|in:afin,pacar,business',
            'total_amount'     => 'required|numeric',
            'remaining_amount' => 'required|numeric',
            'due_date'         => 'nullable|date',
            'status'           => 'required|in:active,paid',
            'notes'            => 'nullable|string',
        ]);

        $receivable->update($validated);
        return redirect()->route('receivables.index')->with('success', 'Receivable updated successfully.');
    }

    public function destroy(Receivable $receivable)
    {
        $receivable->delete();
        return redirect()->route('receivables.index')->with('success', 'Receivable deleted successfully.');
    }
}
