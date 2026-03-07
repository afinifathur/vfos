@extends('layouts.app')

@section('title', 'Set New Budget')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('budgets.index') }}" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-primary transition-colors gap-1 group">
            <span class="material-symbols-outlined text-base group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Back to Budgets
        </a>
        <h2 class="text-2xl font-bold text-white mt-4">Create Monthly Budget</h2>
        <p class="text-slate-400 text-sm mt-1">Set a spending limit for a specific category or classification.</p>
    </div>

    <div class="bg-card-dark border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <form action="{{ route('budgets.store') }}" method="POST" class="p-8 space-y-8">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Month Selection -->
                <div class="space-y-2">
                    <label for="month" class="text-xs font-black text-slate-500 uppercase tracking-widest">Target Month</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">calendar_month</span>
                        </span>
                        <select name="month" id="month" required
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white font-medium">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ old('month', now()->month) == $m ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                </option>
                            @endforeach
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">expand_more</span>
                        </span>
                    </div>
                </div>

                <!-- Year Selection -->
                <div class="space-y-2">
                    <label for="year" class="text-xs font-black text-slate-500 uppercase tracking-widest">Target Year</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">schedule</span>
                        </span>
                        <select name="year" id="year" required
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white font-medium">
                            @foreach(range(now()->year - 2, now()->year + 5) as $y)
                                <option value="{{ $y }}" {{ old('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">expand_more</span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <!-- Category Selection -->
                <div class="space-y-2">
                    <label for="category_id" class="text-xs font-black text-slate-500 uppercase tracking-widest">Main Category</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">category</span>
                        </span>
                        <select name="category_id" id="category_id" required onchange="updateSubcategories()"
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white font-medium">
                            <option value="">Select a Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ ucwords($category->name) }}
                                </option>
                            @endforeach
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">expand_more</span>
                        </span>
                    </div>
                    @error('category_id')<p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Subcategory Selection -->
                <div class="space-y-2">
                    <label for="subcategory_id" class="text-xs font-black text-slate-500 uppercase tracking-widest">Specific Item (Optional)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">account_tree</span>
                        </span>
                        <select name="subcategory_id" id="subcategory_id"
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">All {{ old('category_id') ? 'Sub-items' : 'Items' }}</option>
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">expand_more</span>
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-500 font-medium">Leave empty to set a budget for the entire category.</p>
                </div>

                <!-- Allocated Amount -->
                <div class="space-y-2">
                    <label for="allocated_amount" class="text-xs font-black text-slate-500 uppercase tracking-widest">Allocated Amount (Limit)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400 font-bold">Rp</span>
                        <input type="number" name="allocated_amount" id="allocated_amount" value="{{ old('allocated_amount') }}" required min="0" step="1000"
                            class="w-full pl-12 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-black text-xl placeholder:text-slate-700"
                            placeholder="0">
                    </div>
                    @error('allocated_amount')<p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="pt-4 flex items-center gap-4">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl transition-all shadow-xl shadow-primary/20 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">save</span>
                    Set Budget
                </button>
                <a href="{{ route('budgets.index') }}" class="px-8 py-4 text-slate-400 font-bold hover:text-white transition-colors">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    const categorySubcategories = @json($categories->pluck('subcategories', 'id'));

    function updateSubcategories() {
        const categoryId = document.getElementById('category_id').value;
        const subcategorySelect = document.getElementById('subcategory_id');
        
        // Clear existing options except the first one
        subcategorySelect.innerHTML = '<option value="">All Items</option>';
        
        if (categoryId && categorySubcategories[categoryId]) {
            categorySubcategories[categoryId].forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.id;
                option.textContent = sub.name.charAt(0).toUpperCase() + sub.name.slice(1);
                subcategorySelect.appendChild(option);
            });
            subcategorySelect.disabled = false;
        } else {
            subcategorySelect.disabled = true;
        }
    }

    // Initialize on load if there's an old value
    window.onload = function() {
        if (document.getElementById('category_id').value) {
            updateSubcategories();
            // Re-select subcategory if old value exists
            const oldSubId = "{{ old('subcategory_id') }}";
            if (oldSubId) {
                document.getElementById('subcategory_id').value = oldSubId;
            }
        } else {
             document.getElementById('subcategory_id').disabled = true;
        }
    };
</script>
@endsection
