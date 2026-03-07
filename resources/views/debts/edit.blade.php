@extends('layouts.app')

@section('title', 'Manage Debt')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('debts.index') }}" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-primary transition-colors gap-1 group">
            <span class="material-symbols-outlined text-base group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Back to Debt Management
        </a>
        <h2 class="text-2xl font-bold text-white mt-4">Update Debt Details</h2>
        <p class="text-slate-400 text-sm mt-1">Adjust and track the progress of your liability <span class="text-white font-bold">{{ $debt->name }}</span>.</p>
    </div>

    <div class="bg-card-dark border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <form action="{{ route('debts.update', $debt) }}" method="POST" class="p-8 space-y-8">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Name -->
                <div class="md:col-span-2 space-y-2">
                    <label for="name" class="text-xs font-black text-slate-500 uppercase tracking-widest">Account / Creditor Name</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">account_balance</span>
                        </span>
                        <input type="text" name="name" id="name" value="{{ old('name', $debt->name) }}" required
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">
                    </div>
                </div>

                <!-- Total Original Amount -->
                <div class="space-y-2">
                    <label for="total_amount" class="text-xs font-black text-slate-500 uppercase tracking-widest">Original Amount</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400 font-bold text-sm">Rp</span>
                        <input type="number" name="total_amount" id="total_amount" value="{{ old('total_amount', (int)$debt->total_amount) }}" required min="0" step="1000"
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">
                    </div>
                </div>

                <!-- Remaining Amount -->
                <div class="space-y-2">
                    <label for="remaining_amount" class="text-xs font-black text-slate-500 uppercase tracking-widest">Current Balance</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400 font-bold text-sm">Rp</span>
                        <input type="number" name="remaining_amount" id="remaining_amount" value="{{ old('remaining_amount', (int)$debt->remaining_amount) }}" required min="0" step="1000"
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">
                    </div>
                </div>

                <!-- Due Date -->
                <div class="space-y-2">
                    <label for="due_date" class="text-xs font-black text-slate-500 uppercase tracking-widest">Due Date / Maturity</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">calendar_today</span>
                        </span>
                        <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $debt->due_date ? \Carbon\Carbon::parse($debt->due_date)->format('Y-m-d') : '') }}"
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">
                    </div>
                </div>

                <!-- Status -->
                <div class="space-y-2">
                    <label for="status" class="text-xs font-black text-slate-500 uppercase tracking-widest">Account Status</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">info</span>
                        </span>
                        <select name="status" id="status" required
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white font-medium">
                            <option value="active" {{ old('status', $debt->status) == 'active' ? 'selected' : '' }}>Active Liability</option>
                            <option value="paid" {{ old('status', $debt->status) == 'paid' ? 'selected' : '' }}>Fully Paid</option>
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">expand_more</span>
                        </span>
                    </div>
                </div>

                <!-- Notes -->
                <div class="md:col-span-2 space-y-2">
                    <label for="notes" class="text-xs font-black text-slate-500 uppercase tracking-widest">Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">{{ old('notes', $debt->notes) }}</textarea>
                </div>
            </div>

            <div class="pt-4 flex items-center gap-4">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl transition-all shadow-xl shadow-primary/20 flex items-center justify-center gap-2 text-lg">
                    <span class="material-symbols-outlined">save</span>
                    Update Details
                </button>
                <a href="{{ route('debts.index') }}" class="px-8 py-4 text-slate-400 font-bold hover:text-white transition-colors">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
