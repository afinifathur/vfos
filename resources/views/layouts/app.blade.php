<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'vFOS') }} - Financial OS</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#3c83f6",
                        "background-light": "#f5f7f8",
                        "background-dark": "#0F172A", 
                        "card-dark": "#1E293B", 
                    },
                    fontFamily: {
                        "display": ["Inter"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .active-nav {
            background-color: #1E293B;
            border-right: 3px solid #3c83f6;
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0F172A; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 flex-shrink-0 bg-background-light dark:bg-background-dark border-r border-slate-200 dark:border-slate-800 flex flex-col transition-all duration-300 relative">
            <div class="p-6 flex items-center gap-3 overflow-hidden">
                <div class="flex-shrink-0 size-8 bg-primary rounded-lg flex items-center justify-center text-white shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                </div>
                <div class="sidebar-text opacity-100 transition-opacity duration-300">
                    <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white leading-none">vFOS</h1>
                    <p class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400 font-semibold">Financial OS</p>
                </div>
            </div>
            
            <!-- Toggle Button -->
            <div class="px-4 mb-2">
                <button id="sidebarToggle" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-card-dark transition-all group overflow-hidden">
                    <span class="material-symbols-outlined transition-transform duration-300" id="sidebarToggleIcon">menu_open</span>
                    <span class="text-xs font-semibold uppercase tracking-wider sidebar-text opacity-100 transition-opacity duration-300">Minimize</span>
                </button>
            </div>
            <nav class="flex-1 px-4 space-y-1 mt-4 overflow-y-auto">
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('/') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="/">
                    <span class="material-symbols-outlined {{ request()->is('/') ? '' : 'group-hover:text-primary' }}">dashboard</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Dashboard</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('wealth-statement*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="{{ route('wealth-statement') }}">
                    <span class="material-symbols-outlined {{ request()->is('wealth-statement') ? '' : 'group-hover:text-primary' }}">description</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Wealth Statement</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('profit-loss*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="{{ route('profit-loss') }}">
                    <span class="material-symbols-outlined {{ request()->is('profit-loss') ? '' : 'group-hover:text-primary' }}">receipt_long</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Profit & Loss</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('transactions*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="/transactions">
                    <span class="material-symbols-outlined {{ request()->is('transactions*') ? '' : 'group-hover:text-primary' }}">swap_horiz</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Transactions</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('categories*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="/categories">
                    <span class="material-symbols-outlined {{ request()->is('categories*') ? '' : 'group-hover:text-primary' }}">category</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Categories</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('accounts*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="/accounts">
                    <span class="material-symbols-outlined {{ request()->is('accounts*') ? '' : 'group-hover:text-primary' }}">account_balance</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Accounts</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('goals*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="/goals">
                    <span class="material-symbols-outlined {{ request()->is('goals*') ? '' : 'group-hover:text-primary' }}">flag</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Goals</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('budgets*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="/budgets">
                    <span class="material-symbols-outlined {{ request()->is('budgets*') ? '' : 'group-hover:text-primary' }}">pie_chart</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Budgets</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('debts*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="/debts">
                    <span class="material-symbols-outlined {{ request()->is('debts*') ? '' : 'group-hover:text-primary' }}">credit_card</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Debts</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('receivables*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="/receivables">
                    <span class="material-symbols-outlined {{ request()->is('receivables*') ? '' : 'group-hover:text-primary' }}">trending_up</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Receivables</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('investments*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="/investments">
                    <span class="material-symbols-outlined {{ request()->is('investments*') ? '' : 'group-hover:text-primary' }}">show_chart</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Investments</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('assets*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="/assets">
                    <span class="material-symbols-outlined {{ request()->is('assets*') ? '' : 'group-hover:text-primary' }}">diamond</span>
                    <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Assets</span>
                </a>
                <div class="pt-8 pb-4">
                    <p class="px-3 text-[10px] uppercase font-bold text-slate-500 tracking-wider mb-2 sidebar-text opacity-100 transition-opacity duration-300">User</p>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('settings*') ? 'text-primary bg-primary/10 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors group' }}" href="{{ route('settings.index') }}">
                        <span class="material-symbols-outlined {{ request()->is('settings*') ? '' : 'group-hover:text-primary' }}">settings</span>
                        <span class="text-sm sidebar-text opacity-100 transition-opacity duration-300">Settings</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="h-16 flex items-center justify-between px-8 bg-background-light dark:bg-background-dark border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">@yield('title', 'Dashboard')</h2>
                    <div class="h-4 w-px bg-slate-200 dark:bg-slate-800"></div>
                    <div class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-card-dark px-3 py-1.5 rounded-lg cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <span class="material-symbols-outlined text-base">calendar_month</span>
                        <span>{{ now()->format('F Y') }}</span>
                        <span class="material-symbols-outlined text-base">expand_more</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/transactions/create" class="flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg text-sm font-semibold transition-all shadow-lg shadow-primary/20">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Quick Add
                    </a>
                    <div class="flex items-center gap-3 pl-4 border-l border-slate-200 dark:border-slate-800">
                        <button class="size-9 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors">
                            <span class="material-symbols-outlined">notifications</span>
                        </button>

                        <!-- User avatar + dropdown -->
                        <div class="relative" id="userMenu">
                            <button onclick="document.getElementById('userDropdown').classList.toggle('hidden')" class="flex items-center gap-2 cursor-pointer focus:outline-none group">
                                <div class="size-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-sm font-bold shadow border-2 border-slate-700 select-none">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <span class="hidden lg:block text-sm font-medium text-slate-700 dark:text-slate-300 max-w-[100px] truncate">{{ auth()->user()->name }}</span>
                                <span class="material-symbols-outlined text-slate-500 text-base">expand_more</span>
                            </button>

                            <!-- Dropdown -->
                            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-52 bg-card-dark border border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden">
                                <div class="px-4 py-3 border-b border-slate-700">
                                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('settings.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-300 hover:bg-slate-700 transition-colors">
                                    <span class="material-symbols-outlined text-base">settings</span>
                                    Pengaturan
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-400 hover:bg-red-500/10 transition-colors">
                                        <span class="material-symbols-outlined text-base">logout</span>
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Area -->
            <div class="flex-1 overflow-y-auto p-8 space-y-8 bg-slate-50/50 dark:bg-background-dark">
                @if(session('success'))
                    <div class="rounded-md bg-green-500/10 p-4 border border-green-500/20">
                        <div class="flex">
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-500">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="rounded-md bg-red-500/10 p-4 border border-red-500/20">
                        <div class="flex">
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-500">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
<script>
// ── Global Thousand-Separator Formatter ───────────────────────────────────
window.initializeNumberFormatting = function (container = document) {
    function formatNum(raw) {
        if (raw === '' || raw === null || raw === undefined) return '';
        var str = String(raw).trim();
        var parts = str.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    function stripNum(val) {
        return val.replace(/,/g, '');
    }

    var numInputs = container.querySelectorAll('input[inputmode="decimal"]');
    // Also catch original type="number" if not yet processed
    if (container === document) {
        numInputs = Array.from(numInputs).concat(Array.from(container.querySelectorAll('input[type="number"]')));
    } else {
        // For cloned elements, they might already be inputmode="decimal" or still type="number"
        var moreInputs = container.querySelectorAll('input[type="number"]');
        numInputs = Array.from(numInputs).concat(Array.from(moreInputs));
    }

    numInputs.forEach(function (input) {
        if (input.dataset.numberFormatted) return;
        input.dataset.numberFormatted = "true";

        // Switch to text so we can display commas
        input.setAttribute('type', 'text');
        input.setAttribute('inputmode', 'decimal');

        // Format initial value
        if (input.value !== '') {
            input.value = formatNum(input.value);
        }

        // On focus: remove commas so user can edit cleanly
        input.addEventListener('focus', function () {
            this.value = stripNum(this.value);
        });

        // On blur: re-apply formatting
        input.addEventListener('blur', function () {
            var stripped = stripNum(this.value);
            if (stripped !== '') {
                this.value = formatNum(stripped);
            }
        });

        // Allow only numbers, dot, comma, minus sign
        input.addEventListener('keypress', function (e) {
            var allowed = /[0-9.,\-]/;
            if (!allowed.test(e.key) && !['Backspace','Delete','Tab','ArrowLeft','ArrowRight'].includes(e.key)) {
                e.preventDefault();
            }
        });
    });
};
document.addEventListener('DOMContentLoaded', function () {
    window.initializeNumberFormatting();

    // Before any form submits: strip commas so server gets plain numbers
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('input[inputmode="decimal"]').forEach(function (input) {
                input.value = input.value.replace(/,/g, '');
            });
        });
    });

    // ── Sidebar Toggle Logic ────────────────────────────────────────────────
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
    const sidebarTexts = document.querySelectorAll('.sidebar-text');

    function updateSidebar(isMinimized) {
        if (isMinimized) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
            sidebarToggleIcon.style.transform = 'rotate(180deg)';
            sidebarToggleIcon.innerText = 'menu';
            sidebarTexts.forEach(el => {
                el.classList.replace('opacity-100', 'opacity-0');
                setTimeout(() => el.classList.add('hidden'), 300);
            });
        } else {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            sidebarToggleIcon.style.transform = 'rotate(0deg)';
            sidebarToggleIcon.innerText = 'menu_open';
            sidebarTexts.forEach(el => {
                el.classList.remove('hidden');
                setTimeout(() => el.classList.replace('opacity-0', 'opacity-100'), 10);
            });
        }
    }

    // Initial state
    let isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
    if (isMinimized) updateSidebar(true);

    sidebarToggle.addEventListener('click', () => {
        isMinimized = !isMinimized;
        localStorage.setItem('sidebarMinimized', isMinimized);
        updateSidebar(isMinimized);
    });
});
</script>
@stack('scripts')
</body>
</html>
