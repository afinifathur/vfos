@extends('layouts.app')

@section('title', 'Profit & Loss Statement')

@section('content')
<div class="max-w-7xl mx-auto space-y-8">

    {{-- ── Header & Filters ──────────────────────────────────────────────────────────── --}}
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Profit & Loss</h2>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                Income Statement for {{ $months[$month] }} {{ $year }}
            </p>
        </div>
        
        <form method="GET" action="{{ route('profit-loss') }}" class="flex flex-wrap items-center gap-3">
            <!-- Owner Filter -->
            <div class="relative">
                <select name="owner" class="pl-4 pr-10 py-2.5 bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none shadow-sm cursor-pointer transition-all">
                    <option value="all" {{ $owner == 'all' ? 'selected' : '' }}>Joint View (All Accounts)</option>
                    <option value="afin" {{ $owner == 'afin' ? 'selected' : '' }}>My View (Afin)</option>
                    <option value="pacar" {{ $owner == 'pacar' ? 'selected' : '' }}>Partner View</option>
                </select>
                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-lg">expand_more</span>
            </div>

            <!-- Month Filter -->
            <div class="relative">
                <select name="month" class="pl-4 pr-10 py-2.5 bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none shadow-sm cursor-pointer transition-all">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-lg">expand_more</span>
            </div>

            <!-- Year Filter -->
            <div class="relative">
                <select name="year" class="pl-4 pr-10 py-2.5 bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none shadow-sm cursor-pointer transition-all">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-lg">expand_more</span>
            </div>

            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 transition-all">
                Filter
            </button>
            <a href="{{ route('profit-loss.pdf', ['month' => $month, 'year' => $year, 'owner' => $owner]) }}" target="_blank" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span> Download PDF
            </a>
        </form>
    </header>

    {{-- ── Hero: Net Profit Margin ─────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
        <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-widest">Net Profit (Monthly)</p>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $netProfit >= 0 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500' }}">
                        {{ $netProfit >= 0 ? 'Surplus' : 'Deficit' }}
                    </span>
                </div>
                <h2 class="text-5xl font-black tracking-tight {{ $netProfit >= 0 ? 'text-primary' : 'text-rose-500' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </h2>
                <div class="flex items-center gap-2 {{ $profitMargin >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                    <span class="material-symbols-outlined text-sm">{{ $profitMargin >= 0 ? 'trending_up' : 'trending_down' }}</span>
                    <span class="text-sm font-semibold">{{ number_format($profitMargin, 1) }}% Net Margin</span>
                </div>
            </div>
            
            <div class="flex gap-6 flex-wrap">
                <div class="text-right">
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Gross Income</p>
                    <p class="text-2xl font-bold text-emerald-500">Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
                </div>
                <div class="w-px h-12 bg-slate-200 dark:bg-slate-700 self-center"></div>
                <div class="text-right">
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Total Expenses</p>
                    <p class="text-2xl font-bold text-rose-500">(Rp {{ number_format($totalExpense, 0, ',', '.') }})</p>
                </div>
            </div>
        </div>
        
        <div class="absolute -right-24 -top-24 w-80 h-80 {{ $netProfit >= 0 ? 'bg-primary/5' : 'bg-rose-500/5' }} rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        {{-- ── Income Table ────────────────────────────────────────────────────────── --}}
        <section class="space-y-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-500">add_circle</span>
                Operating Income
            </h3>
            
            <div class="bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Income Category</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($incomeItems as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium dark:text-white">{{ $item->category_name }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-semibold text-emerald-500 whitespace-nowrap">
                                    Rp {{ number_format($item->total, 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-6 py-12 text-center text-slate-500 text-sm">
                                No income recorded for this period.
                            </td>
                        </tr>
                        @endforelse
                        
                        <tr class="bg-slate-50/80 dark:bg-slate-800/40 border-t-2 border-slate-200 dark:border-slate-700">
                            <td class="px-6 py-4 font-black tracking-wider uppercase text-xs text-slate-500">Total Income</td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-base font-black text-emerald-500">Rp {{ number_format($totalIncome, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ── Expense Table ───────────────────────────────────────────────────────── --}}
        <section class="space-y-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-rose-500">do_not_disturb_on</span>
                Operating Expenses
            </h3>
            
            <div class="bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Expense Category</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($expenseItems as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium dark:text-white">{{ $item->category_name }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-semibold text-rose-500 whitespace-nowrap">
                                    (Rp {{ number_format($item->total, 0, ',', '.') }})
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-6 py-12 text-center text-slate-500 text-sm">
                                No expenses recorded for this period.
                            </td>
                        </tr>
                        @endforelse
                        
                        <tr class="bg-slate-50/80 dark:bg-slate-800/40 border-t-2 border-slate-200 dark:border-slate-700">
                            <td class="px-6 py-4 font-black tracking-wider uppercase text-xs text-slate-500">Total Expenses</td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-base font-black text-rose-500">(Rp {{ number_format($totalExpense, 0, ',', '.') }})</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </div>

</div>
@endsection

@push('scripts')
<style>
@media print {
    aside, header.sticky, form { display: none !important; }
    main { overflow: visible !important; }
    .bg-white, .dark\:bg-card-dark, .bg-slate-50 {
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
@endpush
