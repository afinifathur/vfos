@extends('layouts.app')

@section('title', 'Record Transaction')

@section('content')
<div class="max-w-5xl mx-auto pb-12">
    <!-- Header & Breadcrumbs -->
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 uppercase tracking-widest mb-4">
            <a href="{{ route('transactions.index') }}" class="hover:text-primary transition-colors">Transactions</a>
            <span class="material-symbols-outlined text-sm">chevron_right</span>
            <span class="text-slate-300">New Entry</span>
        </nav>
        <h2 class="text-3xl font-black text-white">Record Transaction</h2>
        <p class="text-slate-400 text-sm mt-1">Add a new financial event to your ledger with detailed categorization.</p>
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

    <form action="{{ route('transactions.store') }}" method="POST" id="transaction-form">
        @csrf
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
                                        <input type="radio" name="type" value="{{ $val }}" class="sr-only peer" {{ old('type', $val === 'expense' ? 'expense' : '') == $val ? 'checked' : '' }}>
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
                                <input type="date" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}" 
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
                                                <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
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

                                <!-- Destination Account (Custom Dark Dropdown) -->
                                <div id="destination_account_wrapper" class="flex-1 space-y-2 hidden transition-all duration-300">
                                    <label class="text-xs font-black text-primary uppercase tracking-widest">To Account (Destination)</label>
                                    {{-- Hidden real input for form submission --}}
                                    <input type="hidden" name="to_account_id" id="to_account_id" value="{{ old('to_account_id') }}">
                                    <div class="relative" id="to_account_dropdown">
                                        {{-- Trigger Button --}}
                                        <button type="button" id="to_account_trigger"
                                            class="w-full flex items-center gap-3 pl-4 pr-4 py-2.5 bg-slate-900/50 border border-primary/30 rounded-xl text-white font-medium transition-all hover:border-primary focus:outline-none focus:ring-2 focus:ring-primary">
                                            <span class="text-primary material-symbols-outlined text-lg flex-shrink-0">account_balance</span>
                                            <span id="to_account_label" class="flex-1 text-left text-slate-400 truncate">Select Destination</span>
                                            <span class="material-symbols-outlined text-primary text-lg flex-shrink-0 transition-transform duration-200" id="to_account_chevron">expand_more</span>
                                        </button>
                                        {{-- Dropdown Panel --}}
                                        <div id="to_account_panel"
                                            class="absolute z-50 mt-2 w-full bg-slate-900 border border-primary/20 rounded-xl shadow-2xl shadow-black/40 overflow-hidden hidden">
                                            <div class="max-h-56 overflow-y-auto custom-scrollbar">
                                                @foreach($accounts as $account)
                                                <button type="button"
                                                    data-value="{{ $account->id }}"
                                                    data-label="{{ $account->name }}"
                                                    class="to-account-option w-full flex items-center gap-3 px-4 py-1.5 text-left text-sm font-medium text-slate-300 hover:bg-primary/10 hover:text-white transition-colors">
                                                    <span class="material-symbols-outlined text-base text-slate-500">account_balance</span>
                                                    {{ $account->name }}
                                                </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Amount (auto-calculated, read-only) -->
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Total Amount</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400 font-bold text-xl">Rp</span>
                                <div id="total_display" class="w-full pl-12 pr-4 py-4 bg-slate-800/60 border border-slate-700 rounded-xl text-white font-black text-3xl cursor-default select-none">0</div>
                                <input type="hidden" name="total_amount" id="total_amount" value="{{ old('total_amount', 0) }}">
                            </div>
                            <p class="text-[10px] text-slate-500 font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">auto_fix_high</span>
                                Auto-calculated from line items below.
                            </p>
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
                    <!-- Template Item (Hidden or first one) -->
                    <div class="transaction-item bg-card-dark border border-slate-800 p-6 rounded-2xl shadow-sm relative group">
                        <button type="button" class="remove-item absolute -top-2 -right-2 size-8 bg-rose-500 text-white rounded-full flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-opacity z-10">
                             <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                        
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                            <!-- Category & Subcategory -->
                            <div class="md:col-span-4 space-y-4">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Category</label>
                                    <select name="items[0][category_id]" onchange="updateSubcategories(this)" required
                                        class="w-full px-4 py-2 bg-slate-900/50 border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white text-sm font-medium dark-select">
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('items.0.category_id') == $category->id ? 'selected' : '' }}>
                                                {{ ucwords($category->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="space-y-1.5 subcategory-wrapper">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Subcategory (Optional)</label>
                                    <select name="items[0][subcategory_id]" disabled
                                        class="w-full px-4 py-2 bg-slate-900/50 border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white text-sm font-medium disabled:opacity-30 dark-select">
                                        <option value="">General</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-5 space-y-1.5">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Item Description</label>
                                <textarea name="items[0][description]" rows="3"
                                    class="w-full px-4 py-2 bg-slate-900/50 border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all text-white text-sm font-medium"
                                    placeholder="What was this for?">{{ old('items.0.description') }}</textarea>
                            </div>

                            <!-- Amount -->
                            <div class="md:col-span-3 space-y-1.5 text-right">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Amount</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-500 font-bold text-xs">Rp</span>
                                    <input type="number" step="0.01" name="items[0][amount]" value="{{ old('items.0.amount') }}" required
                                        class="item-amount w-full pl-9 pr-4 py-2 bg-slate-900 text-right border border-slate-800 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all text-white font-black text-lg" 
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="pt-8 border-t border-slate-800 flex items-center justify-between">
                <div class="text-slate-500 text-xs italic">
                    <span class="font-bold text-slate-400">Tip:</span> You can split a single transaction (e.g., a grocery bill) into multiple categories.
                </div>
                <div class="flex items-center gap-6">
                    <a href="{{ route('transactions.index') }}" class="text-sm font-bold text-slate-400 hover:text-white transition-colors">Discard</a>
                    <button type="submit" class="rounded-xl bg-primary hover:bg-primary/90 px-12 py-4 text-sm font-black text-white shadow-xl shadow-primary/20 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">save</span>
                        Save Transaction
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    const categoryData = @json($categories->keyBy('id'));
    let itemCount = 1;

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
            const template = container.querySelector('.transaction-item').cloneNode(true);
            
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

        // Initialize first row subcategories if needed (for old input)
        const firstSelect = container.querySelector('select[name*="category_id"]');
        if (firstSelect.value) {
            updateSubcategories(firstSelect);
            const oldSubId = "{{ old('items.0.subcategory_id') }}";
            if (oldSubId) {
                const subSelect = container.querySelector('select[name*="subcategory_id"]');
                subSelect.value = oldSubId;
            }
        }

        // Handle Transfer Type Toggle
        const typeInputs = document.querySelectorAll('input[name="type"]');
        const destWrapper = document.getElementById('destination_account_wrapper');
        const transferArrow = document.getElementById('transfer_arrow');
        const toAccountInput = document.getElementById('to_account_id');

        function toggleTransferUI(type) {
            if (type === 'transfer') {
                destWrapper.classList.remove('hidden');
                transferArrow.classList.remove('hidden');
                transferArrow.classList.add('md:flex');
            } else {
                destWrapper.classList.add('hidden');
                transferArrow.classList.add('hidden');
                transferArrow.classList.remove('md:flex');
                toAccountInput.value = '';
                document.getElementById('to_account_label').textContent = 'Select Destination';
                document.getElementById('to_account_label').classList.add('text-slate-400');
                document.getElementById('to_account_label').classList.remove('text-white');
                // Deselect all options visually
                document.querySelectorAll('.to-account-option').forEach(btn => btn.classList.remove('bg-primary/20', 'text-white'));
            }
        }

        typeInputs.forEach(input => {
            input.addEventListener('change', function() {
                toggleTransferUI(this.value);
            });
            // Initial state check
            if (input.checked) {
                toggleTransferUI(input.value);
            }
        });

        // ── Custom To Account Dropdown Logic ──
        const trigger  = document.getElementById('to_account_trigger');
        const panel    = document.getElementById('to_account_panel');
        const chevron  = document.getElementById('to_account_chevron');
        const label    = document.getElementById('to_account_label');

        trigger.addEventListener('click', function() {
            const isOpen = !panel.classList.contains('hidden');
            panel.classList.toggle('hidden', isOpen);
            chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
            trigger.classList.toggle('ring-2', !isOpen);
            trigger.classList.toggle('ring-primary', !isOpen);
            trigger.classList.toggle('border-primary', !isOpen);
        });

        document.querySelectorAll('.to-account-option').forEach(btn => {
            btn.addEventListener('click', function() {
                const value = this.dataset.value;
                const name  = this.dataset.label;

                toAccountInput.value = value;
                label.textContent = name;
                label.classList.remove('text-slate-400');
                label.classList.add('text-white');

                // Highlight selected
                document.querySelectorAll('.to-account-option').forEach(b => b.classList.remove('bg-primary/20', 'text-white', 'font-bold'));
                this.classList.add('bg-primary/20', 'text-white', 'font-bold');

                // Close panel
                panel.classList.add('hidden');
                chevron.style.transform = '';
                trigger.classList.remove('ring-2', 'ring-primary', 'border-primary');
            });
        });

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('to_account_dropdown');
            if (wrapper && !wrapper.contains(e.target)) {
                panel.classList.add('hidden');
                chevron.style.transform = '';
                trigger.classList.remove('ring-2', 'ring-primary', 'border-primary');
            }
        });
    });

    function recalcTotal() {
        let sum = 0;
        document.querySelectorAll('.item-amount').forEach(input => {
            sum += parseFloat(input.value || 0);
        });
        document.getElementById('total_amount').value = sum.toFixed(2);
        document.getElementById('total_display').textContent = new Intl.NumberFormat('id-ID').format(sum);
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-amount')) {
            recalcTotal();
        }
    });

    // Also recalc after item is removed
    document.getElementById('items-container').addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            setTimeout(recalcTotal, 50);
        }
    });
</script>
<style>
/* Custom scrollbar for dropdown */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }

/* Force dark color-scheme on all native selects so browser renders options dark */
select.dark-select, select.dark-select option {
    color-scheme: dark;
    background-color: #0f172a;
    color: #f1f5f9;
}
select.dark-select option:hover,
select.dark-select option:checked {
    background-color: #1e3a5f;
}
</style>
@endsection
