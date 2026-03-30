<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profit & Loss – {{ $user->name }} – {{ $months[$month] }} {{ $year }}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
        font-family: 'Inter', Helvetica, Arial, sans-serif;
        font-size: 8pt;
        color: #111827;
        background: white;
        line-height: 1.4;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .page {
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto;
        padding: 40px 40px;
        background: white;
    }

    /* ── Header ── */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 25px;
    }
    .company-name {
        font-size: 11pt;
        font-weight: 800;
        letter-spacing: 0.3px;
        color: #000;
    }
    .company-info {
        font-size: 7.5pt;
        color: #4B5563;
        margin-top: 8px;
        line-height: 1.6;
    }
    .brand-logo {
        font-size: 18pt;
        font-weight: 800;
        letter-spacing: -1px;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .brand-logo span {
        display: inline-block;
        width: 12px;
        height: 12px;
        background: #111827;
    }

    /* ── Title Block ── */
    .title-block {
        border-top: 1px solid #E5E7EB;
        border-bottom: 1px solid #E5E7EB;
        padding: 18px 0;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .doc-title {
        font-size: 18pt;
        font-weight: 800;
        letter-spacing: -0.5px;
        color: #111827;
    }
    .title-meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        color: #6B7280;
        font-size: 7.5pt;
    }
    .title-meta-label {
        margin-bottom: 2px;
    }
    .title-meta-value {
        color: #111827;
        font-weight: 500;
    }

    /* ── Client Meta Grid ── */
    .meta-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        margin-bottom: 30px;
    }
    .meta-table {
        width: 100%;
        border-collapse: collapse;
    }
    .meta-table td {
        padding: 4px 0;
        vertical-align: top;
    }
    .meta-label {
        width: 120px;
        color: #9CA3AF;
        font-size: 8pt;
    }
    .meta-value {
        font-weight: 500;
        font-size: 8pt;
    }
    .meta-value-bold {
        font-weight: 700;
        font-size: 8pt;
    }
    .meta-value-numeric {
        font-variant-numeric: tabular-nums;
        text-align: right;
    }
    
    /* ── Highlight Box (Right Side) ── */
    .summary-box td {
        padding: 4px 0;
    }
    .summary-box .meta-label {
        color: #6B7280;
    }
    .summary-box .meta-value-numeric {
        font-weight: 600;
    }
    .summary-total {
        font-weight: 800 !important;
        font-size: 9pt !important;
    }

    /* ── Transactions Data Table ── */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }
    .data-table th {
        color: #9CA3AF;
        font-size: 7pt;
        font-weight: 500;
        padding: 8px 10px;
        border-bottom: 1px solid #E5E7EB;
        text-align: left;
    }
    .data-table th:last-child {
        text-align: right;
    }
    .data-table td {
        padding: 6px 10px;
        vertical-align: middle;
        font-size: 8pt;
        color: #111827;
    }
    .data-table td:last-child {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .data-table tbody tr:nth-child(even) {
        background-color: #F9FAFB;
    }
    .group-header td {
        font-weight: 800;
        font-size: 9pt;
        color: #111827;
        padding-top: 25px;
        padding-bottom: 10px;
        text-transform: uppercase;
        border-bottom: 1px solid #E5E7EB;
    }
    .total-row td {
        font-weight: 800;
        padding: 12px 10px;
        border-top: 1px solid #E5E7EB;
    }
    .grand-total td {
        font-weight: 800;
        font-size: 10pt;
        padding: 15px 10px;
        border-top: 2px solid #111827;
        border-bottom: 2px solid #111827;
    }

    .text-red { color: #DC2626 !important; }
    .text-green { color: #16A34A !important; }

    /* Print settings */
    @page { margin: 0; size: A4 portrait; }
    @media screen {
        body { background: #E5E7EB; padding: 40px 0; }
        .page { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin: 0 auto 40px; }
    }
</style>
</head>
<body>

<div class="page">
    {{-- ── Header ── --}}
    <div class="header">
        <div>
            <div class="company-name">VFOS PERSONAL FINANCIAL OS</div>
            <div class="company-info">
                Database Entry System & Autopilot Ledger<br>
                Jakarta, ID — 10001<br>
                Email : system@vfos.test<br>
                Telp : -
            </div>
        </div>
        <div class="brand-logo">
            vFOS
        </div>
    </div>

    {{-- ── Title Block ── --}}
    <div class="title-block">
        <div class="doc-title">Profit & Loss Statement</div>
        <div class="title-meta-grid">
            <div>
                <div class="title-meta-label">Period / Currency</div>
                <div class="title-meta-value">{{ strtoupper($months[$month]) }} {{ $year }} / IDR</div>
            </div>
            <div>
                <div class="title-meta-label">SID / PID</div>
                <div class="title-meta-value">IDD{{ date('ymd') }}V{{ sprintf('%04d', $user->id) }}</div>
            </div>
        </div>
    </div>

    {{-- ── Client & Summary Grid ── --}}
    <div class="meta-container">
        <div>
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Client Name</td>
                    <td class="meta-value-bold">{{ sprintf('%07d', $user->id) }} {{ strtoupper($user->name) }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Email</td>
                    <td class="meta-value">{{ $user->email }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Account Owner Filter</td>
                    <td class="meta-value">{{ strtoupper($owner == 'all' ? 'JOINT ACCOUNT / ALL' : $owner) }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Generation Date</td>
                    <td class="meta-value">{{ now()->format('d/m/Y H:i') }} WIB</td>
                </tr>
            </table>
        </div>
        <div>
            <table class="meta-table summary-box">
                <tr>
                    <td class="meta-label">Gross Income</td>
                    <td class="meta-value-numeric">{{ number_format($totalIncome, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Total Expense</td>
                    <td class="meta-value-numeric">({{ number_format($totalExpense, 0, ',', '.') }})</td>
                </tr>
                <tr>
                    <td class="meta-label">Profit Margin</td>
                    <td class="meta-value-numeric">{{ number_format($profitMargin, 1) }}%</td>
                </tr>
                <tr class="total-row">
                    <td class="meta-label font-bold" style="color:#111827;">NET PROFIT (LOSS)</td>
                    <td class="meta-value-numeric summary-total {{ $netProfit >= 0 ? '' : 'text-red' }}">
                        {{ $netProfit < 0 ? '(' : '' }}{{ number_format(abs($netProfit), 0, ',', '.') }}{{ $netProfit < 0 ? ')' : '' }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ── Main Table ── --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%">Category Type</th>
                <th style="width: 65%">Description / Category Name</th>
                <th style="width: 20%">Ending Balance (Rp)</th>
            </tr>
        </thead>
        <tbody>
            
            {{-- INCOME --}}
            <tr class="group-header">
                <td colspan="3">OPERATING INCOME</td>
            </tr>
            @forelse($incomeItems as $item)
            <tr>
                <td>Income</td>
                <td>{{ $item->category_name }}</td>
                <td>{{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="color:#9CA3AF;font-style:italic;text-align:center;padding:15px;">No income recorded for this period.</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2" style="text-align:right">T O T A L &nbsp;&nbsp; I N C O M E</td>
                <td>{{ number_format($totalIncome, 0, ',', '.') }}</td>
            </tr>

            {{-- EXPENSES --}}
            <tr class="group-header">
                <td colspan="3">OPERATING EXPENSES</td>
            </tr>
            @forelse($expenseItems as $item)
            <tr>
                <td>Expense</td>
                <td>{{ $item->category_name }}</td>
                <td>({{ number_format($item->total, 0, ',', '.') }})</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="color:#9CA3AF;font-style:italic;text-align:center;padding:15px;">No expenses recorded for this period.</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2" style="text-align:right">T O T A L &nbsp;&nbsp; E X P E N S E S</td>
                <td>({{ number_format($totalExpense, 0, ',', '.') }})</td>
            </tr>

            {{-- GRAND TOTAL --}}
            <tr class="grand-total">
                <td colspan="2" style="text-align:right;letter-spacing:1px;">N E T &nbsp;&nbsp; P R O F I T</td>
                <td class="{{ $netProfit >= 0 ? '' : 'text-red' }}">
                    {{ $netProfit < 0 ? '(' : '' }}{{ number_format(abs($netProfit), 0, ',', '.') }}{{ $netProfit < 0 ? ')' : '' }}
                </td>
            </tr>
        </tbody>
    </table>

    <div style="font-size: 6.5pt; color: #9CA3AF; text-align: center; margin-top: 50px;">
        *** This is a computer-generated document. No signature is required. ***<br>
        vFOS System &copy; {{ now()->year }}
    </div>

</div>

<script>
    window.onload = function() {
        window.print();
    };
</script>
</body>
</html>
