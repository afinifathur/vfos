{{-- Mobile More Menu Drawer / Bottom Sheet --}}
<div id="mobileMoreMenu" class="md:hidden fixed inset-0 z-50 pointer-events-none transition-all duration-300">
    <!-- Backdrop Overlay -->
    <div id="mobileMoreBackdrop" 
         onclick="closeMobileMoreMenu()" 
         class="absolute inset-0 bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    </div>

    <!-- Slide-up Bottom Sheet -->
    <div id="mobileMoreSheet" 
         class="absolute inset-x-0 bottom-0 max-h-[85vh] max-h-[85dvh] bg-white dark:bg-[#1E293B] rounded-t-3xl shadow-2xl flex flex-col min-h-0 transform translate-y-full transition-transform duration-300 ease-out border-t border-slate-200 dark:border-slate-700/60 overscroll-contain">
        
        <!-- Sheet Handle & Header -->
        <div class="px-5 pt-3 pb-3 flex-shrink-0 border-b border-slate-100 dark:border-slate-800">
            <!-- Drag Indicator -->
            <div class="w-10 h-1 bg-slate-300 dark:bg-slate-600 rounded-full mx-auto mb-3"></div>
            
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="size-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-base">widgets</span>
                    </div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Semua Modul</h2>
                </div>
                <button type="button" 
                        onclick="closeMobileMoreMenu()" 
                        class="size-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center active:scale-95 transition-all"
                        aria-label="Tutup Menu">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        </div>

        <!-- Scrollable Navigation Groups -->
        <div class="flex-1 min-h-0 overflow-y-auto px-4 py-4 space-y-5" style="padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 4rem); -webkit-overflow-scrolling: touch; overscroll-behavior: contain;">
            
            <!-- Group 1: Perencanaan (Planning) -->
            <div>
                <p class="px-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-2">
                    Perencanaan
                </p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('budgets.index') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('budgets*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">pie_chart</span>
                        </div>
                        <span class="text-xs truncate">Budgets</span>
                    </a>
                    
                    <a href="{{ route('goals.index') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('goals*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">flag</span>
                        </div>
                        <span class="text-xs truncate">Goals</span>
                    </a>
                </div>
            </div>

            <!-- Group 2: Aset & Kewajiban (Assets & Liabilities) -->
            <div>
                <p class="px-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-2">
                    Aset &amp; Kewajiban
                </p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('debts.index') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('debts*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">credit_card</span>
                        </div>
                        <span class="text-xs truncate">Hutang (Debts)</span>
                    </a>

                    <a href="{{ route('receivables.index') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('receivables*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">trending_up</span>
                        </div>
                        <span class="text-xs truncate">Piutang</span>
                    </a>

                    <a href="{{ route('investments.index') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('investments*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-purple-500/10 text-purple-500 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">show_chart</span>
                        </div>
                        <span class="text-xs truncate">Investasi</span>
                    </a>

                    <a href="{{ route('assets.index') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('assets*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-cyan-500/10 text-cyan-500 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">diamond</span>
                        </div>
                        <span class="text-xs truncate">Aset Lain</span>
                    </a>
                </div>
            </div>

            <!-- Group 3: Laporan Keuangan (Reports) -->
            <div>
                <p class="px-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-2">
                    Laporan
                </p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('wealth-statement') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('wealth-statement*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">description</span>
                        </div>
                        <span class="text-xs truncate">Wealth Statement</span>
                    </a>

                    <a href="{{ route('profit-loss') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('profit-loss*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-teal-500/10 text-teal-500 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">receipt_long</span>
                        </div>
                        <span class="text-xs truncate">Profit &amp; Loss</span>
                    </a>
                </div>
            </div>

            <!-- Group 4: Sistem & Konfigurasi (System) -->
            <div>
                <p class="px-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-2">
                    Sistem &amp; Data
                </p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('gmail-inbox.index') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('gmail-inbox*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-red-500/10 text-red-500 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">inbox</span>
                        </div>
                        <span class="text-xs truncate">Gmail Inbox</span>
                    </a>

                    <a href="{{ route('categories.index') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('categories*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-sky-500/10 text-sky-500 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">category</span>
                        </div>
                        <span class="text-xs truncate">Kategori</span>
                    </a>

                    <a href="{{ route('subcategories.index') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('subcategories*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-orange-500/10 text-orange-500 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">folder_copy</span>
                        </div>
                        <span class="text-xs truncate">Subkategori</span>
                    </a>

                    <a href="{{ route('settings.index') }}" 
                       class="flex items-center gap-2.5 p-3 rounded-xl border transition-all active:scale-[0.98] {{ request()->routeIs('settings*') ? 'bg-primary/10 border-primary/30 text-primary font-semibold' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <div class="size-8 rounded-lg bg-slate-500/10 text-slate-500 dark:text-slate-400 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-lg">settings</span>
                        </div>
                        <span class="text-xs truncate">Pengaturan</span>
                    </a>
                </div>
            </div>

            <!-- User Info & Logout -->
            <div class="pt-2 border-t border-slate-200 dark:border-slate-700/70">
                <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700/60">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="size-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-sm font-bold shadow flex-shrink-0">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                        @csrf
                        <button type="submit" 
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-500 hover:bg-rose-500/10 rounded-lg transition-colors"
                                title="Keluar dari Aplikasi">
                            <span class="material-symbols-outlined text-base">logout</span>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    window.openMobileMoreMenu = function() {
        const menu = document.getElementById('mobileMoreMenu');
        const backdrop = document.getElementById('mobileMoreBackdrop');
        const sheet = document.getElementById('mobileMoreSheet');
        if (!menu || !backdrop || !sheet) return;

        menu.classList.remove('pointer-events-none');
        menu.classList.add('pointer-events-auto');
        
        // Trigger animations
        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            backdrop.classList.add('opacity-100');
            sheet.classList.remove('translate-y-full');
            sheet.classList.add('translate-y-0');
        });

        document.body.classList.add('overflow-hidden');
    };

    window.closeMobileMoreMenu = function() {
        const menu = document.getElementById('mobileMoreMenu');
        const backdrop = document.getElementById('mobileMoreBackdrop');
        const sheet = document.getElementById('mobileMoreSheet');
        if (!menu || !backdrop || !sheet) return;

        backdrop.classList.remove('opacity-100');
        backdrop.classList.add('opacity-0');
        sheet.classList.remove('translate-y-0');
        sheet.classList.add('translate-y-full');

        setTimeout(() => {
            menu.classList.remove('pointer-events-auto');
            menu.classList.add('pointer-events-none');
            document.body.classList.remove('overflow-hidden');
        }, 300);
    };

    window.toggleMobileMoreMenu = function() {
        const sheet = document.getElementById('mobileMoreSheet');
        if (sheet && sheet.classList.contains('translate-y-0')) {
            window.closeMobileMoreMenu();
        } else {
            window.openMobileMoreMenu();
        }
    };

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const sheet = document.getElementById('mobileMoreSheet');
            if (sheet && sheet.classList.contains('translate-y-0')) {
                window.closeMobileMoreMenu();
            }
        }
    });
</script>
