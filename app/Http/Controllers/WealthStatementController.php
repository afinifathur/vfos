<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Investment;
use App\Models\Receivable;
use App\Models\Debt;
use App\Services\Finance\Kernel\FinancialKernelService;

class WealthStatementController extends Controller
{
    protected FinancialKernelService $kernel;

    public function __construct(FinancialKernelService $kernel)
    {
        $this->kernel = $kernel;
    }

    public function index()
    {
        $userId = auth()->id();

        // ── Get standard financial summary DTO ─────────────────────────────────
        $summary = $this->kernel->getAccountSummary($userId);

        $totalCash        = $summary->liquid_assets;
        $totalInvestments = $summary->investment;
        $totalReceivables = $summary->receivable;
        $totalDebts       = $summary->payable;
        $totalAssets      = $summary->total_assets;
        $totalLiabilities = $summary->total_liabilities;
        $netWorth         = $summary->net_worth;

        // Fetch collections for display details
        $accounts    = $this->kernel->getAccountBalances($userId);
        $investments = Investment::where('user_id', $userId)->get();
        $receivables = Receivable::where('user_id', $userId)->where('status', 'active')->get();
        $debts       = Debt::where('user_id', $userId)->where('status', 'active')->get();

        $totalInvested = $investments->sum('total_cost');
        $investmentPL  = $totalInvestments - $totalInvested;

        $assets             = Asset::where('user_id', $userId)->get();
        $appreciatingAssets = $assets->filter(fn($a) => $a->current_value >= $a->purchase_price);
        $depreciatingAssets = $assets->filter(fn($a) => $a->current_value < $a->purchase_price);
        $totalFixedAssets   = $appreciatingAssets->sum('current_value');
        $totalDepreciating  = $depreciatingAssets->sum('current_value');

        // ── Breakdown table rows ──────────────────────────────────────────────
        $breakdownRows = [];

        // Accounts (Cash)
        foreach ($accounts as $account) {
            $bal = $this->kernel->getAccountBalance($account);
            if ($bal == 0) continue;
            $breakdownRows[] = [
                'label'       => $account->name,
                'group'       => 'Cash & Bank',
                'amount'      => $bal,
                'composition' => $totalAssets > 0 ? ($bal / $totalAssets) * 100 : 0,
                'status'      => 'Liquid',
                'status_class'=> 'text-blue-400 bg-blue-400/10',
                'dot'         => 'bg-blue-400',
                'is_liability'=> false,
            ];
        }

        // Investments
        foreach ($investments as $inv) {
            if ($inv->market_value == 0) continue;
            $breakdownRows[] = [
                'label'       => $inv->name . ($inv->ticker ? " ({$inv->ticker})" : ''),
                'group'       => 'Investment',
                'amount'      => $inv->market_value,
                'composition' => $totalAssets > 0 ? ($inv->market_value / $totalAssets) * 100 : 0,
                'status'      => $inv->gain_loss >= 0 ? 'Bullish' : 'Bearish',
                'status_class'=> $inv->gain_loss >= 0 ? 'text-emerald-500 bg-emerald-500/10' : 'text-rose-500 bg-rose-500/10',
                'dot'         => $inv->gain_loss >= 0 ? 'bg-emerald-500' : 'bg-rose-500',
                'is_liability'=> false,
            ];
        }

        // Receivables
        foreach ($receivables as $rec) {
            if ($rec->remaining_amount == 0) continue;
            $breakdownRows[] = [
                'label'       => $rec->name,
                'group'       => 'Receivable',
                'amount'      => $rec->remaining_amount,
                'composition' => $totalAssets > 0 ? ($rec->remaining_amount / $totalAssets) * 100 : 0,
                'status'      => 'Pending',
                'status_class'=> 'text-orange-400 bg-orange-400/10',
                'dot'         => 'bg-orange-400',
                'is_liability'=> false,
            ];
        }

        // Fixed Assets
        foreach ($appreciatingAssets as $asset) {
            $breakdownRows[] = [
                'label'       => $asset->name,
                'group'       => 'Fixed Asset',
                'amount'      => $asset->current_value,
                'composition' => $totalAssets > 0 ? ($asset->current_value / $totalAssets) * 100 : 0,
                'status'      => 'Appreciating',
                'status_class'=> 'text-purple-400 bg-purple-400/10',
                'dot'         => 'bg-purple-400',
                'is_liability'=> false,
            ];
        }

        // Depreciating Assets
        foreach ($depreciatingAssets as $asset) {
            $breakdownRows[] = [
                'label'       => $asset->name,
                'group'       => 'Depreciating Asset',
                'amount'      => $asset->current_value,
                'composition' => $totalAssets > 0 ? ($asset->current_value / $totalAssets) * 100 : 0,
                'status'      => 'Depreciating',
                'status_class'=> 'text-slate-400 bg-slate-400/10',
                'dot'         => 'bg-slate-400',
                'is_liability'=> false,
            ];
        }

        // Debts (liabilities)
        foreach ($debts as $debt) {
            if ($debt->remaining_amount == 0) continue;
            $breakdownRows[] = [
                'label'       => $debt->name,
                'group'       => 'Debt',
                'amount'      => -$debt->remaining_amount,
                'composition' => $totalAssets > 0 ? ($debt->remaining_amount / $totalAssets) * 100 : 0,
                'status'      => 'Liability',
                'status_class'=> 'text-rose-500 bg-rose-500/10',
                'dot'         => 'bg-rose-500',
                'is_liability'=> true,
            ];
        }

        // Sort: assets first, then liabilities
        usort($breakdownRows, fn($a, $b) => $b['amount'] <=> $a['amount']);

        return view('wealth-statement', compact(
            'totalCash', 'totalInvestments', 'investmentPL',
            'totalReceivables', 'totalFixedAssets', 'totalDepreciating',
            'totalDebts', 'totalAssets', 'totalLiabilities', 'netWorth',
            'accounts', 'investments', 'receivables', 'appreciatingAssets',
            'depreciatingAssets', 'debts', 'breakdownRows'
        ));
    }

    public function pdf()
    {
        $userId = auth()->id();

        // ── Get standard financial summary DTO ─────────────────────────────────
        $summary = $this->kernel->getAccountSummary($userId);

        $totalCash        = $summary->liquid_assets;
        $totalInvestments = $summary->investment;
        $totalReceivables = $summary->receivable;
        $totalDebts       = $summary->payable;
        $totalAssets      = $summary->total_assets;
        $totalLiabilities = $summary->total_liabilities;
        $netWorth         = $summary->net_worth;

        // Fetch collections for display details
        $accounts    = $this->kernel->getAccountBalances($userId);
        $investments = Investment::where('user_id', $userId)->get();
        $receivables = Receivable::where('user_id', $userId)->where('status', 'active')->get();
        $debts       = Debt::where('user_id', $userId)->where('status', 'active')->get();

        $totalInvested = $investments->sum('total_cost');
        $investmentPL  = $totalInvestments - $totalInvested;

        $assets             = Asset::where('user_id', $userId)->get();
        $appreciatingAssets = $assets->filter(fn($a) => $a->current_value >= $a->purchase_price);
        $depreciatingAssets = $assets->filter(fn($a) => $a->current_value < $a->purchase_price);
        $totalFixedAssets   = $appreciatingAssets->sum('current_value');
        $totalDepreciating  = $depreciatingAssets->sum('current_value');

        $user = auth()->user();

        return view('wealth-statement-pdf', compact(
            'user',
            'totalCash', 'totalInvestments', 'investmentPL',
            'totalReceivables', 'totalFixedAssets', 'totalDepreciating',
            'totalDebts', 'totalAssets', 'totalLiabilities', 'netWorth',
            'accounts', 'investments', 'receivables', 'appreciatingAssets',
            'depreciatingAssets', 'debts'
        ));
    }
}
