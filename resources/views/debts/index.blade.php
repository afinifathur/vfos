@extends('layouts.app')

@section('title', 'Debt Management')

@section('content')
@php
    function getDebtStyle($name) {
        $name = strtolower($name);
        if (str_contains($name, 'home') || str_contains($name, 'mortgage') || str_contains($name, 'house')) {
            return ['icon' => 'home', 'color' => 'emerald-500', 'bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-500', 'type' => 'Fixed Rate Loan'];
        }
        if (str_contains($name, 'card') || str_contains($name, 'visa') || str_contains($name, 'mastercard') || str_contains($name, 'credit')) {
            return ['icon' => 'credit_card', 'color' => 'red-500', 'bg' => 'bg-red-500/10', 'text' => 'text-red-500', 'type' => 'Revolving Credit'];
        }
        if (str_contains($name, 'auto') || str_contains($name, 'car') || str_contains($name, 'vehicle') || str_contains($name, 'motor')) {
            return ['icon' => 'directions_car', 'color' => 'primary', 'bg' => 'bg-primary/10', 'text' => 'text-primary', 'type' => 'Installment Loan'];
        }
        if (str_contains($name, 'student') || str_contains($name, 'school') || str_contains($name, 'education')) {
            return ['icon' => 'school', 'color' => 'amber-500', 'bg' => 'bg-amber-500/10', 'text' => 'text-amber-500', 'type' => 'Education Loan'];
        }
        if (str_contains($name, 'personal') || str_contains($name, 'cash') || str_contains($name, 'payday')) {
             return ['icon' => 'payments', 'color' => 'purple-500', 'bg' => 'bg-purple-500/10', 'text' => 'text-purple-500', 'type' => 'Personal Loan'];
        }
        return ['icon' => 'account_balance_wallet', 'color' => 'slate-500', 'bg' => 'bg-slate-500/10', 'text' => 'text-slate-500', 'type' => 'Liability'];
    }
@endphp
<div class="max-w-7xl mx-auto xl:mr-8 xl:ml-0 space-y-8">
    <!-- Header -->
    <header class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100">Debt Management</h2>
            <p class="text-slate-500 dark:text-slate-400">Track and optimize your liabilities</p>
        </div>
        <a href="{{ route('debts.create') }}" class="bg-primary hover:bg-primary/90 text-white px-5 py-2.5 rounded-lg font-semibold flex items-center gap-2 transition-all shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Add New Debt
        </a>
    </header>

    <!-- KPI Cards -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Debt Amount -->
        <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-red-500/5 rounded-full blur-2xl"></div>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium relative z-10">Total Debt Amount</p>
            <div class="mt-2 flex items-baseline gap-2 relative z-10">
                <span class="text-2xl font-bold">Rp {{ number_format($totalDebtAmount, 0, ',', '.') }}</span>
            </div>
            <p class="text-xs text-red-500 mt-2 flex items-center gap-1 relative z-10">
                <span class="material-symbols-outlined text-[14px]">account_balance</span> Standard liability measure
            </p>
        </div>

        <!-- Total Principal Paid -->
        <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl"></div>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium relative z-10">Total Principal Paid</p>
            <div class="mt-2 flex items-baseline gap-2 relative z-10">
                <span class="text-2xl font-bold">Rp {{ number_format(max(0, $totalPrincipalPaid), 0, ',', '.') }}</span>
            </div>
            <p class="text-xs text-emerald-500 mt-2 flex items-center gap-1 relative z-10">
                <span class="material-symbols-outlined text-[14px]">check_circle</span> Principal reduced globally
            </p>
        </div>

        <!-- Average Interest Rate (Placeholder) -->
        <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-slate-500/5 rounded-full blur-2xl"></div>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium relative z-10">Average Interest Rate</p>
            <div class="mt-2 flex items-baseline gap-2 relative z-10">
                <span class="text-2xl font-bold text-slate-300">-</span>
            </div>
            <p class="text-xs text-slate-400 mt-2 relative z-10">Data unavailable in schema</p>
        </div>

        <!-- Next Payment Date -->
        <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl"></div>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium relative z-10">Next Payment Date</p>
            <div class="mt-2 flex items-baseline gap-2 relative z-10">
                <span class="text-2xl font-bold">{{ $nextPaymentDate ? \Carbon\Carbon::parse($nextPaymentDate)->format('M d, Y') : 'None found' }}</span>
            </div>
            <p class="text-xs {{ $nextPaymentDate ? 'text-amber-500' : 'text-slate-400' }} mt-2 flex items-center gap-1 relative z-10">
                <span class="material-symbols-outlined text-[14px]">{{ $nextPaymentDate ? 'event_repeat' : 'event' }}</span> 
                {{ $nextPaymentDate ? $upcomingPayments->count() . ' payments upcoming' : 'No schedules available' }}
            </p>
        </div>
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Debt Grid -->
        <section class="xl:col-span-2 space-y-6">
            <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span class="material-symbols-outlined">list_alt</span>
                Active Liabilities
            </h3>

            @forelse($debts as $debt)
                @php
                    $style = getDebtStyle($debt->name);
                    $paidAmount = $debt->total_amount - $debt->remaining_amount;
                    $progressPercentage = $debt->total_amount > 0 ? ($paidAmount / $debt->total_amount) * 100 : 0;
                    
                    // Logic to determine status badge based on due date proximity
                    $badgeText = "On Track";
                    $badgeStyle = "bg-emerald-500/10 text-emerald-500";
                    $barColor = "bg-emerald-500";
                    
                    if ($debt->due_date) {
                        $daysUntilDue = \Carbon\Carbon::parse($debt->due_date)->diffInDays(now(), false);
                        if ($daysUntilDue > -3 && $daysUntilDue <= 0) {
                            $badgeText = "Due Soon";
                            $badgeStyle = "bg-red-500/10 text-red-500";
                            $barColor = "bg-red-500";
                        } elseif ($daysUntilDue <= -3 && $daysUntilDue >= -14) {
                            $badgeText = "Upcoming";
                            $badgeStyle = "bg-amber-500/10 text-amber-500";
                        }
                    }
                @endphp
                <!-- Debt Card -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm hover:border-[{{$style['color']}}]/30 transition-colors group">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex gap-4">
                                <div class="size-12 rounded-lg {{ $style['bg'] }} {{ $style['text'] }} flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[28px]">{{ $style['icon'] }}</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-lg">{{ $debt->name }}</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-widest font-bold">{{ $style['type'] }}</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 {{ $badgeStyle }} text-xs font-bold rounded-full uppercase tracking-tighter">{{ $badgeText }}</span>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="space-y-1">
                                <p class="text-xs text-slate-500 dark:text-slate-400">Current Balance</p>
                                <p class="text-lg font-bold">Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs text-slate-500 dark:text-slate-400">Original Amount</p>
                                <p class="text-lg font-bold">Rp {{ number_format($debt->total_amount, 0, ',', '.') }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs text-slate-500 dark:text-slate-400">Interest Rate</p>
                                <p class="text-lg font-bold text-slate-300">-</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs text-slate-500 dark:text-slate-400">Min. Payment</p>
                                <p class="text-lg font-bold text-slate-300">-</p>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="text-slate-500">Paid: Rp {{ number_format(max(0, $paidAmount), 0, ',', '.') }}</span>
                                <span class="text-slate-500">Progress: {{ number_format(max(0, $progressPercentage), 1) }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                <div class="{{ $barColor }} h-full transition-all duration-700 ease-out" style="width: {{ min(100, max(0, $progressPercentage)) }}%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-3 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
                        <p class="text-xs {{ $badgeText === 'Due Soon' ? 'text-red-500 font-bold' : 'text-slate-500' }}">
                            {{ $debt->due_date ? 'Maturity / Next Date: ' . \Carbon\Carbon::parse($debt->due_date)->format('F Y') : 'No due date set' }}
                        </p>
                        <div class="flex gap-4">
                            <a href="{{ route('debts.edit', $debt) }}" class="text-xs font-bold text-primary hover:underline">Manage Account</a>
                             <form action="{{ route('debts.destroy', $debt) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this debt entry?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-red-500 hover:underline">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-1 lg:col-span-2 bg-card-dark border border-slate-800 border-dashed rounded-xl p-12 text-center flex flex-col items-center justify-center shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-slate-800/50 border border-slate-700 flex items-center justify-center text-slate-500 mb-4">
                        <span class="material-symbols-outlined text-[32px]">payments</span>
                    </div>
                    <h4 class="text-lg font-bold text-white mb-2">No Active Liabilities</h4>
                    <p class="text-slate-400 text-sm max-w-sm mb-6">Congratulations! You seemingly don't have any active debts. Time to focus on investments!</p>
                    <a href="{{ route('debts.create') }}" class="bg-primary hover:bg-primary/90 text-white text-sm font-bold px-4 py-2 rounded-lg transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Add A Debt
                    </a>
                </div>
            @endforelse
        </section>

        <!-- Sidebar Schedule -->
        <aside class="space-y-6">
            <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span class="material-symbols-outlined">calendar_today</span>
                Upcoming Payments
            </h3>
            
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="p-4 space-y-4">
                    @forelse($upcomingPayments as $payment)
                        @php
                            $date = \Carbon\Carbon::parse($payment->due_date);
                            $month = $date->format('M');
                            $day = $date->format('d');
                            $daysUntilDue = $date->diffInDays(now(), false);
                            
                            $isOverdueOrDueSoon = $daysUntilDue > -3 && $daysUntilDue <= 0;
                            $containerClass = $isOverdueOrDueSoon 
                                ? "border-red-500/20 bg-red-500/5" 
                                : "border-slate-100 dark:border-slate-800";
                            $calendarBg = $isOverdueOrDueSoon
                                ? "bg-red-500 text-white"
                                : "bg-slate-200 dark:bg-slate-800 text-slate-900 dark:text-white";
                        @endphp
                        <!-- Upcoming Item -->
                        <div class="flex gap-4 p-3 rounded-lg border {{ $containerClass }}">
                            <div class="flex-shrink-0 flex flex-col items-center justify-center {{ $calendarBg }} w-12 h-14 rounded-lg">
                                <span class="text-[10px] font-bold uppercase">{{ $month }}</span>
                                <span class="text-xl font-black">{{ $day }}</span>
                            </div>
                            <div class="flex-1 overflow-hidden">
                                <p class="text-sm font-bold truncate">{{ $payment->name }}</p>
                                <p class="text-xs text-slate-500">Remaining Balance</p>
                                <p class="text-sm font-black {{ $isOverdueOrDueSoon ? 'text-red-500' : 'mt-1' }}">Rp {{ number_format($payment->remaining_amount, 0, ',', '.') }}</p>
                            </div>
                            <div class="flex items-center">
                                <a href="{{ route('debts.edit', $payment) }}" class="material-symbols-outlined text-slate-400 hover:text-primary transition-colors cursor-pointer">chevron_right</a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-500 text-sm">
                            No upcoming payments scheduled.
                        </div>
                    @endforelse
                </div>
                
                @if($upcomingPayments->count() > 0)
                    <button class="w-full py-4 text-sm font-bold text-slate-500 hover:text-primary transition-colors border-t border-slate-200 dark:border-slate-800 uppercase tracking-widest">
                        View Full Calendar
                    </button>
                @endif
            </div>

            <!-- Insights Section -->
            @if($debts->count() > 0)
            <div class="bg-primary/10 border border-primary/20 rounded-xl p-6 relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/20 rounded-full blur-2xl"></div>
                <h4 class="font-bold text-primary flex items-center gap-2 mb-4 relative z-10">
                    <span class="material-symbols-outlined text-[20px]">lightbulb</span>
                    Strategy Tip
                </h4>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed relative z-10">
                    Focus on eliminating high-interest debt first while maintaining minimum payments on others. This "avalanche method" saves the most money in the long term mathematically.
                </p>
            </div>
            @endif
        </aside>
    </div>
</div>
@endsection
