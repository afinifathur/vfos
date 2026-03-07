<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Wealth Statement – {{ $user->name }} – {{ now()->format('d M Y') }}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --navy:    #0A1628;
        --navy-lt: #122040;
        --gold:    #C9A24E;
        --gold-lt: #F0D98C;
        --green:   #16A34A;
        --red:     #DC2626;
        --slate:   #475569;
        --border:  #E2E8F0;
        --bg:      #F8FAFC;
    }

    body {
        font-family: 'Inter', sans-serif;
        font-size: 9.5pt;
        color: #1E293B;
        background: white;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* ── Page Layout ── */
    .page {
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto;
        background: white;
        display: flex;
        flex-direction: column;
    }

    /* ── Header Band ── */
    .header-band {
        background: var(--navy);
        color: white;
        padding: 24px 36px 20px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }
    .header-brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .brand-logo {
        width: 36px; height: 36px;
        background: var(--gold);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; font-weight: 800; color: var(--navy);
        letter-spacing: -1px;
    }
    .brand-name { font-family: 'Playfair Display', serif; font-size: 22pt; color: white; line-height: 1; }
    .brand-sub  { font-size: 7pt; color: var(--gold-lt); letter-spacing: 3px; text-transform: uppercase; margin-top: 3px; }

    .header-meta { text-align: right; }
    .doc-type  { font-size: 8pt; lettser-spacing: 2px; text-transform: uppercase; color: var(--gold-lt); margin-bottom: 4px; }
    .doc-date  { font-size: 9pt; color: #94a3b8; }

    /* ── Gold Accent Line ── */
    .gold-bar { height: 3px; background: linear-gradient(90deg, var(--gold) 0%, var(--gold-lt) 50%, transparent 100%); }

    /* ── Client Info Bar ── */
    .client-bar {
        background: var(--bg);
        border-bottom: 1px solid var(--border);
        padding: 14px 36px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .client-label { font-size: 7pt; text-transform: uppercase; letter-spacing: 1.5px; color: var(--slate); }
    .client-name  { font-size: 12pt; font-weight: 700; color: var(--navy); margin-top: 2px; }
    .client-email { font-size: 8pt; color: var(--slate); }
    .ref-pill {
        background: var(--navy);
        color: var(--gold-lt);
        font-size: 7.5pt;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 100px;
        letter-spacing: 0.5px;
    }

    /* ── Body ── */
    .body { padding: 28px 36px; flex: 1; }

    /* ── Section Header ── */
    .section-title {
        font-size: 7pt;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--gold);
        font-weight: 700;
        margin-bottom: 10px;
        padding-bottom: 6px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .section-title::before {
        content: '';
        display: inline-block;
        width: 14px; height: 2px;
        background: var(--gold);
        border-radius: 1px;
    }

    /* ── Net Worth Hero ── */
    .net-worth-hero {
        background: var(--navy);
        border-radius: 10px;
        padding: 22px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 22px;
        position: relative;
        overflow: hidden;
    }
    .net-worth-hero::after {
        content: '';
        position: absolute;
        right: -60px; top: -60px;
        width: 160px; height: 160px;
        background: var(--gold);
        opacity: 0.06;
        border-radius: 50%;
    }
    .nw-label { font-size: 7pt; text-transform: uppercase; letter-spacing: 2px; color: var(--gold-lt); margin-bottom: 6px; }
    .nw-amount { font-family: 'Playfair Display', serif; font-size: 28pt; color: white; font-weight: 700; line-height: 1; }
    .nw-sub { font-size: 8pt; color: #94a3b8; margin-top: 6px; }
    .nw-split { display: flex; gap: 28px; }
    .nw-split-item { text-align: right; }
    .nw-split-label { font-size: 7pt; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 4px; }
    .nw-split-value { font-size: 13pt; font-weight: 700; }
    .color-green { color: #4ade80; }
    .color-red   { color: #f87171; }
    .nw-divider  { width: 1px; background: #1e3a5f; align-self: stretch; }

    /* ── Summary Grid ── */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-bottom: 20px;
    }
    .summary-card {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 14px 16px;
        background: white;
    }
    .summary-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
    .summary-card-icon {
        width: 28px; height: 28px;
        border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px;
    }
    .summary-card-badge { font-size: 6.5pt; font-weight: 700; padding: 2px 7px; border-radius: 100px; }
    .summary-card-label { font-size: 7.5pt; color: var(--slate); margin-bottom: 4px; }
    .summary-card-value { font-size: 11.5pt; font-weight: 700; color: var(--navy); line-height: 1.2; }
    .summary-card-footer { font-size: 7pt; color: var(--slate); margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; }

    .icon-blue   { background: #EFF6FF; color: #3B82F6; }
    .icon-indigo { background: #EEF2FF; color: #6366F1; }
    .icon-orange { background: #FFF7ED; color: #F97316; }
    .icon-purple { background: #FAF5FF; color: #A855F7; }

    .badge-blue   { background: #DBEAFE; color: #1D4ED8; }
    .badge-green  { background: #DCFCE7; color: #15803D; }
    .badge-red    { background: #FEE2E2; color: #B91C1C; }
    .badge-purple { background: #F3E8FF; color: #9333EA; }

    /* ── Table ── */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5pt;
        margin-bottom: 20px;
    }
    .data-table thead tr {
        background: var(--navy);
        color: white;
    }
    .data-table thead th {
        padding: 8px 12px;
        font-weight: 600;
        font-size: 7pt;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        text-align: left;
    }
    .data-table thead th:not(:first-child) { text-align: right; }
    .data-table tbody tr { border-bottom: 1px solid #F1F5F9; }
    .data-table tbody tr:hover { background: #F8FAFC; }
    .data-table tbody tr.group-header td {
        background: #F1F5F9;
        font-weight: 700;
        font-size: 7.5pt;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 7px 12px;
    }
    .data-table tbody tr.total-row td {
        background: var(--navy);
        color: white;
        font-weight: 700;
        padding: 10px 12px;
        font-size: 10pt;
    }
    .data-table td {
        padding: 8px 12px;
        vertical-align: middle;
    }
    .data-table td:not(:first-child) { text-align: right; }
    .dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; margin-right: 7px; vertical-align: middle; }
    .dot-green  { background: var(--green); }
    .dot-blue   { background: #3B82F6; }
    .dot-red    { background: var(--red); }
    .dot-orange { background: #F97316; }
    .dot-purple { background: #A855F7; }
    .dot-gray   { background: #94A3B8; }

    .status-tag {
        display: inline-block;
        font-size: 6.5pt;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 100px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .tag-green  { background: #DCFCE7; color: #15803D; }
    .tag-blue   { background: #DBEAFE; color: #1D4ED8; }
    .tag-red    { background: #FEE2E2; color: #B91C1C; }
    .tag-orange { background: #FFEDD5; color: #C2410C; }
    .tag-purple { background: #F3E8FF; color: #9333EA; }
    .tag-gray   { background: #F1F5F9; color: #64748B; }

    .text-right { text-align: right; }
    .text-red   { color: var(--red); }
    .text-green { color: var(--green); }
    .font-mono  { font-variant-numeric: tabular-nums; }

    /* ── Liabilities section ── */
    .liab-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
    .liab-card {
        border: 1px solid #FCA5A5;
        border-radius: 8px;
        padding: 14px 16px;
        background: #FFF5F5;
        display: flex; align-items: center; gap: 14px;
    }
    .liab-card.gray { border-color: var(--border); background: #F8FAFC; }
    .liab-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
    }
    .liab-icon-red    { background: #FEE2E2; }
    .liab-icon-gray   { background: #F1F5F9; }
    .liab-label { font-size: 7pt; text-transform: uppercase; letter-spacing: 1px; color: var(--slate); margin-bottom: 3px; }
    .liab-amount { font-size: 14pt; font-weight: 700; }
    .liab-sub    { font-size: 7.5pt; color: var(--slate); margin-top: 2px; }

    /* ── Footer ── */
    .footer {
        border-top: 1px solid var(--border);
        padding: 14px 36px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg);
    }
    .footer-left  { font-size: 7pt; color: var(--slate); }
    .footer-right { font-size: 7pt; color: var(--slate); text-align: right; }
    .footer-brand { color: var(--navy); font-weight: 700; font-size: 8.5pt; }
    .disclaimer {
        padding: 10px 36px 16px;
        font-size: 6.5pt;
        color: #94A3B8;
        line-height: 1.6;
    }

    /* ── Print Rules ── */
    @page {
        size: A4 portrait;
        margin: 0;
    }
    @media print {
        body { margin: 0; }
        .page { width: 100%; }
        .no-break { page-break-inside: avoid; }
    }
    @media screen {
        body { background: #E2E8F0; padding: 24px 0; }
        .page { box-shadow: 0 8px 40px rgba(0,0,0,0.15); margin: 0 auto 24px; }
    }
</style>
</head>
<body>
<div class="page">

    {{-- ══ HEADER BAND ══ --}}
    <div class="header-band">
        <div class="header-brand">
            <div class="brand-logo">vF</div>
            <div>
                <div class="brand-name">vFOS</div>
                <div class="brand-sub">Financial Intelligence System</div>
            </div>
        </div>
        <div class="header-meta">
            <div class="doc-type">Personal Wealth Statement</div>
            <div class="doc-date">Statement Date: {{ now()->format('d F Y') }}</div>
        </div>
    </div>
    <div class="gold-bar"></div>

    {{-- ══ CLIENT INFO BAR ══ --}}
    <div class="client-bar">
        <div>
            <div class="client-label">Prepared for</div>
            <div class="client-name">{{ $user->name }}</div>
            <div class="client-email">{{ $user->email }}</div>
        </div>
        <div style="text-align:right;">
            <div class="ref-pill">Report No: WS-{{ date('Ymd') }}-{{ sprintf('%04d', $user->id) }}</div>
            <div style="font-size:7pt;color:#64748b;margin-top:6px;">Confidential — For client use only</div>
        </div>
    </div>

    {{-- ══ BODY ══ --}}
    <div class="body">

        {{-- Net Worth Hero --}}
        <div class="net-worth-hero no-break">
            <div>
                <div class="nw-label">Total Net Worth</div>
                <div class="nw-amount">Rp {{ number_format($netWorth, 0, ',', '.') }}</div>
                <div class="nw-sub">As per {{ now()->format('d F Y, H:i') }} WIB</div>
            </div>
            <div class="nw-split">
                <div class="nw-split-item">
                    <div class="nw-split-label">Total Assets</div>
                    <div class="nw-split-value color-green">Rp {{ number_format($totalAssets, 0, ',', '.') }}</div>
                </div>
                <div class="nw-divider"></div>
                <div class="nw-split-item">
                    <div class="nw-split-label">Total Liabilities</div>
                    <div class="nw-split-value color-red">(Rp {{ number_format($totalLiabilities, 0, ',', '.') }})</div>
                </div>
            </div>
        </div>

        {{-- ── Section 1: Assets Breakdown ── --}}
        <div class="section-title">Assets Breakdown</div>
        <div class="summary-grid no-break">
            <div class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-card-icon icon-blue">💳</div>
                    <span class="summary-card-badge badge-blue">Liquid</span>
                </div>
                <div class="summary-card-label">Cash & Bank</div>
                <div class="summary-card-value">Rp {{ number_format($totalCash, 0, ',', '.') }}</div>
                <div class="summary-card-footer">
                    <span>{{ $accounts->count() }} accounts</span>
                    <span style="color:#1D4ED8;font-weight:600;">{{ $totalAssets > 0 ? number_format(($totalCash/$totalAssets)*100,1) : 0 }}%</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-card-icon icon-indigo">📈</div>
                    <span class="summary-card-badge {{ $investmentPL >= 0 ? 'badge-green' : 'badge-red' }}">
                        {{ $investmentPL >= 0 ? '+' : '' }}Rp {{ number_format($investmentPL, 0, ',', '.') }}
                    </span>
                </div>
                <div class="summary-card-label">Investments</div>
                <div class="summary-card-value">Rp {{ number_format($totalInvestments, 0, ',', '.') }}</div>
                <div class="summary-card-footer">
                    <span>{{ $investments->count() }} positions</span>
                    <span style="color:#6366F1;font-weight:600;">{{ $totalAssets > 0 ? number_format(($totalInvestments/$totalAssets)*100,1) : 0 }}%</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-card-icon icon-orange">⏳</div>
                </div>
                <div class="summary-card-label">Receivables</div>
                <div class="summary-card-value">Rp {{ number_format($totalReceivables, 0, ',', '.') }}</div>
                <div class="summary-card-footer">
                    <span>{{ $receivables->count() }} outstanding</span>
                    <span style="color:#F97316;font-weight:600;">{{ $totalAssets > 0 ? number_format(($totalReceivables/$totalAssets)*100,1) : 0 }}%</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-card-icon icon-purple">🏠</div>
                </div>
                <div class="summary-card-label">Fixed Assets</div>
                <div class="summary-card-value">Rp {{ number_format($totalFixedAssets, 0, ',', '.') }}</div>
                <div class="summary-card-footer">
                    <span>{{ $appreciatingAssets->count() }} items</span>
                    <span style="color:#A855F7;font-weight:600;">{{ $totalAssets > 0 ? number_format(($totalFixedAssets/$totalAssets)*100,1) : 0 }}%</span>
                </div>
            </div>
        </div>

        {{-- ── Section 2: Liabilities ── --}}
        @if($totalLiabilities > 0 || $totalDepreciating > 0)
        <div class="section-title">Liabilities & Depreciating Assets</div>
        <div class="liab-grid no-break">
            <div class="liab-card">
                <div class="liab-icon liab-icon-red">🏦</div>
                <div>
                    <div class="liab-label">Total Debts & Obligations</div>
                    <div class="liab-amount text-red">Rp {{ number_format($totalLiabilities, 0, ',', '.') }}</div>
                    <div class="liab-sub">{{ $debts->count() }} active obligations</div>
                </div>
            </div>
            <div class="liab-card gray">
                <div class="liab-icon liab-icon-gray">🚗</div>
                <div>
                    <div class="liab-label">Depreciating Assets</div>
                    <div class="liab-amount" style="color:#64748b;">Rp {{ number_format($totalDepreciating, 0, ',', '.') }}</div>
                    <div class="liab-sub">{{ $depreciatingAssets->count() }} items losing value</div>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Section 3: Wealth Detail Table ── --}}
        <div class="section-title">Wealth Composition — Detailed Breakdown</div>
        <table class="data-table no-break">
            <thead>
                <tr>
                    <th style="width:36%;">Component</th>
                    <th>Description</th>
                    <th>Value (Rp)</th>
                    <th>Composition</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                {{-- Cash & Bank --}}
                <tr class="group-header"><td colspan="5">I. Cash & Bank Accounts</td></tr>
                @foreach($accounts as $acc)
                    @php $bal = $acc->calculateBalance(); @endphp
                    @if($bal != 0)
                    <tr>
                        <td><span class="dot dot-blue"></span>{{ $acc->name }}</td>
                        <td style="color:#64748b;">Current Balance</td>
                        <td class="font-mono">{{ number_format($bal, 0, ',', '.') }}</td>
                        <td class="font-mono">{{ $totalAssets > 0 ? number_format(($bal/$totalAssets)*100,1) : 0 }}%</td>
                        <td><span class="status-tag tag-blue">Liquid</span></td>
                    </tr>
                    @endif
                @endforeach

                {{-- Investments --}}
                <tr class="group-header"><td colspan="5">II. Investment Portfolio</td></tr>
                @forelse($investments as $inv)
                    @if($inv->market_value > 0)
                    <tr>
                        <td><span class="dot {{ $inv->gain_loss >= 0 ? 'dot-green' : 'dot-red' }}"></span>{{ $inv->name }}</td>
                        <td style="color:#64748b;">{{ $inv->ticker ?? $inv->asset_class }} · Qty: {{ number_format($inv->quantity, 2) }}</td>
                        <td class="font-mono">{{ number_format($inv->market_value, 0, ',', '.') }}</td>
                        <td class="font-mono">{{ $totalAssets > 0 ? number_format(($inv->market_value/$totalAssets)*100,1) : 0 }}%</td>
                        <td><span class="status-tag {{ $inv->gain_loss >= 0 ? 'tag-green' : 'tag-red' }}">{{ $inv->gain_loss >= 0 ? 'Bullish' : 'Bearish' }}</span></td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="5" style="color:#94a3b8;font-style:italic;padding:8px 12px;">No investments recorded</td></tr>
                @endforelse

                {{-- Receivables --}}
                @if($receivables->count() > 0)
                <tr class="group-header"><td colspan="5">III. Receivables</td></tr>
                @foreach($receivables as $rec)
                <tr>
                    <td><span class="dot dot-orange"></span>{{ $rec->name }}</td>
                    <td style="color:#64748b;">Outstanding receivable{{ $rec->due_date ? ' · Due: ' . \Carbon\Carbon::parse($rec->due_date)->format('d M Y') : '' }}</td>
                    <td class="font-mono">{{ number_format($rec->remaining_amount, 0, ',', '.') }}</td>
                    <td class="font-mono">{{ $totalAssets > 0 ? number_format(($rec->remaining_amount/$totalAssets)*100,1) : 0 }}%</td>
                    <td><span class="status-tag tag-orange">Pending</span></td>
                </tr>
                @endforeach
                @endif

                {{-- Fixed Assets --}}
                @if($appreciatingAssets->count() > 0)
                <tr class="group-header"><td colspan="5">IV. Fixed Assets (Appreciating)</td></tr>
                @foreach($appreciatingAssets as $ast)
                <tr>
                    <td><span class="dot dot-purple"></span>{{ $ast->name }}</td>
                    <td style="color:#64748b;">{{ ucfirst($ast->type) }} · Original: Rp {{ number_format($ast->purchase_price, 0, ',', '.') }}</td>
                    <td class="font-mono">{{ number_format($ast->current_value, 0, ',', '.') }}</td>
                    <td class="font-mono">{{ $totalAssets > 0 ? number_format(($ast->current_value/$totalAssets)*100,1) : 0 }}%</td>
                    <td><span class="status-tag tag-purple">Appreciating</span></td>
                </tr>
                @endforeach
                @endif

                {{-- Debts --}}
                @if($debts->count() > 0)
                <tr class="group-header"><td colspan="5">V. Debts & Liabilities</td></tr>
                @foreach($debts as $debt)
                <tr>
                    <td><span class="dot dot-red"></span>{{ $debt->name }}</td>
                    <td style="color:#64748b;">Remaining obligation{{ $debt->due_date ? ' · Due: ' . \Carbon\Carbon::parse($debt->due_date)->format('d M Y') : '' }}</td>
                    <td class="font-mono text-red">({{ number_format($debt->remaining_amount, 0, ',', '.') }})</td>
                    <td class="font-mono">{{ $totalAssets > 0 ? number_format(($debt->remaining_amount/$totalAssets)*100,1) : 0 }}%</td>
                    <td><span class="status-tag tag-red">Liability</span></td>
                </tr>
                @endforeach
                @endif

                {{-- NET WORTH TOTAL ROW --}}
                <tr class="total-row">
                    <td colspan="2" style="letter-spacing:1px;">NET POSITION (Total Assets − Total Liabilities)</td>
                    <td class="font-mono" style="font-size:11pt;color:{{ $netWorth >= 0 ? '#4ade80' : '#f87171' }};">
                        {{ $netWorth < 0 ? '(' : '' }}Rp {{ number_format(abs($netWorth), 0, ',', '.') }}{{ $netWorth < 0 ? ')' : '' }}
                    </td>
                    <td class="font-mono" style="color:#94a3b8;font-size:8pt;">100%</td>
                    <td><span class="status-tag" style="background:rgba(74,222,128,0.15);color:#4ade80;">{{ $netWorth >= 0 ? 'Positive' : 'Negative' }}</span></td>
                </tr>
            </tbody>
        </table>

    </div>{{-- end .body --}}

    {{-- ══ DISCLAIMER ══ --}}
    <div class="disclaimer">
        <strong>Disclaimer:</strong> This Wealth Statement has been auto-generated by the vFOS Personal Financial Intelligence System based on data entered by the account holder.
        The figures presented herein are for personal financial planning purposes only and do not constitute certified financial advice, tax counsel, or an official audit.
        All values are in Indonesian Rupiah (IDR) unless otherwise stated. The accuracy of this report depends entirely on the data entered by the user.
    </div>

    {{-- ══ FOOTER ══ --}}
    <div class="footer">
        <div class="footer-left">
            <div class="footer-brand">vFOS — Personal Financial Intelligence</div>
            <div>Generated: {{ now()->format('d F Y, H:i:s') }} WIB</div>
        </div>
        <div class="footer-right">
            <div>Document: WS-{{ date('Ymd') }}-{{ sprintf('%04d', $user->id) }}</div>
            <div style="color:#C9A24E;font-weight:600;">CONFIDENTIAL</div>
        </div>
    </div>

</div>{{-- end .page --}}

<script>
    window.onload = function () { window.print(); };
</script>
</body>
</html>
