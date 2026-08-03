@extends('layouts.app')

@section('title', 'Gmail Inbox Synchronization')

@section('content')
@php
    $reconnectRequired = $isConnected && (!$hasRequiredScopes || ($errors->has('google') && str_contains($errors->first('google'), 'ACCESS_TOKEN_SCOPE_INSUFFICIENT')));
@endphp
<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Gmail Inbox Cache</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Synchronize and persist emails locally to establish a reusable offline Connector cache.
                </p>
            </div>
            <div>
                @if($isConnected)
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-500/10 border border-green-500/20 text-green-500 text-xs font-semibold">
                        <span class="size-2 rounded-full bg-green-500 animate-pulse"></span>
                        Connected: {{ $googleEmail }}
                    </div>
                @else
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-semibold">
                        <span class="size-2 rounded-full bg-red-500"></span>
                        Google Account Disconnected
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Connection Warning -->
    @if(!$isConnected)
        <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 text-amber-500 text-sm flex gap-3">
            <span class="material-symbols-outlined flex-shrink-0">warning</span>
            <div>
                <p class="font-semibold">Synchronization Unavailable</p>
                <p class="text-xs mt-0.5">Please configure your Google Credentials and authenticate under <a href="{{ route('settings.index') }}#google" class="underline hover:text-amber-400">Settings > Google</a> first.</p>
            </div>
        </div>
    @endif

    @if($reconnectRequired)
        <div class="bg-rose-500/10 border border-rose-500/20 rounded-xl p-4 text-rose-500 text-sm flex gap-3 animate-pulse">
            <span class="material-symbols-outlined flex-shrink-0">warning</span>
            <div>
                <p class="font-semibold">Reauthorization Required</p>
                <p class="text-xs mt-0.5">Your Google connection lacks required permissions (gmail.readonly). Please <a href="{{ route('settings.index') }}#google" class="underline font-bold hover:text-rose-400">Reconnect your Google Account</a> under settings to restore synchronization.</p>
            </div>
        </div>
    @endif

    @if($errors->any() && !$reconnectRequired)
        <div class="rounded-xl bg-red-500/10 p-4 border border-red-500/20 text-red-500 text-sm flex gap-3">
            <span class="material-symbols-outlined flex-shrink-0">error</span>
            <div>
                <p class="font-semibold">Error Occurred</p>
                <ul class="list-disc list-inside text-xs mt-1 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if($isConnected)
        <!-- Stats Grid & Sync Controller Card -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sync Statistics Card (Revision 6) -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Last Sync Session</h4>
                        <span class="material-symbols-outlined text-slate-400">insights</span>
                    </div>
                    @if($lastSyncJob)
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-xs border-b border-slate-100 dark:border-slate-800 pb-2">
                                <span class="text-slate-400">Last Sync:</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300">
                                    {{ $lastSyncJob->finished_at ? $lastSyncJob->finished_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s') : 'N/A' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-xs border-b border-slate-100 dark:border-slate-800 pb-2">
                                <span class="text-slate-400">Duration:</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300">
                                    {{ number_format($lastSyncJob->duration_ms / 1000, 2) }} seconds
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-xs border-b border-slate-100 dark:border-slate-800 pb-2">
                                <span class="text-slate-400">Status:</span>
                                <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-full {{ $lastSyncJob->status === 'SUCCESS' ? 'bg-green-500/10 text-green-500 border border-green-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20' }}">
                                    {{ $lastSyncJob->status }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 pt-2 text-center">
                                <div class="bg-slate-50 dark:bg-slate-800/40 rounded-lg p-2 border border-slate-100 dark:border-slate-850">
                                    <p class="text-[10px] text-slate-400 uppercase">Downloaded</p>
                                    <p class="text-base font-bold text-slate-800 dark:text-white">{{ $lastSyncJob->downloaded }}</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-800/40 rounded-lg p-2 border border-slate-100 dark:border-slate-850">
                                    <p class="text-[10px] text-slate-400 uppercase">Skipped</p>
                                    <p class="text-base font-bold text-slate-550 dark:text-slate-300">{{ $lastSyncJob->skipped }}</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-800/40 rounded-lg p-2 border border-slate-100 dark:border-slate-850">
                                    <p class="text-[10px] text-slate-400 uppercase">Failed</p>
                                    <p class="text-base font-bold text-red-550 dark:text-red-400">{{ $lastSyncJob->failed }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-6 text-xs text-slate-500 dark:text-slate-400">
                            <span class="material-symbols-outlined text-3xl mb-1 text-slate-350 dark:text-slate-600">sync_problem</span>
                            <p>No synchronization history available.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sync Controller Card -->
            <div class="lg:col-span-2 bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Inbox Sync Controls</h4>
                <form id="syncForm" action="{{ route('gmail-inbox.sync') }}" method="POST" class="space-y-4" onsubmit="showSyncLoader()">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-3">
                            <label for="query" class="block text-xs font-semibold text-slate-400 mb-1">Gmail Search Query</label>
                            <input type="text" name="query" id="query" value="{{ old('query', 'newer_than:30d') }}" 
                                {{ $reconnectRequired ? 'disabled' : '' }}
                                class="w-full text-sm rounded-lg bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-white focus:ring-primary focus:border-primary p-2 {{ $reconnectRequired ? 'opacity-50 cursor-not-allowed' : '' }}"
                                placeholder="newer_than:30d from:(bca@bca.co.id OR cba@bca.co.id)">
                        </div>
                        <div>
                            <label for="max_results" class="block text-xs font-semibold text-slate-400 mb-1">Max Download</label>
                            <select name="max_results" id="max_results" 
                                {{ $reconnectRequired ? 'disabled' : '' }}
                                class="w-full text-sm rounded-lg bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-white focus:ring-primary focus:border-primary p-2 {{ $reconnectRequired ? 'opacity-50 cursor-not-allowed' : '' }}">
                                <option value="20">20 messages</option>
                                <option value="50">50 messages</option>
                                <option value="100" selected>100 messages</option>
                                <option value="250">250 messages</option>
                            </select>
                        </div>
                    </div>

                    <!-- Presets Grid -->
                    <div class="space-y-2">
                        <span class="block text-xs font-semibold text-slate-400">Quick Sync Presets</span>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @if(!$reconnectRequired) onclick="setQueryPreset('newer_than:30d from:(bca@bca.co.id OR cba@bca.co.id)')" @else disabled @endif
                                class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-350 text-xs font-medium transition-all {{ $reconnectRequired ? 'opacity-50 cursor-not-allowed' : '' }}">
                                Recent BCA
                            </button>
                            <button type="button" @if(!$reconnectRequired) onclick="setQueryPreset('newer_than:30d from:noreply.livin@bankmandiri.co.id')" @else disabled @endif
                                class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-350 text-xs font-medium transition-all {{ $reconnectRequired ? 'opacity-50 cursor-not-allowed' : '' }}">
                                Recent Mandiri
                            </button>
                            <button type="button" @if(!$reconnectRequired) onclick="setQueryPreset('newer_than:30d from:bnicall@bni.co.id')" @else disabled @endif
                                class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-350 text-xs font-medium transition-all {{ $reconnectRequired ? 'opacity-50 cursor-not-allowed' : '' }}">
                                Recent BNI
                            </button>
                            <button type="button" @if(!$reconnectRequired) onclick="setQueryPreset('newer_than:7d')" @else disabled @endif
                                class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-350 text-xs font-medium transition-all {{ $reconnectRequired ? 'opacity-50 cursor-not-allowed' : '' }}">
                                Last 7 Days
                            </button>
                            <button type="button" @if(!$reconnectRequired) onclick="setQueryPreset('newer_than:30d')" @else disabled @endif
                                class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-350 text-xs font-medium transition-all {{ $reconnectRequired ? 'opacity-50 cursor-not-allowed' : '' }}">
                                Last 30 Days
                            </button>
                            <button type="button" @if(!$reconnectRequired) onclick="setQueryPreset('is:unread')" @else disabled @endif
                                class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-350 text-xs font-medium transition-all {{ $reconnectRequired ? 'opacity-50 cursor-not-allowed' : '' }}">
                                Unread
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        @if(!$reconnectRequired)
                            <button type="submit" id="syncSubmitBtn" class="flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary/95 text-white rounded-lg text-sm font-semibold transition-all shadow-md shadow-primary/20 cursor-pointer">
                                <span class="material-symbols-outlined text-lg" id="syncBtnIcon">sync</span>
                                <span id="syncBtnText">Sync Cache Now</span>
                            </button>
                        @else
                            <button type="button" disabled class="flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-semibold cursor-not-allowed">
                                <span class="material-symbols-outlined text-lg">lock</span>
                                Sync Cache Now (Scope Missing)
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Finance Import Pipeline Dashboard -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800/80 pb-4 mb-4">
            <div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Finance Import Pipeline</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Review parsed transaction details and import them directly into the finance ledger.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form action="{{ route('gmail-inbox.parse') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                        {{ $reconnectRequired ? 'disabled' : '' }}
                        class="flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-750 transition-all {{ $reconnectRequired ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                        <span class="material-symbols-outlined text-base">analytics</span>
                        Parse Sync Cache
                    </button>
                </form>
                <button type="button" 
                    @if(!$reconnectRequired) onclick="submitImportSelected()" @else disabled @endif
                    class="flex items-center gap-1.5 px-3.5 py-2 bg-primary/10 hover:bg-primary/20 text-primary rounded-lg text-xs font-semibold border border-primary/20 transition-all {{ $reconnectRequired ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                    <span class="material-symbols-outlined text-base">check_box</span>
                    Import Selected
                </button>
                <form action="{{ route('gmail-inbox.import-all-ready') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                        {{ $reconnectRequired ? 'disabled' : '' }}
                        class="flex items-center gap-1.5 px-3.5 py-2 bg-primary hover:bg-primary/95 text-white rounded-lg text-xs font-semibold transition-all shadow-md shadow-primary/20 {{ $reconnectRequired ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                        <span class="material-symbols-outlined text-base">cloud_download</span>
                        Import All Ready
                    </button>
                </form>
            </div>
        </div>

        <!-- Import Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-indigo-50/40 dark:bg-indigo-500/5 rounded-xl p-4 border border-indigo-100 dark:border-indigo-500/10 flex items-center gap-3">
                <div class="p-2 rounded-lg bg-indigo-500/15 text-indigo-500 flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">pending_actions</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Ready to Import</p>
                    <p class="text-xl font-bold text-indigo-650 dark:text-indigo-400 mt-0.5">{{ $stats['ready'] }}</p>
                </div>
            </div>
            <div class="bg-green-50/40 dark:bg-green-500/5 rounded-xl p-4 border border-green-100 dark:border-green-500/10 flex items-center gap-3">
                <div class="p-2 rounded-lg bg-green-500/15 text-green-500 flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">done_all</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Successfully Imported</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-0.5">{{ $stats['imported'] }}</p>
                </div>
            </div>
            <div class="bg-amber-50/40 dark:bg-amber-500/5 rounded-xl p-4 border border-amber-100 dark:border-amber-500/10 flex items-center gap-3">
                <div class="p-2 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">content_copy</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Duplicates Ignored</p>
                    <p class="text-xl font-bold text-amber-600 dark:text-amber-450 mt-0.5">{{ $stats['duplicate'] }}</p>
                </div>
            </div>
            <div class="bg-red-50/40 dark:bg-red-500/5 rounded-xl p-4 border border-red-100 dark:border-red-500/10 flex items-center gap-3">
                <div class="p-2 rounded-lg bg-red-500/15 text-red-500 flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">error_med</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Parsing/Import Failed</p>
                    <p class="text-xl font-bold text-red-600 dark:text-red-400 mt-0.5">{{ $stats['failed'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Local Inbox Cache Table -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <!-- Table Filter / Search Header -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Local Messages List</h3>
            <form action="{{ route('gmail-inbox.index') }}" method="GET" class="w-full sm:max-w-xs flex gap-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search cached subject, body, sender..."
                    class="w-full text-xs rounded-lg bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-white focus:ring-primary focus:border-primary p-2">
                <button type="submit" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-250 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-750 flex items-center justify-center">
                    <span class="material-symbols-outlined text-sm">search</span>
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <form id="importSelectedForm" action="{{ route('gmail-inbox.import-selected') }}" method="POST">
                @csrf
                <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                    <thead class="text-xs uppercase bg-slate-50 dark:bg-slate-800/40 text-slate-450 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="px-6 py-4 font-semibold text-center w-12">
                                <input type="checkbox" id="selectAllCheckbox" class="rounded border-slate-300 dark:border-slate-700 text-primary focus:ring-primary size-4" onclick="toggleSelectAll(this)">
                            </th>
                            <th class="px-6 py-4 font-semibold">Date</th>
                            <th class="px-6 py-4 font-semibold">Connector</th>
                            <th class="px-6 py-4 font-semibold">Subject</th>
                            <th class="px-6 py-4 font-semibold">Sender</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold">Labels</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                        @forelse($messages as $msg)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 cursor-pointer transition-colors" onclick="openMessageDrawer({{ $msg->id }})">
                                <td class="px-6 py-4 text-center w-12" onclick="event.stopPropagation()">
                                    <input type="checkbox" name="selected_messages[]" value="{{ $msg->id }}" class="message-checkbox rounded border-slate-300 dark:border-slate-700 text-primary focus:ring-primary size-4" {{ $msg->status === 'READY' ? '' : 'disabled' }}>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-650 dark:text-slate-350">
                                    {{ $msg->internal_date ? \Carbon\Carbon::createFromTimestampMs($msg->internal_date)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s') : $msg->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs">
                                    <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ $msg->connector }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white max-w-xs truncate">
                                    {{ $msg->subject ?: '(No Subject)' }}
                                </td>
                                <td class="px-6 py-4 text-xs max-w-[160px] truncate text-slate-650 dark:text-slate-350">
                                    {{ $msg->from }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs">
                                    @php
                                        $statusColors = [
                                            'NEW' => 'bg-blue-500/10 text-blue-500 border-blue-500/20',
                                            'SYNCED' => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
                                            'PARSED' => 'bg-indigo-500/10 text-indigo-500 border-indigo-500/20',
                                            'READY' => 'bg-purple-500/10 text-purple-500 border-purple-500/20',
                                            'IMPORTED' => 'bg-green-500/10 text-green-500 border-green-500/20',
                                            'FAILED' => 'bg-red-500/10 text-red-500 border-red-500/20',
                                            'IGNORED' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                                            'ARCHIVED' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
                                        ];
                                        $statusColor = $statusColors[$msg->status] ?? 'bg-slate-500/10 text-slate-450 border-slate-500/20';
                                    @endphp
                                    <span class="inline-flex px-2.5 py-0.5 text-[10px] font-bold rounded-full border {{ $statusColor }}">
                                        {{ $msg->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 max-w-[200px] truncate">
                                    @if(!empty($msg->labels))
                                        <div class="flex gap-1 overflow-hidden max-w-full">
                                            @foreach(array_slice($msg->labels, 0, 3) as $lbl)
                                                <span class="inline-flex px-1.5 py-0.5 text-[9px] font-medium rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                                    {{ $lbl }}
                                                </span>
                                            @endforeach
                                            @if(count($msg->labels) > 3)
                                                <span class="inline-flex px-1.5 py-0.5 text-[9px] font-medium rounded bg-slate-100 dark:bg-slate-800 text-slate-400">
                                                    +{{ count($msg->labels) - 3 }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                    <span class="material-symbols-outlined text-4xl text-slate-350 dark:text-slate-600 mb-2">inbox</span>
                                    <p class="text-sm font-semibold">No Cached Messages Found</p>
                                    <p class="text-xs mt-1 text-slate-400">Sync with Gmail to download and view messages here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($messages->hasPages())
            <div class="p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/10">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ========================================================================= -->
<!-- Slide-out message viewer drawer -->
<!-- ========================================================================= -->
<div id="msgDrawer" class="fixed inset-y-0 right-0 z-50 w-full max-w-3xl bg-white dark:bg-card-dark border-l border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300 ease-in-out">
    <!-- Header -->
    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white" id="drawerSubject">(Loading Subject...)</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" id="drawerFrom"></p>
        </div>
        <button onclick="closeMessageDrawer()" class="p-2 text-slate-450 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors flex items-center justify-center">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <!-- Drawer Navigation Tabs -->
    <div class="px-6 border-b border-slate-100 dark:border-slate-850 flex gap-4 text-xs font-semibold text-slate-400">
        <button id="tabBtnHeaders" onclick="switchDrawerTab('headers')" class="py-3 border-b-2 border-primary text-primary">Headers</button>
        <button id="tabBtnHtml" onclick="switchDrawerTab('html')" class="py-3 border-b-2 border-transparent hover:text-slate-600 dark:hover:text-white">HTML Preview</button>
        <button id="tabBtnPlainText" onclick="switchDrawerTab('plain')" class="py-3 border-b-2 border-transparent hover:text-slate-600 dark:hover:text-white">Plain Text</button>
        <button id="tabBtnMime" onclick="switchDrawerTab('mime')" class="py-3 border-b-2 border-transparent hover:text-slate-600 dark:hover:text-white">Raw MIME</button>
        <button id="tabBtnMetadata" onclick="switchDrawerTab('metadata')" class="py-3 border-b-2 border-transparent hover:text-slate-600 dark:hover:text-white">Cache Metadata</button>
    </div>

    <!-- Drawer Content Area -->
    <div class="flex-1 overflow-y-auto p-6">
        <!-- Loading State -->
        <div id="drawerLoader" class="flex flex-col items-center justify-center h-64 text-slate-500 dark:text-slate-400">
            <div class="size-8 rounded-full border-4 border-slate-200 dark:border-slate-800 border-t-primary animate-spin mb-4"></div>
            <p class="text-sm font-medium">Fetching details from local database...</p>
        </div>

        <!-- 1. Headers Panel -->
        <div id="tabPanelHeaders" class="space-y-4 hidden">
            <div class="grid grid-cols-6 gap-2 text-xs py-2 border-b border-slate-100 dark:border-slate-800/80">
                <span class="col-span-1 text-slate-400 font-semibold uppercase">Subject:</span>
                <span class="col-span-5 font-medium text-slate-900 dark:text-white" id="headerValSubject"></span>
            </div>
            <div class="grid grid-cols-6 gap-2 text-xs py-2 border-b border-slate-100 dark:border-slate-800/80">
                <span class="col-span-1 text-slate-400 font-semibold uppercase">From:</span>
                <span class="col-span-5 font-medium text-slate-900 dark:text-white" id="headerValFrom"></span>
            </div>
            <div class="grid grid-cols-6 gap-2 text-xs py-2 border-b border-slate-100 dark:border-slate-800/80">
                <span class="col-span-1 text-slate-400 font-semibold uppercase">To:</span>
                <span class="col-span-5 font-medium text-slate-900 dark:text-white" id="headerValTo"></span>
            </div>
            <div class="grid grid-cols-6 gap-2 text-xs py-2 border-b border-slate-100 dark:border-slate-800/80">
                <span class="col-span-1 text-slate-400 font-semibold uppercase">Date:</span>
                <span class="col-span-5 font-medium text-slate-900 dark:text-white" id="headerValDate"></span>
            </div>
            <div class="pt-2">
                <span class="block text-xs font-semibold text-slate-400 uppercase mb-2">Snippet:</span>
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs leading-relaxed text-slate-755 dark:text-slate-300" id="headerValSnippet"></div>
            </div>
        </div>

        <!-- 2. HTML Panel (Sandboxed) -->
        <div id="tabPanelHtml" class="h-full min-h-[400px] hidden">
            <iframe id="htmlSandboxFrame" sandboxed="allow-same-origin" class="w-full h-full min-h-[400px] border border-slate-200 dark:border-slate-800 rounded-xl bg-white"></iframe>
        </div>

        <!-- 3. Plain Text Panel -->
        <div id="tabPanelPlainText" class="hidden">
            <pre id="plainTextContainer" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-xs font-mono text-slate-700 dark:text-slate-350 overflow-x-auto whitespace-pre-wrap leading-relaxed"></pre>
        </div>

        <!-- 4. Raw MIME (JSON) Panel -->
        <div id="tabPanelMime" class="hidden">
            <pre id="mimeJsonContainer" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-xs font-mono text-indigo-650 dark:text-indigo-400 overflow-x-auto whitespace-pre leading-relaxed"></pre>
        </div>

        <!-- 5. Cache Metadata Panel -->
        <div id="tabPanelMetadata" class="space-y-4 hidden">
            <div class="grid grid-cols-6 gap-2 text-xs py-2 border-b border-slate-100 dark:border-slate-800/80">
                <span class="col-span-2 text-slate-400 font-semibold uppercase">Google Msg ID:</span>
                <span class="col-span-4 font-mono text-slate-700 dark:text-slate-300" id="metaMsgId"></span>
            </div>
            <div class="grid grid-cols-6 gap-2 text-xs py-2 border-b border-slate-100 dark:border-slate-800/80">
                <span class="col-span-2 text-slate-400 font-semibold uppercase">Google Thread ID:</span>
                <span class="col-span-4 font-mono text-slate-700 dark:text-slate-300" id="metaThreadId"></span>
            </div>
            <div class="grid grid-cols-6 gap-2 text-xs py-2 border-b border-slate-100 dark:border-slate-800/80">
                <span class="col-span-2 text-slate-400 font-semibold uppercase">History ID:</span>
                <span class="col-span-4 font-mono text-slate-700 dark:text-slate-300" id="metaHistoryId"></span>
            </div>
            <div class="grid grid-cols-6 gap-2 text-xs py-2 border-b border-slate-100 dark:border-slate-800/80">
                <span class="col-span-2 text-slate-400 font-semibold uppercase">SHA256 Checksum:</span>
                <span class="col-span-4 font-mono text-slate-700 dark:text-slate-300 break-all" id="metaChecksum"></span>
            </div>
            <div class="grid grid-cols-6 gap-2 text-xs py-2 border-b border-slate-100 dark:border-slate-800/80">
                <span class="col-span-2 text-slate-400 font-semibold uppercase">Sync Status:</span>
                <span class="col-span-4 font-medium text-slate-700 dark:text-slate-300" id="metaStatus"></span>
            </div>
            <div class="grid grid-cols-6 gap-2 text-xs py-2 border-b border-slate-100 dark:border-slate-800/80">
                <span class="col-span-2 text-slate-400 font-semibold uppercase">Parsed At:</span>
                <span class="col-span-4 font-medium text-slate-700 dark:text-slate-300" id="metaParsedAt"></span>
            </div>
            <div class="pt-2">
                <span class="block text-xs font-semibold text-slate-400 uppercase mb-2">Label Badges:</span>
                <div class="flex flex-wrap gap-2" id="metaLabels"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // ─── Query Preset Setup ──────────────────────────────────────────────────
    window.setQueryPreset = function (presetQuery) {
        document.getElementById('query').value = presetQuery;
    }

    // ─── Sync Loading Controls ───────────────────────────────────────────────
    window.showSyncLoader = function () {
        const btn = document.getElementById('syncSubmitBtn');
        const icon = document.getElementById('syncBtnIcon');
        const text = document.getElementById('syncBtnText');

        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
        }
        if (icon) {
            icon.classList.add('animate-spin');
        }
        if (text) {
            text.innerText = 'Syncing... Please wait';
        }
    }

    // ─── Drawer Management ───────────────────────────────────────────────────
    let currentDrawerMessageData = null;

    window.openMessageDrawer = function (id) {
        const drawer = document.getElementById('msgDrawer');
        const loader = document.getElementById('drawerLoader');

        // Close any open panels
        hideDrawerPanels();
        loader.classList.remove('hidden');

        // Slide drawer in
        drawer.classList.remove('translate-x-full');

        // Fetch message details locally
        fetch(`/gmail-inbox/${id}`)
            .then(res => res.json())
            .then(payload => {
                if (payload.success) {
                    currentDrawerMessageData = payload.data;
                    renderDrawerMessage(payload.data);
                } else {
                    alert('Error: ' + payload.error);
                    closeMessageDrawer();
                }
            })
            .catch(err => {
                console.error(err);
                alert('An unexpected error occurred while fetching details.');
                closeMessageDrawer();
            });
    }

    window.closeMessageDrawer = function () {
        const drawer = document.getElementById('msgDrawer');
        drawer.classList.add('translate-x-full');
        currentDrawerMessageData = null;
    }

    function hideDrawerPanels() {
        document.getElementById('tabPanelHeaders').classList.add('hidden');
        document.getElementById('tabPanelHtml').classList.add('hidden');
        document.getElementById('tabPanelPlainText').classList.add('hidden');
        document.getElementById('tabPanelMime').classList.add('hidden');
        document.getElementById('tabPanelMetadata').classList.add('hidden');
    }

    function renderDrawerMessage(data) {
        // Set Header labels
        document.getElementById('drawerSubject').innerText = data.subject || '(No Subject)';
        document.getElementById('drawerFrom').innerText = `From: ${data.from}`;

        // Populate Headers Panel
        document.getElementById('headerValSubject').innerText = data.subject || '(No Subject)';
        document.getElementById('headerValFrom').innerText = data.from || '';
        document.getElementById('headerValTo').innerText = data.to || '';
        document.getElementById('headerValDate').innerText = data.internalDate 
            ? new Date(parseInt(data.internalDate)).toLocaleString('id-ID')
            : 'Unknown';
        document.getElementById('headerValSnippet').innerText = data.snippet || '(No snippet available)';

        // Populate HTML Frame Safely
        const iframe = document.getElementById('htmlSandboxFrame');
        if (data.html) {
            iframe.srcdoc = data.html;
        } else {
            iframe.srcdoc = `<div style="font-family: sans-serif; font-size: 13px; color: #64748b; padding: 20px;">No HTML representation available.</div>`;
        }

        // Populate Plain Text Box
        document.getElementById('plainTextContainer').innerText = data.plainText || '(No plain text content available)';

        // Populate Raw MIME Json
        document.getElementById('mimeJsonContainer').innerText = JSON.stringify(data.rawPayload, null, 2);

        // Populate Metadata
        document.getElementById('metaMsgId').innerText = data.id || '';
        document.getElementById('metaThreadId').innerText = data.threadId || '';
        document.getElementById('metaHistoryId').innerText = data.historyId || '';
        document.getElementById('metaChecksum').innerText = data.checksum || 'N/A';
        document.getElementById('metaStatus').innerText = data.status || 'NEW';
        document.getElementById('metaParsedAt').innerText = data.parsedAt 
            ? new Date(data.parsedAt).toLocaleString('id-ID')
            : 'Not Parsed';

        const labelsDiv = document.getElementById('metaLabels');
        labelsDiv.innerHTML = '';
        if (data.labels && data.labels.length > 0) {
            data.labels.forEach(lbl => {
                const span = document.createElement('span');
                span.className = 'inline-flex px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700';
                span.innerText = lbl;
                labelsDiv.appendChild(span);
            });
        } else {
            labelsDiv.innerHTML = '<span class="text-xs text-slate-450">-</span>';
        }

        // Hide Loader, Display Headers panel
        document.getElementById('drawerLoader').classList.add('hidden');
        switchDrawerTab('headers');
    }

    window.switchDrawerTab = function (tabName) {
        hideDrawerPanels();

        // Remove active class borders from all tab buttons
        document.getElementById('tabBtnHeaders').className = 'py-3 border-b-2 border-transparent hover:text-slate-600 dark:hover:text-white';
        document.getElementById('tabBtnHtml').className = 'py-3 border-b-2 border-transparent hover:text-slate-600 dark:hover:text-white';
        document.getElementById('tabBtnPlainText').className = 'py-3 border-b-2 border-transparent hover:text-slate-600 dark:hover:text-white';
        document.getElementById('tabBtnMime').className = 'py-3 border-b-2 border-transparent hover:text-slate-600 dark:hover:text-white';
        document.getElementById('tabBtnMetadata').className = 'py-3 border-b-2 border-transparent hover:text-slate-600 dark:hover:text-white';

        if (tabName === 'headers') {
            document.getElementById('tabPanelHeaders').classList.remove('hidden');
            document.getElementById('tabBtnHeaders').className = 'py-3 border-b-2 border-primary text-primary';
        } else if (tabName === 'html') {
            document.getElementById('tabPanelHtml').classList.remove('hidden');
            document.getElementById('tabBtnHtml').className = 'py-3 border-b-2 border-primary text-primary';
        } else if (tabName === 'plain') {
            document.getElementById('tabPanelPlainText').classList.remove('hidden');
            document.getElementById('tabBtnPlainText').className = 'py-3 border-b-2 border-primary text-primary';
        } else if (tabName === 'mime') {
            document.getElementById('tabPanelMime').classList.remove('hidden');
            document.getElementById('tabBtnMime').className = 'py-3 border-b-2 border-primary text-primary';
        } else if (tabName === 'metadata') {
            document.getElementById('tabPanelMetadata').classList.remove('hidden');
            document.getElementById('tabBtnMetadata').className = 'py-3 border-b-2 border-primary text-primary';
        }
    }

    // ─── Bulk Import Actions ──────────────────────────────────────────────────
    window.toggleSelectAll = function (master) {
        const checkboxes = document.querySelectorAll('.message-checkbox:not(:disabled)');
        checkboxes.forEach(cb => {
            cb.checked = master.checked;
        });
    }

    window.submitImportSelected = function () {
        const checkboxes = document.querySelectorAll('.message-checkbox:checked');
        if (checkboxes.length === 0) {
            alert('Please select at least one READY message to import.');
            return;
        }
        document.getElementById('importSelectedForm').submit();
    }
</script>
@endsection
