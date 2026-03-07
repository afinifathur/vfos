@extends('layouts.app')

@section('title', 'Edit Asset')

@section('content')
<div class="px-4 xl:px-8 max-w-7xl mx-auto space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold dark:text-white">Edit {{ $asset->name }}</h2>
            <p class="text-sm text-slate-500">Update valuations and details for this asset.</p>
        </div>
        
        <form action="{{ route('assets.destroy', $asset) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this asset from your portfolio?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-500/10 hover:bg-red-500/20 text-red-500 px-4 py-2 rounded-lg font-medium text-sm transition-colors border border-red-500/20 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">delete</span>
                Delete Asset
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6">
        <form action="{{ route('assets.update', $asset) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Asset Name</label>
                    <input type="text" name="name" id="name" value="{{ $asset->name }}" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Description / Details</label>
                    <input type="text" name="description" id="description" value="{{ $asset->description }}" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                </div>
            </div>

            <!-- Asset Type -->
            <div>
                <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Asset Type</label>
                <div class="flex flex-wrap gap-4">
                    @foreach(['Real Estate', 'Vehicle', 'Commodity', 'Electronics', 'Collectible', 'Other'] as $assetType)
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="{{ $assetType }}" {{ $asset->type == $assetType ? 'checked' : '' }} class="text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">{{ $assetType }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Purchase Price -->
                <div>
                    <label for="purchase_price" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Purchase Price ($)</label>
                    <input type="number" step="0.01" name="purchase_price" id="purchase_price" value="{{ rtrim(rtrim(number_format($asset->purchase_price, 2, '.', ''), '0'), '.') }}" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                </div>

                <!-- Current Value -->
                <div>
                    <label for="current_value" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Current Market Value ($)</label>
                    <input type="number" step="0.01" name="current_value" id="current_value" value="{{ rtrim(rtrim(number_format($asset->current_value, 2, '.', ''), '0'), '.') }}" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <a href="{{ route('assets.index') }}" class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-6 py-2 rounded-lg font-medium text-sm transition-colors shadow-sm">
                    Update Asset
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
