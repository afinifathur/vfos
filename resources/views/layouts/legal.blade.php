<!DOCTYPE html>
<html class="dark scroll-smooth" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Legal') — vFOS Financial Operating System</title>
    <meta name="description" content="@yield('meta_description', 'Official Privacy Policy and Terms of Service for vFOS (Financial Operating System).')">
    <meta name="robots" content="index, follow">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#3c83f6",
                        "primary-hover": "#2563eb",
                        "background-dark": "#0B111E",
                        "surface-dark": "#0F172A",
                        "card-dark": "#162032",
                        "border-dark": "#1E293B",
                        "border-highlight": "#334155",
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background-color: #0B111E; color: #cbd5e1; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .glass-header {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(30, 41, 59, 0.8);
        }
        .legal-card {
            background: #162032;
            border: 1px solid #1E293B;
        }
        .toc-link:hover {
            color: #60a5fa;
            border-left-color: #3b82f6;
        }
        .toc-link.active {
            color: #60a5fa;
            border-left-color: #3b82f6;
            font-weight: 600;
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0B111E; }
        ::-webkit-scrollbar-thumb { background: #1E293B; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #334155; }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-[#0B111E] text-slate-300 antialiased selection:bg-blue-600 selection:text-white">

    <!-- Top Navigation Bar -->
    <header class="glass-header sticky top-0 z-50 transition-all">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Brand -->
            <a href="{{ route('legal.privacy') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-white text-2xl">account_balance_wallet</span>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="text-white font-bold text-lg tracking-tight">vFOS</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20">Legal</span>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium hidden sm:block">Financial Operating System</span>
                </div>
            </a>

            <!-- Navigation Links -->
            <nav class="flex items-center gap-2 sm:gap-4 text-sm font-medium">
                <a href="{{ route('legal.privacy') }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('legal.privacy') ? 'bg-blue-500/10 text-blue-400 border border-blue-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50' }}">
                    Privacy Policy
                </a>
                <a href="{{ route('legal.terms') }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('legal.terms') ? 'bg-blue-500/10 text-blue-400 border border-blue-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50' }}">
                    Terms of Service
                </a>

                <div class="h-5 w-px bg-slate-800 mx-1 hidden sm:block"></div>

                @auth
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-medium text-xs sm:text-sm shadow-sm transition-all">
                        <span class="material-symbols-outlined text-sm">dashboard</span>
                        <span>Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-medium text-xs sm:text-sm transition-all">
                        <span class="material-symbols-outlined text-sm">login</span>
                        <span>Sign In</span>
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 py-10 sm:py-14">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-[#090E17] border-t border-slate-800/80 py-10 text-sm text-slate-500">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <!-- Branding & Summary -->
                <div class="flex flex-col items-center md:items-start gap-1">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-300">vFOS</span>
                        <span class="text-xs text-slate-500">· Financial Operating System</span>
                    </div>
                    <p class="text-xs text-slate-500 max-w-sm text-center md:text-left">
                        Personal finance, ledger tracking, wealth intelligence, and automated transaction ingestion.
                    </p>
                </div>

                <!-- Footer Navigation -->
                <div class="flex flex-wrap justify-center gap-6 text-xs text-slate-400 font-medium">
                    <a href="{{ route('legal.privacy') }}" class="hover:text-blue-400 transition-colors">Privacy Policy</a>
                    <a href="{{ route('legal.terms') }}" class="hover:text-blue-400 transition-colors">Terms of Service</a>
                    <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener noreferrer" class="hover:text-blue-400 transition-colors flex items-center gap-1">
                        <span>Google User Data Policy</span>
                        <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                    </a>
                    <a href="{{ route('login') }}" class="hover:text-blue-400 transition-colors">Sign In</a>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-800/60 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-600">
                <p>&copy; {{ date('Y') }} vFOS (https://vfos.ngopikopi.my.id). All rights reserved.</p>
                <p>Self-Hosted & Private Personal Finance Operating System</p>
            </div>
        </div>
    </footer>

</body>
</html>

