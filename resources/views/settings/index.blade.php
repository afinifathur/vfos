@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="max-w-6xl mx-auto w-full">
    <div class="flex flex-col lg:flex-row gap-10">

        {{-- Vertical Tab Navigation --}}
        <aside class="lg:w-64 flex-shrink-0">
            <nav class="flex flex-col gap-1">
                @php
                $tabs = [
                    ['id' => 'profile',       'icon' => 'person',               'label' => 'Profile'],
                    ['id' => 'preferences',   'icon' => 'tune',                 'label' => 'Preferences'],
                    ['id' => 'security',      'icon' => 'security',             'label' => 'Security'],
                    ['id' => 'integrations',  'icon' => 'api',                  'label' => 'API Integrations'],
                    ['id' => 'notifications', 'icon' => 'notifications_active', 'label' => 'Notifications'],
                ];
                @endphp
                @foreach($tabs as $tab)
                <button
                    onclick="switchTab('{{ $tab['id'] }}')"
                    id="tab-btn-{{ $tab['id'] }}"
                    class="tab-btn flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-colors
                        {{ $loop->first
                            ? 'bg-primary/10 text-primary font-semibold'
                            : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark' }}">
                    <span class="material-symbols-outlined">{{ $tab['icon'] }}</span>
                    {{ $tab['label'] }}
                </button>
                @endforeach
            </nav>
        </aside>

        {{-- Settings Content --}}
        <div class="flex-1 space-y-6">

            {{-- ── PROFILE TAB ────────────────────────────────────── --}}
            <div id="tab-profile" class="tab-content space-y-6">
                <form action="{{ route('settings.profile') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <section class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6">
                        <h3 class="text-lg font-bold mb-6">Profile Information</h3>
                        <div class="flex flex-col md:flex-row gap-8 items-start mb-6">
                            {{-- Avatar --}}
                            <div class="relative group flex-shrink-0">
                                <div class="size-24 rounded-full overflow-hidden border-4 border-slate-100 dark:border-slate-800 bg-slate-200">
                                    <img id="avatar-preview" class="w-full h-full object-cover" alt="Profile photo"
                                        src="{{ session('profile_photo', 'https://ui-avatars.com/api/?name=User&background=3c83f6&color=fff&size=96') }}">
                                </div>
                                <label for="photo-upload" class="absolute bottom-0 right-0 size-8 bg-primary text-white rounded-full flex items-center justify-center border-4 border-white dark:border-card-dark shadow-lg cursor-pointer hover:bg-primary/90 transition">
                                    <span class="material-symbols-outlined text-sm">photo_camera</span>
                                </label>
                                <input type="file" name="photo" id="photo-upload" class="hidden" accept="image/*">
                            </div>
                            {{-- Fields --}}
                            <div class="flex-1 w-full space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Full Name</label>
                                        <input type="text" name="name" value="{{ session('user_name', 'User') }}"
                                            class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 focus:ring-primary focus:border-primary">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Email Address</label>
                                        <input type="email" name="email" value="{{ session('user_email', 'user@vfos.com') }}"
                                            class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 focus:ring-primary focus:border-primary">
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Bio</label>
                                    <textarea name="bio" rows="2"
                                        class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 focus:ring-primary focus:border-primary"
                                        placeholder="Enter a brief description...">{{ session('user_bio', '') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                            <button type="reset" class="px-5 py-2 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 transition-colors">Discard</button>
                            <button type="submit" class="px-8 py-2.5 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all">Save Profile</button>
                        </div>
                    </section>
                </form>
            </div>

            {{-- ── PREFERENCES TAB ────────────────────────────────── --}}
            <div id="tab-preferences" class="tab-content hidden space-y-6">
                <form action="{{ route('settings.preferences') }}" method="POST">
                    @csrf
                    <section class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6">
                        <h3 class="text-lg font-bold mb-6">Preferences</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Primary Currency</label>
                                <select name="currency" class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 focus:ring-primary focus:border-primary">
                                    <option value="IDR" {{ session('pref_currency', 'IDR') === 'IDR' ? 'selected' : '' }}>IDR - Indonesian Rupiah</option>
                                    <option value="USD" {{ session('pref_currency', 'IDR') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                    <option value="EUR" {{ session('pref_currency', 'IDR') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                    <option value="SGD" {{ session('pref_currency', 'IDR') === 'SGD' ? 'selected' : '' }}>SGD - Singapore Dollar</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Display Language</label>
                                <select name="language" class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 focus:ring-primary focus:border-primary">
                                    <option value="en" {{ session('pref_language', 'en') === 'en' ? 'selected' : '' }}>English (US)</option>
                                    <option value="id" {{ session('pref_language', 'en') === 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Date Format</label>
                                <select name="date_format" class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 focus:ring-primary focus:border-primary">
                                    <option value="d/m/Y">DD/MM/YYYY (31/12/2025)</option>
                                    <option value="m/d/Y">MM/DD/YYYY (12/31/2025)</option>
                                    <option value="Y-m-d">YYYY-MM-DD (2025-12-31)</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Appearance</label>
                                <div class="flex items-center gap-2 bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-slate-700 p-1 rounded-lg">
                                    <button type="button" onclick="setTheme('dark')" id="theme-dark"
                                        class="flex-1 flex items-center justify-center gap-2 py-1.5 rounded text-sm bg-white dark:bg-card-dark shadow-sm font-medium transition-all">
                                        <span class="material-symbols-outlined text-sm">dark_mode</span> Dark
                                    </button>
                                    <button type="button" onclick="setTheme('light')" id="theme-light"
                                        class="flex-1 flex items-center justify-center gap-2 py-1.5 rounded text-sm text-slate-500 hover:text-slate-800 transition-colors font-medium">
                                        <span class="material-symbols-outlined text-sm">light_mode</span> Light
                                    </button>
                                </div>
                                <input type="hidden" name="theme" id="theme-input" value="{{ session('pref_theme', 'dark') }}">
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700 mt-6">
                            <button type="reset" class="px-5 py-2 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 transition-colors">Discard</button>
                            <button type="submit" class="px-8 py-2.5 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all">Save Preferences</button>
                        </div>
                    </section>
                </form>
            </div>

            {{-- ── SECURITY TAB ────────────────────────────────────── --}}
            <div id="tab-security" class="tab-content hidden space-y-6">
                <form action="{{ route('settings.security') }}" method="POST">
                    @csrf
                    <section class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6">
                        <h3 class="text-lg font-bold mb-6">Security</h3>
                        <div class="space-y-5 max-w-lg">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">PIN Akses Aplikasi</label>
                                <input type="password" name="pin" maxlength="6" placeholder="Masukkan 4-6 digit PIN baru"
                                    class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 focus:ring-primary focus:border-primary tracking-widest text-xl">
                                <p class="text-xs text-slate-500 dark:text-slate-400">PIN digunakan untuk membuka kunci halaman sensitif.</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Konfirmasi PIN</label>
                                <input type="password" name="pin_confirm" maxlength="6" placeholder="Ulangi PIN"
                                    class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 focus:ring-primary focus:border-primary tracking-widest text-xl">
                            </div>

                            <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-background-dark rounded-xl border border-slate-200 dark:border-slate-700">
                                    <div>
                                        <p class="font-medium text-sm">Auto-lock Screen</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Kunci otomatis setelah tidak aktif</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="auto_lock" class="sr-only peer" {{ session('pref_auto_lock', false) ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-slate-300 peer-focus:ring-2 peer-focus:ring-primary rounded-full peer peer-checked:bg-primary transition-all"></div>
                                        <div class="absolute left-0.5 top-0.5 bg-white w-5 h-5 rounded-full transition-all peer-checked:translate-x-5"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700 mt-6">
                            <button type="submit" class="px-8 py-2.5 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all">Save Security</button>
                        </div>
                    </section>
                </form>
            </div>

            {{-- ── API INTEGRATIONS TAB ────────────────────────────── --}}
            <div id="tab-integrations" class="tab-content hidden space-y-6">
                <section class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold">API Integrations</h3>
                            <p class="text-sm text-slate-500">Manage external financial data sources</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        {{-- Yahoo Finance --}}
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-background-dark/40 border border-slate-200 dark:border-slate-700 rounded-xl">
                            <div class="flex items-center gap-4">
                                <div class="size-10 bg-blue-500/10 text-blue-500 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined">trending_up</span>
                                </div>
                                <div>
                                    <p class="font-semibold">Yahoo Finance</p>
                                    <div class="flex items-center gap-1.5">
                                        <span class="size-1.5 rounded-full bg-green-500"></span>
                                        <p class="text-xs text-slate-500">Connected · Real-time data (Saham IDX & Komoditas)</p>
                                    </div>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-xs font-bold bg-green-500/10 text-green-500 rounded-lg">Aktif</span>
                        </div>
                        {{-- Kontan --}}
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-background-dark/40 border border-slate-200 dark:border-slate-700 rounded-xl">
                            <div class="flex items-center gap-4">
                                <div class="size-10 bg-emerald-500/10 text-emerald-500 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined">diamond</span>
                                </div>
                                <div>
                                    <p class="font-semibold">Kontan.co.id</p>
                                    <div class="flex items-center gap-1.5">
                                        <span class="size-1.5 rounded-full bg-green-500"></span>
                                        <p class="text-xs text-slate-500">Connected · NAB Reksa Dana via web scraping</p>
                                    </div>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-xs font-bold bg-green-500/10 text-green-500 rounded-lg">Aktif</span>
                        </div>
                        {{-- Bareksa --}}
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-background-dark/40 border border-slate-200 dark:border-slate-700 rounded-xl">
                            <div class="flex items-center gap-4">
                                <div class="size-10 bg-lime-500/10 text-lime-600 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined">analytics</span>
                                </div>
                                <div>
                                    <p class="font-semibold">Bareksa.com</p>
                                    <div class="flex items-center gap-1.5">
                                        <span class="size-1.5 rounded-full bg-green-500"></span>
                                        <p class="text-xs text-slate-500">Connected · Scraper NAB Reksa Dana</p>
                                    </div>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-xs font-bold bg-green-500/10 text-green-500 rounded-lg">Aktif</span>
                        </div>
                        {{-- Kemenangan Signature --}}
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-background-dark/40 border border-slate-200 dark:border-slate-700 rounded-xl">
                            <div class="flex items-center gap-4">
                                <div class="size-10 bg-amber-500/10 text-amber-500 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined">payments</span>
                                </div>
                                <div>
                                    <p class="font-semibold">Kemenangan Signature</p>
                                    <div class="flex items-center gap-1.5">
                                        <span class="size-1.5 rounded-full bg-green-500"></span>
                                        <p class="text-xs text-slate-500">Connected · Harga Buyback Emas (6K - 24K)</p>
                                    </div>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-xs font-bold bg-green-500/10 text-green-500 rounded-lg">Aktif</span>
                        </div>
                        {{-- Currency --}}
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-background-dark/40 border border-slate-200 dark:border-slate-700 rounded-xl">
                            <div class="flex items-center gap-4">
                                <div class="size-10 bg-yellow-500/10 text-yellow-500 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined">currency_exchange</span>
                                </div>
                                <div>
                                    <p class="font-semibold">ExchangeRate API</p>
                                    <div class="flex items-center gap-1.5">
                                        <span class="size-1.5 rounded-full bg-green-500"></span>
                                        <p class="text-xs text-slate-500">Connected · Kurs USD/IDR real-time (open.er-api.com)</p>
                                    </div>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-xs font-bold bg-green-500/10 text-green-500 rounded-lg">Aktif</span>
                        </div>
                        {{-- n8n Automation --}}
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-background-dark/40 border border-slate-200 dark:border-slate-700 rounded-xl">
                            <div class="flex items-center gap-4">
                                <div class="size-10 bg-orange-500/10 text-orange-500 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined">hub</span>
                                </div>
                                <div>
                                    <p class="font-semibold">n8n Automation</p>
                                    <div class="flex items-center gap-1.5">
                                        <span class="size-1.5 rounded-full bg-green-500"></span>
                                        <p class="text-xs text-slate-500">Connected · Inbound Transaction Webhook</p>
                                    </div>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-xs font-bold bg-green-500/10 text-green-500 rounded-lg">Aktif</span>
                        </div>
                        {{-- Refresh Now --}}
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                            <form action="{{ route('investments.refresh') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="flex items-center gap-2 px-5 py-2.5 bg-yellow-400/90 hover:bg-yellow-400 text-yellow-900 font-bold text-sm rounded-lg transition-all shadow-md shadow-yellow-400/20">
                                    <span class="material-symbols-outlined text-[18px]">refresh</span>
                                    Refresh Semua Harga Sekarang
                                </button>
                            </form>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Atau otomatis diperbarui setiap hari kerja pukul 09:05 dan 16:05.</p>
                        </div>
                    </div>
                </section>
                
                {{-- Sync DB Lokal - Server --}}
                <section class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6 mt-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold">Production Sync</h3>
                            <p class="text-sm text-slate-500">Tarik data terbaru dari Server Production (Veronica @ 192.168.1.50) ke Laragon Lokal</p>
                        </div>
                    </div>
                    
                    @if(session('sync_log'))
                        <div class="mb-4 bg-slate-900/50 p-4 rounded-xl border border-slate-700">
                            <p class="text-xs text-rose-400 font-black mb-2 uppercase tracking-widest">Error Logs:</p>
                            <pre class="text-[10px] text-slate-400 whitespace-pre-wrap max-h-40 overflow-y-auto">{{ session('sync_log') }}</pre>
                        </div>
                    @endif
                    @error('sync')
                        <div class="mb-4 text-xs font-bold text-rose-500 bg-rose-500/10 p-3 rounded-xl">{{ $message }}</div>
                    @enderror

                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-background-dark/40 border border-slate-200 dark:border-slate-700 rounded-xl relative overflow-hidden group">
                        <div class="absolute inset-0 bg-primary/5 translate-y-full group-hover:translate-y-0 transition-transform"></div>
                        <div class="relative z-10 flex items-center gap-4">
                            <div class="size-12 bg-primary/10 text-primary border border-primary/20 rounded-xl flex items-center justify-center shadow-inner">
                                <span class="material-symbols-outlined text-2xl">cloud_download</span>
                            </div>
                            <div>
                                <p class="font-bold text-slate-800 dark:text-white text-lg">Pound to Local Docks</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="material-symbols-outlined text-slate-400 text-sm">dns</span>
                                    <p class="text-xs font-medium text-slate-500">vfos-db (Prod) ➔ Laragon MySQL</p>
                                </div>
                            </div>
                        </div>
                        <div class="relative z-10">
                            <form action="{{ route('settings.sync') }}" method="POST" onsubmit="return confirm('Proses ini akan MENGHAPUS/TIMPA data lokal dengan data production terbaru. Jika kamu memiliki data lokal yang belum tersimpan ke server, data tersebut akan HILANG. Lanjutkan?')">
                                @csrf
                                <button type="submit" class="flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold text-sm rounded-xl transition-all shadow-lg shadow-primary/20 hover:scale-105 active:scale-95">
                                    <span class="material-symbols-outlined text-[18px]">sync</span>
                                    Sync Data
                                </button>
                            </form>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ── NOTIFICATIONS TAB ───────────────────────────────── --}}
            <div id="tab-notifications" class="tab-content hidden space-y-6">
                <form action="{{ route('settings.notifications') }}" method="POST">
                    @csrf
                    <section class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-6">
                        <h3 class="text-lg font-bold mb-6">Notifications</h3>
                        <div class="space-y-4">
                            @php
                            $notifOptions = [
                                ['key' => 'price_alert',     'label' => 'Price Alerts',           'desc' => 'Notifikasi saat harga aset berubah signifikan'],
                                ['key' => 'debt_reminder',   'label' => 'Debt Reminders',          'desc' => 'Pengingat jatuh tempo hutang/piutang'],
                                ['key' => 'budget_warning',  'label' => 'Budget Warnings',         'desc' => 'Peringatan saat pengeluaran mendekati batas budget'],
                                ['key' => 'daily_summary',   'label' => 'Daily Portfolio Summary', 'desc' => 'Ringkasan portfolio setiap hari kerja'],
                            ];
                            @endphp
                            @foreach($notifOptions as $opt)
                            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-background-dark rounded-xl border border-slate-200 dark:border-slate-700">
                                <div>
                                    <p class="font-medium text-sm text-slate-800 dark:text-slate-200">{{ $opt['label'] }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $opt['desc'] }}</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="notif_{{ $opt['key'] }}" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:ring-2 peer-focus:ring-primary rounded-full peer peer-checked:bg-primary transition-all"></div>
                                    <div class="absolute left-0.5 top-0.5 bg-white w-5 h-5 rounded-full transition-all peer-checked:translate-x-5"></div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        <div class="flex justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700 mt-6">
                            <button type="submit" class="px-8 py-2.5 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all">Save Notifications</button>
                        </div>
                    </section>
                </form>
            </div>

        </div>{{-- /settings content --}}
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Tab Switching ──────────────────────────────────────────────────────────
function switchTab(id) {
    // Hide all content panels
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    // Reset all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.className = btn.className
            .replace('bg-primary/10 text-primary font-semibold', '')
            + ' text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark';
    });
    // Show target panel
    document.getElementById('tab-' + id).classList.remove('hidden');
    // Highlight active button
    var activeBtn = document.getElementById('tab-btn-' + id);
    activeBtn.className = activeBtn.className
        .replace('text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark', '')
        + ' bg-primary/10 text-primary font-semibold';
    // Save active tab in URL hash
    history.replaceState(null, '', '#' + id);
}

// ── Auto-open tab from URL hash ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var hash = window.location.hash.replace('#', '');
    var validTabs = ['profile', 'preferences', 'security', 'integrations', 'notifications'];
    if (hash && validTabs.includes(hash)) {
        switchTab(hash);
    }

    // ── Theme toggle ──────────────────────────────────────────────────────
    function setTheme(theme) {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        document.getElementById('theme-input').value = theme;
        document.getElementById('theme-dark').className  = theme === 'dark'
            ? 'flex-1 flex items-center justify-center gap-2 py-1.5 rounded text-sm bg-white dark:bg-card-dark shadow-sm font-medium transition-all'
            : 'flex-1 flex items-center justify-center gap-2 py-1.5 rounded text-sm text-slate-500 hover:text-slate-800 transition-colors font-medium';
        document.getElementById('theme-light').className = theme === 'light'
            ? 'flex-1 flex items-center justify-center gap-2 py-1.5 rounded text-sm bg-white dark:bg-card-dark shadow-sm font-medium transition-all'
            : 'flex-1 flex items-center justify-center gap-2 py-1.5 rounded text-sm text-slate-500 hover:text-slate-800 transition-colors font-medium';
    }
    window.setTheme = setTheme;

    // ── Avatar preview ─────────────────────────────────────────────────────
    var photoInput = document.getElementById('photo-upload');
    if (photoInput) {
        photoInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar-preview').src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
</script>
@endpush
