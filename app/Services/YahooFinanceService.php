<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YahooFinanceService
{
    /**
     * Fetch the latest market price for a given ticker.
     *
     * @param string $ticker
     * @return float|null
     */
    public function getStockPrice(string $ticker)
    {
        // Only append .JK for pure Indonesian stock tickers (letters only, 4 chars, no suffix)
        // Skip for commodity/forex tickers like GC=F, CL=F, EURUSD=X etc.
        $symbol = $ticker;
        if (strlen($ticker) === 4 && ctype_alpha($ticker)) {
            $symbol .= '.JK';
        }

        $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}";
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->timeout(8)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $price = $data['chart']['result'][0]['meta']['regularMarketPrice'] ?? null;
                
                if ($price) {
                    return (float) $price;
                }
            } else {
                Log::error("Yahoo Finance API error for {$symbol}: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("Yahoo Finance fetch failed for {$symbol}: " . $e->getMessage());
        }

        return null;
    }
}
