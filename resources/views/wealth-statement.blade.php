@extends('layouts.app')

@section('title', 'Wealth Statement')

@section('content')
<div class="max-w-7xl mx-auto space-y-8">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Wealth Statement</h2>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                Comprehensive snapshot of your financial position as of {{ now()->format('d F Y') }}
            </p>
        </div>
        <a href="{{ route('wealth-statement.pdf') }}" target="_blank"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
            Download PDF
        </a>
    </header>

    {{-- ── Hero: Net Worth ─────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
        <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="space-y-2">
                <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-widest">Total Net Worth</p>
                <h2 class="text-5xl font-black tracking-tight text-slate-900 dark:text-white">
                    Rp {{ number_format($netWorth, 0, ',', '.') }}
                </h2>
                <div class="flex items-center gap-2 {{ $netWorth >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                    <span class="material-symbols-outlined text-sm">{{ $netWorth >= 0 ? 'trending_up' : 'trending_down' }}</span>
                    <span class="text-sm font-semibold">{{ $netWorth >= 0 ? 'Positive' : 'Negative' }} net worth position</span>
                </div>
            </div>
            <div class="flex gap-6 flex-wrap">
                <div class="text-right">
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Total Assets</p>
                    <p class="text-2xl font-bold text-emerald-500">Rp {{ number_format($totalAssets, 0, ',', '.') }}</p>
                </div>
                <div class="w-px h-12 bg-slate-200 dark:bg-slate-700 self-center"></div>
                <div class="text-right">
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Total Liabilities</p>
                    <p class="text-2xl font-bold text-rose-500">(Rp {{ number_format($totalLiabilities, 0, ',', '.') }})</p>
                </div>
            </div>
        </div>
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    {{-- ── Assets Breakdown ────────────────────────────────────────────────── --}}
    <section class="space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-500">account_balance_wallet</span>
            Assets Breakdown
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

            {{-- Cash & Bank --}}
            <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm group hover:border-blue-500/30 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <div class="size-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500">
                        <span class="material-symbols-outlined">payments</span>
                    </div>
                    <span class="text-[10px] font-bold text-blue-400 bg-blue-400/10 px-2 py-1 rounded-full">Liquid</span>
                </div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Cash & Bank</p>
                <p class="text-2xl font-bold dark:text-white mt-1">Rp {{ number_format($totalCash, 0, ',', '.') }}</p>
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-500 flex justify-between">
                    <span>{{ $accounts->count() }} accounts</span>
                    <span class="text-blue-400 font-semibold">{{ $totalAssets > 0 ? number_format(($totalCash / $totalAssets) * 100, 1) : 0 }}% of assets</span>
                </div>
            </div>

            {{-- Investments --}}
            <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm group hover:border-primary/30 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined">show_chart</span>
                    </div>
                    <span class="text-[10px] font-bold {{ $investmentPL >= 0 ? 'text-emerald-500 bg-emerald-500/10' : 'text-rose-500 bg-rose-500/10' }} px-2 py-1 rounded-full">
                        {{ $investmentPL >= 0 ? '+' : '' }}Rp {{ number_format($investmentPL, 0, ',', '.') }}
                    </span>
                </div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Investments</p>
                <p class="text-2xl font-bold dark:text-white mt-1">Rp {{ number_format($totalInvestments, 0, ',', '.') }}</p>
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-500 flex justify-between">
                    <span>{{ $investments->count() }} positions</span>
                    <span class="text-primary font-semibold">{{ $totalAssets > 0 ? number_format(($totalInvestments / $totalAssets) * 100, 1) : 0 }}% of assets</span>
                </div>
            </div>

            {{-- Receivables --}}
            <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm group hover:border-orange-500/30 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <div class="size-10 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-500">
                        <span class="material-symbols-outlined">pending_actions</span>
                    </div>
                </div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Receivables</p>
                <p class="text-2xl font-bold dark:text-white mt-1">Rp {{ number_format($totalReceivables, 0, ',', '.') }}</p>
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-500 flex justify-between">
                    <span>{{ $receivables->count() }} outstanding</span>
                    <span class="text-orange-400 font-semibold">{{ $totalAssets > 0 ? number_format(($totalReceivables / $totalAssets) * 100, 1) : 0 }}% of assets</span>
                </div>
            </div>

            {{-- Fixed Assets (Appreciating) --}}
            <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm group hover:border-purple-500/30 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <div class="size-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-500">
                        <span class="material-symbols-outlined">home_work</span>
                    </div>
                </div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Fixed Assets</p>
                <p class="text-2xl font-bold dark:text-white mt-1">Rp {{ number_format($totalFixedAssets, 0, ',', '.') }}</p>
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-500 flex justify-between">
                    <span>{{ $appreciatingAssets->count() }} items</span>
                    <span class="text-purple-400 font-semibold">{{ $totalAssets > 0 ? number_format(($totalFixedAssets / $totalAssets) * 100, 1) : 0 }}% of assets</span>
                </div>
            </div>

        </div>
    </section>

    {{-- ── Liabilities & Depreciating ───────────────────────────────────────── --}}
    <section class="space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <span class="material-symbols-outlined text-rose-500">credit_card</span>
            Liabilities & Depreciating Assets
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Debts --}}
            <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-slate-200 dark:border-rose-500/20 shadow-sm flex items-center gap-6">
                <div class="size-16 rounded-2xl bg-rose-500/10 flex items-center justify-center text-rose-500 flex-shrink-0">
                    <span class="material-symbols-outlined text-3xl">account_balance</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Debts</p>
                    <p class="text-3xl font-bold text-rose-500 truncate">Rp {{ number_format($totalDebts, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $debts->count() }} active obligations</p>
                </div>
                <span class="text-xs font-bold text-rose-500 px-3 py-1 bg-rose-500/10 rounded-full whitespace-nowrap">Liability</span>
            </div>

            {{-- Depreciating Assets --}}
            <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-6">
                <div class="size-16 rounded-2xl bg-slate-500/10 flex items-center justify-center text-slate-400 flex-shrink-0">
                    <span class="material-symbols-outlined text-3xl">directions_car</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Depreciating Value</p>
                    <p class="text-3xl font-bold text-slate-400 truncate">Rp {{ number_format($totalDepreciating, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $depreciatingAssets->count() }} items losing value</p>
                </div>
                <span class="text-xs font-bold text-slate-500 px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-full whitespace-nowrap">Depreciating</span>
            </div>
        </div>
    </section>

    {{-- ── Detailed Breakdown Table ─────────────────────────────────────────── --}}
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Wealth Calculation Breakdown</h3>
            <span class="text-xs text-slate-500">Last updated: {{ now()->format('d M Y, H:i') }}</span>
        </div>

        <div class="bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Item</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Group</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Balance</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Composition</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($breakdownRows as $row)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-2 rounded-full {{ $row['dot'] }} flex-shrink-0"></div>
                                        <span class="text-sm font-medium dark:text-white max-w-xs truncate">{{ $row['label'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">{{ $row['group'] }}</td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm font-bold {{ $row['is_liability'] ? 'text-rose-500' : 'dark:text-white' }}">
                                        {{ $row['is_liability'] ? '(' : '' }}Rp {{ number_format(abs($row['amount']), 0, ',', '.') }}{{ $row['is_liability'] ? ')' : '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-20 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $row['is_liability'] ? 'bg-rose-500' : 'bg-primary' }}"
                                                 style="width: {{ min(100, $row['composition']) }}%"></div>
                                        </div>
                                        <span class="text-xs text-slate-500 w-10 text-right">{{ number_format($row['composition'], 1) }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $row['status_class'] }}">
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 text-sm">
                                    No wealth data found. Add accounts, investments, or assets to get started.
                                </td>
                            </tr>
                        @endforelse

                        {{-- Net Position row --}}
                        <tr class="bg-slate-50/80 dark:bg-slate-800/40 border-t-2 border-slate-200 dark:border-slate-700">
                            <td class="px-6 py-5 font-black text-slate-900 dark:text-white" colspan="2">
                                Net Position
                            </td>
                            <td class="px-6 py-5 text-right">
                                <span class="text-xl font-black {{ $netWorth >= 0 ? 'text-primary' : 'text-rose-500' }}">
                                    Rp {{ number_format($netWorth, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right text-xs text-slate-500" colspan="2">
                                Assets: Rp {{ number_format($totalAssets, 0, ',', '.') }} &mdash;
                                Liabilities: Rp {{ number_format($totalLiabilities, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<style>
@media print {
    aside, header.sticky { display: none !important; }
    main { overflow: visible !important; }
}
</style>
@endpush
