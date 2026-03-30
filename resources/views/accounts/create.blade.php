@extends('layouts.app')

@section('title', 'Create Account')

@section('content')
<div class="max-w-4xl mx-auto pb-12">
    <!-- Header & Breadcrumbs -->
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 uppercase tracking-widest mb-4">
            <a href="{{ route('accounts.index') }}" class="hover:text-primary transition-colors">Accounts</a>
            <span class="material-symbols-outlined text-sm">chevron_right</span>
            <span class="text-slate-300">New Account</span>
        </nav>
        <h2 class="text-3xl font-black text-white px-1">Setup New Account</h2>
        <p class="text-slate-400 text-sm mt-1 px-1">Configure a new financial source, e-wallet, or investment bridge.</p>
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

    <form action="{{ route('accounts.store') }}" method="POST" enctype="multipart/form-data" id="account-form">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Side: Icon Upload -->
            <div class="space-y-6">
                <div class="bg-card-dark border border-slate-800 rounded-2xl p-8 flex flex-col items-center text-center">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest mb-6 block w-full text-left">Account Icon</label>
                    <div class="relative group">
                        <div id="icon-preview" class="size-32 rounded-3xl bg-slate-900 border-2 border-dashed border-slate-700 flex items-center justify-center overflow-hidden transition-all group-hover:border-primary/50">
                            <span class="material-symbols-outlined text-5xl text-slate-600 group-hover:text-primary/50 transition-colors">account_balance_wallet</span>
                        </div>
                        <label for="icon" class="absolute -bottom-2 -right-2 size-10 bg-primary text-white rounded-xl shadow-lg shadow-primary/30 flex items-center justify-center cursor-pointer hover:scale-105 active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-xl">upload</span>
                            <input type="file" name="icon" id="icon" class="hidden" accept="image/png,image/x-icon,image/jpeg">
                        </label>
                    </div>
                    <p class="text-[10px] text-slate-500 font-medium mt-6 leading-relaxed">
                        Recommended: PNG or ICO<br>Square ratio, max 1MB
                    </p>
                </div>
                
                <div class="bg-card-dark border border-slate-800 rounded-2xl p-6">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-slate-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary shadow-inner"></div>
                        </div>
                        <span class="text-sm font-bold text-slate-400 group-hover:text-white transition-colors">Active Account</span>
                    </label>
                </div>
            </div>

            <!-- Right Side: Account Details -->
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-card-dark border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                    <div class="px-8 py-4 border-b border-slate-800 bg-slate-900/30 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">settings</span>
                        <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider">Account Configuration</h3>
                    </div>
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Name -->
                            <div class="md:col-span-2 space-y-2">
                                <label for="name" class="text-xs font-black text-slate-500 uppercase tracking-widest">Account Name</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                                        <span class="material-symbols-outlined text-lg">label</span>
                                    </span>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                        class="w-full pl-11 pr-4 py-3.5 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-bold text-lg"
                                        placeholder="e.g. BCA Priority, GOPAY Main">
                                </div>
                            </div>

                            <!-- Type -->
                            <div class="space-y-2">
                                <label for="type" class="text-xs font-black text-slate-500 uppercase tracking-widest">Account Type</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                                        <span class="material-symbols-outlined text-lg">category</span>
                                    </span>
                                    <select name="type" id="type" required
                                        class="w-full pl-11 pr-4 py-3.5 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white font-medium">
                                        <option value="bank">Bank Account</option>
                                        <option value="ewallet">Electronic Wallet</option>
                                        <option value="cash">Physical Cash</option>
                                        <option value="investment">Investment Broker</option>
                                        <option value="other">Other Assets</option>
                                    </select>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500">
                                        <span class="material-symbols-outlined text-lg">expand_more</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Owner -->
                            @if(auth()->user()->role === 'admin')
                            <div class="space-y-2">
                                <label for="owner" class="text-xs font-black text-slate-500 uppercase tracking-widest">Primary Owner</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                                        <span class="material-symbols-outlined text-lg">person</span>
                                    </span>
                                    <select name="owner" id="owner" required
                                        class="w-full pl-11 pr-4 py-3.5 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white font-medium">
                                        <option value="afin">Afin</option>
                                        <option value="pacar">Pacar</option>
                                        <option value="business">Business</option>
                                    </select>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500">
                                        <span class="material-symbols-outlined text-lg">expand_more</span>
                                    </span>
                                </div>
                            </div>
                            @else
                                <input type="hidden" name="owner" value="{{ auth()->user()->role === 'partner' ? 'pacar' : (auth()->user()->role === 'business' ? 'business' : 'afin') }}">
                            @endif
                            <!-- Initial Balance -->
                            <div class="space-y-2">
                                <label for="initial_balance" class="text-xs font-black text-slate-500 uppercase tracking-widest">Initial Balance</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                                        <span class="material-symbols-outlined text-lg">payments</span>
                                    </span>
                                    <input type="number" step="0.01" name="initial_balance" id="initial_balance" value="{{ old('initial_balance', 0) }}"
                                        class="w-full pl-11 pr-4 py-3.5 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium"
                                        placeholder="0.00">
                                </div>
                            </div>

                            <!-- Goal Connection -->
                            <div class="md:col-span-2 space-y-2">
                                <label for="goal_id" class="text-xs font-black text-slate-500 uppercase tracking-widest">Link to Sinking Fund Goal (Optional)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                                        <span class="material-symbols-outlined text-lg">flag</span>
                                    </span>
                                    <select name="goal_id" id="goal_id" class="w-full pl-11 pr-4 py-3.5 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white font-medium">
                                        <option value="">-- No specific goal --</option>
                                        @foreach($goals as $goal)
                                            <option value="{{ $goal->id }}">{{ $goal->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500">
                                        <span class="material-symbols-outlined text-lg">expand_more</span>
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-500 font-medium">If linked, this account's balance will be counted towards the goal's progress.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="pt-4 flex items-center justify-end gap-8">
                    <a href="{{ route('accounts.index') }}" class="text-sm font-bold text-slate-500 hover:text-white transition-colors">Discard</a>
                    <button type="submit" class="rounded-xl bg-primary hover:bg-primary/90 px-12 py-4 text-sm font-black text-white shadow-xl shadow-primary/20 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">save</span>
                        Create Account
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.getElementById('icon').addEventListener('change', function(e) {
        const preview = document.getElementById('icon-preview');
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.innerHTML = `<img src="${event.target.result}" class="w-full h-full object-cover">`;
                preview.classList.remove('border-dashed');
                preview.classList.add('border-solid', 'border-primary/30');
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endsection
