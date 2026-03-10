@extends('layouts.app')

@section('title', 'Reconcile Account: ' . $account->name)

@section('content')
<div class="max-w-3xl mx-auto space-y-8">
    <header class="mb-8">
        <h2 class="text-3xl font-black tracking-tight text-slate-800 dark:text-white font-outfit justify-between flex items-center">
            Reconcile Account
            <span class="text-sm font-bold bg-primary/10 text-primary px-3 py-1 rounded-full uppercase tracking-widest">{{ $account->name }}</span>
        </h2>
        <p class="text-slate-500 dark:text-slate-400 mt-2">Adjust the system balance of your account to reflect its actual state in reality.</p>
    </header>

    <div class="bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xl overflow-hidden relative">
        <div class="absolute -right-16 -bottom-16 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
        <form action="{{ route('accounts.processReconcile', $account) }}" method="POST" class="p-6 md:p-10 space-y-8 relative z-10" id="reconcileForm">
            @csrf

            <!-- Balance Comparison -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-10 border-b border-slate-200 dark:border-slate-800">
                <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 text-center flex flex-col justify-center shadow-inner">
                    <span class="text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2 block">System Balance</span>
                    <span class="text-3xl font-black text-slate-800 dark:text-white mt-1">
                        Rp <span id="systemBalance">{{ number_format($account->total_balance, 0, ',', '.') }}</span>
                    </span>
                    <p class="text-[10px] uppercase font-bold text-slate-400 mt-3 tracking-wider">Recorded Limit Limit</p>
                </div>
                
                <div class="bg-primary/5 p-6 rounded-2xl border border-primary/20 text-center relative overflow-hidden group focus-within:ring-2 focus-within:ring-primary/50 focus-within:bg-primary/10 transition-all shadow-sm">
                    <label for="actual_balance" class="text-[11px] font-black text-primary uppercase tracking-widest mb-2 block cursor-pointer">Actual Balance (Kenyataan)</label>
                    <div class="flex items-center justify-center gap-2 mt-2">
                        <span class="text-3xl font-black text-primary leading-none">Rp</span>
                        <input type="number" id="actual_balance" name="actual_balance" value="{{ old('actual_balance', floatval($account->total_balance)) }}" 
                               class="text-4xl font-black text-primary bg-transparent border-none p-0 w-48 text-center focus:ring-0 placeholder-primary/30 leading-none" 
                               placeholder="0" required
                               oninput="calculateDifference()">
                    </div>
                    <p class="text-[10px] uppercase font-bold text-primary/60 mt-4 tracking-wider">Count your cash/bank app</p>
                </div>
            </div>

            <!-- Difference Feedback -->
            <div id="differenceFeedback" class="hidden p-5 rounded-2xl flex items-center gap-5 transition-all border shadow-sm">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 border" id="diffIconBg">
                    <span class="material-symbols-outlined text-3xl" id="diffIcon">sync_alt</span>
                </div>
                <div>
                    <h4 class="font-black text-xl tracking-tight" id="diffTitle">Adjusting</h4>
                    <p class="text-xs font-medium uppercase tracking-wider mt-1" id="diffDescription">A system adjustment will be made.</p>
                </div>
            </div>

            <!-- Category & Notes -->
            <div class="space-y-6 pt-2">
                <div class="space-y-2">
                    <label for="category_id" class="block text-xs font-black uppercase tracking-widest text-slate-700 dark:text-slate-300">Adjustment Category <span class="text-rose-500">*</span></label>
                    <p class="text-[11px] font-medium text-slate-500 mb-2">If there's a difference, this category will be applied to the adjustment transaction.</p>
                    <div class="relative">
                        <select name="category_id" id="category_id" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-3.5 pl-12 text-sm text-slate-700 dark:text-slate-200 placeholder-slate-400 font-bold transition-all appearance-none" required>
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">category</span>
                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                    </div>
                    @error('category_id')
                        <p class="text-rose-500 text-xs mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="notes" class="block text-xs font-black uppercase tracking-widest text-slate-700 dark:text-slate-300">Notes (Optional)</label>
                    <div class="relative">
                        <textarea name="notes" id="notes" rows="3" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-3.5 pl-12 text-sm text-slate-700 dark:text-slate-200 placeholder-slate-400 font-medium transition-all resize-none" placeholder="E.g., Lupa catat uang parkir">{{ old('notes') }}</textarea>
                        <span class="material-symbols-outlined absolute left-4 top-4 text-slate-400 pointer-events-none">edit_note</span>
                    </div>
                    @error('notes')
                        <p class="text-rose-500 text-xs mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-4 pt-6 mt-10 border-t border-slate-200 dark:border-slate-800">
                <a href="{{ route('accounts.index') }}" class="px-6 py-4 rounded-xl font-black text-slate-400 hover:text-white hover:bg-slate-800 transition-colors uppercase tracking-widest text-[11px]">
                    Cancel
                </a>
                <button type="submit" class="flex-1 bg-primary hover:bg-primary/90 text-white px-6 py-4 rounded-xl font-black flex justify-center items-center gap-3 transition-all shadow-xl shadow-primary/20 active:scale-95 group uppercase tracking-widest text-sm">
                    <span class="material-symbols-outlined font-black group-hover:rotate-180 transition-transform duration-500">task_alt</span>
                    Save Current Status
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const systemBalanceVal = parseFloat('{{ $account->total_balance }}');
    
    function calculateDifference() {
        const input = document.getElementById('actual_balance').value;
        const feedback = document.getElementById('differenceFeedback');
        const diffTitle = document.getElementById('diffTitle');
        const diffDesc = document.getElementById('diffDescription');
        const diffIcon = document.getElementById('diffIcon');
        const diffIconBg = document.getElementById('diffIconBg');
        
        if (input === '') {
            feedback.classList.add('hidden');
            return;
        }

        const actual = parseFloat(input);
        const diff = actual - systemBalanceVal;

        // Reset classes
        feedback.className = 'p-6 rounded-2xl flex items-center gap-5 transition-all border shadow-sm';
        diffIconBg.className = 'w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 border';

        feedback.classList.remove('hidden');

        if (Math.abs(diff) < 0.01) {
            // Equal
            feedback.classList.add('bg-emerald-500/10', 'border-emerald-500/20', 'text-emerald-700', 'dark:text-emerald-400');
            diffIconBg.classList.add('bg-emerald-500/20', 'border-emerald-500/30', 'text-emerald-500');
            diffIcon.innerText = 'check_circle';
            diffTitle.innerText = 'Perfectly Balanced';
            diffDesc.classList.add('text-emerald-500/80');
            diffDesc.classList.remove('text-blue-500/80', 'text-rose-500/80');
            diffDesc.innerText = 'No transaction needed. System checked timestamp will be updated.';
        } else if (diff > 0) {
            // Positive Diff (Found more money)
            feedback.classList.add('bg-blue-500/10', 'border-blue-500/20', 'text-blue-700', 'dark:text-blue-400');
            diffIconBg.classList.add('bg-blue-500/20', 'border-blue-500/30', 'text-blue-500');
            diffIcon.innerText = 'trending_up';
            diffTitle.innerText = '+ Rp ' + Math.abs(diff).toLocaleString('id-ID');
            diffDesc.classList.add('text-blue-500/80');
            diffDesc.classList.remove('text-emerald-500/80', 'text-rose-500/80');
            diffDesc.innerText = 'System will automatically create an INCOME transaction.';
        } else {
            // Negative Diff (Money missing)
            feedback.classList.add('bg-rose-500/10', 'border-rose-500/20', 'text-rose-700', 'dark:text-rose-400');
            diffIconBg.classList.add('bg-rose-500/20', 'border-rose-500/30', 'text-rose-500');
            diffIcon.innerText = 'trending_down';
            diffTitle.innerText = '- Rp ' + Math.abs(diff).toLocaleString('id-ID');
            diffDesc.classList.add('text-rose-500/80');
            diffDesc.classList.remove('text-emerald-500/80', 'text-blue-500/80');
            diffDesc.innerText = 'System will automatically create an EXPENSE transaction.';
        }
    }

    // Run on load to set initial state if any
    calculateDifference();
</script>
@endsection
