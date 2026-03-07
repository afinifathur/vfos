@extends('layouts.app')

@section('title', 'Create Category')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <header class="flex items-center gap-4 border-b border-slate-200 dark:border-slate-800 pb-6">
        <a href="{{ route('categories.index') }}" class="p-2 bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 rounded-lg text-slate-500 hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
        </a>
        <div>
            <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">New Category</h2>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Create a top-level classification for your finances.</p>
        </div>
    </header>

    <div class="bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <form action="{{ route('categories.store') }}" method="POST" class="p-8 space-y-8">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Name -->
                <div class="space-y-2">
                    <label for="name" class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Category Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all dark:text-white placeholder:text-slate-400"
                        placeholder="e.g. Groceries, Salary, etc.">
                    @error('name')<p class="text-xs text-rose-500 font-medium">{{ $message }}</p>@enderror
                </div>

                <!-- Type -->
                <div class="space-y-2">
                    <label for="type" class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Transaction Type</label>
                    <div class="relative">
                        <select name="type" id="type" required
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all dark:text-white">
                            <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                            <option value="income" {{ old('type') === 'income' ? 'selected' : '' }}>Income</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                    </div>
                    @error('type')<p class="text-xs text-rose-500 font-medium">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Active Status -->
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                        <span class="material-symbols-outlined">toggle_on</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Active Status</p>
                        <p class="text-xs text-slate-500">Inactive categories won't show in transaction selections.</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>

            <div class="flex items-center justify-end gap-4 pt-4">
                <a href="{{ route('categories.index') }}" class="px-6 py-3 text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">Cancel</a>
                <button type="submit" class="px-8 py-3 bg-primary hover:bg-primary/90 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-primary/20">
                    Create Category
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
