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
                <div class="relative" id="date_range_wrapper">
                    <input type="hidden" name="start_date" id="start_date" value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}">
                    <input type="hidden" name="end_date" id="end_date" value="{{ request('end_date', now()->endOfMonth()->toDateString()) }}">
                    
                    <button type="button" id="date_range_picker_btn" class="flex items-center gap-2 bg-slate-50 dark:bg-[#223149] text-sm rounded-lg px-4 py-2.5 text-slate-300 hover:bg-slate-100 dark:hover:bg-[#2d3d5a] transition-colors border-none outline-none">
                        <span class="material-symbols-outlined !text-lg">calendar_month</span>
                        <span id="date_display">
                            @if(request('start_date') && request('end_date'))
                                {{ \Carbon\Carbon::parse(request('start_date'))->format('M d, Y') }} - {{ \Carbon\Carbon::parse(request('end_date'))->format('M d, Y') }}
                            @else
                                {{ now()->startOfMonth()->format('M d, Y') }} - {{ now()->endOfMonth()->format('M d, Y') }}
                            @endif
                        </span>
                    </button>
                    {{-- Invisible input for Flatpickr trigger --}}
                    <input type="text" id="flatpickr_hidden" class="absolute inset-0 opacity-0 pointer-events-none">
                </div>
                <button type="button" class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined">filter_list</span>
                </button>
            </div>
        </form>
    </div>

    @php
        $uncategorizedExpense = $categories->first(fn($c) => strtolower($c->name) === 'uncategorized' && $c->type === 'expense');
        $uncategorizedIncome = $categories->first(fn($c) => strtolower($c->name) === 'uncategorized' && $c->type === 'income');
        $uncategorizedExpenseId = $uncategorizedExpense?->id ?? 41;
        $uncategorizedIncomeId = $uncategorizedIncome?->id ?? 42;
    @endphp

    <!-- Transactions Table Card -->
    <div class="bg-white dark:bg-[#1a2333] border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/30 text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">
                        <th class="px-6 py-4 border-b dark:border-slate-800">Date</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Description</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Category ▼</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Subcategory ▼</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Account</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Amount</th>
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
                            <td class="px-6 py-4 whitespace-nowrap transaction-row" data-transaction-id="{{ $transaction->id }}">
                                @php
                                    $defaultCategoryId = ($transaction->type === 'income') ? $uncategorizedIncomeId : $uncategorizedExpenseId;
                                    $currentCategoryId = $transaction->primaryItem?->category_id ?? $defaultCategoryId;
                                @endphp
                                <select class="category-select bg-slate-50 dark:bg-[#1a2333] text-slate-800 dark:text-slate-100 border border-slate-200 dark:border-slate-700 text-xs rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-primary focus:border-transparent w-full min-w-[140px] max-w-[180px]" 
                                        data-transaction-id="{{ $transaction->id }}"
                                        data-current-val="{{ $currentCategoryId }}">
                                    @foreach($categories as $cat)
                                        @if(($transaction->type === 'income' && $cat->type === 'income') || ($transaction->type !== 'income' && $cat->type === 'expense'))
                                            <option value="{{ $cat->id }}" {{ ($currentCategoryId == $cat->id) ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <select class="subcategory-select bg-slate-50 dark:bg-[#1a2333] text-slate-800 dark:text-slate-100 border border-slate-200 dark:border-slate-700 text-xs rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-primary focus:border-transparent w-full min-w-[140px] max-w-[180px]"
                                            data-transaction-id="{{ $transaction->id }}"
                                            data-current-val="{{ $transaction->primaryItem?->subcategory_id ?? '' }}"
                                            {{ !$currentCategoryId ? 'disabled' : '' }}>
                                        <option value="">General</option>
                                    </select>
                                    <div class="status-indicator size-5 flex items-center justify-center" data-indicator-id="{{ $transaction->id }}"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm dark:text-slate-300">{{ $transaction->account?->name ?? '— (akun dihapus)' }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-bold {{ $transaction->type === 'income' ? 'text-emerald-500' : 'text-red-500' }}">
                                    {{ $transaction->type === 'income' ? '+' : '-' }}Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}
                                </p>
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

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const dateDisplay = document.getElementById('date_display');
    const pickerBtn = document.getElementById('date_range_picker_btn');
    const hiddenTrigger = document.getElementById('flatpickr_hidden');
    const form = startDateInput.closest('form');

    const fp = flatpickr(hiddenTrigger, {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: [startDateInput.value, endDateInput.value],
        positionElement: pickerBtn,
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                const start = instance.formatDate(selectedDates[0], "Y-m-d");
                const end = instance.formatDate(selectedDates[1], "Y-m-d");
                
                // Only submit if dates changed or we have both
                startDateInput.value = start;
                endDateInput.value = end;
                
                // Show a brief moment before submitting for better UX
                setTimeout(() => {
                    form.submit();
                }, 100);
            }
        }
    });

    pickerBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent any accidental submit
        fp.open();
    });
});
</script>

<script type="application/json" id="categories-data">
    {!! json_encode($categories->map(function($cat) {
        return [
            'id' => $cat->id,
            'name' => $cat->name,
            'subcategories' => $cat->subcategories->map(function($sub) {
                return [
                    'id' => $sub->id,
                    'name' => $sub->name
                ];
            })
        ];
    })) !!}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inline Category/Subcategory Editing logic
    const categoriesDataElement = document.getElementById('categories-data');
    if (categoriesDataElement) {
        const categories = JSON.parse(categoriesDataElement.textContent);

        function populateSubcategories(row, selectedCategoryId, currentSubcategoryId) {
            const subSelect = row.querySelector('.subcategory-select');
            subSelect.innerHTML = '<option value="">General</option>';

            if (!selectedCategoryId) {
                subSelect.disabled = true;
                return;
            }

            const category = categories.find(c => c.id == selectedCategoryId);
            if (category && category.subcategories && category.subcategories.length > 0) {
                subSelect.disabled = false;
                category.subcategories.forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub.id;
                    option.textContent = sub.name;
                    if (sub.id == currentSubcategoryId) {
                        option.selected = true;
                    }
                    subSelect.appendChild(option);
                });
            } else {
                subSelect.disabled = true;
            }
        }

        function saveCategoryUpdate(transactionId, categoryId, subcategoryId, catSelect, subSelect, indicator) {
            // Disable selects while saving (Requirement 3)
            catSelect.disabled = true;
            subSelect.disabled = true;

            // Show Saving... indicator (Requirement 4)
            indicator.innerHTML = `
                <div class="flex items-center gap-1 text-[10px] text-slate-400 font-bold whitespace-nowrap">
                    <svg class="animate-spin h-3.5 w-3.5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Saving...</span>
                </div>
            `;

            fetch(`/api/transactions/${transactionId}/inline-category`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    category_id: categoryId,
                    subcategory_id: subcategoryId || null
                })
            })
            .then(async response => {
                if (!response.ok) {
                    const errData = await response.json().catch(() => ({}));
                    throw new Error(errData.message || 'Validation failed');
                }
                return response.json();
            })
            .then(data => {
                // Show "✓ Saved" for 1 second (Requirement 4)
                indicator.innerHTML = `
                    <div class="flex items-center gap-1 text-[10px] text-emerald-500 font-bold whitespace-nowrap">
                        <span class="material-symbols-outlined text-sm" style="font-size: 14px;">check_circle</span>
                        <span>Saved</span>
                    </div>
                `;

                // Update data-current-val
                catSelect.dataset.currentVal = categoryId;
                subSelect.dataset.currentVal = subcategoryId || '';

                setTimeout(() => {
                    indicator.innerHTML = '';
                }, 1000);
            })
            .catch(error => {
                console.error(error);
                // Show red error icon
                indicator.innerHTML = `
                    <div class="flex items-center gap-1 text-[10px] text-red-500 font-bold whitespace-nowrap" title="${error.message}">
                        <span class="material-symbols-outlined text-sm" style="font-size: 14px;">error</span>
                        <span>Error</span>
                    </div>
                `;

                // Immediately restore Category and Subcategory to their last successfully saved values (Requirement 5)
                catSelect.value = catSelect.dataset.currentVal;
                populateSubcategories(row = catSelect.closest('tr'), catSelect.value, subSelect.dataset.currentVal);

                setTimeout(() => {
                    indicator.innerHTML = '';
                }, 2000);
            })
            .finally(() => {
                // Re-enable selects (Requirement 3)
                catSelect.disabled = false;
                // subSelect should be enabled only if the category has subcategories
                const category = categories.find(c => c.id == catSelect.value);
                const hasSubcategories = category && category.subcategories && category.subcategories.length > 0;
                subSelect.disabled = !hasSubcategories;
            });
        }

        // Initialize all rows
        document.querySelectorAll('.category-select').forEach(catSelect => {
            const transactionId = catSelect.dataset.transactionId;
            const subSelect = document.querySelector(`.subcategory-select[data-transaction-id="${transactionId}"]`);
            if (subSelect) {
                const row = catSelect.closest('tr');
                const initialCategoryId = catSelect.dataset.currentVal;
                const initialSubcategoryId = subSelect.dataset.currentVal;
                populateSubcategories(row, initialCategoryId, initialSubcategoryId);
            }
        });

        // Event listener for Category select change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('category-select')) {
                const catSelect = e.target;
                const transactionId = catSelect.dataset.transactionId;
                const row = catSelect.closest('tr');
                const subSelect = row.querySelector('.subcategory-select');
                const indicator = row.querySelector('.status-indicator');
                const newCategoryId = catSelect.value;

                // Reset subcategory to null (Requirement 1 & 2)
                populateSubcategories(row, newCategoryId, null);

                // Immediately save the category update with subcategory_id = null (Requirement 1)
                saveCategoryUpdate(transactionId, newCategoryId, null, catSelect, subSelect, indicator);
            }
        });

        // Event listener for Subcategory select change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('subcategory-select')) {
                const subSelect = e.target;
                const transactionId = subSelect.dataset.transactionId;
                const row = subSelect.closest('tr');
                const catSelect = row.querySelector('.category-select');
                const indicator = row.querySelector('.status-indicator');
                
                const categoryId = catSelect.value;
                const subcategoryId = subSelect.value;

                // Immediately save the category/subcategory update (Requirement 2)
                saveCategoryUpdate(transactionId, categoryId, subcategoryId, catSelect, subSelect, indicator);
            }
        });
    }
});
</script>

<style>
    /* Custom Flatpickr Dark Theme Overrides */
    .flatpickr-calendar.dark {
        background: #1a2333;
        border-color: #334155;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
    }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
        background: #3c83f6 !important;
        border-color: #3c83f6 !important;
    }
    .flatpickr-day.inRange {
        background: rgba(60, 131, 246, 0.2) !important;
        box-shadow: -5px 0 0 rgba(60, 131, 246, 0.2), 5px 0 0 rgba(60, 131, 246, 0.2) !important;
    }
</style>
@endpush
@endsection
