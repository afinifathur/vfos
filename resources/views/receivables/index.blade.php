@extends('layouts.app')

@section('title', 'Receivables Management')

@section('content')
@php
    function getReceivableStyle($name) {
        $name = strtolower($name);
        if (str_contains($name, 'project') || str_contains($name, 'startup') || str_contains($name, 'business') || str_contains($name, 'client')) {
            return ['icon' => 'business_center', 'bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-500 text-slate-400', 'type' => 'Business / Startup'];
        }
        if (str_contains($name, 'family') || str_contains($name, 'friend') || str_contains($name, 'relative')) {
            return ['icon' => 'group', 'bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-500 text-slate-400', 'type' => 'Family / Friends'];
        }
        return ['icon' => 'person', 'bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-500 text-slate-400', 'type' => 'Personal Loan'];
    }
@endphp
<div class="max-w-7xl mx-auto xl:mr-8 xl:ml-0 space-y-8">
    <header class="flex justify-between items-center mb-8 bg-card-dark p-4 rounded-xl border border-slate-800 shadow-sm relative overflow-hidden">
        <div class="absolute -right-4 -top-4 w-48 h-48 bg-primary/5 rounded-full blur-3xl"></div>
        <div class="relative z-10">
            <h2 class="text-3xl font-bold tracking-tight text-white">Receivables Management</h2>
            <p class="text-slate-400">Track and manage loans you've issued to others</p>
        </div>
        <a href="{{ route('receivables.create') }}" class="bg-primary hover:bg-primary/90 text-white font-bold py-2.5 px-6 rounded-lg flex items-center gap-2 transition-all shadow-lg shadow-primary/20 relative z-10">
            <span class="material-symbols-outlined">add_circle</span>
            Record New Loan
        </a>
    </header>

    <!-- KPI Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-card-dark p-6 rounded-xl border border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <p class="text-sm font-medium text-slate-400 uppercase tracking-wider mb-2 relative z-10">Total Outstanding</p>
            <p class="text-2xl font-bold text-white relative z-10">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</p>
            <div class="mt-2 flex items-center text-xs font-medium text-emerald-500 relative z-10">
                <span class="material-symbols-outlined text-xs mr-1">trending_up</span>
                Active Portfolio
            </div>
        </div>
        <div class="bg-card-dark p-6 rounded-xl border border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="absolute inset-0 bg-red-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <p class="text-sm font-medium text-slate-400 uppercase tracking-wider mb-2 relative z-10">Overdue Amount</p>
            <p class="text-2xl font-bold text-red-500 relative z-10">Rp {{ number_format($overdueAmount, 0, ',', '.') }}</p>
            <div class="mt-2 flex items-center text-xs font-medium text-red-500 relative z-10">
                <span class="material-symbols-outlined text-xs mr-1">warning</span>
                Requires Attention
            </div>
        </div>
        <div class="bg-card-dark p-6 rounded-xl border border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <p class="text-sm font-medium text-slate-400 uppercase tracking-wider mb-2 relative z-10">Expected This Month</p>
            <p class="text-2xl font-bold text-white relative z-10">Rp {{ number_format($expectedThisMonth, 0, ',', '.') }}</p>
            <div class="mt-2 flex items-center text-xs font-medium text-primary relative z-10">
                <span class="material-symbols-outlined text-xs mr-1">calendar_today</span>
                Forecasted Inflow
            </div>
        </div>
        <div class="bg-card-dark p-6 rounded-xl border border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="absolute inset-0 bg-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <p class="text-sm font-medium text-slate-400 uppercase tracking-wider mb-2 relative z-10">Total Collected</p>
            <p class="text-2xl font-bold text-emerald-500 relative z-10">Rp {{ number_format($totalCollected, 0, ',', '.') }}</p>
            <div class="mt-2 flex items-center text-xs font-medium text-emerald-500 relative z-10">
                <span class="material-symbols-outlined text-xs mr-1">check_circle</span>
                Successful Returns
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Active Loans Section -->
        <div class="lg:col-span-2 space-y-6">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">local_atm</span>
                Active Loans
            </h3>
            
            @forelse($receivables as $receivable)
                @php
                    $style = getReceivableStyle($receivable->name);
                    $paidAmount = $receivable->total_amount - $receivable->remaining_amount;
                    $progressPercentage = $receivable->total_amount > 0 ? ($paidAmount / $receivable->total_amount) * 100 : 0;
                    
                    $badgeText = "On Track";
                    $badgeStyle = "bg-emerald-500/10 text-emerald-500 border-emerald-500/20";
                    $barColor = "bg-primary";
                    $statusText = "";
                    $statusColor = "";
                    
                    if ($receivable->due_date) {
                        $daysUntilDue = \Carbon\Carbon::parse($receivable->due_date)->diffInDays(now(), false);
                        if ($daysUntilDue > 0) {
                            $badgeText = "Overdue";
                            $badgeStyle = "bg-red-500/10 text-red-500 border-red-500/20";
                            $barColor = "bg-red-500";
                            $statusText = floor($daysUntilDue) . " days overdue";
                            $statusColor = "text-red-500";
                        } else {
                            $statusText = "Due in " . abs(floor($daysUntilDue)) . " days";
                        }
                    } else {
                        $statusText = "No due date";
                    }
                @endphp
                <!-- Loan Card -->
                <div class="bg-card-dark rounded-xl border border-slate-800 overflow-hidden hover:border-slate-700 transition-colors shadow-sm relative group">
                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-95 group-hover:scale-100 flex gap-2">
                         <form action="{{ route('receivables.destroy', $receivable) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this receivable?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-slate-500 hover:text-red-500 bg-slate-800/80 hover:bg-slate-700 size-8 rounded-full flex items-center justify-center transition-colors backdrop-blur-sm shadow-sm" title="Delete ">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </form>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full {{ $style['bg'] }} flex items-center justify-center">
                                    <span class="material-symbols-outlined {{ $style['text'] }}">{{ $style['icon'] }}</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-lg text-white">{{ $receivable->name }}</h4>
                                    <p class="text-sm text-slate-500">{{ $style['type'] }} • {{ $receivable->notes ? \Illuminate\Support\Str::limit($receivable->notes, 30) : 'No specific details' }}</p>
                                </div>
                            </div>
                            <span class="{{ $badgeStyle }} text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border">{{ $badgeText }}</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-6 mb-4">
                            <div>
                                <p class="text-xs text-slate-400 mb-1">Total Loan Amount</p>
                                <p class="text-lg font-bold text-white">Rp {{ number_format($receivable->total_amount, 0, ',', '.') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400 mb-1">Remaining Balance</p>
                                <p class="text-lg font-bold {{ $badgeText === 'Overdue' ? 'text-red-500' : 'text-primary' }}">Rp {{ number_format($receivable->remaining_amount, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <div class="flex justify-between text-xs mb-1.5">
                                <span class="text-slate-500">Repayment Progress ({{ number_format(max(0, $progressPercentage), 0) }}%)</span>
                                <span class="font-medium {{ $statusColor }}">{{ $statusText }}</span>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-2 shadow-inner">
                                <div class="{{ $barColor }} h-2 rounded-full transition-all duration-1000 ease-out" style="width: {{ min(100, max(0, $progressPercentage)) }}%"></div>
                            </div>
                        </div>
                        
                        <div class="flex gap-3 mt-4 pt-4 border-t border-slate-800">
                            @if($badgeText === 'Overdue')
                                <a href="mailto:?subject=Overdue Payment Reminder: {{ $receivable->name }}&body=Hi, this is a reminder about the overdue payment of Rp {{ number_format($receivable->remaining_amount, 0, ',', '.') }}" class="flex-1 bg-red-500/10 hover:bg-red-500/20 text-red-500 font-bold py-2 rounded-lg text-sm transition-colors text-center border border-red-500/20">Contact Borrower</a>
                            @else
                                <a href="mailto:?subject=Payment Reminder: {{ $receivable->name }}" class="flex-1 bg-primary/10 hover:bg-primary/20 text-primary font-bold py-2 rounded-lg text-sm transition-colors text-center border border-primary/20">Send Reminder</a>
                            @endif
                            <a href="{{ route('receivables.edit', $receivable) }}" class="px-6 border border-slate-700 hover:bg-slate-800 text-white font-medium py-2 rounded-lg text-sm transition-colors text-center">Manage Loan</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-card-dark border border-slate-800 border-dashed rounded-xl p-12 text-center flex flex-col items-center justify-center shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-slate-800/50 border border-slate-700 flex items-center justify-center text-slate-500 mb-4">
                        <span class="material-symbols-outlined text-[32px]">handshake</span>
                    </div>
                    <h4 class="text-lg font-bold text-white mb-2">No Active Receivables</h4>
                    <p class="text-slate-400 text-sm max-w-sm mb-6">You currently have no active loans issued to others.</p>
                    <a href="{{ route('receivables.create') }}" class="bg-primary hover:bg-primary/90 text-white text-sm font-bold px-4 py-2 rounded-lg transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Lend Money
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Secondary Section -->
        <div class="space-y-8">
            <!-- Aging Summary -->
            <section>
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-400">hourglass_empty</span>
                    Aging Summary
                </h3>
                <div class="bg-card-dark p-6 rounded-xl border border-slate-800 shadow-sm">
                    @php
                        $totalAging = max(1, $currentOverdueAmount + $thirtyToSixtyOverdue + $overSixtyOverdue);
                        $currentPct = ($currentOverdueAmount / $totalAging) * 100;
                        $thirtyPct = ($thirtyToSixtyOverdue / $totalAging) * 100;
                        $overSixtyPct = ($overSixtyOverdue / $totalAging) * 100;
                    @endphp
                    <div class="space-y-5">
                        <div class="group cursor-default">
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="text-slate-400 group-hover:text-slate-300 transition-colors">Current (0-30 days)</span>
                                <span class="font-bold text-white">Rp {{ number_format($currentOverdueAmount, 0, ',', '.') }}</span>
                            </div>
                            <div class="w-full bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="bg-primary h-full transition-all duration-1000" style="width: {{ $currentPct }}%"></div>
                            </div>
                        </div>
                        <div class="group cursor-default">
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="text-slate-400 group-hover:text-slate-300 transition-colors">31-60 days</span>
                                <span class="font-bold text-amber-500">Rp {{ number_format($thirtyToSixtyOverdue, 0, ',', '.') }}</span>
                            </div>
                            <div class="w-full bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="bg-amber-500 h-full transition-all duration-1000" style="width: {{ $thirtyPct }}%"></div>
                            </div>
                        </div>
                        <div class="group cursor-default">
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="text-slate-400 group-hover:text-slate-300 transition-colors">60+ days overdue</span>
                                <span class="font-bold text-red-500">Rp {{ number_format($overSixtyOverdue, 0, ',', '.') }}</span>
                            </div>
                            <div class="w-full bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="bg-red-500 h-full transition-all duration-1000" style="width: {{ $overSixtyPct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Strategy Tip -->
            @if($overdueAmount > 0)
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-6 relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-red-500/20 rounded-full blur-2xl"></div>
                <h4 class="font-bold text-red-500 flex items-center gap-2 mb-4 relative z-10">
                    <span class="material-symbols-outlined text-[20px]">warning</span>
                    Collection Alert
                </h4>
                <p class="text-sm text-slate-300 leading-relaxed relative z-10">
                    You have <span class="font-bold text-white">Rp {{ number_format($overdueAmount, 0, ',', '.') }}</span> currently overdue. Consider sending follow-up reminders to improve your cash flow recovery rate.
                </p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
