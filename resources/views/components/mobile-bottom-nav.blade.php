{{-- Mobile Fixed Bottom Navigation Bar --}}
@php
    $isHome = request()->routeIs('dashboard');
    $isTransactions = request()->routeIs('transactions*') && !request()->routeIs('transactions.create');
    $isQuickAdd = request()->routeIs('transactions.create');
    $isAccounts = request()->routeIs('accounts*');
    $isMore = request()->routeIs(
        'budgets*', 'goals*', 'debts*', 'receivables*', 'investments*',
        'assets*', 'wealth-statement*', 'profit-loss*', 'gmail-inbox*',
        'categories*', 'subcategories*', 'settings*'
    );
@endphp

<nav id="mobileBottomNav" class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-[#0F172A]/95 backdrop-blur-xl border-t border-slate-200 dark:border-slate-800 transition-colors shadow-lg shadow-black/10">
    <div class="max-w-lg mx-auto px-2 pt-1.5 flex items-center justify-around relative" style="padding-bottom: max(env(safe-area-inset-bottom, 0px), 0.5rem);">
        
        <!-- 1. Home / Dashboard -->
        <a href="{{ route('dashboard') }}" 
           class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 px-1 rounded-xl transition-all duration-150 {{ $isHome ? 'text-primary' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 active:scale-95' }}">
            <span class="material-symbols-outlined text-[23px] leading-none transition-transform {{ $isHome ? 'scale-110 font-bold' : '' }}">
                dashboard
            </span>
            <span class="text-[10px] mt-1 leading-none tracking-tight {{ $isHome ? 'font-bold' : 'font-medium' }}">
                Home
            </span>
            @if($isHome)
                <span class="size-1 bg-primary rounded-full mt-0.5"></span>
            @endif
        </a>

        <!-- 2. Transactions -->
        <a href="{{ route('transactions.index') }}" 
           class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 px-1 rounded-xl transition-all duration-150 {{ $isTransactions ? 'text-primary' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 active:scale-95' }}">
            <span class="material-symbols-outlined text-[23px] leading-none transition-transform {{ $isTransactions ? 'scale-110 font-bold' : '' }}">
                swap_horiz
            </span>
            <span class="text-[10px] mt-1 leading-none tracking-tight {{ $isTransactions ? 'font-bold' : 'font-medium' }}">
                Transaksi
            </span>
            @if($isTransactions)
                <span class="size-1 bg-primary rounded-full mt-0.5"></span>
            @endif
        </a>

        <!-- 3. Quick Add (+) - Prominent Center Action -->
        <div class="flex-1 flex flex-col items-center justify-center px-1">
            <a href="{{ route('transactions.create') }}" 
               aria-label="Tambah Transaksi Cepat"
               class="size-12 -mt-5 rounded-full bg-primary hover:bg-primary/95 text-white flex items-center justify-center shadow-lg shadow-primary/40 ring-4 ring-white dark:ring-[#0F172A] active:scale-90 transition-transform duration-150 {{ $isQuickAdd ? 'ring-primary/40' : '' }}">
                <span class="material-symbols-outlined text-2xl font-bold">add</span>
            </a>
            <span class="text-[10px] mt-1 leading-none tracking-tight {{ $isQuickAdd ? 'text-primary font-bold' : 'text-slate-500 dark:text-slate-400 font-medium' }}">
                Tambah
            </span>
        </div>

        <!-- 4. Accounts -->
        <a href="{{ route('accounts.index') }}" 
           class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 px-1 rounded-xl transition-all duration-150 {{ $isAccounts ? 'text-primary' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 active:scale-95' }}">
            <span class="material-symbols-outlined text-[23px] leading-none transition-transform {{ $isAccounts ? 'scale-110 font-bold' : '' }}">
                account_balance
            </span>
            <span class="text-[10px] mt-1 leading-none tracking-tight {{ $isAccounts ? 'font-bold' : 'font-medium' }}">
                Akun
            </span>
            @if($isAccounts)
                <span class="size-1 bg-primary rounded-full mt-0.5"></span>
            @endif
        </a>

        <!-- 5. More Menu Button -->
        <button type="button" 
                onclick="toggleMobileMoreMenu()" 
                aria-label="Buka Menu Lainnya"
                class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 px-1 rounded-xl transition-all duration-150 {{ $isMore ? 'text-primary' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 active:scale-95' }}">
            <span class="material-symbols-outlined text-[23px] leading-none transition-transform {{ $isMore ? 'scale-110 font-bold' : '' }}">
                grid_view
            </span>
            <span class="text-[10px] mt-1 leading-none tracking-tight {{ $isMore ? 'font-bold' : 'font-medium' }}">
                Lainnya
            </span>
            @if($isMore)
                <span class="size-1 bg-primary rounded-full mt-0.5"></span>
            @endif
        </button>

    </div>
</nav>
