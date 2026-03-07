@extends('layouts.app')

@section('title', 'Asset Management')

@section('content')
@php
    function getAssetIcon($type) {
        $type = strtolower($type);
        if (str_contains($type, 'real estate') || str_contains($type, 'house') || str_contains($type, 'property')) return 'home';
        if (str_contains($type, 'land')) return 'landscape';
        if (str_contains($type, 'commodity') || str_contains($type, 'gold')) return 'savings';
        if (str_contains($type, 'vehicle') || str_contains($type, 'car')) return 'directions_car';
        if (str_contains($type, 'motorcycle') || str_contains($type, 'bike')) return 'two_wheeler';
        if (str_contains($type, 'electronic') || str_contains($type, 'phone')) return 'smartphone';
        if (str_contains($type, 'art') || str_contains($type, 'collectible')) return 'palette';
        return 'diamond';
    }
@endphp

<div class="max-w-7xl mx-auto xl:mr-8 xl:ml-0 space-y-12">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Asset Management</h2>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Track and manage your global asset portfolio.</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="bg-primary/5 dark:bg-slate-800 p-4 rounded-xl border border-primary/20 dark:border-slate-700 min-w-[240px]">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase">Total Asset Value</p>
                <div class="flex items-end gap-2">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</h3>
                </div>
            </div>
            <a href="{{ route('assets.create') }}" class="px-6 py-4 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-colors shadow-lg shadow-primary/25 h-full flex items-center justify-center">
                Add New Asset
            </a>
        </div>
    </header>

    @if($appreciatingAssets->isNotEmpty())
    <!-- Appreciating Assets Section -->
    <section>
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center text-success">
                    <span class="material-symbols-outlined">trending_up</span>
                </div>
                <h3 class="text-xl font-bold dark:text-white">Appreciating Assets</h3>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($appreciatingAssets as $asset)
            <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow relative group">
                <div class="flex justify-between items-start mb-4">
                    <span class="material-symbols-outlined p-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-slate-600 dark:text-slate-400">{{ getAssetIcon($asset->type) }}</span>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-success/10 text-success border border-success/20">{{ $asset->type }}</span>
                </div>
                <h4 class="text-lg font-bold mb-1 dark:text-white">{{ $asset->name }}</h4>
                <p class="text-xs text-slate-500 mb-4">{{ $asset->description ?? 'No description' }}</p>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Purchase Price</span>
                        <span class="font-medium dark:text-slate-300">Rp {{ number_format($asset->purchase_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Current Value</span>
                        <span class="font-bold text-slate-900 dark:text-white">Rp {{ number_format($asset->current_value, 0, ',', '.') }}</span>
                    </div>
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <span class="text-xs font-semibold uppercase text-slate-400 tracking-wider">Appreciation</span>
                        <span class="px-3 py-1 rounded-lg bg-success text-white text-xs font-bold">+{{ number_format($asset->percentage, 1) }}%</span>
                    </div>
                </div>
                
                <!-- Quick Actions Overlay -->
                <div class="absolute inset-0 bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl flex items-center justify-center gap-4">
                    <a href="{{ route('assets.edit', $asset) }}" class="p-3 bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors rounded-full shadow-sm">
                        <span class="material-symbols-outlined text-[20px] block">edit</span>
                    </a>
                    <form action="{{ route('assets.destroy', $asset) }}" method="POST" onsubmit="return confirm('Delete this asset?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-3 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-colors rounded-full shadow-sm">
                            <span class="material-symbols-outlined text-[20px] block">delete</span>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    @if($appreciatingAssets->isNotEmpty() && $depreciatingAssets->isNotEmpty())
    <!-- Visual Divider -->
    <div class="relative py-4">
        <div aria-hidden="true" class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-200 dark:border-slate-800"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="bg-slate-50 dark:bg-background-dark px-4 text-xs font-bold uppercase text-slate-400 tracking-widest">Portfolio Breakdown</span>
        </div>
    </div>
    @endif

    @if($depreciatingAssets->isNotEmpty())
    <!-- Depreciating Assets Section -->
    <section>
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center text-danger">
                    <span class="material-symbols-outlined">trending_down</span>
                </div>
                <h3 class="text-xl font-bold dark:text-white">Depreciating Assets</h3>
            </div>
            <span class="text-xs text-slate-500 font-medium italic">Calculated based on current market value</span>
        </div>
        
        <div class="bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Asset</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Purchase Price</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Current Value</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Depreciation</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($depreciatingAssets as $asset)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-slate-500">{{ getAssetIcon($asset->type) }}</span>
                                </div>
                                <div>
                                    <p class="font-bold dark:text-white">{{ $asset->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $asset->description ?? $asset->type }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-sm font-medium text-slate-600 dark:text-slate-300 text-right">Rp {{ number_format($asset->purchase_price, 0, ',', '.') }}</td>
                        <td class="px-6 py-5 text-sm font-bold text-slate-900 dark:text-white text-right">Rp {{ number_format($asset->current_value, 0, ',', '.') }}</td>
                        <td class="px-6 py-5 text-right">
                            <span class="px-3 py-1 rounded-lg bg-danger text-white text-xs font-bold">{{ number_format($asset->percentage, 1) }}%</span>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('assets.edit', $asset) }}" class="p-2 text-slate-400 hover:text-primary transition-colors hover:bg-primary/10 rounded">
                                    <span class="material-symbols-outlined text-[18px] block">edit</span>
                                </a>
                                <form action="{{ route('assets.destroy', $asset) }}" method="POST" onsubmit="return confirm('Delete this asset?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-400 hover:text-red-500 transition-colors hover:bg-red-500/10 rounded">
                                        <span class="material-symbols-outlined text-[18px] block">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

    @if($assets->isEmpty())
        <div class="bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center shadow-sm">
            <div class="w-20 h-20 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-4xl text-slate-400 dark:text-slate-500">diamond</span>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No assets tracked yet</h3>
            <p class="text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-8">Start building your physical and digital asset portfolio to see your total net worth grow.</p>
            <a href="{{ route('assets.create') }}" class="inline-flex py-3 px-6 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-colors shadow-lg shadow-primary/25">
                Add Your First Asset
            </a>
        </div>
    @endif

</div>
@endsection
