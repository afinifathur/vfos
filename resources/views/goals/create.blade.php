@extends('layouts.app')

@section('title', 'New Sinking Fund')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">New Goal</h2>
        <p class="text-slate-500 dark:text-slate-400 mt-1">Set a new financial target for your sinking funds.</p>
    </div>

    <form action="{{ route('goals.store') }}" method="POST" class="bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        @csrf
        
        <div class="p-8 space-y-6">
            {{-- Name & Owner Grid --}}
            <div class="grid grid-cols-1 {{ auth()->user()->role === 'admin' ? 'md:grid-cols-2' : '' }} gap-6">
                {{-- Name --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Goal Name</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">flag</span>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full pl-12 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border {{ $errors->has('name') ? 'border-rose-500' : 'border-slate-200 dark:border-slate-700' }} rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all dark:text-white" placeholder="e.g., Dana Nikah, DP Rumah, Liburan Jepang">
                    </div>
                    @error('name') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Owner (Admin Only) --}}
                @if(auth()->user()->role === 'admin')
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Primary Owner</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">person</span>
                        <select name="owner" required class="w-full pl-12 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border {{ $errors->has('owner') ? 'border-rose-500' : 'border-slate-200 dark:border-slate-700' }} rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none appearance-none transition-all dark:text-white cursor-pointer">
                            <option value="afin">Afin</option>
                            <option value="pacar">Pacar</option>
                            <option value="business">Business</option>
                        </select>
                    </div>
                    @error('owner') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>
                @else
                    <input type="hidden" name="owner" value="{{ auth()->user()->role === 'partner' ? 'pacar' : (auth()->user()->role === 'business' ? 'business' : 'afin') }}">
                @endif
            </div>

            {{-- Target Amount --}}
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Target Amount (Rp)</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">payments</span>
                    <input type="number" name="target_amount" value="{{ old('target_amount') }}" required min="0" step="1000" class="w-full pl-12 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border {{ $errors->has('target_amount') ? 'border-rose-500' : 'border-slate-200 dark:border-slate-700' }} rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all dark:text-white" placeholder="100000000">
                </div>
                @error('target_amount') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Target Date & Color Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Target Date --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Target Deadline (Optional)</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">calendar_month</span>
                        <input type="date" name="target_date" value="{{ old('target_date') }}" class="w-full pl-12 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border {{ $errors->has('target_date') ? 'border-rose-500' : 'border-slate-200 dark:border-slate-700' }} rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all dark:text-white">
                    </div>
                    @error('target_date') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Color --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Theme Color</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">palette</span>
                        <input type="color" name="color" value="{{ old('color', '#3c83f6') }}" class="w-full h-[46px] pl-12 pr-2 py-1 bg-slate-50 dark:bg-slate-800 border {{ $errors->has('color') ? 'border-rose-500' : 'border-slate-200 dark:border-slate-700' }} rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none cursor-pointer transition-all dark:text-white">
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Notes</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-4 text-slate-400">notes</span>
                    <textarea name="notes" rows="3" class="w-full pl-12 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border {{ $errors->has('notes') ? 'border-rose-500' : 'border-slate-200 dark:border-slate-700' }} rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all dark:text-white" placeholder="Any details or link about this goal...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="px-8 py-5 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
            <a href="{{ route('goals.index') }}" class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">save</span> Create Goal
            </button>
        </div>
    </form>
</div>
@endsection
