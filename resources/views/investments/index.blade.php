@extends('layouts.app')

@section('title', 'Investment Dashboard')

@section('content')
@php
    function getAssetStyle($class) {
        $class = strtolower($class);
        if (str_contains($class, 'stock')) {
            return ['icon' => 'show_chart', 'bg' => 'bg-blue-100 dark:bg-blue-900/40', 'text' => 'text-blue-600 dark:text-blue-400'];
        }
        if (str_contains($class, 'crypto')) {
            return ['icon' => 'currency_bitcoin', 'bg' => 'bg-orange-100 dark:bg-orange-900/40', 'text' => 'text-orange-600 dark:text-orange-400'];
        }
        if (str_contains($class, 'commodity') || str_contains($class, 'gold')) {
            return ['icon' => 'potted_plant', 'bg' => 'bg-yellow-100 dark:bg-yellow-900/40', 'text' => 'text-yellow-600 dark:text-yellow-400'];
        }
        if (str_contains($class, 'mutual fund')) {
            return ['icon' => 'diamond', 'bg' => 'bg-emerald-100 dark:bg-emerald-900/40', 'text' => 'text-emerald-600 dark:text-emerald-400'];
        }
        return ['icon' => 'diamond', 'bg' => 'bg-slate-100 dark:bg-slate-800', 'text' => 'text-slate-600 dark:text-slate-400'];
    }
@endphp
<div class="max-w-7xl mx-auto xl:mr-8 xl:ml-0 space-y-8">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Investments Portfolio</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Real-time market performance across your asset classes.</p>
        </div>
        <div class="flex items-center gap-3">
            <form action="{{ route('investments.index') }}" method="GET" class="relative group hidden sm:block">
                @if(request('type')) <input type="hidden" name="type" value="{{ request('type') }}"> @endif
                @if(request('sort')) <input type="hidden" name="sort" value="{{ request('sort') }}"> @endif
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <span class="material-symbols-outlined text-sm">search</span>
                </span>
                <input name="search" value="{{ request('search') }}" class="pl-10 pr-4 py-2 bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none text-sm min-w-[240px]" placeholder="Search ticker..." type="text">
            </form>
            <button id="refresh-btn" type="button" onclick="startRefresh()"
                class="bg-yellow-400/90 hover:bg-yellow-400 text-yellow-900 px-5 py-2.5 rounded-lg font-bold text-sm flex items-center gap-2 transition-all shadow-md shadow-yellow-400/20">
                <span id="refresh-icon" class="material-symbols-outlined text-[18px]">refresh</span>
                <span id="refresh-text">Refresh Data</span>
            </button>
            <a href="{{ route('investments.create') }}" class="bg-primary hover:bg-primary/90 text-white px-5 py-2.5 rounded-lg font-bold text-sm flex items-center gap-2 transition-all shadow-lg shadow-primary/20">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Add Investment
            </a>
        </div>
    </header>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Portfolio Value</p>
                <div class="size-8 rounded-full bg-primary/10 flex flex-col items-center justify-center">
                    <span class="material-symbols-outlined text-primary text-sm">account_balance_wallet</span>
                </div>
            </div>
            <div class="flex flex-col relative z-10">
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Rp {{ number_format($totalPortfolioValue, 2, ',', '.') }}</h3>
                <p class="text-sm font-semibold text-primary mt-1 flex items-center">
                    <span class="material-symbols-outlined text-xs mr-1">data_usage</span>
                    Total Invested: Rp {{ number_format($totalInvested, 2, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            @php
                $isProfit = $totalProfitLoss >= 0;
                $profitColor = $isProfit ? 'emerald-500' : 'red-500';
                $profitIcon = $isProfit ? 'trending_up' : 'trending_down';
                $profitBg = $isProfit ? 'bg-emerald-500/5' : 'bg-red-500/5';
            @endphp
            <div class="absolute inset-0 {{ $profitBg }} opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Profit/Loss</p>
                <div class="size-8 rounded-full bg-{{ $profitColor }}/10 flex flex-col items-center justify-center">
                    <span class="material-symbols-outlined text-{{ $profitColor }} text-sm">analytics</span>
                </div>
            </div>
            <div class="flex flex-col relative z-10">
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $isProfit ? '+' : '' }}Rp {{ number_format($totalProfitLoss, 2, ',', '.') }}</h3>
                <p class="text-sm font-semibold text-{{ $profitColor }} mt-1 flex items-center">
                    <span class="material-symbols-outlined text-xs mr-1">{{ $profitIcon }}</span>
                    {{ $isProfit ? 'Overall Gain' : 'Overall Loss' }}
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group opacity-50">
            <div class="absolute inset-0 bg-slate-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">24h Change</p>
                <div class="size-8 rounded-full bg-slate-500/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-slate-500 text-sm">show_chart</span>
                </div>
            </div>
            <div class="flex flex-col relative z-10">
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">-</h3>
                <p class="text-sm font-semibold text-slate-500 mt-1 flex items-center">
                    Historical prices not tracked
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group opacity-50">
            <div class="absolute inset-0 bg-slate-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Annual Yield</p>
                <div class="size-8 rounded-full bg-slate-500/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-slate-500 text-sm">savings</span>
                </div>
            </div>
            <div class="flex flex-col relative z-10">
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">-</h3>
                <p class="text-sm font-semibold text-slate-500 mt-1 flex items-center">
                    Dividends not tracked
                </p>
            </div>
        </div>
    </div>

    <!-- Filters & Views -->
    <div class="flex flex-col lg:flex-row justify-between items-center gap-4 mb-6">
        <div class="flex items-center bg-white dark:bg-card-dark p-1 rounded-lg border border-slate-200 dark:border-slate-800 w-full lg:w-auto overflow-x-auto hide-scrollbar">
            @php
                $currentType = request('type');
                $btnClass = "px-4 py-1.5 text-sm font-medium rounded-md shadow-sm transition-all whitespace-nowrap ";
                $activeClass = "bg-primary text-white";
                $inactiveClass = "text-slate-500 dark:text-slate-400 hover:text-primary";
            @endphp
            <a href="{{ route('investments.index', request()->except(['type', 'page'])) }}" 
               class="{{ $btnClass }} {{ !$currentType ? $activeClass : $inactiveClass }}">All Assets</a>
            <a href="{{ route('investments.index', array_merge(request()->all(), ['type' => 'Stock', 'page' => 1])) }}" 
               class="{{ $btnClass }} {{ $currentType === 'Stock' ? $activeClass : $inactiveClass }}">Stocks</a>
            <a href="{{ route('investments.index', array_merge(request()->all(), ['type' => 'Crypto', 'page' => 1])) }}" 
               class="{{ $btnClass }} {{ $currentType === 'Crypto' ? $activeClass : $inactiveClass }}">Crypto</a>
            <a href="{{ route('investments.index', array_merge(request()->all(), ['type' => 'Commodity', 'page' => 1])) }}" 
               class="{{ $btnClass }} {{ $currentType === 'Commodity' ? $activeClass : $inactiveClass }}">Commodities</a>
            <a href="{{ route('investments.index', array_merge(request()->all(), ['type' => 'Mutual Fund', 'page' => 1])) }}" 
               class="{{ $btnClass }} {{ $currentType === 'Mutual Fund' ? $activeClass : $inactiveClass }}">Mutual Funds</a>
        </div>
        <div class="flex items-center gap-3 w-full lg:w-auto justify-end">
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Sort By:</p>
            <select onchange="location = this.value" class="bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-medium focus:ring-primary focus:outline-none p-2 min-w-[140px] text-slate-900 dark:text-slate-100">
                <option value="{{ route('investments.index', array_merge(request()->all(), ['sort' => 'market_value'])) }}" {{ request('sort') == 'market_value' ? 'selected' : '' }}>Market Value</option>
                <option value="{{ route('investments.index', array_merge(request()->all(), ['sort' => 'ticker_az'])) }}" {{ request('sort') == 'ticker_az' ? 'selected' : '' }}>Ticker A-Z</option>
            </select>
        </div>
    </div>

    <!-- Asset Table -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-500">Asset / Ticker</th>
                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-500 text-right">Price</th>
                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-500 text-right">Avg. Cost</th>
                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-500 text-right">Quantity</th>
                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-500 text-right">Market Value</th>
                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-500 text-right">Gain / Loss</th>
                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-500 text-center">Alloc.</th>
                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-500 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($investments as $inv)
                        @php
                            $style = getAssetStyle($inv->asset_class);
                            $isGain = $inv->gain_loss >= 0;
                            $gainColor = $isGain ? 'text-emerald-500' : 'text-red-500';
                            $gainBg = $isGain ? 'bg-emerald-500/10' : 'bg-red-500/10';
                            $allocation = $totalPortfolioValue > 0 ? ($inv->market_value / $totalPortfolioValue) * 100 : 0;
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="size-9 rounded-full {{ $style['bg'] }} flex items-center justify-center {{ $style['text'] }}">
                                        <span class="material-symbols-outlined text-[18px]">{{ $style['icon'] }}</span>
                                    </div>
                                    <div class="max-w-[120px] truncate">
                                        <p class="text-sm font-bold text-slate-900 dark:text-white truncate" title="{{ $inv->ticker }}">{{ $inv->ticker ?? '-' }}</p>
                                        <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold truncate" title="{{ $inv->name }}">{{ $inv->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span class="text-sm font-bold text-slate-900 dark:text-slate-200">Rp {{ number_format($inv->current_price, 2, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-sm text-slate-500">
                                Rp {{ number_format($inv->average_cost, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-sm text-slate-500 font-medium">
                                {{ number_format($inv->quantity, 4, ',', '.') }} 
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span class="text-sm font-bold text-slate-900 dark:text-white">Rp {{ number_format($inv->market_value, 2, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex flex-col items-end gap-0.5">
                                    <span class="text-sm font-bold {{ $gainColor }}">{{ $isGain ? '+' : '' }}Rp {{ number_format($inv->gain_loss, 2, ',', '.') }}</span>
                                    <span class="text-[9px] font-bold {{ $gainBg }} {{ $gainColor }} px-1.5 py-0.5 rounded-full border border-{{ $isGain ? 'emerald' : 'red' }}-500/20">
                                        {{ $isGain ? '+' : '' }}{{ number_format($inv->gain_loss_percentage, 1) }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">
                                    {{ number_format($allocation, 1) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                    <a href="{{ route('investments.edit', $inv) }}" class="text-slate-400 hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <form action="{{ route('investments.destroy', $inv) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this asset?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors flex items-center">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-500">
                                    <span class="material-symbols-outlined text-5xl mb-3 text-slate-300 dark:text-slate-700">query_stats</span>
                                    <p class="text-lg font-bold text-slate-900 dark:text-white mb-1">No investments tracked yet</p>
                                    <p class="text-sm">Add your first stock, crypto, or commodity to see your portfolio grow.</p>
                                    <a href="{{ route('investments.create') }}" class="mt-4 bg-primary text-white font-bold py-2 px-6 rounded-lg hover:bg-primary/90 transition-colors shadow-lg shadow-primary/20">
                                        Add Asset
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($investments->count() > 0)
        <div class="bg-slate-50 dark:bg-slate-800/30 px-6 py-3 flex items-center justify-between border-t border-slate-200 dark:border-slate-800">
            <p class="text-xs text-slate-500 font-medium tracking-wide">Showing {{ $investments->count() }} out of {{ $investments->count() }} assets</p>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';
let _refreshRunning = false;

async function startRefresh() {
    if (_refreshRunning) return;

    const btn  = document.getElementById('refresh-btn');
    const icon = document.getElementById('refresh-icon');
    const text = document.getElementById('refresh-text');

    // UI: loading state
    _refreshRunning = true;
    btn.disabled = true;
    btn.classList.add('opacity-70', 'cursor-wait');
    icon.style.animation = 'spin 1s linear infinite';
    text.textContent = 'Memuat...';

    try {
        // Step 1: dapatkan daftar investment ID
        const listRes = await fetch('{{ route("investments.refresh") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });
        const listData = await listRes.json();

        if (listData.status !== 'ok' || !listData.investments?.length) {
            alert('Tidak ada investasi yang perlu diperbarui.');
            resetRefreshBtn();
            return;
        }

        const items   = listData.investments;
        const total   = items.length;
        let updated   = 0;
        let failed    = 0;

        // Step 2: update tiap investasi satu per satu
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            text.textContent = `${i + 1} / ${total} — ${item.label}`;

            try {
                const itemRes = await fetch(`/investments/${item.id}/refresh-item`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                });
                const itemData = await itemRes.json();

                if (itemData.status === 'updated') updated++;
                else if (itemData.status === 'failed' || itemData.status === 'error') failed++;
                // 'skipped' tidak dihitung

            } catch (e) {
                failed++;
                console.warn('Error updating item', item.id, e);
            }
        }

        // Selesai
        const msg = `✅ ${updated}/${total} aset diperbarui${failed > 0 ? ` (${failed} gagal)` : ''}`;
        text.textContent = msg;
        icon.style.animation = '';

        setTimeout(() => { window.location.reload(); }, 1500);

    } catch (err) {
        console.error(err);
        alert('Terjadi error: ' + err.message);
        resetRefreshBtn();
    }
}

function resetRefreshBtn() {
    const btn  = document.getElementById('refresh-btn');
    const icon = document.getElementById('refresh-icon');
    const text = document.getElementById('refresh-text');
    _refreshRunning = false;
    btn.disabled = false;
    btn.classList.remove('opacity-70', 'cursor-wait');
    icon.style.animation = '';
    text.textContent = 'Refresh Data';
}
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush

