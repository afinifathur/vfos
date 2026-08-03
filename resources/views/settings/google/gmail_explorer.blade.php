@extends('layouts.app')

@section('title', 'Gmail Explorer')

@section('content')
<div class="max-w-6xl mx-auto w-full space-y-6">

    {{-- Breadcrumbs & Status Indicator --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('settings.index') }}#google" class="flex items-center gap-1.5 text-sm text-slate-500 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Back to Settings
            </a>
            <span class="text-slate-400">/</span>
            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Gmail Explorer</span>
        </div>
        <div class="flex items-center gap-2 text-xs bg-green-500/10 text-green-400 border border-green-500/20 px-3 py-1.5 rounded-full font-medium">
            <span class="size-1.5 rounded-full bg-green-500"></span>
            Connected: {{ $status['google_email'] }}
        </div>
    </div>

    {{-- Search & Settings Panel --}}
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
        <h3 class="text-xl font-bold mb-2">Gmail Message Explorer</h3>
        <p class="text-sm text-slate-500">Retrieve, inspect, and preview your Gmail messages using query search filters.</p>
        
        {{-- Preset Query Buttons --}}
        <div class="mt-6">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Quick Search Presets</p>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="applyPreset('newer_than:30d from:(bca@bca.co.id OR cba@bca.co.id)')"
                    class="px-3.5 py-1.5 text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-primary hover:text-white rounded-lg transition-all text-slate-600 dark:text-slate-300">
                    🔍 Recent BCA
                </button>
                <button type="button" onclick="applyPreset('newer_than:30d from:noreply.livin@bankmandiri.co.id')"
                    class="px-3.5 py-1.5 text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-primary hover:text-white rounded-lg transition-all text-slate-600 dark:text-slate-300">
                    🔍 Recent Mandiri
                </button>
                <button type="button" onclick="applyPreset('newer_than:30d from:bnicall@bni.co.id')"
                    class="px-3.5 py-1.5 text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-primary hover:text-white rounded-lg transition-all text-slate-600 dark:text-slate-300">
                    🔍 Recent BNI
                </button>
                <button type="button" onclick="applyPreset('newer_than:7d')"
                    class="px-3.5 py-1.5 text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-primary hover:text-white rounded-lg transition-all text-slate-600 dark:text-slate-300">
                    📅 Last 7 Days
                </button>
                <button type="button" onclick="applyPreset('newer_than:30d')"
                    class="px-3.5 py-1.5 text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-primary hover:text-white rounded-lg transition-all text-slate-600 dark:text-slate-300">
                    📅 Last 30 Days
                </button>
                <button type="button" onclick="applyPreset('is:unread')"
                    class="px-3.5 py-1.5 text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-primary hover:text-white rounded-lg transition-all text-slate-600 dark:text-slate-300">
                    ✉️ Unread
                </button>
            </div>
        </div>

        {{-- Search Input Form --}}
        <form action="{{ route('settings.google.gmail-explorer') }}" method="GET" class="mt-6 space-y-4">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 space-y-1.5">
                    <label for="q" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Gmail Search Query</label>
                    <input type="text" name="q" id="q" value="{{ $query }}"
                        placeholder="e.g. after:2026/06/30 before:2026/08/01 from:bca@bca.co.id"
                        class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div class="w-full md:w-32 space-y-1.5">
                    <label for="max_results" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Max Results</label>
                    <input type="number" name="max_results" id="max_results" value="{{ $maxResults }}" min="1" max="100"
                        class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <button type="submit"
                        class="w-full md:w-auto px-6 py-2.5 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg">search</span>
                        Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Error Banner --}}
    @if($error)
        <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-400 text-sm font-medium flex items-center gap-3">
            <span class="material-symbols-outlined text-[18px]">error</span>
            {{ $error }}
        </div>
    @endif

    {{-- Results Table Panel --}}
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm overflow-hidden">
        <h4 class="text-md font-bold mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-slate-400">mail</span>
            Search Results ({{ count($messages) }})
        </h4>

        @if(count($messages) === 0)
            <div class="py-12 text-center text-slate-500 dark:text-slate-400">
                <span class="material-symbols-outlined text-4xl mb-2 text-slate-300 dark:text-slate-600">inbox</span>
                <p class="text-sm">No messages loaded. Perform a query search above.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                            <th class="py-3 px-4">Message ID</th>
                            <th class="py-3 px-4">Thread ID</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">From</th>
                            <th class="py-3 px-4">Subject</th>
                            <th class="py-3 px-4">Snippet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 text-xs">
                        @foreach($messages as $msg)
                            <tr onclick="openMessage('{{ $msg['id'] }}')" 
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/40 cursor-pointer transition-colors group">
                                <td class="py-3.5 px-4 font-mono text-[10px] text-slate-400 select-all group-hover:text-primary">
                                    {{ $msg['id'] }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[10px] text-slate-400 select-all">
                                    {{ $msg['threadId'] }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                    @try
                                        {{ \Carbon\Carbon::createFromTimestampMs($msg['internalDate'])->format('d M Y H:i:s') }}
                                    @catch(\Exception $e)
                                        {{ $msg['date'] }}
                                    @endtry
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-800 dark:text-slate-200 whitespace-nowrap max-w-[150px] truncate">
                                    {{ $msg['from'] }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white max-w-[200px] truncate">
                                    {{ $msg['subject'] }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 max-w-[250px] truncate">
                                    {{ $msg['snippet'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Message Viewer Side Drawer (Slides out from right) --}}
<div id="messageDrawer" class="fixed inset-y-0 right-0 z-50 w-full max-w-3xl bg-slate-900 border-l border-slate-700 shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col">
    
    {{-- Drawer Header --}}
    <div class="p-6 border-b border-slate-700 flex items-center justify-between bg-card-dark">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-primary text-xl">mail</span>
            <div>
                <h3 class="text-sm font-bold text-white leading-none">Inspect Message</h3>
                <p class="text-[10px] font-mono text-slate-400 mt-1 select-all" id="drawerMsgId"></p>
            </div>
        </div>
        <button onclick="closeDrawer()" class="size-8 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    {{-- Tab Selector --}}
    <div class="border-b border-slate-700 bg-slate-900/80 flex">
        <button onclick="switchViewerTab('headers')" id="btn-tab-headers" class="tab-viewer-btn px-6 py-3 text-xs font-semibold text-primary border-b-2 border-primary">
            Headers
        </button>
        <button onclick="switchViewerTab('html')" id="btn-tab-html" class="tab-viewer-btn px-6 py-3 text-xs font-semibold text-slate-400 hover:text-white">
            HTML Preview
        </button>
        <button onclick="switchViewerTab('plain')" id="btn-tab-plain" class="tab-viewer-btn px-6 py-3 text-xs font-semibold text-slate-400 hover:text-white">
            Plain Text
        </button>
        <button onclick="switchViewerTab('rawMime')" id="btn-tab-rawMime" class="tab-viewer-btn px-6 py-3 text-xs font-semibold text-slate-400 hover:text-white">
            Raw MIME
        </button>
        <button onclick="switchViewerTab('metadata')" id="btn-tab-metadata" class="tab-viewer-btn px-6 py-3 text-xs font-semibold text-slate-400 hover:text-white">
            Metadata
        </button>
    </div>

    {{-- Loading Spinner --}}
    <div id="drawerLoader" class="flex-1 flex flex-col items-center justify-center text-slate-400 gap-3">
        <svg class="animate-spin size-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm font-medium">Fetching email payload...</span>
    </div>

    {{-- Drawer Scrollable Content --}}
    <div id="drawerContent" class="flex-1 overflow-y-auto p-6 hidden space-y-6">
        
        {{-- Section: Headers --}}
        <div id="section-headers" class="tab-viewer-content space-y-4">
            <div class="bg-card-dark p-5 rounded-xl border border-slate-700 space-y-3">
                <div class="grid grid-cols-6 gap-2">
                    <span class="col-span-1 text-[10px] uppercase font-bold text-slate-400">Subject</span>
                    <span class="col-span-5 text-sm font-semibold text-white" id="headerSubject"></span>
                </div>
                <div class="grid grid-cols-6 gap-2">
                    <span class="col-span-1 text-[10px] uppercase font-bold text-slate-400">From</span>
                    <span class="col-span-5 text-sm text-slate-300" id="headerFrom"></span>
                </div>
                <div class="grid grid-cols-6 gap-2">
                    <span class="col-span-1 text-[10px] uppercase font-bold text-slate-400">To</span>
                    <span class="col-span-5 text-sm text-slate-300" id="headerTo"></span>
                </div>
                <div class="grid grid-cols-6 gap-2">
                    <span class="col-span-1 text-[10px] uppercase font-bold text-slate-400">Date</span>
                    <span class="col-span-5 text-sm text-slate-300" id="headerDate"></span>
                </div>
            </div>
            
            <div class="bg-card-dark p-5 rounded-xl border border-slate-700">
                <span class="text-[10px] uppercase font-bold text-slate-400 mb-2 block">Snippet</span>
                <p class="text-sm text-slate-300 italic" id="messageSnippet"></p>
            </div>
        </div>

        {{-- Section: HTML Preview --}}
        <div id="section-html" class="tab-viewer-content hidden h-full flex flex-col">
            <div id="noHtmlAlert" class="p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-xl text-yellow-400 text-xs font-semibold mb-4 hidden">
                ⚠️ No HTML version of this message is available.
            </div>
            <div class="flex-1 min-h-[400px] bg-white rounded-xl overflow-hidden border border-slate-700">
                <iframe id="htmlIframe" class="w-full h-full border-0 bg-white" sandbox></iframe>
            </div>
        </div>

        {{-- Section: Plain Text Preview --}}
        <div id="section-plain" class="tab-viewer-content hidden">
            <div id="noPlainAlert" class="p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-xl text-yellow-400 text-xs font-semibold mb-4 hidden">
                ⚠️ No plain text version of this message is available.
            </div>
            <pre id="plainTextBlock" class="p-5 bg-card-dark text-slate-300 text-xs font-mono rounded-xl border border-slate-700 whitespace-pre-wrap overflow-x-auto max-h-[500px] overflow-y-auto"></pre>
        </div>

        {{-- Section: Raw MIME structure --}}
        <div id="section-rawMime" class="tab-viewer-content hidden">
            <div class="p-4 bg-slate-800 rounded-xl border border-slate-700">
                <pre id="rawMimeBlock" class="text-[11px] text-emerald-400 font-mono overflow-x-auto max-h-[500px] overflow-y-auto"></pre>
            </div>
        </div>

        {{-- Section: Metadata --}}
        <div id="section-metadata" class="tab-viewer-content hidden">
            <div class="bg-card-dark p-5 rounded-xl border border-slate-700 divide-y divide-slate-800">
                <div class="py-2.5 flex justify-between">
                    <span class="text-xs text-slate-400">Message ID</span>
                    <span class="text-xs font-mono text-white select-all" id="metaMsgId"></span>
                </div>
                <div class="py-2.5 flex justify-between">
                    <span class="text-xs text-slate-400">Thread ID</span>
                    <span class="text-xs font-mono text-white select-all" id="metaThreadId"></span>
                </div>
                <div class="py-2.5 flex justify-between">
                    <span class="text-xs text-slate-400">Internal Date</span>
                    <span class="text-xs font-mono text-white" id="metaInternalDate"></span>
                </div>
                <div class="py-2.5 flex justify-between">
                    <span class="text-xs text-slate-400">History ID</span>
                    <span class="text-xs font-mono text-white" id="metaHistoryId"></span>
                </div>
                <div class="py-2.5 flex justify-between flex-col gap-2">
                    <span class="text-xs text-slate-400">Label IDs</span>
                    <div class="flex flex-wrap gap-1.5" id="metaLabels"></div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    function applyPreset(query) {
        document.getElementById('q').value = query;
    }

    let activeMessageData = null;

    function openMessage(id) {
        const drawer = document.getElementById('messageDrawer');
        const loader = document.getElementById('drawerLoader');
        const content = document.getElementById('drawerContent');
        const msgIdEl = document.getElementById('drawerMsgId');

        // Reset display and open drawer
        msgIdEl.textContent = id;
        drawer.classList.remove('translate-x-full');
        loader.classList.remove('hidden');
        content.classList.add('hidden');

        // Fetch detail payload
        fetch(`/settings/google/gmail-explorer/message/${id}`)
            .then(res => {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(json => {
                if (!json.success) {
                    alert(json.message || 'Gagal memuat email.');
                    closeDrawer();
                    return;
                }

                activeMessageData = json.data;
                populateDrawer(json.data);

                loader.classList.add('hidden');
                content.classList.remove('hidden');
            })
            .catch(err => {
                alert('Gagal mengambil data email dari server.');
                closeDrawer();
            });
    }

    function populateDrawer(data) {
        // Headers tab
        document.getElementById('headerSubject').textContent = data.subject;
        document.getElementById('headerFrom').textContent = data.from;
        document.getElementById('headerTo').textContent = data.to;
        document.getElementById('headerDate').textContent = data.date;
        document.getElementById('messageSnippet').textContent = data.snippet;

        // HTML tab
        const iframe = document.getElementById('htmlIframe');
        const noHtmlAlert = document.getElementById('noHtmlAlert');
        if (data.html) {
            noHtmlAlert.classList.add('hidden');
            iframe.parentElement.classList.remove('hidden');
            iframe.srcdoc = data.html;
        } else {
            noHtmlAlert.classList.remove('hidden');
            iframe.parentElement.classList.add('hidden');
            iframe.srcdoc = '';
        }

        // Plain text tab
        const plainBlock = document.getElementById('plainTextBlock');
        const noPlainAlert = document.getElementById('noPlainAlert');
        if (data.plainText) {
            noPlainAlert.classList.add('hidden');
            plainBlock.classList.remove('hidden');
            plainBlock.textContent = data.plainText;
        } else {
            noPlainAlert.classList.remove('hidden');
            plainBlock.classList.add('hidden');
            plainBlock.textContent = '';
        }

        // Raw MIME tab
        document.getElementById('rawMimeBlock').textContent = JSON.stringify(data.rawMime, null, 2);

        // Metadata tab
        document.getElementById('metaMsgId').textContent = data.id;
        document.getElementById('metaThreadId').textContent = data.threadId;
        document.getElementById('metaInternalDate').textContent = data.internalDate + ' (' + new Date(parseInt(data.internalDate)).toLocaleString() + ')';
        document.getElementById('metaHistoryId').textContent = data.historyId;
        
        const labelsContainer = document.getElementById('metaLabels');
        labelsContainer.innerHTML = '';
        if (data.labelIds && data.labelIds.length) {
            data.labelIds.forEach(lbl => {
                const span = document.createElement('span');
                span.className = 'px-2 py-0.5 text-[10px] font-mono font-bold bg-slate-800 text-slate-300 rounded';
                span.textContent = lbl;
                labelsContainer.appendChild(span);
            });
        }

        // Default tab is headers
        switchViewerTab('headers');
    }

    function closeDrawer() {
        document.getElementById('messageDrawer').classList.add('translate-x-full');
    }

    function switchViewerTab(tabId) {
        // Handle button styles
        document.querySelectorAll('.tab-viewer-btn').forEach(btn => {
            btn.className = 'tab-viewer-btn px-6 py-3 text-xs font-semibold text-slate-400 hover:text-white';
        });
        const activeBtn = document.getElementById('btn-tab-' + tabId);
        if (activeBtn) {
            activeBtn.className = 'tab-viewer-btn px-6 py-3 text-xs font-semibold text-primary border-b-2 border-primary';
        }

        // Handle section visibility
        document.querySelectorAll('.tab-viewer-content').forEach(sect => {
            sect.classList.add('hidden');
        });
        const activeSect = document.getElementById('section-' + tabId);
        if (activeSect) {
            activeSect.classList.remove('hidden');
        }
    }
</script>
@endpush
