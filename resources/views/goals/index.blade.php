@extends('layouts.app')

@section('title', 'Sinking Funds / Goals')

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Sinking Funds</h2>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Track your progress towards your financial goals</p>
        </div>
        <a href="{{ route('goals.create') }}" class="px-5 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">add</span> New Goal
        </a>
    </header>

    @if($goals->isEmpty())
        <div class="bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center text-slate-500">
            <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-700 mb-4">flag</span>
            <p class="text-base font-semibold">No active goals yet.</p>
            <p class="text-sm mt-2 mb-6">Create a goal like "Dana Nikah" or "DP Mobil" and link your investments/accounts to track progress automatically.</p>
            <a href="{{ route('goals.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-white rounded-xl font-semibold transition-all">
                Create First Goal
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($goals as $goal)
                @php
                    $progress = $goal->progress_percentage;
                    $isComplete = $goal->is_completed || $progress >= 100;
                    $color = $goal->color ?? '#3c83f6';
                @endphp
                <div class="bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 p-6 flex flex-col relative overflow-hidden group hover:shadow-xl transition-all hover:border-slate-300 dark:hover:border-slate-600">
                    
                    {{-- Decorative Blur Background --}}
                    <div class="absolute -top-12 -right-12 size-32 rounded-full blur-3xl opacity-20 transition-opacity group-hover:opacity-40" style="background-color: {{ $color }}"></div>

                    {{-- Header --}}
                    <div class="flex justify-between items-start mb-6 relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="size-10 rounded-xl flex items-center justify-center text-white" style="background-color: {{ $color }}">
                                <span class="material-symbols-outlined text-[18px]">
                                    {{ $isComplete ? 'check_circle' : 'emoji_events' }}
                                </span>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">
                                    {{ $goal->name }}
                                </h3>
                                @if($goal->target_date)
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mt-1 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">calendar_month</span>
                                    {{ $goal->target_date->format('M Y') }}
                                </p>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Dropdown Actions --}}
                        <div class="relative dropdown-container">
                            <button onclick="document.getElementById('dropdown-{{ $goal->id }}').classList.toggle('hidden')" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <span class="material-symbols-outlined text-[20px]">more_vert</span>
                            </button>
                            <div id="dropdown-{{ $goal->id }}" class="hidden absolute right-0 mt-2 w-40 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 z-50">
                                <a href="{{ route('goals.edit', $goal) }}" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 text-sm font-medium text-slate-700 dark:text-slate-300 rounded-t-xl transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">edit</span> Edit
                                </a>
                                <form action="{{ route('goals.destroy', $goal) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this goal?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 hover:bg-rose-50 dark:hover:bg-rose-900/10 text-sm font-medium text-rose-600 rounded-b-xl transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">delete</span> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Financial Stats --}}
                    <div class="mb-4 space-y-1 relative z-10">
                        <div class="flex justify-between items-end">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                Rp {{ number_format($goal->current_amount, 0, ',', '.') }}
                            </p>
                            <p class="text-xs font-medium text-slate-500 text-right">
                                Target: Rp {{ number_format($goal->target_amount, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mt-auto relative z-10">
                        <div class="flex justify-between text-xs font-bold mb-2">
                            <span class="text-slate-500 uppercase tracking-widest text-[9px]">Progress</span>
                            <span style="color: {{ $color }}">{{ number_format($progress, 1) }}%</span>
                        </div>
                        <div class="h-3 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full rounded-full transition-all duration-1000 ease-out relative" style="width: {{ $progress }}%; background-color: {{ $color }}">
                                {{-- Shimmer effect overlay --}}
                                <div class="absolute top-0 inset-0 bg-white/20 w-full h-full" style="clip-path: polygon(0 0, 100% 0, 80% 100%, 0% 100%);"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Links Info --}}
                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80 flex gap-3 text-xs text-slate-500 font-medium relative z-10">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">account_balance</span>
                            {{ $goal->accounts->count() }} Accounts
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">trending_up</span>
                            {{ $goal->investments->count() }} Investments
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        document.querySelectorAll('.dropdown-container').forEach(function(container) {
            if (!container.contains(event.target)) {
                let menu = container.querySelector('div[id^="dropdown-"]');
                if (menu && !menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                }
            }
        });
    });
</script>
@endpush
