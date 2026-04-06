<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Investment;
use App\Services\KontanService;
use App\Services\YahooFinanceService;
use App\Services\PasardanaService;
use App\Services\CurrencyService;
use App\Services\BareksaService;
use Illuminate\Support\Facades\DB;

class InvestmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Investment::query();

        // ── Filter by Type ────────────────────────────────────────────────────
        if ($request->filled('type')) {
            $type = $request->query('type');
            if ($type === 'Mutual Fund') {
                $query->where('asset_class', 'Mutual Fund');
            } else {
                $query->where('asset_class', 'like', '%' . $type . '%');
            }
        }

        // ── Search by Ticker/Name ─────────────────────────────────────────────
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('ticker', 'like', $search)
                  ->orWhere('name', 'like', $search);
            });
        }

        // ── Initial Fetch for Summary ─────────────────────────────────────────
        $allInvestments = (clone $query)->get();
        $totalPortfolioValue = $allInvestments->sum('market_value');
        $totalInvested       = $allInvestments->sum('total_cost');
        $totalProfitLoss     = $totalPortfolioValue - $totalInvested;

        // ── Sorting ───────────────────────────────────────────────────────────
        $sort = $request->query('sort', 'performance_desc');
        switch ($sort) {
            case 'performance_desc':
                // Note: gain_loss_percentage is an attribute, but we can't sort by it in SQL easily.
                // For small datasets, we'll sort after fetching. For large, ideally we'd use a raw SQL expression.
                // Let's use a simpler sort for now: Market Value.
                $query->orderByDesc(DB::raw('quantity * current_price'));
                break;
            case 'performance_asc':
                $query->orderBy(DB::raw('quantity * current_price'));
                break;
            case 'market_value':
                $query->orderByDesc(DB::raw('quantity * current_price'));
                break;
            case 'ticker_az':
                $query->orderBy('ticker', 'asc');
                break;
            default:
                $query->latest();
                break;
        }

        $investments = $query->paginate(50)->appends($request->all());

        return view('investments.index', compact(
            'investments',
            'totalPortfolioValue',
            'totalInvested',
            'totalProfitLoss'
        ));
    }

    public function create()
    {
        $goals = \App\Models\Goal::all();
        return view('investments.create', compact('goals'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'ticker'        => 'nullable|string|max:50',
            'asset_class'   => 'required|string',
            'owner'         => 'required|in:afin,pacar,business',
            'scraping_url'  => 'nullable|url|max:500',
            'currency'      => 'nullable|string|max:10',
            'price_unit'    => 'nullable|string|max:20',
            'quantity'      => 'required|numeric|min:0',
            'average_cost'  => 'required|numeric|min:0',
            'current_price' => 'nullable|numeric|min:0',
            'goal_id'       => 'nullable|exists:goals,id',
        ]);

        $validated['currency']   = $validated['currency']   ?? 'IDR';
        $validated['price_unit'] = $validated['price_unit'] ?? 'unit';

        if ($validated['asset_class'] === 'Mutual Fund' && !empty($validated['scraping_url'])) {
            $url = $validated['scraping_url'];
            if (str_contains(strtolower($url), 'bareksa.com')) {
                $bareksa = new BareksaService();
                $nav = $bareksa->getNavFromBareksa($url);
            } else {
                $kontan = new KontanService();
                $nav = $kontan->getNavFromKontan($url);
            }
            if ($nav) { $validated['current_price'] = $nav; }
        }

        // Final safety: if it's mutual fund and we don't have a ticker, make it null
        if ($validated['asset_class'] === 'Mutual Fund') {
            $validated['ticker'] = null;
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
        $goals = \App\Models\Goal::all();
        return view('investments.edit', compact('investment', 'goals'));
    }

    public function update(Request $request, Investment $investment)
    {

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'ticker'        => 'nullable|string|max:50',
            'asset_class'   => 'required|string',
            'owner'         => 'required|in:afin,pacar,business',
            'scraping_url'  => 'nullable|url|max:500',
            'currency'      => 'nullable|string|max:10',
            'price_unit'    => 'nullable|string|max:20',
            'quantity'      => 'required|numeric|min:0',
            'average_cost'  => 'required|numeric|min:0',
            'current_price' => 'nullable|numeric|min:0',
            'goal_id'       => 'nullable|exists:goals,id',
        ]);

        $validated['currency']   = $validated['currency']   ?? 'IDR';
        $validated['price_unit'] = $validated['price_unit'] ?? 'unit';

        if ($validated['asset_class'] === 'Mutual Fund' && !empty($validated['scraping_url'])) {
            if ($validated['scraping_url'] !== $investment->scraping_url) {
                $url = $validated['scraping_url'];
                if (str_contains(strtolower($url), 'bareksa.com')) {
                    $bareksa = new BareksaService();
                    $nav = $bareksa->getNavFromBareksa($url);
                } else {
                    $kontan = new KontanService();
                    $nav = $kontan->getNavFromKontan($url);
                }
                if ($nav) { $validated['current_price'] = $nav; }
            }
        }

        if ($validated['asset_class'] === 'Mutual Fund') {
            $validated['ticker'] = null;
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
        $investments = Investment::whereNotNull('ticker')
            ->orWhere(function ($q) {
                $q->where('asset_class', 'Mutual Fund');
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

        set_time_limit(30);

        try {
            $price    = null;
            $yahoo    = new YahooFinanceService();
            $kontan   = new KontanService();
            $pasardana = new PasardanaService();
            $currency  = new CurrencyService();

            if ($investment->asset_class === 'Mutual Fund') {
                if ($investment->scraping_url) {
                    $url = $investment->scraping_url;
                    if (str_contains(strtolower($url), 'bareksa.com')) {
                        $bareksa = new BareksaService();
                        $price = $bareksa->getNavFromBareksa($url);
                    } else {
                        $price = $kontan->getNavFromKontan($url);
                    }
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
        $investment->delete();
        return redirect()->route('investments.index')->with('success', 'Investment deleted successfully.');
    }
}
