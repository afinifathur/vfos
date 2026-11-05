@extends('layouts.app')

@section('title', 'Record Transaction')

@section('content')

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- 1. MOBILE QUICK ADD (Optimized for One-Handed iPhone Daily Entry)          --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="block md:hidden max-w-lg mx-auto pb-8">
    <!-- Mobile Top Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('transactions.index') }}" 
               class="size-9 rounded-xl bg-slate-800/80 border border-slate-700 text-slate-400 hover:text-white flex items-center justify-center active:scale-95 transition-all"
               title="Kembali ke Daftar">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
            </a>
            <div>
                <h2 class="text-lg font-black text-white font-outfit leading-tight">Quick Add</h2>
                <p class="text-[11px] text-slate-400">Catat transaksi harian cepat</p>
            </div>
        </div>
        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-primary/10 text-primary border border-primary/20">
            Fokus Cepat
        </span>
    </div>

    <!-- Live Success Banner -->
    <div id="mobile_success_banner" class="hidden mb-4 p-3.5 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs shadow-lg shadow-emerald-500/5 transition-all">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-400 text-lg">check_circle</span>
                <span id="mobile_success_text" class="font-bold">Transaksi berhasil dicatat!</span>
            </div>
            <button type="button" onclick="this.closest('#mobile_success_banner').classList.add('hidden')" class="text-emerald-400/80 hover:text-emerald-300 text-base leading-none">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
    </div>

    <!-- Live Error Banner -->
    <div id="mobile_error_banner" class="hidden mb-4 p-3.5 rounded-2xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs shadow-lg shadow-rose-500/5 transition-all">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-rose-400 text-lg">error</span>
                <span id="mobile_error_text" class="font-medium">Periksa kembali data Anda.</span>
            </div>
            <button type="button" onclick="this.closest('#mobile_error_banner').classList.add('hidden')" class="text-rose-400/80 hover:text-rose-300 text-base leading-none">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-xl bg-rose-500/10 p-3 border border-rose-500/20 text-xs text-rose-400">
            <p class="font-bold mb-1">Terjadi kesalahan validasi:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Mobile Quick Add Form -->
    <form id="mobile_quick_add_form" method="POST" action="{{ route('transactions.store') }}" novalidate class="space-y-4">
        @csrf
        
        <!-- 1. Transaction Type Selector (Segmented Toggle) -->
        <div class="p-1 bg-slate-900/90 rounded-2xl border border-slate-800 grid grid-cols-2 gap-1 shadow-sm">
            <button type="button" 
                    id="mobile_type_btn_expense" 
                    onclick="setMobileType('expense')" 
                    class="py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 bg-rose-500/20 text-rose-400 border border-rose-500/30 shadow">
                <span class="material-symbols-outlined text-base">payments</span>
                <span>Pengeluaran</span>
            </button>
            <button type="button" 
                    id="mobile_type_btn_income" 
                    onclick="setMobileType('income')" 
                    class="py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 text-slate-400 hover:text-white border border-transparent">
                <span class="material-symbols-outlined text-base">add_card</span>
                <span>Pemasukan</span>
            </button>
        </div>
        <input type="hidden" name="type" id="mobile_type_input" value="expense">

        <!-- 2. Prominent Amount Card -->
        <div class="bg-card-dark border-2 border-slate-800 focus-within:border-primary/60 rounded-2xl p-4 shadow-xl transition-all">
            <div class="flex items-center justify-between mb-1.5">
                <label for="mobile_amount_display" class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                    Nominal Transaksi
                </label>
                <span id="mobile_type_badge" class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-400">
                    Expense
                </span>
            </div>
            
            <div class="flex items-baseline gap-2 py-1">
                <span class="text-2xl font-black text-slate-400 select-none">Rp</span>
                <input type="text" 
                       id="mobile_amount_display" 
                       inputmode="numeric" 
                       placeholder="0" 
                       autocomplete="off"
                       class="w-full bg-transparent border-none p-0 text-3xl font-black text-white placeholder:text-slate-600 focus:ring-0 outline-none leading-none tracking-tight">
                {{-- Real raw numeric value for payload/fallback --}}
                <input type="hidden" name="items[0][amount]" id="mobile_amount_raw" value="{{ old('items.0.amount', '') }}">
            </div>

            <!-- Quick Amount Chips -->
            <div class="pt-3 mt-2 border-t border-slate-800/80 flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                <button type="button" onclick="addMobilePreset(2000)" class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 text-[11px] font-bold border border-slate-700/60 active:scale-95 transition-all flex-shrink-0">
                    +2k
                </button>
                <button type="button" onclick="addMobilePreset(5000)" class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 text-[11px] font-bold border border-slate-700/60 active:scale-95 transition-all flex-shrink-0">
                    +5k
                </button>
                <button type="button" onclick="addMobilePreset(10000)" class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 text-[11px] font-bold border border-slate-700/60 active:scale-95 transition-all flex-shrink-0">
                    +10k
                </button>
                <button type="button" onclick="addMobilePreset(20000)" class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 text-[11px] font-bold border border-slate-700/60 active:scale-95 transition-all flex-shrink-0">
                    +20k
                </button>
                <button type="button" onclick="addMobilePreset(50000)" class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 text-[11px] font-bold border border-slate-700/60 active:scale-95 transition-all flex-shrink-0">
                    +50k
                </button>
                <button type="button" onclick="clearMobileAmount()" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-[11px] font-bold border border-rose-500/20 active:scale-95 transition-all flex-shrink-0 ml-auto">
                    Reset
                </button>
            </div>
        </div>

        <!-- 3. Account & Date Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- Account -->
            <div class="space-y-1.5">
                <label for="mobile_account_id" class="text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center justify-between">
                    <span>Akun</span>
                    @if($defaultAccountId)
                        <span class="text-[9px] text-emerald-400 font-semibold lowercase">default: cash</span>
                    @endif
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">account_balance_wallet</span>
                    <select name="account_id" id="mobile_account_id" required
                            class="w-full pl-10 pr-9 py-3 bg-slate-900/60 border border-slate-800 rounded-xl text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all dark-select">
                        @if(!$defaultAccountId)
                            <option value="" disabled selected>Pilih Akun...</option>
                        @endif
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('account_id', $defaultAccountId) == $acc->id ? 'selected' : '' }}>
                                {{ $acc->name }} ({{ ucfirst($acc->type) }})
                            </option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">expand_more</span>
                </div>
            </div>

            <!-- Date -->
            <div class="space-y-1.5">
                <label for="mobile_transaction_date" class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                    Tanggal
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">calendar_today</span>
                    <input type="date" 
                           name="transaction_date" 
                           id="mobile_transaction_date" 
                           value="{{ old('transaction_date', date('Y-m-d')) }}" 
                           class="w-full pl-10 pr-4 py-3 bg-slate-900/60 border border-slate-800 rounded-xl text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                </div>
            </div>
        </div>

        <!-- 4. Category & Subcategory Box -->
        <div class="space-y-3 p-4 bg-slate-900/40 rounded-2xl border border-slate-800/80">
            <!-- Category -->
            <div class="space-y-1.5">
                <label for="mobile_category_id" class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                    Kategori <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">category</span>
                    <select name="items[0][category_id]" 
                            id="mobile_category_id" 
                            onchange="updateMobileSubcategories()"
                            required
                            class="w-full pl-10 pr-9 py-3 bg-slate-900/80 border border-slate-800 rounded-xl text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all dark-select">
                        <option value="">Pilih Kategori...</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">expand_more</span>
                </div>
            </div>

            <!-- Subcategory -->
            <div class="space-y-1.5">
                <label for="mobile_subcategory_id" class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                    Subkategori (Opsional)
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">folder_open</span>
                    <select name="items[0][subcategory_id]" 
                            id="mobile_subcategory_id"
                            disabled
                            class="w-full pl-10 pr-9 py-3 bg-slate-900/80 border border-slate-800 rounded-xl text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all disabled:opacity-40 dark-select">
                        <option value="">General (Utama)</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">expand_more</span>
                </div>
            </div>
        </div>

        <!-- 5. Description -->
        <div class="space-y-1.5">
            <label for="mobile_description" class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                Deskripsi / Catatan Singkat
            </label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">edit_note</span>
                <input type="text" 
                       name="items[0][description]" 
                       id="mobile_description" 
                       value="{{ old('items.0.description', '') }}"
                       placeholder="Contoh: Parkir, Jajan, Makan siang..." 
                       maxlength="255"
                       class="w-full pl-10 pr-4 py-3 bg-slate-900/60 border border-slate-800 rounded-xl text-sm text-white placeholder:text-slate-500 font-medium focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
            </div>
        </div>

        <!-- 6. Save Action -->
        <div class="pt-2 space-y-3">
            <button type="submit" 
                    id="mobile_submit_btn" 
                    class="w-full py-4 px-6 rounded-2xl bg-primary hover:bg-primary/95 active:scale-[0.98] text-white font-black text-sm shadow-xl shadow-primary/30 flex items-center justify-center gap-2 transition-all">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                <span>Simpan Transaksi</span>
            </button>

            <a href="{{ route('transactions.index') }}" 
               class="w-full py-2.5 text-xs text-slate-400 hover:text-white font-semibold flex items-center justify-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-sm">list</span>
                <span>Lihat Riwayat Transaksi</span>
            </a>
        </div>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- 2. DESKTOP TRANSACTION ENTRY (Preserved 100% with All Split Features)      --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="hidden md:block max-w-5xl mx-auto pb-12">
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
                                                <option value="{{ $account->id }}" {{ old('account_id', $defaultAccountId ?? '') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
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
                    <!-- Template Item (First item) -->
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
    // Categories and Accounts data
    const allCategories = @json($categories);
    const categoryData = @json($categories->keyBy('id'));
    let itemCount = 1;

    // ── Mobile Quick Add Logic ──────────────────────────────────────────────
    let activeMobileType = 'expense';

    window.setMobileType = function(type) {
        activeMobileType = type;
        const typeInput = document.getElementById('mobile_type_input');
        if (typeInput) typeInput.value = type;

        const btnExpense = document.getElementById('mobile_type_btn_expense');
        const btnIncome = document.getElementById('mobile_type_btn_income');
        const badge = document.getElementById('mobile_type_badge');

        if (type === 'expense') {
            btnExpense.className = "py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 bg-rose-500/20 text-rose-400 border border-rose-500/30 shadow";
            btnIncome.className = "py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 text-slate-400 hover:text-white border border-transparent";
            if (badge) {
                badge.textContent = 'Expense';
                badge.className = 'text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-400';
            }
        } else {
            btnExpense.className = "py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 text-slate-400 hover:text-white border border-transparent";
            btnIncome.className = "py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 shadow";
            if (badge) {
                badge.textContent = 'Income';
                badge.className = 'text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400';
            }
        }

        renderMobileCategories(type);
    };

    function renderMobileCategories(type) {
        const select = document.getElementById('mobile_category_id');
        if (!select) return;

        select.innerHTML = '<option value="">Pilih Kategori...</option>';
        const filtered = allCategories.filter(c => c.type === type);
        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name.charAt(0).toUpperCase() + c.name.slice(1);
            select.appendChild(opt);
        });
        updateMobileSubcategories();
    }

    window.updateMobileSubcategories = function() {
        const catSelect = document.getElementById('mobile_category_id');
        const subSelect = document.getElementById('mobile_subcategory_id');
        if (!catSelect || !subSelect) return;

        const catId = catSelect.value;
        subSelect.innerHTML = '<option value="">General (Utama)</option>';

        if (!catId) {
            subSelect.disabled = true;
            return;
        }

        const cat = allCategories.find(c => String(c.id) === String(catId));
        if (cat && cat.subcategories && cat.subcategories.length > 0) {
            cat.subcategories.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name.charAt(0).toUpperCase() + sub.name.slice(1);
                subSelect.appendChild(opt);
            });
            subSelect.disabled = false;
        } else {
            subSelect.disabled = true;
        }
    };

    // Amount input handling
    const mobileAmountDisplay = document.getElementById('mobile_amount_display');
    const mobileAmountRaw = document.getElementById('mobile_amount_raw');

    function setMobileAmount(numericVal) {
        if (!numericVal || isNaN(numericVal) || numericVal <= 0) {
            mobileAmountDisplay.value = '';
            mobileAmountRaw.value = '';
            return;
        }
        mobileAmountRaw.value = numericVal;
        mobileAmountDisplay.value = new Intl.NumberFormat('id-ID').format(numericVal);
    }

    if (mobileAmountDisplay) {
        mobileAmountDisplay.addEventListener('input', function() {
            const digits = this.value.replace(/\D/g, '');
            if (!digits) {
                mobileAmountRaw.value = '';
                this.value = '';
                return;
            }
            const num = parseInt(digits, 10);
            mobileAmountRaw.value = num;
            this.value = new Intl.NumberFormat('id-ID').format(num);
        });
    }

    // Initialize display value from old/preset raw value if present
    if (mobileAmountRaw && mobileAmountRaw.value) {
        const initVal = parseFloat(mobileAmountRaw.value);
        if (!isNaN(initVal) && initVal > 0) {
            setMobileAmount(initVal);
        }
    }

    window.addMobilePreset = function(addVal) {
        const current = parseInt(mobileAmountRaw.value || 0, 10);
        setMobileAmount(current + addVal);
    };

    window.clearMobileAmount = function() {
        setMobileAmount(0);
        if (mobileAmountDisplay) mobileAmountDisplay.focus();
    };

    function showMobileSuccess(message) {
        const banner = document.getElementById('mobile_success_banner');
        const text = document.getElementById('mobile_success_text');
        const errorBanner = document.getElementById('mobile_error_banner');
        if (errorBanner) errorBanner.classList.add('hidden');
        if (banner && text) {
            text.textContent = message;
            banner.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function showMobileError(message) {
        const banner = document.getElementById('mobile_error_banner');
        const text = document.getElementById('mobile_error_text');
        const successBanner = document.getElementById('mobile_success_banner');
        if (successBanner) successBanner.classList.add('hidden');
        if (banner && text) {
            text.textContent = message;
            banner.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    // Mobile Form Submission
    const mobileForm = document.getElementById('mobile_quick_add_form');
    if (mobileForm) {
        mobileForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const successBanner = document.getElementById('mobile_success_banner');
            const errorBanner = document.getElementById('mobile_error_banner');
            if (successBanner) successBanner.classList.add('hidden');
            if (errorBanner) errorBanner.classList.add('hidden');

            const rawAmount = parseFloat(mobileAmountRaw.value || 0);
            if (isNaN(rawAmount) || rawAmount <= 0) {
                showMobileError('Silakan masukkan jumlah nominal transaksi yang valid.');
                mobileAmountDisplay.focus();
                return;
            }

            const categoryId = document.getElementById('mobile_category_id').value;
            if (!categoryId) {
                showMobileError('Silakan pilih kategori transaksi.');
                document.getElementById('mobile_category_id').focus();
                return;
            }

            const accountId = document.getElementById('mobile_account_id').value;
            if (!accountId) {
                showMobileError('Silakan pilih akun sumber transaksi.');
                document.getElementById('mobile_account_id').focus();
                return;
            }

            const submitBtn = document.getElementById('mobile_submit_btn');
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined text-lg animate-spin">progress_activity</span><span>Menyimpan...</span>';

            const payload = {
                account_id: parseInt(accountId, 10),
                type: document.getElementById('mobile_type_input').value,
                transaction_date: document.getElementById('mobile_transaction_date').value,
                items: [
                    {
                        category_id: parseInt(categoryId, 10),
                        subcategory_id: document.getElementById('mobile_subcategory_id').value ? parseInt(document.getElementById('mobile_subcategory_id').value, 10) : null,
                        description: document.getElementById('mobile_description').value.trim() || null,
                        amount: rawAmount
                    }
                ]
            };

            try {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

                const res = await fetch("{{ route('transactions.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    const formatted = new Intl.NumberFormat('id-ID').format(rawAmount);
                    showMobileSuccess(`Transaksi Rp ${formatted} berhasil dicatat!`);

                    // Reset form fields for next entry
                    setMobileAmount(0);
                    document.getElementById('mobile_description').value = '';
                    document.getElementById('mobile_category_id').value = '';
                    updateMobileSubcategories();

                    // Re-focus amount for rapid entry
                    setTimeout(() => {
                        mobileAmountDisplay.focus();
                    }, 50);
                } else {
                    let msg = data.message || 'Terjadi kesalahan saat menyimpan transaksi.';
                    if (data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        if (firstKey && data.errors[firstKey][0]) {
                            msg = data.errors[firstKey][0];
                        }
                    }
                    showMobileError(msg);
                }
            } catch (err) {
                console.error(err);
                // Fallback to standard HTTP form submission if fetch fails
                mobileForm.submit();
                return;
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        });
    }

    // ── Desktop Form Logic (Preserved) ───────────────────────────────────────
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
        // Initialize mobile categories for default 'expense'
        renderMobileCategories('expense');

        const container = document.getElementById('items-container');
        const addButton = document.getElementById('add-item');

        if (addButton && container) {
            addButton.addEventListener('click', function() {
                const template = container.querySelector('.transaction-item').cloneNode(true);
                
                template.querySelectorAll('input, select, textarea').forEach(el => {
                    const name = el.getAttribute('name');
                    if (name) {
                        el.setAttribute('name', name.replace(/\[\d+\]/, `[${itemCount}]`));
                    }
                    
                    if (el.classList.contains('item-amount')) {
                        el.value = '';
                        el.removeAttribute('data-number-formatted');
                        el.setAttribute('type', 'number');
                    } else if (el.tagName === 'SELECT' && el.getAttribute('name').includes('subcategory_id')) {
                        el.innerHTML = '<option value="">General</option>';
                        el.disabled = true;
                    } else {
                        el.value = '';
                    }
                });
                
                container.appendChild(template);
                itemCount++;
                
                if (window.initializeNumberFormatting) {
                    window.initializeNumberFormatting(template);
                }
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

            // Initialize first row subcategories if needed
            const firstSelect = container.querySelector('select[name*="category_id"]');
            if (firstSelect && firstSelect.value) {
                updateSubcategories(firstSelect);
                const oldSubId = "{{ old('items.0.subcategory_id') }}";
                if (oldSubId) {
                    const subSelect = container.querySelector('select[name*="subcategory_id"]');
                    if (subSelect) subSelect.value = oldSubId;
                }
            }
        }

        // Handle Desktop Transfer Type Toggle
        const typeInputs = document.querySelectorAll('input[name="type"]:not(#mobile_type_input)');
        const destWrapper = document.getElementById('destination_account_wrapper');
        const transferArrow = document.getElementById('transfer_arrow');
        const toAccountInput = document.getElementById('to_account_id');

        function toggleTransferUI(type) {
            if (!destWrapper || !transferArrow || !toAccountInput) return;
            if (type === 'transfer') {
                destWrapper.classList.remove('hidden');
                transferArrow.classList.remove('hidden');
                transferArrow.classList.add('md:flex');
            } else {
                destWrapper.classList.add('hidden');
                transferArrow.classList.add('hidden');
                transferArrow.classList.remove('md:flex');
                toAccountInput.value = '';
                const destLabel = document.getElementById('to_account_label');
                if (destLabel) {
                    destLabel.textContent = 'Select Destination';
                    destLabel.classList.add('text-slate-400');
                    destLabel.classList.remove('text-white');
                }
                document.querySelectorAll('.to-account-option').forEach(btn => btn.classList.remove('bg-primary/20', 'text-white'));
            }
        }

        typeInputs.forEach(input => {
            input.addEventListener('change', function() {
                toggleTransferUI(this.value);
            });
            if (input.checked) {
                toggleTransferUI(input.value);
            }
        });

        // ── Custom To Account Dropdown Logic ──
        const trigger  = document.getElementById('to_account_trigger');
        const panel    = document.getElementById('to_account_panel');
        const chevron  = document.getElementById('to_account_chevron');
        const label    = document.getElementById('to_account_label');

        if (trigger && panel && chevron && label) {
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

                    document.querySelectorAll('.to-account-option').forEach(b => b.classList.remove('bg-primary/20', 'text-white', 'font-bold'));
                    this.classList.add('bg-primary/20', 'text-white', 'font-bold');

                    panel.classList.add('hidden');
                    chevron.style.transform = '';
                    trigger.classList.remove('ring-2', 'ring-primary', 'border-primary');
                });
            });

            document.addEventListener('click', function(e) {
                const wrapper = document.getElementById('to_account_dropdown');
                if (wrapper && !wrapper.contains(e.target)) {
                    panel.classList.add('hidden');
                    chevron.style.transform = '';
                    trigger.classList.remove('ring-2', 'ring-primary', 'border-primary');
                }
            });
        }
    });

    function recalcTotal() {
        let sum = 0;
        document.querySelectorAll('.item-amount').forEach(input => {
            const val = (input.value || '0').replace(/,/g, '');
            sum += parseFloat(val || 0);
        });
        const totalAmountInput = document.getElementById('total_amount');
        const totalDisplay = document.getElementById('total_display');
        if (totalAmountInput) totalAmountInput.value = sum.toFixed(2);
        if (totalDisplay) totalDisplay.textContent = new Intl.NumberFormat('id-ID').format(sum);
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-amount')) {
            recalcTotal();
        }
    });

    const itemsContainer = document.getElementById('items-container');
    if (itemsContainer) {
        itemsContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                setTimeout(recalcTotal, 50);
            }
        });
    }
</script>

<style>
/* Custom scrollbar for dropdown */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }

/* Force dark color-scheme on native selects */
select.dark-select, select.dark-select option {
    color-scheme: dark;
    background-color: #0f172a;
    color: #f1f5f9;
}
select.dark-select option:hover,
select.dark-select option:checked {
    background-color: #1e3a5f;
}

/* Hide scrollbar for quick chips */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endsection
