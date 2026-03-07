@extends('layouts.app')

@section('title', 'Category Management')

@section('content')
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
</style>

<div class="flex flex-col min-w-0 h-full max-w-7xl mx-auto xl:mr-8 xl:ml-0 space-y-6">
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-6">
        <div class="flex items-center gap-6">
            <div>
                <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Categories</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Organize your transaction classifications.</p>
            </div>
            <div class="flex items-center bg-white dark:bg-card-dark rounded-lg p-1 border border-slate-200 dark:border-slate-800 shadow-sm">
                <a href="{{ route('categories.index', ['type' => 'income']) }}" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all {{ $filterType === 'income' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">Income</a>
                <a href="{{ route('categories.index', ['type' => 'expense']) }}" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all {{ $filterType === 'expense' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">Expense</a>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="relative group hidden sm:block">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <span class="material-symbols-outlined text-sm">search</span>
                </span>
                <input class="pl-10 pr-4 py-2 bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none text-sm min-w-[240px]" placeholder="Search categories..." type="text">
            </div>
            <a href="{{ route('categories.create') }}" class="flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold transition-all shadow-lg shadow-emerald-500/10">
                <span class="material-symbols-outlined text-lg">add</span>
                Add Category
            </a>
        </div>
    </header>

    <div class="flex-1 flex flex-col lg:flex-row overflow-hidden gap-8 py-2">
        <!-- Main Categories Column -->
        <div class="w-full lg:w-1/3 flex flex-col gap-4 overflow-y-auto custom-scrollbar pr-2 pb-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Main Categories</h3>
                <span class="text-[10px] text-slate-500 px-2 py-0.5 bg-white dark:bg-card-dark rounded border border-slate-200 dark:border-slate-800 font-medium shadow-sm">{{ $categories->count() }} Total</span>
            </div>

            @forelse($categories as $category)
                @php
                    $isActive = $activeCategory && $activeCategory->id === $category->id;
                    $bgColor = $filterType === 'income' ? 'bg-emerald-500/20' : 'bg-primary/20';
                    $textColor = $filterType === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-primary';
                    $ringColor = $filterType === 'income' ? 'ring-emerald-500/10' : 'ring-primary/10';
                    $dotColor = $filterType === 'income' ? 'bg-emerald-500' : 'bg-primary';
                    $borderColor = $filterType === 'income' ? 'border-emerald-500' : 'border-primary';

                    $icons = [
                        'makan' => 'restaurant',
                        'bill' => 'payments',
                        'transportasi' => 'directions_car',
                        'hiburan' => 'sports_esports',
                        'kesehatan' => 'medical_services',
                        'belanja' => 'shopping_bag',
                        'travel' => 'flight',
                        'investasi' => 'trending_up',
                        'education' => 'school',
                        'donasi' => 'volunteer_activism',
                        'gaji' => 'account_balance_wallet',
                        'bisnis' => 'business_center',
                        'pemberian orang tua' => 'diversity_1',
                        'hadiah' => 'redeem',
                    ];
                    $icon = $icons[strtolower($category->name)] ?? ($filterType === 'income' ? 'add_card' : 'category');
                @endphp
                <a href="{{ route('categories.index', ['type' => $filterType, 'category' => $category->id]) }}" class="block group cursor-pointer bg-white dark:bg-card-dark border-2 {{ $isActive ? $borderColor : 'border-slate-200 dark:border-slate-800 border-opacity-50 hover:border-slate-400 dark:hover:border-slate-500' }} p-6 rounded-2xl shadow-sm {{ $isActive ? 'shadow-xl shadow-'.$textColor.'/10' : '' }} transition-all relative overflow-hidden mb-4 min-h-[100px] flex flex-col justify-center">
                    @if($isActive)
                        <div class="absolute inset-0 bg-{{ $textColor }}/5 pointer-events-none transition-colors"></div>
                    @endif
                    
                    <div class="flex justify-between items-center relative z-10">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl {{ $bgColor }} flex items-center justify-center {{ $textColor }} flex-shrink-0 shadow-inner">
                                <span class="material-symbols-outlined text-3xl">{{ $icon }}</span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-black text-slate-900 dark:text-white text-lg leading-tight truncate {{ !$category->is_active ? 'opacity-50 line-through' : '' }}">
                                    {{ ucwords($category->name) }}
                                </h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 uppercase font-black tracking-widest mt-1">
                                    {{ $category->subcategories_count }} classifications
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-3 flex-shrink-0">
                            <span class="w-3.5 h-3.5 rounded-full {{ $dotColor }} {{ $isActive ? 'ring-4 '.$ringColor : 'opacity-20' }}"></span>
                            <div class="flex items-center gap-1.5 px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-md">
                                <span class="material-symbols-outlined text-[14px] text-slate-400">equalizer</span>
                                <span class="text-[12px] font-black text-slate-600 dark:text-slate-400">{{ $category->transaction_items_count }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center py-20 bg-slate-50 dark:bg-slate-900/40 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-800/50">
                    <p class="text-slate-500 text-sm font-bold">No {{ $filterType }} categories found.</p>
                </div>
            @endforelse
        </div>

        <!-- Subcategories Column -->
        <div class="w-full lg:w-2/3 flex flex-col bg-white dark:bg-card-dark rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm h-[calc(100vh-280px)]">
            @if($activeCategory)
                <div class="p-8 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/20 dark:bg-slate-900/40 backdrop-blur-md">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 rounded-2xl bg-{{ $filterType === 'income' ? 'emerald' : 'primary' }}/10 flex items-center justify-center text-{{ $filterType === 'income' ? 'emerald' : 'primary' }} shadow-inner">
                            @php
                                $activeIcon = $icons[strtolower($activeCategory->name)] ?? ($filterType === 'income' ? 'add_card' : 'category');
                            @endphp
                            <span class="material-symbols-outlined text-4xl">{{ $activeIcon }}</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ ucwords($activeCategory->name) }}</h3>
                                <span class="text-[10px] bg-{{ $filterType === 'income' ? 'emerald' : 'blue' }}-500 text-white px-2 py-0.5 rounded-full font-black uppercase tracking-widest">{{ $filterType }}</span>
                            </div>
                            <p class="text-sm font-medium text-slate-500">Managing <span class="text-slate-900 dark:text-white font-black">{{ $activeCategory->subcategories_count }}</span> detailed classifications</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <a href="{{ route('categories.edit', $activeCategory) }}" class="flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-primary border border-slate-200 dark:border-slate-700 rounded-2xl transition-all text-xs font-black shadow-sm" title="Edit Category">
                            <span class="material-symbols-outlined text-lg">edit</span>
                            EDIT
                        </a>
                        <form action="{{ route('categories.destroy', $activeCategory) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this main category?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2.5 text-slate-400 hover:text-red-500 hover:bg-red-500/5 border border-slate-200 dark:border-slate-700 rounded-2xl transition-all shadow-sm">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-slate-50 dark:bg-slate-900/95 backdrop-blur-md z-10">
                            <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
                                <th class="px-10 py-6 border-b border-slate-200 dark:border-slate-800/50">Classification Name</th>
                                <th class="px-10 py-6 border-b border-slate-200 dark:border-slate-800/50">Transactions</th>
                                <th class="px-10 py-6 border-b border-slate-200 dark:border-slate-800/50 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/30 bg-white dark:bg-card-dark">
                            @forelse($activeCategory->subcategories as $sub)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-all group">
                                    <td class="px-10 py-7">
                                        <div class="flex items-center gap-5">
                                            <div class="w-3 h-3 rounded-full {{ $sub->is_active ? 'bg-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.4)]' : 'bg-slate-300 dark:bg-slate-700' }}"></div>
                                            <span class="text-base font-bold text-slate-900 dark:text-white {{ !$sub->is_active ? 'opacity-30 line-through' : '' }} tracking-tight">{{ ucwords($sub->name) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-10 py-7">
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm font-black text-slate-400 dark:text-slate-500">{{ $sub->transaction_items_count }}</span>
                                            <div class="h-1.5 w-16 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                                <div class="h-full bg-{{ $filterType === 'income' ? 'emerald' : 'primary' }}" style="width: {{ min(100, $sub->transaction_items_count * 10) }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-10 py-7">
                                        <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                                            <a href="{{ route('subcategories.edit', $sub) }}" class="text-slate-400 hover:text-primary transition-all p-2 hover:bg-primary/5 rounded-xl border border-transparent hover:border-primary/10">
                                                <span class="material-symbols-outlined text-xl block">edit</span>
                                            </a>
                                            <form action="{{ route('subcategories.destroy', $sub) }}" method="POST" onsubmit="return confirm('Delete this subcategory?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-slate-400 hover:text-red-500 transition-all p-2 hover:bg-red-500/5 rounded-xl border border-transparent hover:border-red-500/10">
                                                    <span class="material-symbols-outlined text-xl block">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-10 py-24 text-center text-slate-500">
                                        <div class="flex flex-col items-center gap-4">
                                            <div class="w-20 h-20 rounded-3xl bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-slate-200 dark:text-slate-700">
                                                <span class="material-symbols-outlined text-5xl">inventory_2</span>
                                            </div>
                                            <div>
                                                <p class="font-black text-slate-900 dark:text-white text-lg">No classifications found</p>
                                                <p class="text-sm text-slate-500 mt-1 max-w-[280px] mx-auto">Add subcategories to break down this category into precise transaction groups.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="p-8 border-t border-slate-200 dark:border-slate-800 flex justify-center bg-slate-50/30 dark:bg-slate-900/40">
                    <a href="{{ route('subcategories.create') }}?category_id={{ $activeCategory->id }}" class="flex items-center gap-4 bg-primary text-white text-xs font-black py-4 px-12 rounded-full transition-all hover:scale-105 hover:shadow-2xl hover:shadow-primary/30 group">
                        <span class="material-symbols-outlined text-2xl group-hover:rotate-180 transition-transform duration-500">add_circle</span>
                        NEW SUBCATEGORY
                    </a>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-slate-500 p-8 bg-slate-50/20 dark:bg-slate-900/10">
                    <div class="w-32 h-32 rounded-[2.5rem] bg-white dark:bg-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-none flex items-center justify-center text-slate-100 dark:text-white/5 mb-8 relative">
                         <div class="absolute inset-0 bg-primary/5 rounded-[2.5rem] animate-pulse"></div>
                        <span class="material-symbols-outlined text-6xl relative z-10">dashboard_customize</span>
                    </div>
                    <p class="text-2xl font-black text-slate-900 dark:text-white">Classification Console</p>
                    <p class="text-base mt-2 max-w-sm text-center font-medium leading-relaxed">Select a main category from the sidebar to view detailed sub-items and transaction activity.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
