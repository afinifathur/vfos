<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Account;
use App\Models\Investment;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index()
    {
        $goals = Goal::with(['accounts', 'investments'])->get();
        return view('goals.index', compact('goals'));
    }

    public function create()
    {
        return view('goals.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner' => 'required|in:afin,pacar,business',
            'target_amount' => 'required|numeric|min:0',
            'target_date' => 'nullable|date',
            'color' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['color'] = $validated['color'] ?? '#3c83f6';
        
        Goal::create($validated);

        return redirect()->route('goals.index')->with('success', 'Sinking fund objective has been created.');
    }

    public function edit(Goal $goal)
    {
        return view('goals.edit', compact('goal'));
    }

    public function update(Request $request, Goal $goal)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner' => 'required|in:afin,pacar,business',
            'target_amount' => 'required|numeric|min:0',
            'target_date' => 'nullable|date',
            'color' => 'nullable|string',
            'is_completed' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $goal->update($validated);

        return redirect()->route('goals.index')->with('success', 'Sinking fund updated successfully.');
    }

    public function destroy(Goal $goal)
    {
        $goal->delete();
        return redirect()->route('goals.index')->with('success', 'Sinking fund deleted.');
    }
}
