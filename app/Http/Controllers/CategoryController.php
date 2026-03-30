<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Category;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $filterType = $request->query('type', 'expense');

        $categories = Category::withCount(['subcategories', 'transactionItems'])
            ->with(['subcategories' => function ($query) {
                $query->withCount('transactionItems');
            }])
            ->where('type', $filterType)
            ->get();

        $activeCategoryId = $request->query('category');
        $activeCategory   = null;

        if ($activeCategoryId) {
            $activeCategory = $categories->firstWhere('id', $activeCategoryId);
        }

        if (!$activeCategory && $categories->isNotEmpty()) {
            $activeCategory = $categories->first();
        }

        return view('categories.index', compact('categories', 'activeCategory', 'filterType'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required',
            'type'      => 'required|in:income,expense',
            'owner'     => 'required|in:afin,pacar,business',
            'is_active' => 'boolean',
            'is_ignored'=> 'boolean',
        ]);

        $validated['is_ignored'] = $request->has('is_ignored');

        $validated['user_id'] = auth()->id();
        Category::create($validated);
        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {

        $validated = $request->validate([
            'name'      => 'required',
            'type'      => 'required|in:income,expense',
            'owner'     => 'required|in:afin,pacar,business',
            'is_active' => 'boolean',
            'is_ignored'=> 'boolean',
        ]);

        $validated['is_ignored'] = $request->has('is_ignored');

        $category->update($validated);
        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
