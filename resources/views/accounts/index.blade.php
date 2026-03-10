@extends('layouts.app')

@section('title', 'Financial Accounts')

@section('content')
<div class="space-y-10 pb-12">
    <!-- Premium Header -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-card-dark p-8 rounded-2xl border border-slate-800 shadow-2xl relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-64 h-64 bg-primary/5 rounded-full blur-3xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center gap-2 md:gap-8">
            <div>
                <h2 class="text-4xl font-black tracking-tight text-white font-outfit">Financial Accounts</h2>
                <p class="text-slate-400 text-sm mt-1">Total liquidity across {{ $accounts->count() }} specialized institutions.</p>
            </div>
            <div class="h-10 w-px bg-slate-800 hidden md:block"></div>
            <div class="flex flex-col">
                <span class="text-[10px] font-black text-primary uppercase tracking-[0.2em] mb-0.5">Net Worth</span>
                <span class="text-2xl font-black text-white tracking-tighter">
                    Rp {{ number_format($accounts->sum('total_balance'), 0, ',', '.') }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-4 relative z-10">
            <form action="{{ route('accounts.index') }}" method="GET" class="relative group hidden sm:block">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-lg group-focus-within:text-primary transition-colors">search</span>
                <input name="search" value="{{ request('search') }}" class="pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl text-sm text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none w-72 transition-all placeholder:text-slate-600 font-medium" placeholder="Search accounts...">
            </form>
            <a href="{{ route('accounts.create') }}" class="bg-primary hover:bg-primary/90 text-white px-6 py-4 rounded-xl text-sm font-black flex items-center gap-2 transition-all shadow-xl shadow-primary/20 active:scale-95 group">
                <span class="material-symbols-outlined text-lg group-hover:rotate-90 transition-transform">add_circle</span>
                Link New Account
            </a>
        </div>
    </header>

    <!-- Accounts Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
        @forelse($accounts as $account)
        <div class="bg-card-dark border border-slate-800 p-6 rounded-3xl hover:border-primary/40 transition-all group relative shadow-lg hover:shadow-primary/5">
            <div class="flex justify-between items-start mb-6">
                <!-- Custom Icon / Fallback -->
                <div class="w-16 h-16 rounded-2xl bg-slate-900 flex items-center justify-center overflow-hidden border border-slate-800 group-hover:border-primary/30 transition-all shadow-inner">
                    @if($account->icon_path)
                        <img src="{{ asset('storage/' . $account->icon_path) }}" class="w-full h-full object-cover">
                    @else
                        <span class="material-symbols-outlined text-2xl text-slate-500 group-hover:text-primary transition-colors">
                            @if($account->type === 'bank') account_balance @elseif($account->type === 'ewallet') account_balance_wallet @elseif($account->type === 'cash') payments @else category @endif
                        </span>
                    @endif
                </div>
                
                <div class="flex flex-col items-end gap-2">
                    <span class="text-[9px] font-black {{ $account->is_active ? 'text-emerald-500 bg-emerald-500/10' : 'text-slate-500 bg-slate-800/50' }} flex items-center gap-1.5 px-2.5 py-1 rounded-full uppercase tracking-widest border border-white/5">
                        <span class="w-1.5 h-1.5 rounded-full {{ $account->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-500' }}"></span>
                        {{ $account->is_active ? 'Synced' : 'Hidden' }}
                    </span>
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter">{{ $account->owner }}'s Account</span>
                </div>
            </div>

            <div class="space-y-1">
                <h3 class="text-xl font-bold text-white leading-tight group-hover:text-primary transition-colors">{{ $account->name }}</h3>
                <p class="text-xs text-slate-500 font-medium capitalize">{{ $account->type }} Institutional</p>
            </div>

            <div class="mt-8">
                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest mb-1 mx-1">Liquid Balance</p>
                @php
                    $balance = $account->total_balance;
                @endphp
                <div class="flex items-baseline gap-2">
                    <span class="text-slate-400 font-bold text-sm">Rp</span>
                    <span class="text-3xl font-black text-white leading-none tracking-tighter {{ $balance < 0 ? 'text-rose-500' : '' }}">
                        {{ number_format($balance, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Absolute positioned quick actions on hover -->
            <div class="mt-8 pt-5 border-t border-white/5 flex items-center justify-between opacity-60 group-hover:opacity-100 transition-opacity">
                <span class="text-[10px] text-slate-500 font-medium">Refreshed {{ $account->updated_at->diffForHumans() }}</span>
                <div class="flex gap-4">
                    <a href="{{ route('accounts.reconcile', $account) }}" class="text-[10px] font-black text-slate-400 hover:text-emerald-400 transition-colors uppercase tracking-widest">Penyesuaian</a>
                    <a href="{{ route('transactions.index', ['account_id' => $account->id]) }}" class="text-[10px] font-black text-slate-400 hover:text-primary transition-colors uppercase tracking-widest">Transactions</a>
                    <a href="{{ route('accounts.edit', $account) }}" class="text-[10px] font-black text-slate-400 hover:text-primary transition-colors uppercase tracking-widest">Settings</a>
                    <form action="{{ route('accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('Danger! Remove this account and its history?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-[10px] font-black text-slate-400 hover:text-rose-500 transition-colors uppercase tracking-widest">Unlink</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full py-20 bg-card-dark border-2 border-dashed border-slate-800 rounded-3xl flex flex-col items-center justify-center text-center">
            <span class="material-symbols-outlined text-6xl text-slate-700 mb-4">search_off</span>
            <h4 class="text-xl font-bold text-slate-400">No Accounts Found</h4>
            <p class="text-slate-600 mt-1">Start by linking your first financial account.</p>
        </div>
        @endforelse

        @if($accounts->count() > 0)
        <!-- Add New Placeholder -->
        <a href="{{ route('accounts.create') }}" class="border-2 border-dashed border-slate-800 p-8 rounded-3xl flex flex-col items-center justify-center gap-4 text-slate-500 hover:border-primary/50 hover:bg-primary/5 transition-all group shadow-sm">
            <div class="w-14 h-14 rounded-full border-2 border-slate-800 flex items-center justify-center group-hover:border-primary group-hover:scale-110 transition-all">
                <span class="material-symbols-outlined text-3xl">add</span>
            </div>
            <div class="text-center">
                <p class="font-black text-sm text-slate-400 group-hover:text-white uppercase tracking-widest">Connect Another</p>
                <p class="text-[10px] mt-1 text-slate-600">Secure institutional bridge</p>
            </div>
        </a>
        @endif
    </div>

    <!-- Security Footer -->
    <div class="mt-12 flex flex-col md:flex-row items-center justify-between py-10 border-t border-slate-800 gap-6 opacity-40 hover:opacity-100 transition-opacity">
        <div class="flex items-center gap-3 text-slate-400">
            <span class="material-symbols-outlined text-2xl text-primary/50">security</span>
            <div class="flex flex-col">
                <span class="text-[10px] font-black uppercase tracking-widest">AES-256 Encrypted</span>
                <span class="text-[10px] font-medium">Bank-level infrastructure security</span>
            </div>
        </div>
        <div class="flex items-center gap-10">
            <a class="text-[10px] font-black text-slate-500 hover:text-white uppercase tracking-widest transition-colors" href="#">Certifications</a>
            <a class="text-[10px] font-black text-slate-500 hover:text-white uppercase tracking-widest transition-colors" href="#">Uptime SLA</a>
            <a class="text-[10px] font-black text-slate-500 hover:text-white uppercase tracking-widest transition-colors" href="#">Audit Log</a>
        </div>
    </div>
</div>
@endsection
