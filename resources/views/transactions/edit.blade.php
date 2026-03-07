@extends('layouts.app')

@section('title', 'Edit Transaction')

@section('content')
<div class="max-w-5xl mx-auto pb-12">
    <!-- Header & Breadcrumbs -->
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 uppercase tracking-widest mb-4">
            <a href="{{ route('transactions.index') }}" class="hover:text-primary transition-colors">Transactions</a>
            <span class="material-symbols-outlined text-sm">chevron_right</span>
            <span class="text-slate-300">Edit Entry</span>
        </nav>
        <h2 class="text-3xl font-black text-white">Edit Transaction</h2>
        <p class="text-slate-400 text-sm mt-1">Update details for this transaction recorded on <span class="text-white font-bold">{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M Y') }}</span>.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-xl bg-rose-500/10 p-4 border border-rose-500/20 flex gap-3 items-start">
            <span class="material-symbols-outlined text-rose-500 mt-0.5">error</span>
            <div class="space-y-1">
                <p class="text-sm font-bold text-rose-500">Validation Errors</p>
                <ul class="list-disc list-inside text-xs text-rose-400/80">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('transactions.update', $transaction) }}" method="POST" id="transaction-form">
        @csrf
        @method('PUT')
        <div class="space-y-8">
            <!-- General Information Card -->
            <div class="bg-card-dark border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-8 py-4 border-b border-slate-800 bg-slate-900/30 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">info</span>
                    <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider">General Information</h3>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <!-- Transaction Type -->
                        <div class="md:col-span-2 space-y-4">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Transaction Type</label>
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                                @foreach(['expense' => 'Expense', 'income' => 'Income', 'transfer' => 'Transfer', 'withdrawal' => 'Withdrawal'] as $val => $label)
                                    <label class="relative flex flex-col items-center gap-2 p-3 rounded-xl border border-slate-800 bg-slate-900/50 cursor-pointer hover:bg-slate-800 transition-all group overflow-hidden">
                                        <input type="radio" name="type" value="{{ $val }}" class="sr-only peer" {{ old('type', $transaction->type) == $val ? 'checked' : '' }}>
                                        <div class="absolute inset-0 border-2 border-primary opacity-0 peer-checked:opacity-100 transition-opacity rounded-xl"></div>
                                        <span class="material-symbols-outlined text-xl text-slate-500 group-hover:text-primary transition-colors peer-checked:text-primary">
                                            @if($val === 'expense') payments @elseif($val === 'income') add_card @elseif($val === 'transfer') swap_horiz @else account_balance_wallet @endif
                                        </span>
                                        <span class="text-[10px] font-bold uppercase tracking-tighter text-slate-400 peer-checked:text-white">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="space-y-2">
                            <label for="transaction_date" class="text-xs font-black text-slate-500 uppercase tracking-widest">Date</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                                    <span class="material-symbols-outlined text-lg">calendar_month</span>
                                </span>
                                <input type="date" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d')) }}" 
                                    class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">
                            </div>
                        </div>

                        <div class="hidden md:block"></div> <!-- Spacer -->

                        <!-- Account Selection Group -->
                        <div class="md:col-span-4 p-6 rounded-2xl bg-slate-900/40 border border-slate-800/50">
                            <div class="flex flex-col md:flex-row md:items-end gap-6">
                                <!-- Source Account -->
                                <div class="flex-1 space-y-2">
                                    <label for="account_id" class="text-xs font-black text-slate-500 uppercase tracking-widest">From Account</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                                            <span class="material-symbols-outlined text-lg">account_balance</span>
                                        </span>
                                        <select name="account_id" id="account_id" required
                                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white font-medium">
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}" {{ old('account_id', $transaction->account_id) == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500">
                                            <span class="material-symbols-outlined text-lg">expand_more</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Flow Arrow -->
                                <div id="transfer_arrow" class="hidden md:flex items-center justify-center pb-3 text-primary transition-all duration-300">
                                    <span class="material-symbols-outlined text-3xl">arrow_forward</span>
                                </div>

                                <!-- Destination Account -->
                                <div id="destination_account_wrapper" class="flex-1 space-y-2 hidden transition-all duration-300">
                                    <label for="to_account_id" class="text-xs font-black text-primary uppercase tracking-widest">To Account (Destination)</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-primary">
                                            <span class="material-symbols-outlined text-lg">account_balance</span>
                                        </span>
                                        <select name="to_account_id" id="to_account_id"
                                            class="w-full pl-11 pr-4 py-3 bg-primary/10 border border-primary/20 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white font-medium">
                                            <option value="">Select Destination</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}" {{ old('to_account_id', $transaction->to_account_id) == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-primary">
                                            <span class="material-symbols-outlined text-lg">expand_more</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Amount Display -->
                        <div class="md:col-span-2 space-y-2">
                            <label for="total_amount" class="text-xs font-black text-slate-500 uppercase tracking-widest">Total Amount</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400 font-bold text-xl">Rp</span>
                                <input type="number" step="0.01" name="total_amount" id="total_amount" value="{{ old('total_amount', (float)$transaction->total_amount) }}" required
                                    class="w-full pl-12 pr-4 py-4 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-black text-3xl placeholder:text-slate-800" 
                                    placeholder="0">
                            </div>
                            <p class="text-[10px] text-slate-500 font-medium">Sum of all line items must equal this total.</p>
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2 space-y-2">
                            <label for="notes" class="text-xs font-black text-slate-500 uppercase tracking-widest">Overall Notes</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-start pt-3.5 pl-4 pointer-events-none text-slate-500">
                                    <span class="material-symbols-outlined text-lg">notes</span>
                                </span>
                                <input type="text" name="notes" id="notes" value="{{ old('notes', $transaction->notes) }}"
                                    class="w-full pl-11 pr-4 py-3.5 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">analytics</span>
                        Line Items Breakdown
                    </h3>
                    <button type="button" id="add-item" class="flex items-center gap-2 px-4 py-2 bg-slate-800 border border-slate-700 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition-all">
                        <span class="material-symbols-outlined text-sm">add_circle</span>
                        Add Item
                    </button>
                </div>
                
                <div id="items-container" class="grid grid-cols-1 gap-4">
                    @forelse($transaction->transactionItems as $index => $item)
                    <div class="transaction-item bg-card-dark border border-slate-800 p-6 rounded-2xl shadow-sm relative group">
                        <button type="button" class="remove-item absolute -top-2 -right-2 size-8 bg-rose-500 text-white rounded-full flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-opacity z-10">
                             <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                        
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                            <!-- Category & Subcategory -->
                            <div class="md:col-span-4 space-y-4">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Category</label>
                                    <select name="items[{{ $index }}][category_id]" onchange="updateSubcategories(this)" required
                                        class="w-full px-4 py-2 bg-slate-900/50 border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white text-sm font-medium">
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old("items.$index.category_id", $item->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ ucwords($category->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="space-y-1.5 subcategory-wrapper">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Subcategory (Optional)</label>
                                    <select name="items[{{ $index }}][subcategory_id]" 
                                        {{ count($categories->find($item->category_id)->subcategories ?? []) > 0 ? '' : 'disabled' }}
                                        class="w-full px-4 py-2 bg-slate-900/50 border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white text-sm font-medium disabled:opacity-30">
                                        <option value="">General</option>
                                        @if($categories->find($item->category_id))
                                            @foreach($categories->find($item->category_id)->subcategories as $sub)
                                                <option value="{{ $sub->id }}" {{ old("items.$index.subcategory_id", $item->subcategory_id) == $sub->id ? 'selected' : '' }}>
                                                    {{ ucwords($sub->name) }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-5 space-y-1.5">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Item Description</label>
                                <textarea name="items[{{ $index }}][description]" rows="3"
                                    class="w-full px-4 py-2 bg-slate-900/50 border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all text-white text-sm font-medium">{{ old("items.$index.description", $item->description) }}</textarea>
                            </div>

                            <!-- Amount -->
                            <div class="md:col-span-3 space-y-1.5 text-right">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Amount</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-500 font-bold text-xs">Rp</span>
                                    <input type="number" step="0.01" name="items[{{ $index }}][amount]" value="{{ old("items.$index.amount", (float)$item->amount) }}" required
                                        class="item-amount w-full pl-9 pr-4 py-2 bg-slate-900 text-right border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all text-white font-black text-lg">
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <!-- Fallback row if items list is empty for some reason -->
                    <div class="transaction-item bg-card-dark border border-slate-800 p-6 rounded-2xl shadow-sm relative group">
                        <button type="button" class="remove-item absolute -top-2 -right-2 size-8 bg-rose-500 text-white rounded-full flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-opacity z-10">
                             <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                             <div class="md:col-span-4 space-y-4">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Category</label>
                                    <select name="items[0][category_id]" onchange="updateSubcategories(this)" required
                                        class="w-full px-4 py-2 bg-slate-900/50 border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white text-sm font-medium">
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ ucwords($category->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="space-y-1.5 subcategory-wrapper">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Subcategory (Optional)</label>
                                    <select name="items[0][subcategory_id]" disabled
                                        class="w-full px-4 py-2 bg-slate-900/50 border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white text-sm font-medium disabled:opacity-30">
                                        <option value="">General</option>
                                    </select>
                                </div>
                            </div>
                            <div class="md:col-span-5 space-y-1.5">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Item Description</label>
                                <textarea name="items[0][description]" rows="3"
                                    class="w-full px-4 py-2 bg-slate-900/50 border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all text-white text-sm font-medium"
                                    placeholder="What was this for?"></textarea>
                            </div>
                            <div class="md:col-span-3 space-y-1.5 text-right">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Amount</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-500 font-bold text-xs">Rp</span>
                                    <input type="number" step="0.01" name="items[0][amount]" required
                                        class="item-amount w-full pl-9 pr-4 py-2 bg-slate-900 text-right border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all text-white font-black text-lg" 
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="pt-8 border-t border-slate-800 flex items-center justify-between">
                <div class="text-slate-500 text-xs italic">
                    <span class="font-bold text-slate-400">Editing Mode:</span> Be careful when changing amounts as it will affect your account balance.
                </div>
                <div class="flex items-center gap-6">
                    <a href="{{ route('transactions.index') }}" class="text-sm font-bold text-slate-400 hover:text-white transition-colors">Discard Changes</a>
                    <button type="submit" class="rounded-xl bg-primary hover:bg-primary/90 px-12 py-4 text-sm font-black text-white shadow-xl shadow-primary/20 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">save</span>
                        Update Transaction
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    const categoryData = @json($categories->keyBy('id'));
    let itemCount = {{ count($transaction->transactionItems) }};

    function updateSubcategories(selectElement) {
        const categoryId = selectElement.value;
        const row = selectElement.closest('.transaction-item');
        const subSelect = row.querySelector('select[name*="subcategory_id"]');
        
        // Clear subcategory
        subSelect.innerHTML = '<option value="">General</option>';
        
        if (categoryId && categoryData[categoryId] && categoryData[categoryId].subcategories && categoryData[categoryId].subcategories.length > 0) {
            categoryData[categoryId].subcategories.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.id;
                option.textContent = sub.name.charAt(0).toUpperCase() + sub.name.slice(1);
                subSelect.appendChild(option);
            });
            subSelect.disabled = false;
        } else {
            subSelect.disabled = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('items-container');
        const addButton = document.getElementById('add-item');

        addButton.addEventListener('click', function() {
            // Use the first row as a base template to clone
            const firstRow = container.querySelector('.transaction-item');
            const template = firstRow.cloneNode(true);
            
            // Update input names for indexing
            template.querySelectorAll('input, select, textarea').forEach(el => {
                const name = el.getAttribute('name');
                if (name) {
                    el.setAttribute('name', name.replace(/\[\d+\]/, `[${itemCount}]`));
                }
                // Clear values and reset state
                if (el.tagName === 'SELECT' && el.getAttribute('name').includes('subcategory_id')) {
                    el.innerHTML = '<option value="">General</option>';
                    el.disabled = true;
                } else if (el.tagName === 'SELECT' && el.getAttribute('name').includes('category_id')) {
                    el.value = '';
                } else {
                    el.value = '';
                }
            });
            
            container.appendChild(template);
            itemCount++;
        });

        container.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-item');
            if (removeBtn) {
                if (container.querySelectorAll('.transaction-item').length > 1) {
                    removeBtn.closest('.transaction-item').remove();
                } else {
                    alert('You must have at least one line item per transaction.');
                }
            }
        });

        // Handle Transfer Type Toggle
        const typeInputs = document.querySelectorAll('input[name="type"]');
        const destWrapper = document.getElementById('destination_account_wrapper');
        const transferArrow = document.getElementById('transfer_arrow');
        const toAccountSelect = document.getElementById('to_account_id');

        function toggleTransferUI(type) {
            if (type === 'transfer') {
                destWrapper.classList.remove('hidden');
                transferArrow.classList.remove('hidden');
                transferArrow.classList.add('md:flex');
                toAccountSelect.required = true;
            } else {
                destWrapper.classList.add('hidden');
                transferArrow.classList.add('hidden');
                transferArrow.classList.remove('md:flex');
                toAccountSelect.required = false;
                toAccountSelect.value = '';
            }
        }

        typeInputs.forEach(input => {
            input.addEventListener('change', function() {
                toggleTransferUI(this.value);
            });
            // Initial state check for edit mode
            if (input.checked) {
                toggleTransferUI(input.value);
            }
        });
    });

    // Option: Helper to sync total amount sum
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-amount')) {
            const totalDisplay = document.getElementById('total_amount');
            if (totalDisplay && !totalDisplay.matches(':focus')) { 
                let currentSum = 0;
                document.querySelectorAll('.item-amount').forEach(input => {
                    currentSum += parseFloat(input.value || 0);
                });
                totalDisplay.value = currentSum.toFixed(2);
            }
        }
    });
</script>
@endsection
