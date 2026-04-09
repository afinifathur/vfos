<?php

namespace App\Services;

use App\Models\Investment;
use Illuminate\Support\Facades\Log;

class InvestmentUpdateService
{
    protected YahooFinanceService $yahoo;
    protected KontanService $kontan;
    protected BareksaService $bareksa;
    protected CurrencyService $currency;
    protected KemenanganSignatureService $gold;

    public function __construct()
    {
        $this->yahoo     = new YahooFinanceService();
        $this->kontan    = new KontanService();
        $this->bareksa   = new BareksaService();
        $this->currency  = new CurrencyService();
        $this->gold      = new KemenanganSignatureService();
    }

    /**
     * Update prices for all investments belonging to a specific user.
     * Pass null to update ALL investments (used by scheduler/CLI).
     *
     * @param int|null $userId
     * @return array ['updated' => int, 'failed' => int, 'skipped' => int]
     */
    public function updateAll(?int $userId = null): array
    {
        $query = Investment::query();
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }
        $investments = $query->get();

        $updated = 0;
        $failed  = 0;
        $skipped = 0;
        $usdToIdr = null; // Lazy-load once

        foreach ($investments as $investment) {
            try {
                $price = null;

                if ($investment->asset_class === 'Mutual Fund') {
                    // Reksa dana: use Kontan or Bareksa scraper
                    if ($investment->scraping_url) {
                        if (str_contains(strtolower($investment->scraping_url), 'bareksa.com')) {
                            $price = $this->bareksa->getNavFromBareksa($investment->scraping_url);
                        } else {
                            $price = $this->kontan->getNavFromKontan($investment->scraping_url);
                        }
                    }
                } elseif ($investment->ticker) {
                    // Check if ticker is a K-Gold ticker (e.g. 17K, 24K)
                    if (preg_match('/^\d+K\+?$/i', $investment->ticker)) {
                        $price = $this->gold->getPrice($investment->ticker);
                    } else {
                        $rawPrice = $this->yahoo->getStockPrice($investment->ticker);

                        if ($rawPrice !== null) {
                            if ($investment->currency === 'USD') {
                                // Fetch exchange rate once
                                if ($usdToIdr === null) {
                                    $usdToIdr = $this->currency->getUsdToIdr();
                                }
                                // Unit conversion: gold in troy oz → IDR per gram
                                if ($investment->price_unit === 'gram') {
                                    $price = ($rawPrice / 31.1035) * $usdToIdr;
                                } else {
                                    $price = $rawPrice * $usdToIdr;
                                }
                            } else {
                                $price = $rawPrice;
                            }
                        }
                    }
                } else {
                    $skipped++;
                    continue;
                }

                if ($price !== null && $price > 0) {
                    $investment->update(['current_price' => $price]);
                    $updated++;
                } else {
                    $failed++;
                    Log::warning("InvestmentUpdateService: no price returned for [{$investment->ticker}] {$investment->name}");
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error("InvestmentUpdateService: exception for [{$investment->ticker}] {$investment->name}: " . $e->getMessage());
            }
        }

        return compact('updated', 'failed', 'skipped');
    }
}
