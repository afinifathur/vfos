<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Investment;
use App\Services\KontanService;
use App\Services\YahooFinanceService;
use App\Services\PasardanaService;
use App\Services\CurrencyService;

class InvestmentController extends Controller
{
    public function index()
    {
        $userId      = auth()->id();
        $investments = Investment::where('user_id', $userId)->get();

        $totalPortfolioValue = $investments->sum('market_value');
        $totalInvested       = $investments->sum('total_cost');
        $totalProfitLoss     = $totalPortfolioValue - $totalInvested;

        return view('investments.index', compact(
            'investments',
            'totalPortfolioValue',
            'totalInvested',
            'totalProfitLoss'
        ));
    }

    public function create()
    {
        return view('investments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'ticker'        => 'nullable|string|max:50',
            'asset_class'   => 'required|string',
            'scraping_url'  => 'nullable|url|max:500',
            'currency'      => 'nullable|string|max:10',
            'price_unit'    => 'nullable|string|max:20',
            'quantity'      => 'required|numeric|min:0',
            'average_cost'  => 'required|numeric|min:0',
            'current_price' => 'nullable|numeric|min:0',
        ]);

        $validated['currency']   = $validated['currency']   ?? 'IDR';
        $validated['price_unit'] = $validated['price_unit'] ?? 'unit';

        if ($validated['asset_class'] === 'Mutual Fund' && !empty($validated['scraping_url'])) {
            $kontan = new KontanService();
            $nav    = $kontan->getNavFromKontan($validated['scraping_url']);
            if ($nav) { $validated['current_price'] = $nav; }
        }

        if (empty($validated['current_price'])) {
            $validated['current_price'] = 0;
        }

        $validated['user_id'] = auth()->id();
        Investment::create($validated);
        return redirect()->route('investments.index')->with('success', 'Investment added successfully.');
    }

    public function edit(Investment $investment)
    {
        abort_if($investment->user_id !== auth()->id(), 403);
        return view('investments.edit', compact('investment'));
    }

    public function update(Request $request, Investment $investment)
    {
        abort_if($investment->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'ticker'        => 'nullable|string|max:50',
            'asset_class'   => 'required|string',
            'scraping_url'  => 'nullable|url|max:500',
            'currency'      => 'nullable|string|max:10',
            'price_unit'    => 'nullable|string|max:20',
            'quantity'      => 'required|numeric|min:0',
            'average_cost'  => 'required|numeric|min:0',
            'current_price' => 'nullable|numeric|min:0',
        ]);

        $validated['currency']   = $validated['currency']   ?? 'IDR';
        $validated['price_unit'] = $validated['price_unit'] ?? 'unit';

        if ($validated['asset_class'] === 'Mutual Fund' && !empty($validated['scraping_url'])) {
            if ($validated['scraping_url'] !== $investment->scraping_url) {
                $kontan = new KontanService();
                $nav    = $kontan->getNavFromKontan($validated['scraping_url']);
                if ($nav) { $validated['current_price'] = $nav; }
            }
        }

        if (empty($validated['current_price'])) {
            $validated['current_price'] = $investment->current_price ?? 0;
        }

        $investment->update($validated);
        return redirect()->route('investments.index')->with('success', 'Investment updated successfully.');
    }

    /**
     * Return list of investment IDs for the logged-in user.
     * Frontend will call refreshItem() for each ID individually.
     */
    public function refresh(Request $request)
    {
        $userId      = auth()->id();
        $investments = Investment::where('user_id', $userId)
            ->whereNotNull('ticker')
            ->orWhere(function ($q) use ($userId) {
                $q->where('user_id', $userId)->where('asset_class', 'Mutual Fund');
            })
            ->get(['id', 'name', 'ticker', 'asset_class']);

        $ids = $investments->map(fn($inv) => [
            'id'    => $inv->id,
            'label' => $inv->ticker ?? $inv->name,
        ])->values();

        return response()->json([
            'status'      => 'ok',
            'investments' => $ids,
        ]);
    }

    /**
     * Update price for a single investment.
     * Called by frontend one-by-one for each investment ID.
     */
    public function refreshItem(Investment $investment)
    {
        abort_if($investment->user_id !== auth()->id(), 403);

        set_time_limit(30);

        try {
            $price    = null;
            $yahoo    = new YahooFinanceService();
            $kontan   = new KontanService();
            $pasardana = new PasardanaService();
            $currency  = new CurrencyService();

            if ($investment->asset_class === 'Mutual Fund') {
                if ($investment->scraping_url) {
                    $price = $kontan->getNavFromKontan($investment->scraping_url);
                } else {
                    $result = $pasardana->getNavReksaDana($investment->name);
                    $price  = $result['nav'] ?? null;
                }
            } elseif ($investment->ticker) {
                $rawPrice = $yahoo->getStockPrice($investment->ticker);

                if ($rawPrice !== null) {
                    if ($investment->currency === 'USD') {
                        $usdToIdr = $currency->getUsdToIdr();
                        $price = $investment->price_unit === 'gram'
                            ? ($rawPrice / 31.1035) * $usdToIdr
                            : $rawPrice * $usdToIdr;
                    } else {
                        $price = $rawPrice;
                    }
                }
            } else {
                return response()->json(['status' => 'skipped', 'id' => $investment->id]);
            }

            if ($price !== null && $price > 0) {
                $investment->update(['current_price' => $price]);
                return response()->json([
                    'status'  => 'updated',
                    'id'      => $investment->id,
                    'price'   => $price,
                ]);
            }

            return response()->json(['status' => 'failed', 'id' => $investment->id, 'reason' => 'no price returned']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'id' => $investment->id, 'message' => $e->getMessage()]);
        }
    }

    public function destroy(Investment $investment)
    {
        abort_if($investment->user_id !== auth()->id(), 403);
        $investment->delete();
        return redirect()->route('investments.index')->with('success', 'Investment deleted successfully.');
    }
}
