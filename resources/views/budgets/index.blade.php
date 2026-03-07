@extends('layouts.app')

@section('title', 'Budgeting')

@section('content')
@php
    function getCategoryStyle($name) {
        $name = strtolower($name);
        if (str_contains($name, 'hous') || str_contains($name, 'rent') || str_contains($name, 'mort')) {
            return ['icon' => 'home', 'color' => 'primary', 'bg' => 'bg-primary/20', 'text' => 'text-primary'];
        }
        if (str_contains($name, 'food') || str_contains($name, 'din') || str_contains($name, 'groc') || str_contains($name, 'eat')) {
            return ['icon' => 'restaurant', 'color' => 'orange-500', 'bg' => 'bg-orange-500/20', 'text' => 'text-orange-500'];
        }
        if (str_contains($name, 'transp') || str_contains($name, 'car') || str_contains($name, 'fuel') || str_contains($name, 'gas')) {
            return ['icon' => 'directions_car', 'color' => 'blue-500', 'bg' => 'bg-blue-500/20', 'text' => 'text-blue-500'];
        }
        if (str_contains($name, 'entert') || str_contains($name, 'movi') || str_contains($name, 'game') || str_contains($name, 'subscrip')) {
            return ['icon' => 'movie', 'color' => 'purple-500', 'bg' => 'bg-purple-500/20', 'text' => 'text-purple-500'];
        }
        if (str_contains($name, 'util') || str_contains($name, 'bill') || str_contains($name, 'elec') || str_contains($name, 'water') || str_contains($name, 'phone')) {
            return ['icon' => 'bolt', 'color' => 'yellow-500', 'bg' => 'bg-yellow-500/20', 'text' => 'text-yellow-500'];
        }
        if (str_contains($name, 'health') || str_contains($name, 'med') || str_contains($name, 'fit')) {
             return ['icon' => 'medical_services', 'color' => 'rose-500', 'bg' => 'bg-rose-500/20', 'text' => 'text-rose-500'];
        }
        if (str_contains($name, 'shop') || str_contains($name, 'cloth') || str_contains($name, 'person')) {
             return ['icon' => 'local_mall', 'color' => 'pink-500', 'bg' => 'bg-pink-500/20', 'text' => 'text-pink-500'];
        }
        return ['icon' => 'category', 'color' => 'slate-400', 'bg' => 'bg-slate-700/50', 'text' => 'text-slate-400'];
    }
@endphp
<div class="max-w-7xl mx-auto">
    <!-- Action Header (Month & Create) -->
    <div class="flex items-center justify-between mb-8 bg-card-dark p-4 rounded-xl border border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('budgets.index') }}" class="flex items-center bg-slate-800/50 rounded-lg px-3 py-2 border border-slate-700 focus-within:ring-1 focus-within:ring-primary/50 transition-shadow">
            <span class="material-symbols-outlined text-slate-400 text-sm mr-2 select-none">calendar_month</span>
            <select name="month" onchange="this.form.submit()" class="bg-transparent border-none text-sm font-medium text-slate-200 focus:ring-0 p-0 cursor-pointer mr-2 outline-none">
                @foreach($availablePeriods as $period)
                    <option class="bg-card-dark text-slate-200" value="{{ $period->month }}" {{ $period->month == $month && $period->year == $year ? 'selected' : '' }}>
                        {{ $period->label }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="year" value="{{ $year }}">
        </form>
        <a href="{{ route('budgets.create') }}" class="bg-primary hover:bg-primary/90 text-white text-sm font-bold px-4 py-2 rounded-lg flex items-center gap-2 transition-all shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Create Budget
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-card-dark border border-slate-800 p-6 rounded-xl flex flex-col gap-1 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/5 rounded-full blur-2xl"></div>
            <p class="text-slate-400 text-sm font-medium relative z-10">Total Budgeted</p>
            <div class="flex items-baseline gap-2 relative z-10">
                <h3 class="text-3xl font-bold text-white">Rp {{ number_format($totalBudgeted, 0, ',', '.') }}</h3>
            </div>
            <p class="text-slate-500 text-xs font-medium mt-2 flex items-center gap-1 relative z-10">
                <span class="material-symbols-outlined text-[14px]">account_balance_wallet</span>
                For selected month
            </p>
        </div>
        
        @php
            $totalPercentage = $totalBudgeted > 0 ? ($totalSpent / $totalBudgeted) * 100 : 0;
            $remainingPercentage = $totalBudgeted > 0 ? ($remainingBudget / $totalBudgeted) * 100 : 0;
            $spentColor = $totalPercentage > 90 ? 'danger' : ($totalPercentage > 75 ? 'warning' : 'success');
        @endphp

        <div class="bg-card-dark border border-slate-800 p-6 rounded-xl flex flex-col gap-1 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-{{ $spentColor }}/5 rounded-full blur-2xl"></div>
            <p class="text-slate-400 text-sm font-medium relative z-10">Total Spent</p>
            <div class="flex items-baseline gap-2 relative z-10">
                <h3 class="text-3xl font-bold text-white">Rp {{ number_format($totalSpent, 0, ',', '.') }}</h3>
            </div>
            <p class="text-{{ $spentColor }} text-xs font-medium mt-2 flex items-center gap-1 relative z-10">
                @if($totalPercentage > 90)
                    <span class="material-symbols-outlined text-[14px]">trending_up</span>
                @else
                    <span class="material-symbols-outlined text-[14px]">trending_flat</span>
                @endif
                {{ number_format($totalPercentage, 1) }}% of total limit
            </p>
        </div>

        @php
            $remColor = $remainingBudget < 0 ? 'danger' : 'slate-400';
        @endphp
        <div class="bg-card-dark border border-slate-800 p-6 rounded-xl flex flex-col gap-1 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-{{ $remainingBudget < 0 ? 'danger' : 'success' }}/5 rounded-full blur-2xl"></div>
            <p class="text-slate-400 text-sm font-medium relative z-10">Remaining Budget</p>
            <div class="flex items-baseline gap-2 relative z-10">
                <h3 class="text-3xl font-bold text-white">Rp {{ number_format($remainingBudget, 0, ',', '.') }}</h3>
            </div>
            <p class="text-{{ $remColor }} text-xs font-medium mt-2 flex items-center gap-1 relative z-10">
                @if($remainingBudget < 0)
                    <span class="material-symbols-outlined text-[14px]">warning</span>
                    Over budget by Rp {{ number_format(abs($remainingBudget), 0, ',', '.') }}
                @else
                    <span class="material-symbols-outlined text-[14px]">savings</span>
                    {{ number_format(max(0, $remainingPercentage), 1) }}% remaining
                @endif
            </p>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-white">Budget Categories</h3>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @forelse($budgets as $budget)
                @php
                    $percentage = $budget->allocated_amount > 0 ? ($budget->spent / $budget->allocated_amount) * 100 : 0;
                    $statusColor = $percentage >= 100 ? 'danger' : ($percentage > 85 ? 'warning' : 'success');
                    $statusText = $percentage >= 100 ? 'Over Budget' : ($percentage > 85 ? 'Near Limit' : 'On Track');
                    $style = getCategoryStyle($budget->category->name);
                @endphp
                <div class="bg-card-dark border border-slate-800 rounded-xl overflow-hidden hover:border-slate-700 transition-colors shadow-sm relative group">
                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-95 group-hover:scale-100">
                         <form action="{{ route('budgets.destroy', $budget) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this budget?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-slate-500 hover:text-danger bg-slate-800/80 hover:bg-slate-700 size-8 rounded-full flex items-center justify-center transition-colors backdrop-blur-sm shadow-sm" title="Delete Budget">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </form>
                    </div>
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-8 h-8 rounded-lg {{ $style['bg'] }} flex items-center justify-center {{ $style['text'] }}">
                                        <span class="material-symbols-outlined text-[20px]">{{ $style['icon'] }}</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-white">
                                        {{ $budget->category->name }}
                                        @if($budget->subcategory)
                                            <span class="text-slate-500 font-normal">/</span>
                                            <span class="text-primary-light font-medium">{{ $budget->subcategory->name }}</span>
                                        @endif
                                    </h4>
                                </div>
                                <p class="text-slate-400 text-xs font-normal capitalize">
                                    {{ $budget->subcategory ? 'Specific item budget' : 'General category budget' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('budgets.edit', $budget) }}" class="p-1.5 text-slate-500 hover:text-primary transition-colors" title="Edit Budget">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <span class="bg-{{ $statusColor }}/10 text-{{ $statusColor }} text-[11px] font-bold px-2 py-1 rounded-md uppercase tracking-wider border border-{{ $statusColor }}/20">{{ $statusText }}</span>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Spent <span class="text-white font-medium">Rp {{ number_format($budget->spent, 0, ',', '.') }}</span></span>
                                <span class="text-slate-400">Limit <span class="text-white font-medium">Rp {{ number_format($budget->allocated_amount, 0, ',', '.') }}</span></span>
                            </div>
                            <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden shadow-inner">
                                <div class="bg-{{ $statusColor }} h-full transition-all duration-700 ease-out" style="width: {{ min(100, max(0, $percentage)) }}%"></div>
                            </div>
                            <div class="flex justify-between items-center pt-1">
                                <span class="text-xs text-slate-500 font-medium">{{ number_format($percentage, 1) }}% used</span>
                                @if($budget->allocated_amount >= $budget->spent)
                                    <span class="text-xs text-success font-medium">Rp {{ number_format($budget->allocated_amount - $budget->spent, 0, ',', '.') }} left</span>
                                @else
                                    <span class="text-xs text-danger font-medium">Rp {{ number_format($budget->spent - $budget->allocated_amount, 0, ',', '.') }} over</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-1 lg:col-span-2 bg-card-dark border border-slate-800 border-dashed rounded-xl p-12 text-center flex flex-col items-center justify-center shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-slate-800/50 border border-slate-700 flex items-center justify-center text-slate-500 mb-4">
                        <span class="material-symbols-outlined text-[32px]">pie_chart</span>
                    </div>
                    <h4 class="text-lg font-bold text-white mb-2">No Budgets Found</h4>
                    <p class="text-slate-400 text-sm max-w-sm mb-6">You haven't set up any budgets for this month yet. Creating a budget helps you track your spending.</p>
                    <a href="{{ route('budgets.create') }}" class="bg-primary hover:bg-primary/90 text-white text-sm font-bold px-4 py-2 rounded-lg transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Create Your First Budget
                    </a>
                </div>
            @endforelse
        </div>
    </div>
    
    <!-- Visualization Component -->
    @if($budgets->count() > 0 && $totalBudgeted > 0)
    <div class="bg-card-dark border border-slate-800 rounded-xl p-6 mt-8 shadow-sm">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h4 class="text-white text-lg font-bold">Spending Allocation</h4>
                <p class="text-slate-400 text-xs mt-1">Overview of category distribution relative to total spending</p>
            </div>
        </div>
        <div class="flex flex-col md:flex-row gap-10 items-center">
            <div class="relative w-48 h-48 flex items-center justify-center shrink-0">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" fill="transparent" r="16" stroke="#1E293B" stroke-width="4"></circle>
                    @if($totalSpent > 0)
                        @php
                            $offset = 0;
                            $circumference = 100;
                        @endphp
                        @foreach($budgets->sortByDesc('spent') as $budget)
                            @if($budget->spent > 0)
                                @php
                                    $share = ($budget->spent / max($totalSpent, 1)) * 100;
                                    $dasharray = "$share $circumference";
                                    $style = getCategoryStyle($budget->category->name);
                                    // Match color text exact name in tailwind class to hex for stroke
                                    $themeColors = [
                                        'text-primary' => '#3c83f6', 
                                        'text-orange-500' => '#f97316',
                                        'text-blue-500' => '#3b82f6',
                                        'text-purple-500' => '#a855f7',
                                        'text-yellow-500' => '#eab308',
                                        'text-rose-500' => '#f43f5e',
                                        'text-pink-500' => '#ec4899',
                                        'text-slate-400' => '#94a3b8',
                                    ];
                                    $colorHex = $themeColors[$style['text']] ?? '#3c83f6';
                                @endphp
                                <circle cx="18" cy="18" fill="transparent" r="16" stroke="{{ $colorHex }}" stroke-dasharray="{{ $dasharray }}" stroke-dashoffset="-{{ $offset }}" stroke-width="4" stroke-linecap="round"></circle>
                                @php $offset += $share; @endphp
                            @endif
                        @endforeach
                    @endif
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-3xl font-bold text-white">{{ number_format($totalPercentage, 0) }}%</span>
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mt-1">Utilized</span>
                </div>
            </div>
            
            <div class="flex-1 grid grid-cols-2 md:grid-cols-3 gap-4 w-full">
                @foreach($budgets->sortByDesc('spent')->take(6) as $index => $budget)
                    @php
                        $share = $totalSpent > 0 ? ($budget->spent / $totalSpent) * 100 : 0;
                        $style = getCategoryStyle($budget->category->name);
                    @endphp
                    <div class="flex items-center gap-3 bg-slate-800/30 p-3 rounded-xl border border-slate-700/50 hover:bg-slate-800/50 transition-colors">
                        <div class="w-10 h-10 rounded-lg {{ $style['bg'] }} {{ $style['text'] }} flex items-center justify-center shrink-0 shadow-inner">
                            <span class="material-symbols-outlined text-[20px]">{{ $style['icon'] }}</span>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-[11px] text-slate-400 truncate uppercase font-semibold tracking-wider">{{ $budget->category->name }}</p>
                            <div class="flex items-baseline gap-1.5 mt-0.5">
                                <p class="text-base font-bold text-white">{{ number_format($share, 1) }}%</p>
                                <p class="text-[10px] text-slate-500 font-medium">of {{ number_format($share, 0) }}%</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
