@extends('layouts.app')

@section('title', 'Add Asset')

@section('content')
<div class="px-4 xl:px-8 max-w-7xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold dark:text-white">Add New Asset</h2>
        <p class="text-sm text-slate-500">Log a new physical or digital asset into your portfolio.</p>
    </div>

    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6">
        <form action="{{ route('assets.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Asset Name</label>
                    <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="e.g. House (Property)">
                </div>

                <!-- Owner (Admin Only) -->
                @if(auth()->user()->role === 'admin')
                <div>
                    <label for="owner" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Primary Owner</label>
                    <select name="owner" id="owner" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                        <option value="afin">Afin</option>
                        <option value="pacar">Pacar</option>
                        <option value="business">Business</option>
                    </select>
                </div>
                @else
                    <input type="hidden" name="owner" value="{{ auth()->user()->role === 'partner' ? 'pacar' : (auth()->user()->role === 'business' ? 'business' : 'afin') }}">
                @endif

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Description / Details</label>
                    <input type="text" name="description" id="description" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="e.g. Primary Residence • London, UK">
                </div>
            </div>

            <!-- Asset Type -->
            <div>
                <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Asset Type</label>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="Real Estate" class="text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700" checked>
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Real Estate</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="Vehicle" class="text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Vehicle</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="Commodity" class="text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Commodity</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="Electronics" class="text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Electronics</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="Collectible" class="text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Collectible</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Purchase Price -->
                <div>
                    <label for="purchase_price" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Purchase Price ($)</label>
                    <input type="number" step="0.01" name="purchase_price" id="purchase_price" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="e.g. 450000.00">
                </div>

                <!-- Current Value -->
                <div>
                    <label for="current_value" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Current Market Value ($)</label>
                    <input type="number" step="0.01" name="current_value" id="current_value" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="e.g. 620000.00">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <a href="{{ route('assets.index') }}" class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-6 py-2 rounded-lg font-medium text-sm transition-colors shadow-sm">
                    Save Asset
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
