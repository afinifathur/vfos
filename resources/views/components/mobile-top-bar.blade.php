{{-- Mobile Top Bar --}}
<header class="md:hidden sticky top-0 z-30 bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 transition-colors">
    <div class="px-4 py-2.5 flex items-center justify-between gap-3" style="padding-top: max(env(safe-area-inset-top, 0px), 0.625rem);">
        <!-- Left: Brand Logo & Title -->
        <div class="flex items-center gap-2.5 min-w-0">
            <a href="{{ route('dashboard') }}" class="flex-shrink-0 size-8 bg-primary rounded-lg flex items-center justify-center text-white shadow-md shadow-primary/20 active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-lg">account_balance_wallet</span>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-primary leading-none">vFOS</span>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500">•</span>
                    <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400 leading-none">{{ now()->format('M Y') }}</span>
                </div>
                <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate leading-tight mt-0.5">
                    @yield('title', 'Dashboard')
                </h1>
            </div>
        </div>

        <!-- Right: Actions (Notification & User Profile Shortcut) -->
        <div class="flex items-center gap-1.5 flex-shrink-0">
            <a href="{{ route('transactions.create') }}" class="size-8 rounded-full bg-primary/10 text-primary flex items-center justify-center active:scale-95 transition-transform" title="Quick Add">
                <span class="material-symbols-outlined text-lg">add</span>
            </a>
            <button type="button" onclick="openMobileMoreMenu()" class="size-8 rounded-full bg-slate-100 dark:bg-card-dark text-slate-600 dark:text-slate-300 flex items-center justify-center active:scale-95 transition-transform" title="Menu & Profil" aria-label="Buka Menu">
                <div class="size-6 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
            </button>
        </div>
    </div>
</header>
