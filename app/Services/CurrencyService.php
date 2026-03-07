<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Get the USD to IDR exchange rate.
     * Uses open.er-api.com (free, no API key required).
     * Cached for 1 hour to avoid excessive API calls.
     *
     * @return float
     */
    public function getUsdToIdr(): float
    {
        return Cache::remember('usd_to_idr', 3600, function () {
            try {
                $response = Http::timeout(10)->get('https://open.er-api.com/v6/latest/USD');

                if ($response->successful()) {
                    $data = $response->json();
                    $rate = $data['rates']['IDR'] ?? null;

                    if ($rate && $rate > 0) {
                        Log::info("CurrencyService: USD/IDR rate fetched: {$rate}");
                        return (float) $rate;
                    }
                }

                Log::error('CurrencyService: Failed to fetch USD/IDR rate from open.er-api.com');
            } catch (\Exception $e) {
                Log::error('CurrencyService: Exception fetching rate: ' . $e->getMessage());
            }

            // Fallback rate if API is down
            Log::warning('CurrencyService: Using fallback rate 16500 IDR/USD');
            return 16500.0;
        });
    }

    /**
     * Convert a USD amount to IDR.
     *
     * @param float $usdAmount
     * @return float
     */
    public function usdToIdr(float $usdAmount): float
    {
        return $usdAmount * $this->getUsdToIdr();
    }
}
