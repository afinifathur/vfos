@extends('layouts.app')

@section('title', 'Add Investment')

@section('content')
<div class="px-4 xl:px-8 max-w-7xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold dark:text-white">Add Tracking Asset</h2>
        <p class="text-sm text-slate-500">Log a new holding into your portfolio.</p>
    </div>

    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6">
        <form action="{{ route('investments.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Asset Name</label>
                    <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="e.g. Apple Inc.">
                </div>

                <!-- Ticker (shown for non-mutual-fund) -->
                <div id="ticker-field">
                    <label for="ticker" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Ticker Symbol</label>
                    <input type="text" name="ticker" id="ticker" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm uppercase" placeholder="e.g. BBCA.JK">
                </div>

                <!-- Kontan URL (shown only for Mutual Fund) -->
                <div id="scraping-url-field" style="display:none">
                    <label for="scraping_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Kontan URL</label>
                    <input type="url" name="scraping_url" id="scraping_url" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="https://pusatdata.kontan.co.id/reksadana/produk/...">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Salin URL produk reksadana dari pusatdata.kontan.co.id. NAB akan diambil otomatis saat disimpan.</p>
                </div>
            </div>

            <!-- Asset Class -->
            <div>
                <label for="asset_class" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Asset Class</label>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="asset_class" value="Stock" class="asset-class-radio text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700" checked>
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Stock / ETF</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="asset_class" value="Mutual Fund" class="asset-class-radio text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Mutual Fund (Reksa Dana)</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="asset_class" value="Crypto" class="asset-class-radio text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Cryptocurrency</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="asset_class" value="Commodity" class="asset-class-radio text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Commodity</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="asset_class" value="Real Estate" class="asset-class-radio text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Real Estate</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="asset_class" value="Other" class="asset-class-radio text-primary focus:ring-primary dark:bg-slate-900 dark:border-slate-700">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Other</span>
                    </label>
                </div>
            </div>

            <!-- Goal Connection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="goal_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Link to Sinking Fund Goal (Optional)</label>
                    <select name="goal_id" id="goal_id" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                        <option value="">-- No specific goal --</option>
                        @foreach($goals as $goal)
                            <option value="{{ $goal->id }}">{{ $goal->name }} (Target: Rp {{ number_format($goal->target_amount, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>

                @if(auth()->user()->role === 'admin')
                <div>
                    <label for="owner" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Primary Owner</label>
                    <select name="owner" id="owner" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                        <option value="afin">Afin</option>
                        <option value="pacar">Pacar</option>
                        <option value="business">Business</option>
                    </select>
                </div>
                @else
                    <input type="hidden" name="owner" value="{{ auth()->user()->role === 'partner' ? 'pacar' : (auth()->user()->role === 'business' ? 'business' : 'afin') }}">
                @endif
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">If linked, this asset's market value will automatically track progress for the selected goal.</p>

            <!-- Currency & Unit (visible for non-Mutual Fund) -->
            <div id="currency-fields" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="currency" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Currency</label>
                    <select name="currency" id="currency" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                        <option value="IDR">IDR (Rupiah) — harga langsung dari Yahoo Finance</option>
                        <option value="USD">USD — dikonversi ke IDR otomatis via kurs real-time</option>
                    </select>
                </div>
                <div id="price-unit-field" style="display:none">
                    <label for="price_unit" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Unit Harga</label>
                    <select name="price_unit" id="price_unit" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                        <option value="unit">Per Unit (default)</option>
                        <option value="gram">Per Gram — harga dibagi 31.1035 (oz→gram) lalu × kurs</option>
                        <option value="troy_oz">Per Troy Oz — langsung × kurs IDR/USD</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Untuk emas (GC=F): pilih <strong>Per Gram</strong> jika kamu catat dalam gram.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Total Holdings (Units)</label>
                    <input type="number" step="0.000001" name="quantity" id="quantity" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="e.g. 150">
                </div>

                <!-- Average Cost -->
                <div>
                    <label for="average_cost" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Average Purchase Price (Rp)</label>
                    <input type="number" step="0.01" name="average_cost" id="average_cost" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="e.g. 195.00">
                </div>

                <!-- Current Price -->
                <div id="current-price-field">
                    <label for="current_price" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Current Market Price (Rp)</label>
                    <input type="number" step="0.000001" name="current_price" id="current_price" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="e.g. 182.41">
                    <p id="current-price-hint" class="mt-1 text-xs text-slate-500 dark:text-slate-400" style="display:none">Opsional — akan diisi otomatis dari Kontan saat disimpan.</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <a href="{{ route('investments.index') }}" class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-6 py-2 rounded-lg font-medium text-sm transition-colors shadow-sm">
                    Save Tracking
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('.asset-class-radio');
    const tickerField = document.getElementById('ticker-field');
    const scrapingField = document.getElementById('scraping-url-field');
    const priceHint = document.getElementById('current-price-hint');
    const currencyFields = document.getElementById('currency-fields');
    const currencySelect = document.getElementById('currency');
    const priceUnitField = document.getElementById('price-unit-field');

    function toggleFields() {
        const selected = document.querySelector('.asset-class-radio:checked')?.value;
        const isMutualFund = selected === 'Mutual Fund';
        tickerField.style.display = isMutualFund ? 'none' : '';
        scrapingField.style.display = isMutualFund ? '' : 'none';
        priceHint.style.display = isMutualFund ? '' : 'none';
        currencyFields.style.display = isMutualFund ? 'none' : '';
        togglePriceUnit();
    }

    function togglePriceUnit() {
        const isUsd = currencySelect.value === 'USD';
        priceUnitField.style.display = isUsd ? '' : 'none';
    }

    radios.forEach(r => r.addEventListener('change', toggleFields));
    currencySelect.addEventListener('change', togglePriceUnit);
    toggleFields();
});
</script>
@endpush
@endsection
