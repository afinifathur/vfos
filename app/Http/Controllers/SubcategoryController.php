<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Subcategory;
use App\Models\Category;

class SubcategoryController extends Controller
{
    public function index()
    {
        $subcategories = Subcategory::with('category')
            ->whereHas('category', fn($q) => $q->where('user_id', auth()->id()))
            ->get();
        return view('subcategories.index', compact('subcategories'));
    }

    public function create()
    {
        $categories = Category::where('user_id', auth()->id())->get();
        return view('subcategories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required',
            'is_active'   => 'boolean',
        ]);

        // Ensure category belongs to auth user
        Category::where('id', $validated['category_id'])->where('user_id', auth()->id())->firstOrFail();

        Subcategory::create($validated);
        return redirect()->route('subcategories.index')->with('success', 'Subcategory created successfully.');
    }

    public function edit(Subcategory $subcategory)
    {
        abort_if($subcategory->category->user_id !== auth()->id(), 403);
        $categories = Category::where('user_id', auth()->id())->get();
        return view('subcategories.edit', compact('subcategory', 'categories'));
    }

    public function update(Request $request, Subcategory $subcategory)
    {
        abort_if($subcategory->category->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required',
            'is_active'   => 'boolean',
        ]);

        $subcategory->update($validated);
        return redirect()->route('subcategories.index')->with('success', 'Subcategory updated successfully.');
    }

    public function destroy(Subcategory $subcategory)
    {
        abort_if($subcategory->category->user_id !== auth()->id(), 403);
        $subcategory->delete();
        return redirect()->route('subcategories.index')->with('success', 'Subcategory deleted successfully.');
    }
}
