@extends('layouts.app')

@section('title', 'Transaction Management')

@section('content')
<div class="space-y-8">
    <header class="flex justify-between items-center mb-8 bg-card-dark p-6 rounded-2xl border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="absolute -right-4 -top-4 w-48 h-48 bg-primary/5 rounded-full blur-3xl"></div>
        <div class="relative z-10">
            <h2 class="text-3xl font-black tracking-tight text-white font-outfit">Transactions Ledger</h2>
            <p class="text-slate-400 text-sm">Detailed history of your financial activities and cash flow.</p>
        </div>
        <a href="{{ route('transactions.create') }}" class="bg-primary hover:bg-primary/90 text-white font-bold py-3 px-8 rounded-xl flex items-center gap-2 transition-all shadow-xl shadow-primary/20 relative z-10">
            <span class="material-symbols-outlined">add_circle</span>
            Record Transaction
        </a>
    </header>

    <!-- Filter Bar Card -->
    <div class="bg-white dark:bg-[#1a2333] border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
        <form action="{{ route('transactions.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[280px]">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input name="search" value="{{ request('search') }}" class="w-full bg-slate-50 dark:bg-[#223149] border-none focus:ring-2 focus:ring-primary text-sm rounded-lg pl-10 pr-4 py-2.5 text-slate-200 placeholder:text-slate-500" placeholder="Search by description, merchant, or reference..." type="text"/>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <select name="account_id" onchange="this.form.submit()" class="appearance-none bg-slate-50 dark:bg-[#223149] border-none text-sm rounded-lg pl-10 pr-10 py-2.5 focus:ring-2 focus:ring-primary min-w-[160px] text-slate-300">
                        <option value="">All Accounts</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">account_balance</span>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                </div>
                <div class="relative">
                    <select name="category" onchange="this.form.submit()" class="appearance-none bg-slate-50 dark:bg-[#223149] border-none text-sm rounded-lg pl-10 pr-10 py-2.5 focus:ring-2 focus:ring-primary min-w-[160px] text-slate-300">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">category</span>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                </div>
                <div class="relative">
                    <button type="button" class="flex items-center gap-2 bg-slate-50 dark:bg-[#223149] text-sm rounded-lg px-4 py-2.5 text-slate-300 hover:bg-slate-100 dark:hover:bg-[#2d3d5a] transition-colors">
                        <span class="material-symbols-outlined !text-lg">calendar_month</span>
                        <span>{{ now()->startOfMonth()->format('M d, Y') }} - {{ now()->endOfMonth()->format('M d, Y') }}</span>
                    </button>
                </div>
                <button type="button" class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined">filter_list</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Transactions Table Card -->
    <div class="bg-white dark:bg-[#1a2333] border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/30 text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">
                        <th class="px-6 py-4 border-b dark:border-slate-800">Date</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Description</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Category</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Account</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Amount</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Status</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-primary/5 transition-colors">
                            <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-8 rounded bg-primary/10 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-primary">
                                            @if($transaction->type === 'income') payments @else shopping_bag @endif
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold dark:text-white">
                                            @php
                                                $desc = $transaction->transactionItems
                                                    ->pluck('description')
                                                    ->filter()
                                                    ->join(', ');
                                                $desc = $desc ?: $transaction->notes ?: 'Manual Transaction';
                                            @endphp
                                            {{ $desc }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-500">{{ $transaction->type === 'income' ? 'Received' : 'Paid' }} - {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('M Y') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-[11px] font-medium bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 rounded-full">
                                    {{ $transaction->transactionItems->first()?->category->name ?? 'Uncategorized' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm dark:text-slate-300">{{ $transaction->account?->name ?? '— (akun dihapus)' }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-bold {{ $transaction->type === 'income' ? 'text-emerald-500' : 'text-red-500' }}">
                                    {{ $transaction->type === 'income' ? '+' : '-' }}Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}
                                </p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-bold text-emerald-500 bg-emerald-500/10 rounded-md w-fit uppercase">
                                    <span class="size-1.5 bg-emerald-500 rounded-full"></span> Completed
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('transactions.edit', $transaction) }}" class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors text-slate-400 hover:text-primary">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors text-slate-400 hover:text-red-500" onclick="return confirm('Are you sure?')">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">No transactions matching your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination Footer -->
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} transactions
            </p>
            <div class="flex items-center gap-2">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-[#1a2333] border border-slate-200 dark:border-slate-800 rounded-xl p-6 flex items-center gap-4">
            <div class="size-12 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                <span class="material-symbols-outlined !text-3xl">trending_up</span>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Monthly Income</p>
                <h4 class="text-xl font-bold dark:text-white">Rp {{ number_format($monthlyIncome, 0, ',', '.') }}</h4>
            </div>
        </div>
        <div class="bg-white dark:bg-[#1a2333] border border-slate-200 dark:border-slate-800 rounded-xl p-6 flex items-center gap-4">
            <div class="size-12 rounded-full bg-red-500/10 flex items-center justify-center text-red-500">
                <span class="material-symbols-outlined !text-3xl">trending_down</span>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Monthly Expenses</p>
                <h4 class="text-xl font-bold dark:text-white">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</h4>
            </div>
        </div>
        <div class="bg-white dark:bg-[#1a2333] border border-slate-200 dark:border-slate-800 rounded-xl p-6 flex items-center gap-4">
            <div class="size-12 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                <span class="material-symbols-outlined !text-3xl">savings</span>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Net Savings</p>
                <h4 class="text-xl font-bold dark:text-white">Rp {{ number_format($netSavings, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
</div>
@endsection
