@extends('layouts.app')

@section('title', 'Subcategories')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('categories.index') }}" class="p-2 bg-white dark:bg-card-dark border border-slate-200 dark:border-slate-800 rounded-lg text-slate-500 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
            </a>
            <div>
                <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Subcategories</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Detailed breakdown of your spending & income categories.</p>
            </div>
        </div>
        <a href="{{ route('subcategories.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-primary hover:bg-primary/90 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined text-lg">add</span>
            New Subcategory
        </a>
    </header>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="flex items-center gap-3 px-5 py-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-500 text-sm font-semibold">
            <span class="material-symbols-outlined">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    {{-- Table Card --}}
    <div class="bg-white dark:bg-card-dark rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">
                        <th class="px-6 py-4 border-b dark:border-slate-800">Subcategory</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Parent Category</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800">Type</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800 text-center">Status</th>
                        <th class="px-6 py-4 border-b dark:border-slate-800 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($subcategories as $sub)
                        <tr class="hover:bg-primary/5 transition-colors group">
                            {{-- Name --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-8 rounded-lg {{ $sub->category->type === 'income' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500' }} flex items-center justify-center">
                                        <span class="material-symbols-outlined text-base">
                                            {{ $sub->category->type === 'income' ? 'payments' : 'sell' }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-semibold dark:text-white">{{ $sub->name }}</span>
                                </div>
                            </td>

                            {{-- Parent Category --}}
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-medium bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 rounded-full">
                                    {{ $sub->category->name }}
                                </span>
                            </td>

                            {{-- Type --}}
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-full uppercase
                                    {{ $sub->category->type === 'income'
                                        ? 'bg-emerald-500/10 text-emerald-500'
                                        : 'bg-rose-500/10 text-rose-500' }}">
                                    {{ ucfirst($sub->category->type) }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 text-center">
                                @if($sub->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-emerald-500 bg-emerald-500/10 rounded-md uppercase">
                                        <span class="size-1.5 bg-emerald-500 rounded-full"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-slate-400 bg-slate-500/10 rounded-md uppercase">
                                        <span class="size-1.5 bg-slate-400 rounded-full"></span> Inactive
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('subcategories.edit', $sub) }}"
                                       class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors text-slate-400 hover:text-primary">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </a>
                                    <form action="{{ route('subcategories.destroy', $sub) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete \'{{ $sub->name }}\'?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors text-slate-400 hover:text-red-500">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-slate-500">
                                    <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-700">account_tree</span>
                                    <p class="text-lg font-bold text-slate-900 dark:text-white">No subcategories yet</p>
                                    <p class="text-sm">Start by creating a subcategory for one of your categories.</p>
                                    <a href="{{ route('subcategories.create') }}"
                                       class="mt-2 px-6 py-2 bg-primary text-white rounded-lg text-sm font-bold hover:bg-primary/90 transition-colors">
                                        Create Subcategory
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer count --}}
        @if($subcategories->count() > 0)
        <div class="px-6 py-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
            Showing {{ $subcategories->count() }} subcategories
        </div>
        @endif
    </div>

</div>
@endsection
