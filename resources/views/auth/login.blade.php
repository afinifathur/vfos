<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>vFOS — Sign In</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0F172A; overflow: hidden; }

        /* Animated gradient background */
        .bg-animated {
            position: fixed; inset: 0; z-index: 0;
            background: 
                radial-gradient(ellipse 80% 60% at 20% 20%, rgba(60, 131, 246, 0.15) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 80%, rgba(139, 92, 246, 0.10) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 50% 50%, rgba(14, 165, 233, 0.08) 0%, transparent 70%),
                #0F172A;
            animation: bgShift 12s ease-in-out infinite alternate;
        }
        @keyframes bgShift {
            0%   { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }

        /* Floating orbs */
        .orb {
            position: fixed; border-radius: 50%;
            filter: blur(80px); opacity: 0.25; pointer-events: none;
            animation: floatOrb 15s ease-in-out infinite alternate;
        }
        .orb-1 { width: 500px; height: 500px; background: #3c83f6; top: -150px; left: -150px; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: #8b5cf6; bottom: -100px; right: -100px; animation-delay: -5s; }
        .orb-3 { width: 300px; height: 300px; background: #06b6d4; top: 40%; right: 10%; animation-delay: -10s; }
        @keyframes floatOrb {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, -30px) scale(1.1); }
        }

        /* Grid pattern overlay */
        .grid-pattern {
            position: fixed; inset: 0; z-index: 1;
            background-image: 
                linear-gradient(rgba(51, 65, 85, 0.2) 1px, transparent 1px),
                linear-gradient(90deg, rgba(51, 65, 85, 0.2) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        /* Card glassmorphism */
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(51, 65, 85, 0.6);
        }

        /* Input focus glow */
        .input-field {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(51, 65, 85, 0.8);
            color: #f1f5f9;
            transition: all 0.3s ease;
        }
        .input-field:focus {
            outline: none;
            border-color: #3c83f6;
            box-shadow: 0 0 0 3px rgba(60, 131, 246, 0.15), 0 0 20px rgba(60, 131, 246, 0.1);
            background: rgba(15, 23, 42, 0.8);
        }
        .input-field::placeholder { color: #475569; }

        /* Sign in button */
        .btn-primary {
            background: linear-gradient(135deg, #3c83f6 0%, #6366f1 100%);
            box-shadow: 0 4px 20px rgba(60, 131, 246, 0.35);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-primary::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, #6366f1 0%, #3c83f6 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .btn-primary:hover::before { opacity: 1; }
        .btn-primary:hover { box-shadow: 0 8px 30px rgba(60, 131, 246, 0.5); transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary span { position: relative; z-index: 1; }

        /* Logo pulse */
        .logo-ring {
            animation: logoPulse 3s ease-in-out infinite;
        }
        @keyframes logoPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(60, 131, 246, 0.4); }
            50% { box-shadow: 0 0 0 12px rgba(60, 131, 246, 0); }
        }

        /* Input icon */
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }

        /* Error shake animation */
        .shake { animation: shake 0.4s ease-in-out; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%       { transform: translateX(-6px); }
            40%       { transform: translateX(6px); }
            60%       { transform: translateX(-4px); }
            80%       { transform: translateX(4px); }
        }

        /* Fade-in-up for card */
        .fade-up {
            animation: fadeUp 0.6s cubic-bezier(0.23, 1, 0.32, 1) both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">

    <!-- Background layers -->
    <div class="bg-animated"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-pattern"></div>

    <!-- Login Card -->
    <div class="relative z-10 w-full max-w-md px-4 fade-up">
        <div class="glass-card rounded-2xl p-8 shadow-2xl">

            <!-- Logo & Branding -->
            <div class="flex flex-col items-center mb-8">
                <div class="logo-ring w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-blue-500/30">
                    <span class="material-symbols-outlined text-white text-3xl">account_balance_wallet</span>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">vFOS</h1>
                <p class="text-xs uppercase tracking-widest text-slate-400 font-semibold mt-1">Financial Operating System</p>
                <div class="mt-4 h-px w-16 bg-gradient-to-r from-transparent via-blue-500 to-transparent"></div>
            </div>

            <!-- Heading -->
            <div class="mb-6 text-center">
                <h2 class="text-xl font-semibold text-white">Selamat Datang!</h2>
                <p class="text-sm text-slate-400 mt-1">Masuk untuk mengelola keuanganmu</p>
            </div>

            <!-- Error message -->
            @if($errors->any())
                <div class="shake mb-5 flex items-start gap-3 rounded-xl bg-red-500/10 border border-red-500/30 px-4 py-3">
                    <span class="material-symbols-outlined text-red-400 text-lg mt-0.5">error</span>
                    <p class="text-sm text-red-400">{{ $errors->first() }}</p>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-5 flex items-start gap-3 rounded-xl bg-green-500/10 border border-green-500/30 px-4 py-3">
                    <span class="material-symbols-outlined text-green-400 text-lg mt-0.5">check_circle</span>
                    <p class="text-sm text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2" for="email">
                        Email
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-lg pointer-events-none">mail</span>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            placeholder="nama@email.com"
                            class="input-field w-full pl-10 pr-4 py-3 rounded-xl text-sm"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2" for="password">
                        Password
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-lg pointer-events-none">lock</span>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="input-field w-full pl-10 pr-12 py-3 rounded-xl text-sm"
                        >
                        <button type="button" id="togglePwd" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors">
                            <span class="material-symbols-outlined text-lg" id="eyeIcon">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- Remember me -->
                <div class="flex items-center gap-2 pt-1">
                    <input id="remember" type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-blue-500 focus:ring-blue-500 focus:ring-offset-slate-900">
                    <label for="remember" class="text-sm text-slate-400 cursor-pointer select-none">Ingat saya</label>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-primary w-full py-3 rounded-xl text-white font-semibold text-sm mt-2">
                    <span class="flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg">login</span>
                        Masuk ke vFOS
                    </span>
                </button>
            </form>

            <!-- Footer -->
            <p class="text-center text-xs text-slate-600 mt-6">
                vFOS &copy; {{ date('Y') }} &nbsp;·&nbsp; Personal Finance OS
            </p>
        </div>

        <!-- Decorative bottom badge -->
        <div class="flex items-center justify-center gap-2 mt-4">
            <div class="h-px flex-1 bg-gradient-to-r from-transparent to-slate-700"></div>
            <span class="text-xs text-slate-600 font-medium px-2">Secure Login</span>
            <div class="h-px flex-1 bg-gradient-to-l from-transparent to-slate-700"></div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const toggleBtn = document.getElementById('togglePwd');
        const pwdInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        toggleBtn.addEventListener('click', () => {
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';
            eyeIcon.textContent = isPassword ? 'visibility_off' : 'visibility';
        });
    </script>
</body>
</html>
