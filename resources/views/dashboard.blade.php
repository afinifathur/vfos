@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Row 1: KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Net Worth -->
    <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Net Worth</p>
            <span class="material-symbols-outlined text-primary">account_balance</span>
        </div>
        <div class="flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold dark:text-white">Rp {{ number_format($netWorth, 0, ',', '.') }}</h3>
                <p class="text-xs font-semibold text-emerald-500 flex items-center mt-1">
                    <span class="material-symbols-outlined text-xs mr-1">trending_up</span>
                    Calculated <span class="text-slate-400 font-normal ml-1">from all time transactions</span>
                </p>
            </div>
            <div class="w-16 h-8">
                <svg class="w-full h-full text-emerald-500 stroke-current fill-none" stroke-width="2" viewbox="0 0 100 40">
                    <path d="M0 35 L10 32 L25 38 L40 25 L60 28 L80 10 L100 5" stroke-linecap="round"></path>
                </svg>
            </div>
        </div>
    </div>
    <!-- Income -->
    <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Income</p>
            <span class="material-symbols-outlined text-emerald-500">arrow_downward</span>
        </div>
        <div>
            <h3 class="text-2xl font-bold dark:text-white">Rp {{ number_format($monthlyIncome, 0, ',', '.') }}</h3>
            <p class="text-xs text-slate-400 mt-1">This month so far</p>
        </div>
    </div>
    <!-- Expense -->
    <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Expense</p>
            <span class="material-symbols-outlined text-rose-500">arrow_upward</span>
        </div>
        <div>
            <h3 class="text-2xl font-bold dark:text-white">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</h3>
            <p class="text-xs text-slate-400 mt-1">Remaining: Rp {{ number_format($monthlyIncome - $monthlyExpense, 0, ',', '.') }}</p>
        </div>
    </div>
    <!-- Saving Rate -->
    <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Saving Rate</p>
            <span class="material-symbols-outlined text-orange-400">savings</span>
        </div>
        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-2xl font-bold dark:text-white">{{ round($savingRate) }}%</h3>
                <span class="text-[10px] font-bold text-primary px-1.5 py-0.5 bg-primary/10 rounded">GOAL: 50%</span>
            </div>
            <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                <div class="bg-primary h-full rounded-full" style="width: {{ min(100, $savingRate) }}%"></div>
            </div>
        </div>
    </div>
</div>

{{-- Row 2: Monthly Cashflow Trend (Chart.js) --}}
<div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-base font-bold dark:text-white">Monthly Cashflow Trend</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Income, expense & net savings — last 12 months</p>
        </div>
        <div class="flex items-center gap-4 text-xs font-semibold">
            <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm bg-emerald-500/80"></span> Income</span>
            <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm bg-rose-500/80"></span> Expense</span>
            <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-1 rounded-full bg-primary"></span> Net Savings</span>
        </div>
    </div>
    <div class="h-72 w-full">
        <canvas id="cashflowChart"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Budget vs Actual Bar Chart -->
    <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <h3 class="text-base font-bold dark:text-white mb-6">Budget vs Actual (This Month)</h3>
        <div class="space-y-6">
            @forelse($budgets as $budget)
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs font-medium">
                        <span class="text-slate-500 dark:text-slate-400 capitalize">{{ $budget['name'] }}</span>
                        <div class="flex items-center gap-2">
                            @if($budget['budget'] == 0)
                                <span class="text-[10px] text-slate-400 italic">no budget set</span>
                                <span class="dark:text-white text-rose-400 font-bold">
                                    Rp {{ number_format($budget['actual'], 0, ',', '.') }}
                                </span>
                            @else
                                <span class="dark:text-white {{ $budget['actual'] > $budget['budget'] ? 'text-rose-500 font-bold' : '' }}">
                                    Rp {{ number_format($budget['actual'], 0, ',', '.') }}
                                    / Rp {{ number_format($budget['budget'], 0, ',', '.') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all
                            {{ $budget['budget'] == 0 ? 'bg-slate-400' : ($budget['actual'] > $budget['budget'] ? 'bg-rose-500' : 'bg-primary') }}"
                            style="width: {{ $budget['percent'] }}%">
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center gap-2 py-4 text-center">
                    <span class="material-symbols-outlined text-3xl text-slate-300 dark:text-slate-700">pie_chart</span>
                    <p class="text-sm text-slate-500">No spending or budget set this month.</p>
                    <a href="{{ route('budgets.index') }}" class="text-xs font-semibold text-primary hover:underline">Set a budget →</a>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-bold dark:text-white">Recent Transactions</h3>
            <a href="/transactions" class="text-sm font-semibold text-primary hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 text-[10px] uppercase tracking-wider font-bold text-slate-500">
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Description</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($recentTransactions as $transaction)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">{{ $transaction->transaction_date }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-lg text-slate-500">
                                            {{ $transaction->type === 'income' ? 'payments' : 'shopping_bag' }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-medium dark:text-white">{{ $transaction->notes ?: 'Transaction' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">
                                {{ $transaction->transactionItems->first()?->category->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-bold {{ $transaction->type === 'income' ? 'text-emerald-500' : 'text-rose-500' }}">
                                {{ $transaction->type === 'income' ? '+' : '-' }}Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-primary"><span class="material-symbols-outlined text-lg">more_vert</span></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor   = isDark ? 'rgba(51,65,85,0.5)'  : 'rgba(226,232,240,0.8)';
    const labelColor  = isDark ? '#94a3b8' : '#64748b';
    const tooltipBg   = isDark ? '#1e293b' : '#ffffff';
    const tooltipText = isDark ? '#f1f5f9' : '#0f172a';

    // Data from Laravel controller
    const labels  = @json($chartLabels);
    const income  = @json($chartIncome);
    const expense = @json($chartExpense);
    const savings = @json($chartSavings);

    // Format as Rupiah shorthand (e.g. 1.5jt)
    function fmtRp(v) {
        if (Math.abs(v) >= 1_000_000) return 'Rp ' + (v / 1_000_000).toFixed(1).replace('.', ',') + 'jt';
        if (Math.abs(v) >= 1_000)    return 'Rp ' + (v / 1_000).toFixed(0) + 'rb';
        return 'Rp ' + v.toLocaleString('id-ID');
    }

    const ctx = document.getElementById('cashflowChart').getContext('2d');

    // Gradient fill for savings line
    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(60,131,246,0.25)');
    gradient.addColorStop(1, 'rgba(60,131,246,0)');

    new Chart(ctx, {
        data: {
            labels: labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Income',
                    data: income,
                    backgroundColor: 'rgba(16,185,129,0.75)',
                    borderRadius: 5,
                    order: 2,
                },
                {
                    type: 'bar',
                    label: 'Expense',
                    data: expense,
                    backgroundColor: 'rgba(239,68,68,0.75)',
                    borderRadius: 5,
                    order: 2,
                },
                {
                    type: 'line',
                    label: 'Net Savings',
                    data: savings,
                    borderColor: '#3c83f6',
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#3c83f6',
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true,
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: tooltipBg,
                    titleColor: tooltipText,
                    bodyColor: labelColor,
                    borderColor: isDark ? '#334155' : '#e2e8f0',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: ctx => ' ' + ctx.dataset.label + ': ' + fmtRp(ctx.parsed.y),
                    },
                },
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: labelColor, font: { size: 11 } },
                },
                y: {
                    grid: { color: gridColor },
                    ticks: {
                        color: labelColor,
                        font: { size: 11 },
                        callback: v => fmtRp(v),
                    },
                },
            },
        },
    });
});
</script>
@endpush
