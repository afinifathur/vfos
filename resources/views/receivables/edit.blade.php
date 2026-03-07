@extends('layouts.app')

@section('title', 'Manage Receivable')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('receivables.index') }}" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-primary transition-colors gap-1 group">
            <span class="material-symbols-outlined text-base group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Back to Receivables
        </a>
        <h2 class="text-2xl font-bold text-white mt-4">Update Loan Details</h2>
        <p class="text-slate-400 text-sm mt-1">Adjust and track the recovery progress for <span class="text-white font-bold">{{ $receivable->name }}</span>.</p>
    </div>

    <div class="bg-card-dark border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <form action="{{ route('receivables.update', $receivable) }}" method="POST" class="p-8 space-y-8">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Name -->
                <div class="md:col-span-2 space-y-2">
                    <label for="name" class="text-xs font-black text-slate-500 uppercase tracking-widest">Borrower's Name / Description</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">person</span>
                        </span>
                        <input type="text" name="name" id="name" value="{{ old('name', $receivable->name) }}" required
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">
                    </div>
                </div>

                <!-- Total Amount Lent -->
                <div class="space-y-2">
                    <label for="total_amount" class="text-xs font-black text-slate-500 uppercase tracking-widest">Total Amount Lent</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400 font-bold text-sm">Rp</span>
                        <input type="number" name="total_amount" id="total_amount" value="{{ old('total_amount', (int)$receivable->total_amount) }}" required min="0" step="1000"
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">
                    </div>
                </div>

                <!-- Remaining Balance -->
                <div class="space-y-2">
                    <label for="remaining_amount" class="text-xs font-black text-slate-500 uppercase tracking-widest">Current Balance</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400 font-bold text-sm">Rp</span>
                        <input type="number" name="remaining_amount" id="remaining_amount" value="{{ old('remaining_amount', (int)$receivable->remaining_amount) }}" required min="0" step="1000"
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">
                    </div>
                </div>

                <!-- Due Date -->
                <div class="space-y-2">
                    <label for="due_date" class="text-xs font-black text-slate-500 uppercase tracking-widest">Expected Repayment Date</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">calendar_today</span>
                        </span>
                        <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $receivable->due_date ? \Carbon\Carbon::parse($receivable->due_date)->format('Y-m-d') : '') }}"
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">
                    </div>
                </div>

                <!-- Status -->
                <div class="space-y-2">
                    <label for="status" class="text-xs font-black text-slate-500 uppercase tracking-widest">Loan Status</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">info</span>
                        </span>
                        <select name="status" id="status" required
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all text-white font-medium">
                            <option value="active" {{ old('status', $receivable->status) == 'active' ? 'selected' : '' }}>Active (Outstanding)</option>
                            <option value="paid" {{ old('status', $receivable->status) == 'paid' ? 'selected' : '' }}>Recovered (Fully Paid)</option>
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500">
                            <span class="material-symbols-outlined text-lg">expand_more</span>
                        </span>
                    </div>
                </div>

                <!-- Notes -->
                <div class="md:col-span-2 space-y-2">
                    <label for="notes" class="text-xs font-black text-slate-500 uppercase tracking-widest">Details / Purpose</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-4 py-3 bg-slate-900/50 border border-slate-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-white font-medium">{{ old('notes', $receivable->notes) }}</textarea>
                </div>
            </div>

            <div class="pt-4 flex items-center gap-4">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl transition-all shadow-xl shadow-primary/20 flex items-center justify-center gap-2 text-lg">
                    <span class="material-symbols-outlined">save</span>
                    Update Details
                </button>
                <a href="{{ route('receivables.index') }}" class="px-8 py-4 text-slate-400 font-bold hover:text-white transition-colors">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
